<?php
class CartModel
{
    use Database;

    protected $table = 'cart';
    private $hasProductDeletedAtColumn = null;

    private function supportsProductSoftDelete(): bool
    {
        if ($this->hasProductDeletedAtColumn !== null) {
            return $this->hasProductDeletedAtColumn;
        }

        $result = $this->query("SHOW COLUMNS FROM products LIKE 'deleted_at'");
        $this->hasProductDeletedAtColumn = is_array($result) && !empty($result);
        return $this->hasProductDeletedAtColumn;
    }

    /**
     * Get all cart items for a user with farmer details
     */
    public function getCartByUserId($user_id)
    {
        if ($this->supportsProductSoftDelete()) {
            $sql = "SELECT c.*, p.location as farmer_location, u.name as farmer_name, p.image as product_image_db
            FROM {$this->table} c
            LEFT JOIN products p ON c.product_id = p.id AND p.deleted_at IS NULL
            LEFT JOIN users u ON p.farmer_id = u.id
            WHERE c.user_id = :user_id
            AND p.id IS NOT NULL
            ORDER BY c.created_at DESC";
        } else {
            $sql = "SELECT c.*, p.location as farmer_location, u.name as farmer_name, p.image as product_image_db
            FROM {$this->table} c
            LEFT JOIN products p ON c.product_id = p.id
            LEFT JOIN users u ON p.farmer_id = u.id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC";
        }

        $result = $this->query($sql, ['user_id' => $user_id]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get a specific cart item
     */
    public function getCartItem($user_id, $product_id)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND product_id = :product_id LIMIT 1";
        $result = $this->query($sql, [
            'user_id' => $user_id,
            'product_id' => $product_id
        ]);
        return (is_array($result) && !empty($result)) ? $result[0] : null;
    }

    /**
     * Add item to cart
     */
    public function addToCart($data)
    {
        $sql = "INSERT INTO {$this->table}
                (user_id, product_id, product_name, product_price, quantity, farmer_name, farmer_location, product_image)
                VALUES (:user_id, :product_id, :product_name, :product_price, :quantity, :farmer_name, :farmer_location, :product_image)";

        $result = $this->write($sql, $data);

        // Return true if insert was successful
        return $result !== false;
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity($user_id, $product_id, $quantity)
    {
        $sql = "UPDATE {$this->table} 
                SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP 
                WHERE user_id = :user_id AND product_id = :product_id";

        $result = $this->write($sql, [
            'user_id' => $user_id,
            'product_id' => $product_id,
            'quantity' => $quantity
        ]);

        // Return true if update affected rows
        return $result !== false;
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($user_id, $product_id)
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE user_id = :user_id AND product_id = :product_id";

        $result = $this->write($sql, [
            'user_id' => $user_id,
            'product_id' => $product_id
        ]);

        // Return true if delete was successful
        return $result !== false;
    }

    /**
     * Clear all items from user's cart
     */
    public function clearCart($user_id)
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";

        $result = $this->write($sql, ['user_id' => $user_id]);

        return $result !== false;
    }

    /**
     * Get total number of items in cart
     */
    public function getCartItemCount($user_id)
    {
        if ($this->supportsProductSoftDelete()) {
            $sql = "SELECT COUNT(*) as total
                FROM {$this->table} c
                INNER JOIN products p ON p.id = c.product_id
                WHERE c.user_id = :user_id
                AND p.deleted_at IS NULL";
        } else {
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = :user_id";
        }
        $result = $this->query($sql, ['user_id' => $user_id]);

        if (is_array($result) && !empty($result) && isset($result[0]->total)) {
            return (int)$result[0]->total;
        }
        return 0;
    }

    /**
     * Get cart total price
     */
    public function getCartTotal($user_id)
    {
        if ($this->supportsProductSoftDelete()) {
            $sql = "SELECT COALESCE(SUM(c.product_price * c.quantity), 0) as total
                FROM {$this->table} c
                INNER JOIN products p ON p.id = c.product_id
                WHERE c.user_id = :user_id
                AND p.deleted_at IS NULL";
        } else {
            $sql = "SELECT COALESCE(SUM(product_price * quantity), 0) as total
                    FROM {$this->table} WHERE user_id = :user_id";
        }
        $result = $this->query($sql, ['user_id' => $user_id]);

        if (is_array($result) && !empty($result) && isset($result[0]->total)) {
            return (float)$result[0]->total;
        }
        return 0.0;
    }
}
