<?php

class TransporterModel
{
    use Database;

    protected $table = 'transporter_profiles';
    protected $userTable = 'users';

    /**
     * Get transporter profile by user ID
     */
    public function getProfileByUserId($userId)
    {
        $sql = "SELECT tp.*, u.name, u.email, u.status, u.deactivated_at
                FROM {$this->table} tp
                LEFT JOIN {$this->userTable} u ON u.id = tp.user_id
                WHERE tp.user_id = :user_id";

        return $this->get_row($sql, ['user_id' => $userId]);
    }

    /**
     * Create transporter profile
     */
    public function createProfile($userId, $data)
    {
        $sql = "INSERT INTO {$this->table} 
                (user_id, phone, district, full_address, company_name, availability, profile_photo, created_at, updated_at)
                VALUES (:user_id, :phone, :district, :full_address, :company_name, :availability, :profile_photo, NOW(), NOW())";

        $params = [
            'user_id' => $userId,
            'phone' => $data['phone'] ?? null,
            'district' => $data['district'] ?? null,
            'full_address' => $data['full_address'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'availability' => $data['availability'] ?? null,
            'profile_photo' => $data['profile_photo'] ?? null
        ];

        return $this->write($sql, $params);
    }

    /**
     * Update transporter profile
     */
    public function updateProfile($userId, $data)
    {
        $allowed = ['phone', 'district', 'full_address', 'company_name', 'availability', 'profile_photo'];
        $set = [];
        $params = ['user_id' => $userId];

        $this->debugLog('=== TransporterModel::updateProfile DEBUG ===');
        $this->debugLog('Incoming data: ' . json_encode($data));

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "$field = :$field";
                $params[$field] = $data[$field];
                $this->debugLog("Added field: $field = " . $data[$field]);
            }
        }

        if (empty($set)) {
            $this->debugLog('ERROR: No fields to update!');
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . ", updated_at = NOW() WHERE user_id = :user_id";
        $this->debugLog('SQL: ' . $sql);
        $this->debugLog('Params: ' . json_encode($params));

        $result = $this->write($sql, $params);
        $this->debugLog('Write result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));

        return $result;
    }

    /**
     * Update profile photo only
     */
    public function updateProfilePhoto($userId, $filename)
    {
        $sql = "UPDATE {$this->table} SET profile_photo = :profile_photo, updated_at = NOW() WHERE user_id = :user_id";
        return $this->write($sql, ['user_id' => $userId, 'profile_photo' => $filename]);
    }

    /**
     * Remove profile photo (set to NULL)
     */
    public function removeProfilePhoto($userId)
    {
        $sql = "UPDATE {$this->table} SET profile_photo = NULL, updated_at = NOW() WHERE user_id = :user_id";
        return $this->write($sql, ['user_id' => $userId]);
    }

    /**
     * Get old profile photo filename for deletion
     */
    public function getOldPhotoFilename($userId)
    {
        $sql = "SELECT profile_photo FROM {$this->table} WHERE user_id = :user_id";
        $result = $this->get_row($sql, ['user_id' => $userId]);
        return $result ? $result->profile_photo : null;
    }

    /**
     * Validate profile data
     */
    public function validateProfile($data)
    {
        $errors = [];

        // Validate name (if provided)
        if (!empty($data['name'])) {
            if (strlen($data['name']) < 2) {
                $errors['name'] = 'Full name must be at least 2 characters';
            } elseif (strlen($data['name']) > 100) {
                $errors['name'] = 'Full name is too long (max 100 characters)';
            } elseif (!preg_match('/^[a-zA-Z\s\-\.]+$/', $data['name'])) {
                $errors['name'] = 'Full name can only contain letters, spaces, hyphens, and dots';
            }
        }

        // Validate email (if provided)
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address';
            } elseif (strlen($data['email']) > 100) {
                $errors['email'] = 'Email is too long (max 100 characters)';
            }
        }

        // Validate phone (optional but if provided, must be valid)
        if (!empty($data['phone'])) {
            // Remove all non-digit characters for validation
            $cleanPhone = preg_replace('/[^0-9+]/', '', $data['phone']);

            if (!preg_match('/^(\+?94|0)[0-9]{9}$/', $cleanPhone)) {
                $errors['phone'] = 'Phone number must be a valid Sri Lankan number (e.g., +94XXXXXXXXX or 0XXXXXXXXX)';
            }
        }

