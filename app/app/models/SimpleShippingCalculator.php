<?php

/**
 * AgroLink Simple Shipping Cost Calculator.
 *
 * Shared domain service for shipping estimation used by buyer checkout and
 * farmer product location helpers.
 */
class SimpleShippingCalculator
{
    private $db;
    private $config;

    /**
     * @param PDO $database PDO database connection
     */
    public function __construct($database)
    {
        $this->db = $database;
        $this->loadConfiguration();
    }

    private function loadConfiguration()
    {
        try {
            $stmt = $this->db->query("SELECT config_key, config_value FROM platform_config");
            $this->config = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->config[$row['config_key']] = $row['config_value'];
            }
        } catch (PDOException $e) {
            $this->config = [
                'platform_fee_percentage' => 5,
                'platform_fee_min_lkr' => 20,
                'platform_fee_max_lkr' => 150,
                'transporter_earning_percentage' => 85,
                'vehicle_size_multiplier_max' => 2,
                'default_crop_volume_factor' => 1.0,
            ];
        }
    }

    public function calculateShippingCost($params)
    {
        try {
            $validation = $this->validateInputs($params);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            $weightKg = (float)$params['weight_kg'];
            $cropName = trim((string)($params['crop_name'] ?? 'mixed'));
            $weightAlreadyAdjusted = !empty($params['weight_already_adjusted']);

            if ($weightAlreadyAdjusted) {
                $cropFactor = 1.0;
                $effectiveWeight = $weightKg;
            } else {
                $cropFactor = $this->getCropVolumeFactor($cropName);
                $effectiveWeight = $weightKg * $cropFactor;
            }

            $vehicles = $this->getAvailableVehicles($effectiveWeight);

            if (empty($vehicles)) {
                return [
                    'success' => false,
                    'error' => 'No suitable vehicle available for weight: ' .
                        number_format($effectiveWeight, 2) . ' kg (effective)',
                ];
            }

            $distanceData = $this->calculateTotalDistance(
                $params['pickup_district_id'],
                $params['pickup_town_id'],
                $params['delivery_district_id'],
                $params['delivery_town_id']
            );

            if (!$distanceData['success']) {
                return $distanceData;
            }

            $vehicleOptions = [];
            foreach ($vehicles as $vehicle) {
                $costBreakdown = $this->calculateVehicleCost(
                    $vehicle,
                    $distanceData['total_distance_km'],
                    $effectiveWeight
                );
                $vehicleOptions[] = array_merge($vehicle, $costBreakdown);
            }

            usort($vehicleOptions, function ($a, $b) {
                return $a['total_shipping_cost_lkr'] <=> $b['total_shipping_cost_lkr'];
            });

            $selectedVehicle = $vehicleOptions[0];

            return [
                'success' => true,
                'calculation' => [
                    'crop_name' => $cropName,
                    'order_weight_kg' => $weightKg,
                    'crop_volume_factor' => $cropFactor,
                    'effective_weight_kg' => round($effectiveWeight, 2),
                    'district_distance_km' => $distanceData['district_distance_km'],
                    'pickup_extra_km' => $distanceData['pickup_extra_km'],
                    'delivery_extra_km' => $distanceData['delivery_extra_km'],
                    'total_distance_km' => $distanceData['total_distance_km'],
                    'selected_vehicle' => [
                        'id' => $selectedVehicle['id'],
                        'name' => $selectedVehicle['vehicle_name'],
                        'max_weight_kg' => $selectedVehicle['max_weight_kg'],
                    ],
                    'base_fee_lkr' => $selectedVehicle['base_fee_lkr'],
                    'distance_cost_lkr' => $selectedVehicle['distance_cost_lkr'],
                    'weight_cost_lkr' => $selectedVehicle['weight_cost_lkr'],
                    'subtotal_lkr' => $selectedVehicle['subtotal_lkr'],
                    'platform_fee_lkr' => $selectedVehicle['platform_fee_lkr'],
                    'total_shipping_cost_lkr' => $selectedVehicle['total_shipping_cost_lkr'],
                    'transporter_earning_lkr' => $selectedVehicle['transporter_earning_lkr'],
                    'alternative_vehicles' => array_slice($vehicleOptions, 1, 2),
                ],
            ];
        } catch (Exception $e) {
            error_log('Shipping calculation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error calculating shipping cost. Please try again.',
            ];
        }
    }

    private function validateInputs($params)
    {
        $required = [
            'pickup_district_id',
            'pickup_town_id',
            'delivery_district_id',
            'delivery_town_id',
            'weight_kg',
        ];

        foreach ($required as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                return [
                    'valid' => false,
                    'error' => "Missing required field: $field",
                ];
            }
        }

        if (empty($params['weight_already_adjusted'])) {
            $cropName = trim((string)($params['crop_name'] ?? ''));
            if ($cropName === '') {
                return [
                    'valid' => false,
                    'error' => 'Missing required field: crop_name',
                ];
            }
        }

        if ((float)$params['weight_kg'] <= 0) {
            return ['valid' => false, 'error' => 'Weight must be greater than 0'];
        }

        if ((float)$params['weight_kg'] > 5000) {
            return [
                'valid' => false,
                'error' => 'Weight exceeds maximum (5000 kg). Contact support for bulk orders.',
            ];
        }

        return ['valid' => true];
    }

    private function getCropVolumeFactor($cropName)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT volume_factor FROM crop_volume_factors
                 WHERE crop_name = ? AND is_active = 1 LIMIT 1"
            );
            $stmt->execute([$cropName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return (float)$result['volume_factor'];
            }

            return (float)$this->config['default_crop_volume_factor'];
        } catch (PDOException $e) {
            error_log('Crop factor error: ' . $e->getMessage());
            return 1.0;
        }
    }

    private function getAvailableVehicles($effectiveWeight)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, vehicle_name, min_weight_kg, max_weight_kg,
                        base_fee_lkr, cost_per_km_lkr, cost_per_kg_lkr
                 FROM vehicle_types
                 WHERE ? >= min_weight_kg
                 AND ? <= max_weight_kg
                 AND is_active = 1
                 ORDER BY max_weight_kg ASC"
            );

            $stmt->execute([$effectiveWeight, $effectiveWeight]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Vehicle selection error: ' . $e->getMessage());
            return [];
        }
    }

    private function calculateTotalDistance($pickupDistrictId, $pickupTownId, $deliveryDistrictId, $deliveryTownId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT distance_km FROM district_distances
                 WHERE (from_district_id = ? AND to_district_id = ?)
                 OR (from_district_id = ? AND to_district_id = ?)
                 LIMIT 1"
            );
            $stmt->execute([
                $pickupDistrictId,
                $deliveryDistrictId,
                $deliveryDistrictId,
                $pickupDistrictId,
            ]);
            $districtDistance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$districtDistance) {
                return [
                    'success' => false,
                    'error' => 'Distance data not available for selected districts',
                ];
            }

            $distanceKm = (int)$districtDistance['distance_km'];

            $stmt = $this->db->prepare(
                "SELECT extra_distance_km FROM towns WHERE id = ? LIMIT 1"
            );
            $stmt->execute([$pickupTownId]);
            $pickupTown = $stmt->fetch(PDO::FETCH_ASSOC);
            $pickupExtraKm = $pickupTown ? (int)$pickupTown['extra_distance_km'] : 0;

            $stmt->execute([$deliveryTownId]);
            $deliveryTown = $stmt->fetch(PDO::FETCH_ASSOC);
            $deliveryExtraKm = $deliveryTown ? (int)$deliveryTown['extra_distance_km'] : 0;

            $totalDistance = $distanceKm + $pickupExtraKm + $deliveryExtraKm;

            return [
                'success' => true,
                'district_distance_km' => $distanceKm,
                'pickup_extra_km' => $pickupExtraKm,
                'delivery_extra_km' => $deliveryExtraKm,
                'total_distance_km' => $totalDistance,
            ];
        } catch (PDOException $e) {
            error_log('Distance calculation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error calculating distance',
            ];
        }
    }

    private function calculateVehicleCost($vehicle, $totalDistance, $effectiveWeight)
    {
        $baseFee = (float)$vehicle['base_fee_lkr'];
        $distanceCost = $totalDistance * (float)$vehicle['cost_per_km_lkr'];
        $weightCost = $effectiveWeight * (float)$vehicle['cost_per_kg_lkr'];

        $subtotal = $baseFee + $distanceCost + $weightCost;

        $platformFeePercent = (float)$this->config['platform_fee_percentage'] / 100;
        $platformFee = $subtotal * $platformFeePercent;

        $platformFeeMin = (float)$this->config['platform_fee_min_lkr'];
        $platformFeeMax = (float)$this->config['platform_fee_max_lkr'];
        $platformFee = max($platformFeeMin, min($platformFeeMax, $platformFee));

        $totalCost = $subtotal + $platformFee;

        $transporterPercent = (float)$this->config['transporter_earning_percentage'] / 100;
        $transporterEarning = $totalCost * $transporterPercent;

        return [
            'base_fee_lkr' => round($baseFee, 2),
            'distance_cost_lkr' => round($distanceCost, 2),
            'weight_cost_lkr' => round($weightCost, 2),
            'subtotal_lkr' => round($subtotal, 2),
            'platform_fee_lkr' => round($platformFee, 2),
            'total_shipping_cost_lkr' => round($totalCost, 2),
            'transporter_earning_lkr' => round($transporterEarning, 2),
        ];
    }

    public function getAllCrops()
    {
        try {
            $stmt = $this->db->query(
                "SELECT crop_name, category, volume_factor
                 FROM crop_volume_factors
                 WHERE is_active = 1
                 ORDER BY category, crop_name"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllDistricts()
    {
        try {
            $stmt = $this->db->query(
                "SELECT id, district_name, district_code, province
                 FROM districts
                 WHERE is_active = 1
                 ORDER BY province, district_name"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getTownsByDistrict($districtId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, town_name, extra_distance_km, postal_code
                 FROM towns
                 WHERE district_id = ? AND is_active = 1
                 ORDER BY town_name"
            );
            $stmt->execute([$districtId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllVehicleTypes()
    {
        try {
            $stmt = $this->db->query(
                'SELECT * FROM vehicle_types WHERE is_active = 1 ORDER BY max_weight_kg'
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
