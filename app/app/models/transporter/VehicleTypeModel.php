<?php

class VehicleTypeModel
{
    use Model;

    protected $table = 'vehicle_types';
    protected $allowedColumns = [
        'vehicle_name',
        'min_weight_kg',
        'max_weight_kg',
        'base_fee_lkr',
        'cost_per_km_lkr',
        'cost_per_kg_lkr',
        'is_active'
    ];

    /**
     * Get all active vehicle types
     */
    public function getActiveTypes()
    {
        $query = "SELECT * FROM $this->table WHERE is_active = 1 ORDER BY min_weight_kg ASC";
        $result = $this->query($query);
        return is_array($result) ? $result : [];
    }

    /**
     * Get all vehicle types (including inactive)
     */
    public function getAllTypes()
    {
        $query = "SELECT * FROM $this->table ORDER BY min_weight_kg ASC";
        $result = $this->query($query);
        return is_array($result) ? $result : [];
    }

    /**
     * Get vehicle type by ID
     */
    public function getById($id)
    {
        $query = "SELECT * FROM $this->table WHERE id = :id LIMIT 1";
        $result = $this->query($query, ['id' => $id]);
        return (is_array($result) && !empty($result)) ? $result[0] : false;
    }

    /**
     * Get vehicle type by name
     */
    public function getByName($name)
    {
        $query = "SELECT * FROM $this->table WHERE vehicle_name = :name LIMIT 1";
        $result = $this->query($query, ['name' => $name]);
        return (is_array($result) && !empty($result)) ? $result[0] : false;
    }

    /**
     * Convert vehicle_name to lowercase slug for comparison
     */
    public function nameToSlug($name)
    {
        return strtolower(str_replace(' ', '', $name));
    }

    /**
     * Get vehicle type display name with proper formatting
     */
    public function getDisplayName($slug)
    {
        $types = $this->getActiveTypes();
        foreach ($types as $type) {
            if ($this->nameToSlug($type->vehicle_name) === strtolower($slug)) {
                return $type->vehicle_name;
            }
        }
        return ucfirst($slug);
    }
}
