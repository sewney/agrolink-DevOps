<?php

class ProductsModel
{
    use Database;

    protected $table = 'products';
    private $hasFullAddressColumn = null;
    private $hasDeletedAtColumn = null;

    private function supportsFullAddress(): bool
    {
        if ($this->hasFullAddressColumn !== null) {
            return $this->hasFullAddressColumn;
        }

        $result = $this->query("SHOW COLUMNS FROM {$this->table} LIKE 'full_address'");
        $this->hasFullAddressColumn = is_array($result) && !empty($result);
        return $this->hasFullAddressColumn;
    }

    private function supportsDeletedAt(): bool
    {
        if ($this->hasDeletedAtColumn !== null) {
            return $this->hasDeletedAtColumn;
        }

        $result = $this->query("SHOW COLUMNS FROM {$this->table} LIKE 'deleted_at'");
        $this->hasDeletedAtColumn = is_array($result) && !empty($result);
        return $this->hasDeletedAtColumn;
    }

    public function create(array $data)
    {
        try {
            $columns = [
                'farmer_id',
                'name',
                'product_master_id',
                'price',
                'quantity',
                'description',
                'image',
                'location',
                'category',
                'listing_date',
                'district_id',
                'town_id',
            ];

            if ($this->supportsFullAddress()) {
                $columns[] = 'full_address';
            }

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', array_map(function ($col) {
                return ':' . $col;
            }, $columns)) . ")";

            // Ensure district_id, town_id, and product_master_id are set, default to null if not
            if (!isset($data['district_id'])) $data['district_id'] = null;
            if (!isset($data['town_id'])) $data['town_id'] = null;
            if (!isset($data['product_master_id'])) $data['product_master_id'] = null;
            if (!isset($data['full_address'])) $data['full_address'] = null;

            $insertData = [];
            foreach ($columns as $column) {
                $insertData[$column] = $data[$column] ?? null;
            }

            $result = $this->write($sql, $insertData);

            if ($result === false) {
                error_log("ProductsModel::create - Insert failed for data: " . print_r($insertData, true));
            } else {
                error_log("ProductsModel::create - Insert successful, ID: " . $result);
            }

            return $result;
        } catch (Exception $e) {
            error_log("ProductsModel::create - Exception: " . $e->getMessage());
            error_log("ProductsModel::create - Data: " . print_r($data, true));
            return false;
        }
    }

    public function updateByFarmer(int $id, int $farmerId, array $data)
    {
        // Allow dynamic updates for provided fields
        $allowed = ['name', 'product_master_id', 'price', 'quantity', 'description', 'location', 'category', 'listing_date', 'image', 'district_id', 'town_id'];
        if ($this->supportsFullAddress()) {
            $allowed[] = 'full_address';
        }
        $set = [];
        $params = ['id' => $id, 'farmer_id' => $farmerId];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "$field=:$field";
                $params[$field] = $data[$field];
            }
        }
        if (empty($set)) return false;
        $sql = "UPDATE {$this->table} SET " . implode(',', $set) . " WHERE id=:id AND farmer_id=:farmer_id";
        if ($this->supportsDeletedAt()) {
            $sql .= " AND deleted_at IS NULL";
        }
        return $this->write($sql, $params);
    }

    public function deleteByFarmer(int $id, int $farmerId)
    {
        if ($this->supportsDeletedAt()) {
            $sql = "UPDATE {$this->table}
                    SET deleted_at = NOW(), updated_at = NOW()
                    WHERE id = :id AND farmer_id = :farmer_id AND deleted_at IS NULL";
            return $this->write($sql, ['id' => $id, 'farmer_id' => $farmerId]);
        }

        $sql = "DELETE FROM {$this->table} WHERE id=:id AND farmer_id=:farmer_id";
        return $this->write($sql, ['id' => $id, 'farmer_id' => $farmerId]);
    }

    public function countOngoingOrders(int $productId, int $farmerId): int
    {
        $sql = "SELECT COUNT(*) AS cnt
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                WHERE oi.product_id = :product_id
                  AND oi.farmer_id = :farmer_id
                  AND o.status NOT IN ('delivered', 'cancelled')";
        $rows = $this->query($sql, ['product_id' => $productId, 'farmer_id' => $farmerId]);
        if (!is_array($rows) || empty($rows)) {
            return 0;
        }
        return (int)($rows[0]->cnt ?? 0);
    }

    public function getByFarmer(int $farmerId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE farmer_id=:farmer_id";
        if ($this->supportsDeletedAt()) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " ORDER BY created_at DESC";
        $result = $this->query($sql, ['farmer_id' => $farmerId]);
        return $result ?: [];
    }

    public function getById(int $id)
    {
        $sql = "SELECT p.*, u.name AS farmer_name
                FROM {$this->table} p
                JOIN users u ON u.id = p.farmer_id
                WHERE p.id=:id";
        if ($this->supportsDeletedAt()) {
            $sql .= " AND p.deleted_at IS NULL";
        }
        return $this->get_row($sql, ['id' => $id]);
    }

    public function getAvailable(array $filters = [])
    {
        $params = [];
        $where = "p.quantity > 0";
        if ($this->supportsDeletedAt()) {
            $where .= " AND p.deleted_at IS NULL";
        }

        if (!empty($filters['search'])) {
            $where .= " AND (p.name LIKE :search OR p.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['max_price'])) {
            $where .= " AND p.price <= :max_price";
            $params['max_price'] = $filters['max_price'];
        }
        if (!empty($filters['location'])) {
            $where .= " AND p.location = :location";
            $params['location'] = $filters['location'];
        }

        $sql = "SELECT p.*, u.name AS farmer_name
                FROM {$this->table} p
                JOIN users u ON u.id = p.farmer_id
                WHERE {$where}
                ORDER BY p.created_at DESC";

        $result = $this->query($sql, $params);
        return $result ?: [];
    }

    /**
     * Get all products with farmer details for buyer dashboard
     */
    public function getWithFarmerDetails($conditions = [])
    {
        $params = [];
        $where = "p.quantity > 0"; // Only show products with stock
        if ($this->supportsDeletedAt()) {
            $where .= " AND p.deleted_at IS NULL";
        }

        // Add optional conditions
        if (!empty($conditions['category'])) {
            $where .= " AND p.category = :category";
            $params['category'] = $conditions['category'];
        }

        if (!empty($conditions['location'])) {
            $where .= " AND p.location = :location";
            $params['location'] = $conditions['location'];
        }

        if (!empty($conditions['min_price'])) {
            $where .= " AND p.price >= :min_price";
            $params['min_price'] = $conditions['min_price'];
        }

        if (!empty($conditions['max_price'])) {
            $where .= " AND p.price <= :max_price";
            $params['max_price'] = $conditions['max_price'];
        }

        $sql = "SELECT p.*, u.name as farmer_name, u.email as farmer_email, fp.district as farmer_district
            FROM {$this->table} p
            LEFT JOIN users u ON p.farmer_id = u.id
            LEFT JOIN farmer_profiles fp ON fp.user_id = p.farmer_id
                WHERE {$where}
                ORDER BY p.created_at DESC";

        $result = $this->query($sql, $params);
        return $result ?: [];
    }
}
