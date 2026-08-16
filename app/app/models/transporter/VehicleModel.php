<?php

class VehicleModel
{
    use Model;

    protected $table = 'vehicles';
    private $hasDeletedAtColumn = null;
    protected $allowedColumns = [
        'transporter_id',
        'type',
        'vehicle_type_id',
        'registration',
        'capacity',
        'fuel_type',
        'model',
        'status'
    ];

    private function supportsDeletedAt(): bool
    {
        if ($this->hasDeletedAtColumn !== null) {
            return $this->hasDeletedAtColumn;
        }

        $result = $this->query("SHOW COLUMNS FROM {$this->table} LIKE 'deleted_at'");
        $this->hasDeletedAtColumn = is_array($result) && !empty($result);
        return $this->hasDeletedAtColumn;
    }

    public function getByUserId($user_id)
    {
        $query = "SELECT * FROM $this->table WHERE transporter_id = :transporter_id";
        if ($this->supportsDeletedAt()) {
            $query .= " AND deleted_at IS NULL";
        }
        $query .= " ORDER BY created_at DESC";
        $result = $this->query($query, ['transporter_id' => $user_id]);
        return is_array($result) ? $result : [];
    }

    public function getById($id)
    {
        $query = "SELECT * FROM $this->table WHERE id = :id";
        if ($this->supportsDeletedAt()) {
            $query .= " AND deleted_at IS NULL";
        }
        $query .= " LIMIT 1";
        $result = $this->query($query, ['id' => $id]);
        return (is_array($result) && !empty($result)) ? $result[0] : false;
    }

    public function create($data)
    {
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        return $this->insert($data);
    }

    public function updateVehicle($id, $data)
    {
        return $this->update($id, $data);
    }

    public function deleteVehicle($id)
    {
        if ($this->supportsDeletedAt()) {
            $query = "UPDATE $this->table
                      SET deleted_at = NOW(),
                          status = 'inactive',
                          updated_at = NOW()
                      WHERE id = :id
                      AND deleted_at IS NULL";
            return $this->write($query, ['id' => $id]);
        }

        return $this->delete($id);
    }

    public function setActiveVehicle($vehicle_id, $user_id)
    {
        $query = "UPDATE $this->table SET status = 'inactive' WHERE transporter_id = :transporter_id";
        if ($this->supportsDeletedAt()) {
            $query .= " AND deleted_at IS NULL";
        }
        $deactivateResult = $this->write($query, ['transporter_id' => $user_id]);
        if ($deactivateResult === false) {
            return false;
        }

        $query = "UPDATE $this->table SET status = 'active' WHERE id = :id AND transporter_id = :transporter_id";
        if ($this->supportsDeletedAt()) {
            $query .= " AND deleted_at IS NULL";
        }
        return $this->write($query, ['id' => $vehicle_id, 'transporter_id' => $user_id]) !== false;
    }

    public function deactivateAllVehicles($user_id)
    {
        $query = "UPDATE $this->table SET status = 'inactive' WHERE transporter_id = :transporter_id";
        if ($this->supportsDeletedAt()) {
            $query .= " AND deleted_at IS NULL";
        }
        return $this->write($query, ['transporter_id' => $user_id]) !== false;
    }

    public function validate($data)
    {
        $this->errors = [];

        if (empty($data['type'])) {
            $this->errors['type'] = "Vehicle type is required";
        }

        if (empty($data['registration'])) {
            $this->errors['registration'] = "Registration number is required";
        } else if (!preg_match('/^[A-Z]{2,3} \d{4}$/', $data['registration'])) {
            $this->errors['registration'] = "Registration must be 2 or 3 capital letters, a space, and 4 numbers (e.g. AB 1234)";
        }

        // Capacity is now automatically set based on vehicle type
        // Still validate it exists and is valid since it's required in database
        if (!isset($data['capacity']) || !is_numeric($data['capacity']) || $data['capacity'] <= 0) {
            $this->errors['capacity'] = "Invalid vehicle type - capacity could not be determined";
        }

        if (!empty($data['registration'])) {
            if ($this->supportsDeletedAt()) {
                $existing = $this->query(
                    "SELECT id FROM $this->table WHERE registration = :registration AND deleted_at IS NULL LIMIT 1",
                    ['registration' => $data['registration']]
                );
            } else {
                $existing = $this->where(['registration' => $data['registration']]);
            }
            if (!is_array($existing)) {
                $existing = [];
            }
            if (!empty($existing)) {
                // On create, any existing match is a duplicate. On update, ignore self.
                $existingId = $existing[0]->id ?? null;
                if (!isset($data['id']) || ($existingId && $existingId != $data['id'])) {
                    $this->errors['registration'] = "This registration number is already registered";
                }
            }
        }

        return empty($this->errors);
    }
}