        // Validate district (optional)
        if (!empty($data['district'])) {
            if (strlen($data['district']) > 100) {
                $errors['district'] = 'District name is too long (max 100 characters)';
            }
        }

        // Validate full_address (optional)
        if (!empty($data['full_address'])) {
            if (strlen($data['full_address']) > 500) {
                $errors['full_address'] = 'Additional address details are too long (max 500 characters)';
            }
        }

        // Validate company_name (if provided)
        if (!empty($data['company_name'])) {
            if (strlen($data['company_name']) > 255) {
                $errors['company_name'] = 'Company name is too long (max 255 characters)';
            }
        }

        // Validate availability (optional)
        if (!empty($data['availability'])) {
            $validAvailabilities = ['available', 'not available', 'busy'];
            if (!in_array(strtolower($data['availability']), $validAvailabilities)) {
                $errors['availability'] = 'Availability must be one of: available, not available, busy';
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validate password change request
     */
    public function validatePasswordChange($userId, $currentPassword, $newPassword, $confirmPassword)
    {
        $errors = [];

        // Get current password from database
        $sql = "SELECT password FROM {$this->userTable} WHERE id = :id";
        $user = $this->get_row($sql, ['id' => $userId]);

        if (!$user) {
            $errors['current'] = 'User not found';
            return $errors;
        }

        // Verify current password
        if (!password_verify($currentPassword, $user->password)) {
            $errors['current'] = 'Current password is incorrect';
        }

        // Validate new password
        if (empty($newPassword)) {
            $errors['new'] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors['new'] = 'New password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errors['new'] = 'New password must include at least one letter and one number';
        }

        // Validate password confirmation
        if (empty($confirmPassword)) {
            $errors['confirm'] = 'Password confirmation is required';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm'] = 'Passwords do not match';
        }

        // Check if new password is same as old
        if (!empty($newPassword) && password_verify($newPassword, $user->password)) {
            $errors['new'] = 'New password must be different from current password';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Change user password
     */
    public function changePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE {$this->userTable}
                SET password = :password,
                    password_updated_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id";

        return $this->write($sql, [
            'id' => $userId,
            'password' => $hashedPassword
        ]);
    }

    /**
     * Get available delivery requests for transporter based on their vehicle capacity
     */
    public function getAvailableDeliveryRequests($transporterId, array $filters = [])
    {
        $transporterId = (int)$transporterId;
        if ($transporterId <= 0) {
            return [];
        }

        $location = strtolower(trim((string)($filters['location'] ?? '')));
        $location = preg_replace('/\s+/', ' ', $location);
        $maxDistance = isset($filters['max_distance']) ? (float)$filters['max_distance'] : 0;
        $maxWeight = isset($filters['max_weight']) ? (float)$filters['max_weight'] : 0;
        $minPayment = isset($filters['min_payment']) ? (float)$filters['min_payment'] : 0;

        // Get pending delivery requests. Per-request vehicle capability checks are
        // enforced below using active transporter vehicles.
        $sql = "SELECT 
                    dr.*,
                    o.status as order_status,
                    vt.vehicle_name as required_vehicle_type,
                    bd.district_name as buyer_district_name,
                    fd.district_name as farmer_district_name
                FROM delivery_requests dr
                INNER JOIN orders o ON dr.order_id = o.id
                LEFT JOIN vehicle_types vt ON dr.required_vehicle_type_id = vt.id
                LEFT JOIN districts bd ON dr.buyer_district_id = bd.id
                LEFT JOIN districts fd ON dr.farmer_district_id = fd.id
                WHERE dr.status = 'pending'
                AND dr.required_vehicle_type_id IS NOT NULL
                AND dr.total_weight_kg > 0
                AND o.status IN ('processing', 'ready_for_pickup')";

        $params = [];

        if ($location !== '') {
            $sql .= " AND (
                        LOWER(TRIM(COALESCE(dr.farmer_city, ''))) LIKE :location_like
                        OR LOWER(TRIM(COALESCE(fd.district_name, ''))) LIKE :location_like
                    )";
            $params['location_like'] = '%' . $location . '%';
        }

        if ($maxDistance > 0) {
            $sql .= " AND dr.distance_km IS NOT NULL AND dr.distance_km <= :max_distance";
            $params['max_distance'] = $maxDistance;
        }

        if ($maxWeight > 0) {
            $sql .= " AND dr.total_weight_kg <= :max_weight_filter";
            $params['max_weight_filter'] = $maxWeight;
        }

        if ($minPayment > 0) {
            $sql .= " AND dr.shipping_fee >= :min_payment";
            $params['min_payment'] = $minPayment;
        }

        $sql .= " ORDER BY dr.created_at DESC";

        $requests = $this->query($sql, $params);
        if (!is_array($requests) || empty($requests)) {
            return [];
        }

        $matchedRequests = [];
        foreach ($requests as $request) {
            $orderWeightKg = (float)($request->total_weight_kg ?? 0);
            $requiredVehicleTypeId = (int)($request->required_vehicle_type_id ?? 0);

            $eligibleVehicles = $this->getEligibleVehiclesForRequest(
                $transporterId,
                $orderWeightKg,
                $requiredVehicleTypeId
            );

            if (empty($eligibleVehicles)) {
                continue;
            }

            $distanceKm = (float)($request->distance_km ?? 0);
            $bestVehicle = null;
            $bestEstimatedCost = null;

            foreach ($eligibleVehicles as $vehicle) {
                $estimatedCost = $this->estimateVehicleDeliveryCost($vehicle, $distanceKm, $orderWeightKg);
                if ($bestEstimatedCost === null || $estimatedCost < $bestEstimatedCost) {
                    $bestEstimatedCost = $estimatedCost;
                    $bestVehicle = $vehicle;
                }
            }

            $request->eligible_vehicle_count = count($eligibleVehicles);
            $request->matched_vehicle_name = $bestVehicle ? (string)$bestVehicle->vehicle_name : null;
            $request->matched_vehicle_id = $bestVehicle ? (int)$bestVehicle->vehicle_id : null;
            $request->estimated_vehicle_cost_lkr = $bestEstimatedCost !== null ? (float)$bestEstimatedCost : null;

            $matchedRequests[] = $request;
        }

        return $matchedRequests;
    }

    private function getEligibleVehiclesForRequest($transporterId, $orderWeightKg, $requiredVehicleTypeId = null)
    {
        $transporterId = (int)$transporterId;
        $orderWeightKg = (float)$orderWeightKg;
        $requiredVehicleTypeId = (int)$requiredVehicleTypeId;

        if ($transporterId <= 0 || $orderWeightKg <= 0) {
            return [];
        }

        $sql = "SELECT
                    v.id as vehicle_id,
                    v.vehicle_type_id,
                    v.registration,
                    v.model,
                    v.status,
                    vt.vehicle_name,
                    vt.min_weight_kg,
                    vt.max_weight_kg,
                    vt.base_fee_lkr,
                    vt.cost_per_km_lkr,
                    vt.cost_per_kg_lkr
                FROM vehicles v
                INNER JOIN vehicle_types vt ON vt.id = v.vehicle_type_id
                WHERE v.transporter_id = :transporter_id
                AND v.deleted_at IS NULL
                AND LOWER(COALESCE(v.status, '')) = 'active'
                AND vt.is_active = 1
                AND :order_weight >= vt.min_weight_kg
                AND :order_weight <= vt.max_weight_kg";

        $params = [
            'transporter_id' => $transporterId,
            'order_weight' => $orderWeightKg,
        ];

        if ($requiredVehicleTypeId > 0) {
            $sql .= " AND v.vehicle_type_id = :required_vehicle_type_id";
            $params['required_vehicle_type_id'] = $requiredVehicleTypeId;
        }

        $sql .= " ORDER BY vt.max_weight_kg ASC, v.id ASC";

        $result = $this->query($sql, $params);
        return is_array($result) ? $result : [];
    }

    private function estimateVehicleDeliveryCost($vehicle, $distanceKm, $orderWeightKg)
    {
        $baseFee = (float)($vehicle->base_fee_lkr ?? 0);
        $perKm = (float)($vehicle->cost_per_km_lkr ?? 0);
        $perKg = (float)($vehicle->cost_per_kg_lkr ?? 0);

        $distanceKm = max(0.0, (float)$distanceKm);
        $orderWeightKg = max(0.0, (float)$orderWeightKg);

        return round($baseFee + ($distanceKm * $perKm) + ($orderWeightKg * $perKg), 2);
    }

    /**
     * Get delivery requests by transporter ID
     */
    public function getMyDeliveryRequests($transporterId, $status = null)
    {
        $sql = "SELECT 
                    dr.*,
                    o.status as order_status,
                    vt.vehicle_name as required_vehicle_type,
                    bd.district_name as buyer_district_name,
                    fd.district_name as farmer_district_name
                FROM delivery_requests dr
                INNER JOIN orders o ON dr.order_id = o.id
                LEFT JOIN vehicle_types vt ON dr.required_vehicle_type_id = vt.id
                LEFT JOIN districts bd ON dr.buyer_district_id = bd.id
                LEFT JOIN districts fd ON dr.farmer_district_id = fd.id
                WHERE dr.transporter_id = :transporter_id";

        $params = ['transporter_id' => $transporterId];

        if ($status !== null) {
            $sql .= " AND dr.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY dr.created_at DESC";

        $result = $this->query($sql, $params);
        return is_array($result) ? $result : [];
    }

    /**
     * Accept a delivery request
     */
    public function acceptDeliveryRequest($requestId, $transporterId)
    {
        $requestId = (int)$requestId;
        $transporterId = (int)$transporterId;

        if ($requestId <= 0 || $transporterId <= 0) {
            return ['success' => false, 'error' => 'Invalid delivery request'];
        }

        $checkSql = "SELECT dr.*, o.status as order_status
                     FROM delivery_requests dr
                     INNER JOIN orders o ON o.id = dr.order_id
                     WHERE dr.id = :id
                     AND dr.status = 'pending'
                     LIMIT 1";
        $request = $this->get_row($checkSql, ['id' => $requestId]);

        if (!$request) {
            return ['success' => false, 'error' => 'This request is no longer available'];
        }

        $orderStatus = strtolower((string)($request->order_status ?? ''));
        if (!in_array($orderStatus, ['processing', 'ready_for_pickup'], true)) {
            return ['success' => false, 'error' => 'Order is not ready for transporter assignment'];
        }

        $requiredVehicleTypeId = (int)($request->required_vehicle_type_id ?? 0);
        if ($requiredVehicleTypeId <= 0) {
            return ['success' => false, 'error' => 'Required vehicle type is not resolved for this delivery'];
        }

        $orderWeightKg = (float)($request->total_weight_kg ?? 0);
        if ($orderWeightKg <= 0) {
            return ['success' => false, 'error' => 'Invalid delivery weight for this request'];
        }

        $eligibleVehicles = $this->getEligibleVehiclesForRequest($transporterId, $orderWeightKg, $requiredVehicleTypeId);
        if (empty($eligibleVehicles)) {
            return ['success' => false, 'error' => 'No active vehicle can fulfill this request'];
        }

        if (!$this->beginTransaction()) {
            return ['success' => false, 'error' => 'Could not start delivery acceptance transaction'];
        }

        try {
            $sql = "UPDATE delivery_requests
                    SET transporter_id = :transporter_id,
                        status = 'accepted',
                        accepted_at = NOW(),
                        updated_at = NOW()
                    WHERE id = :id
                    AND status = 'pending'
                    AND transporter_id IS NULL";

            $result = $this->write($sql, [
                'id' => $requestId,
                'transporter_id' => $transporterId,
            ]);

            if ($result === false || (int)$result === 0) {
                $this->rollBack();
                return ['success' => false, 'error' => 'This request is no longer available'];
            }

            // Order is already at 'processing' (or later 'ready_for_pickup' set by the farmer).
            // Accepting a delivery must not regress that status, so no order update here.

            if (!$this->commit()) {
                $this->rollBack();
                return ['success' => false, 'error' => 'Failed to commit delivery acceptance'];
            }

            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }

            error_log('TransporterModel::acceptDeliveryRequest error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to accept delivery request'];
        }
    }

    /**
     * Update delivery request status
     */
    public function updateDeliveryStatus($requestId, $transporterId, $status)
    {
        $validStatuses = ['accepted', 'in_transit', 'delivered', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $sql = "UPDATE delivery_requests 
                SET status = :status,
                    updated_at = NOW()
                WHERE id = :id AND transporter_id = :transporter_id";

        $result = $this->write($sql, [
            'id' => $requestId,
            'transporter_id' => $transporterId,
            'status' => $status
        ]);

        // Update order status accordingly — only when transporter progresses the shipment.
        // 'accepted' must not override the farmer's 'ready_for_pickup' state.
        if ($result) {
            $orderStatus = null;
            if ($status === 'in_transit') {
                $orderStatus = 'shipped';
            } elseif ($status === 'delivered') {
                $orderStatus = 'delivered';
            } elseif ($status === 'cancelled') {
                $orderStatus = 'cancelled';
            }

            if ($orderStatus !== null) {
                $request = $this->get_row("SELECT order_id FROM delivery_requests WHERE id = :id", ['id' => $requestId]);
                if ($request) {
                    $updateOrderSql = "UPDATE orders SET status = :status WHERE id = :order_id";
                    $this->write($updateOrderSql, [
                        'status' => $orderStatus,
                        'order_id' => $request->order_id
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Get delivery request details by ID
     */
    public function getDeliveryRequestById($requestId, $transporterId = null)
    {
        $requestId = (int)$requestId;
        $transporterId = $transporterId === null ? null : (int)$transporterId;

        if ($requestId <= 0) {
            return false;
        }

        $sql = "SELECT 
                    dr.*,
                    o.status as order_status,
                    o.payment_status,
                    vt.vehicle_name as required_vehicle_type,
                    bd.district_name as buyer_district_name,
                    fd.district_name as farmer_district_name
                FROM delivery_requests dr
                INNER JOIN orders o ON dr.order_id = o.id
                LEFT JOIN vehicle_types vt ON dr.required_vehicle_type_id = vt.id
                LEFT JOIN districts bd ON dr.buyer_district_id = bd.id
                LEFT JOIN districts fd ON dr.farmer_district_id = fd.id
                WHERE dr.id = :id";

        $request = $this->get_row($sql, ['id' => $requestId]);
        if (!$request) {
            return false;
        }

        if ($transporterId === null || $transporterId <= 0) {
            return $request;
        }

        if ((int)($request->transporter_id ?? 0) === $transporterId) {
            return $request;
        }

        // Allow details for pending requests only when transporter has eligible active vehicles.
        if (strtolower((string)($request->status ?? '')) !== 'pending') {
            return false;
        }

        $requiredVehicleTypeId = (int)($request->required_vehicle_type_id ?? 0);
        $orderWeightKg = (float)($request->total_weight_kg ?? 0);
        if ($requiredVehicleTypeId <= 0 || $orderWeightKg <= 0) {
            return false;
        }

        $eligible = $this->getEligibleVehiclesForRequest($transporterId, $orderWeightKg, $requiredVehicleTypeId);
        return empty($eligible) ? false : $request;
    }

    /**
     * Get transporter's earnings summary
     */
    public function getEarningsSummary($transporterId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_deliveries,
                    SUM(CASE WHEN dr.status = 'delivered' THEN dr.shipping_fee ELSE 0 END) as total_earnings,
                    SUM(CASE WHEN dr.status = 'delivered' AND DATE(dr.updated_at) = CURDATE() THEN dr.shipping_fee ELSE 0 END) as today_earnings,
                    SUM(CASE WHEN dr.status = 'delivered' AND YEARWEEK(dr.updated_at, 1) = YEARWEEK(CURDATE(), 1) THEN dr.shipping_fee ELSE 0 END) as week_earnings,
                    SUM(CASE WHEN dr.status = 'delivered' AND YEAR(dr.updated_at) = YEAR(CURDATE()) AND MONTH(dr.updated_at) = MONTH(CURDATE()) THEN dr.shipping_fee ELSE 0 END) as month_earnings,
                    COUNT(CASE WHEN dr.status IN ('accepted', 'in_transit') THEN 1 END) as active_deliveries,
                    COUNT(CASE WHEN dr.status = 'accepted' THEN 1 END) as accepted_deliveries,
                    COUNT(CASE WHEN dr.status = 'in_transit' THEN 1 END) as in_transit_deliveries,
                    COUNT(CASE WHEN dr.status = 'delivered' THEN 1 END) as completed_deliveries
                FROM delivery_requests dr
                WHERE dr.transporter_id = :transporter_id";

        $result = $this->get_row($sql, ['transporter_id' => $transporterId]);

        // Return default values if no data found
        if (!$result) {
            return (object)[
                'total_deliveries' => 0,
                'total_earnings' => 0,
                'today_earnings' => 0,
                'week_earnings' => 0,
                'month_earnings' => 0,
                'active_deliveries' => 0,
                'accepted_deliveries' => 0,
                'in_transit_deliveries' => 0,
                'completed_deliveries' => 0
            ];
        }

        return $result;
    }

    /**
     * Count transporter deliveries that are not completed.
     *
     * Business rule: transporter account deactivation is allowed only when
     * every assigned delivery is completed (delivered/completed).
     */
    public function countIncompleteDeliveries($transporterId)
    {
        $transporterId = (int)$transporterId;
        if ($transporterId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM delivery_requests
                WHERE transporter_id = :transporter_id
                AND LOWER(COALESCE(status, '')) NOT IN ('delivered', 'completed')";

        $row = $this->get_row($sql, ['transporter_id' => $transporterId]);
        return (int)($row->total ?? 0);
    }

    /**
     * Migrate existing orders to delivery requests (run once)
     */
    public function migrateExistingOrders()
    {
        $details = [];
        $successCount = 0;
        $errorCount = 0;

        // Idempotent source list: only order/farmer pairs that still do not have a delivery request.
        $sql = "SELECT DISTINCT
                    o.id AS order_id,
                    o.buyer_id,
                    o.shipping_cost,
                    o.delivery_address,
                    o.delivery_city,
                    oi.farmer_id
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                LEFT JOIN delivery_requests dr
                    ON dr.order_id = o.id
                    AND dr.farmer_id = oi.farmer_id
                WHERE dr.id IS NULL
                ORDER BY o.id ASC, oi.farmer_id ASC";

        $pendingPairs = $this->query($sql, []);

        if (empty($pendingPairs) || !is_array($pendingPairs)) {
            $details[] = "No orders found that need migration.";
            return ['success' => $successCount, 'errors' => $errorCount, 'details' => $details];
        }

        $details[] = "Found " . count($pendingPairs) . " order/farmer pairs to migrate.";

        foreach ($pendingPairs as $pair) {
            try {
                $orderId = (int)($pair->order_id ?? 0);
                $buyerId = (int)($pair->buyer_id ?? 0);
                $farmerId = (int)($pair->farmer_id ?? 0);

                if ($orderId <= 0 || $buyerId <= 0 || $farmerId <= 0) {
                    $details[] = '✗ Skipped invalid migration row (missing order/buyer/farmer id).';
                    $errorCount++;
                    continue;
                }

                // Secondary idempotency guard in case another process inserted meanwhile.
                $existing = $this->get_row(
                    "SELECT id FROM delivery_requests WHERE order_id = :order_id AND farmer_id = :farmer_id LIMIT 1",
                    ['order_id' => $orderId, 'farmer_id' => $farmerId]
                );
                if ($existing) {
                    $details[] = "• Order #{$orderId} (Farmer ID: {$farmerId}): already migrated, skipped.";
                    continue;
                }

                $buyerSql = "SELECT u.name, bp.phone, bp.apartment_code, bp.street_name, bp.city, bp.district
                            FROM users u
                            LEFT JOIN buyer_profiles bp ON u.id = bp.user_id
                            WHERE u.id = :buyer_id";
                $buyer = $this->get_row($buyerSql, ['buyer_id' => $buyerId]);
                if (!$buyer) {
                    $details[] = "✗ Order #{$orderId}: Buyer ID {$buyerId} not found in users table";
                    $errorCount++;
                    continue;
                }

                $farmerSql = "SELECT u.name, fp.phone, fp.full_address, fp.district
                            FROM users u
                            LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
                            WHERE u.id = :farmer_id";
                $farmer = $this->get_row($farmerSql, ['farmer_id' => $farmerId]);
                if (!$farmer) {
                    $details[] = "✗ Order #{$orderId}: Farmer ID {$farmerId} not found in users table";
                    $errorCount++;
                    continue;
                }

                $buyerDistrictId = $this->getDistrictIdByName($buyer->district ?? '');
                $farmerDistrictId = $this->getDistrictIdByName($farmer->district ?? '');
                if (!$buyerDistrictId || !$farmerDistrictId) {
                    $details[] = "✗ Order #{$orderId}: Invalid district mapping for buyer/farmer profile";
                    $errorCount++;
                    continue;
                }

                // Prefer per-item migrated weight. For legacy single-farmer orders,
                // fallback to order-level total only when per-item weights are missing.
                $weightSql = "SELECT COALESCE(SUM(item_weight_kg), 0) AS farmer_weight
                             FROM order_items
                             WHERE order_id = :order_id AND farmer_id = :farmer_id";
                $weightResult = $this->get_row($weightSql, [
                    'order_id' => $orderId,
                    'farmer_id' => $farmerId,
                ]);
                $totalWeight = (float)($weightResult->farmer_weight ?? 0);

                if ($totalWeight <= 0) {
                    $farmerCountRow = $this->get_row(
                        "SELECT COUNT(DISTINCT farmer_id) AS farmer_count FROM order_items WHERE order_id = :order_id",
                        ['order_id' => $orderId]
                    );
                    $singleFarmerOrder = ((int)($farmerCountRow->farmer_count ?? 0) === 1);

                    if ($singleFarmerOrder) {
                        $orderWeightRow = $this->get_row(
                            "SELECT total_weight_kg FROM orders WHERE id = :order_id LIMIT 1",
                            ['order_id' => $orderId]
                        );
                        $fallbackWeight = (float)($orderWeightRow->total_weight_kg ?? 0);
                        if ($fallbackWeight > 0) {
                            $totalWeight = $fallbackWeight;
                            $details[] = "• Order #{$orderId} (Farmer ID: {$farmerId}): used order-level total_weight_kg fallback.";
                        }
                    }
                }

                if ($totalWeight <= 0) {
                    $details[] = "✗ Order #{$orderId} (Farmer ID: {$farmerId}): missing valid item_weight_kg/total_weight_kg";
                    $errorCount++;
                    continue;
                }

                $vehicleTypeSql = "SELECT id FROM vehicle_types
                                  WHERE min_weight_kg <= :weight
                                  AND max_weight_kg >= :weight
                                  AND is_active = 1
                                  ORDER BY min_weight_kg ASC
                                  LIMIT 1";
                $vehicleTypeResult = $this->query($vehicleTypeSql, ['weight' => $totalWeight]);
                $requiredVehicleTypeId = ($vehicleTypeResult && !empty($vehicleTypeResult)) ? (int)$vehicleTypeResult[0]->id : 0;
                if ($requiredVehicleTypeId <= 0) {
                    $details[] = "✗ Order #{$orderId} (Farmer ID: {$farmerId}): no eligible vehicle type for {$totalWeight}kg";
                    $errorCount++;
                    continue;
                }

                $buyerAddress = trim(($buyer->apartment_code ?? '') . ', ' .
                    ($buyer->street_name ?? '') . ', ' .
                    ($buyer->city ?? ''));
                if (empty(trim($buyerAddress, ', '))) {
                    $buyerAddress = (string)($pair->delivery_address ?? 'Not provided');
                }

                $productPickupAddress = $this->getOrderFarmerPickupAddress($orderId, $farmerId);

                $insertSql = "INSERT INTO delivery_requests (
                    order_id, buyer_id, buyer_name, buyer_phone, buyer_address, buyer_city, buyer_district_id,
                    farmer_id, farmer_name, farmer_phone, farmer_address, farmer_city, farmer_district_id,
                    total_weight_kg, shipping_fee, distance_km, required_vehicle_type_id, status,
                    created_at, updated_at
                ) VALUES (
                    :order_id, :buyer_id, :buyer_name, :buyer_phone, :buyer_address, :buyer_city, :buyer_district_id,
                    :farmer_id, :farmer_name, :farmer_phone, :farmer_address, :farmer_city, :farmer_district_id,
                    :total_weight_kg, :shipping_fee, :distance_km, :required_vehicle_type_id, 'pending',
                    NOW(), NOW()
                )";

                $params = [
                    'order_id' => $orderId,
                    'buyer_id' => $buyerId,
                    'buyer_name' => $buyer->name ?? 'Unknown',
                    'buyer_phone' => $buyer->phone ?? '',
                    'buyer_address' => $buyerAddress,
                    'buyer_city' => $buyer->city ?? ($pair->delivery_city ?? ''),
                    'buyer_district_id' => $buyerDistrictId,
                    'farmer_id' => $farmerId,
                    'farmer_name' => $farmer->name ?? 'Unknown',
                    'farmer_phone' => $farmer->phone ?? '',
                    'farmer_address' => $productPickupAddress !== '' ? $productPickupAddress : ($farmer->full_address ?? ''),
                    'farmer_city' => $farmer->district ?? '',
                    'farmer_district_id' => $farmerDistrictId,
                    'total_weight_kg' => round($totalWeight, 2),
                    'shipping_fee' => (float)($pair->shipping_cost ?? 0),
                    'distance_km' => null,
                    'required_vehicle_type_id' => $requiredVehicleTypeId,
                ];

                $insertResult = $this->write($insertSql, $params);
                if ($insertResult === false || (int)$insertResult <= 0) {
                    $details[] = "✗ Order #{$orderId} (Farmer ID: {$farmerId}): insert failed";
                    $errorCount++;
                    continue;
                }

                $details[] = "✓ Order #{$orderId} (Farmer ID: {$farmerId}): Created - " . round($totalWeight, 2) . "kg, Rs." . (float)($pair->shipping_cost ?? 0);
                $successCount++;
            } catch (Throwable $e) {
                $details[] = "Order #" . (int)($pair->order_id ?? 0) . ": ERROR - " . $e->getMessage();
                $errorCount++;
            }
        }

        return ['success' => $successCount, 'errors' => $errorCount, 'details' => $details];
    }

    /**
     * Get district ID by name
     */
    private function getDistrictIdByName($districtName)
    {
        $districtName = trim((string)$districtName);
        if ($districtName === '') {
            return null;
        }

        $sql = "SELECT id FROM districts WHERE district_name = :name LIMIT 1";
        $result = $this->query($sql, ['name' => $districtName]);
        if ($result && is_array($result) && !empty($result)) {
            return (int)$result[0]->id;
        }

        return null;
    }

    private function orderItemsHavePickupAddressColumn(): bool
    {
        $result = $this->query("SHOW COLUMNS FROM order_items LIKE 'product_full_address'", []);
        return is_array($result) && !empty($result);
    }

    private function getOrderFarmerPickupAddress(int $orderId, int $farmerId): string
    {
        if ($orderId <= 0 || $farmerId <= 0 || !$this->orderItemsHavePickupAddressColumn()) {
            return '';
        }

        $rows = $this->query(
            "SELECT DISTINCT TRIM(product_full_address) AS product_full_address
             FROM order_items
             WHERE order_id = :order_id
               AND farmer_id = :farmer_id
               AND product_full_address IS NOT NULL
               AND TRIM(product_full_address) <> ''",
            [
                'order_id' => $orderId,
                'farmer_id' => $farmerId,
            ]
        );

        if (!is_array($rows) || empty($rows)) {
            return '';
        }

        $addresses = [];
        foreach ($rows as $row) {
            $address = trim((string)($row->product_full_address ?? ''));
            if ($address !== '') {
                $addresses[] = $address;
            }
        }

        $addresses = array_values(array_unique($addresses));
        return implode(' | ', $addresses);
    }

    private function debugLog($message)
    {
        if (defined('DEBUG') && DEBUG) {
            error_log((string)$message);
        }
    }
}
