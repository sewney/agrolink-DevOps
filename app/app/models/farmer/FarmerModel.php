<?php

class FarmerModel
{
    use Database;

    protected $table = 'farmer_profiles';
    protected $userTable = 'users';

    /**
     * Get farmer profile by user ID
     */
    public function getProfileByUserId($userId)
    {
        $sql = "SELECT fp.*, u.name, u.email, u.status, u.deactivated_at
                FROM {$this->table} fp
                LEFT JOIN {$this->userTable} u ON u.id = fp.user_id
                WHERE fp.user_id = :user_id";

        return $this->get_row($sql, ['user_id' => $userId]);
    }

    /**
     * Create farmer profile
     */
    public function createProfile($userId, $data)
    {
        $sql = "INSERT INTO {$this->table} 
                (user_id, phone, district, crops_selling, full_address, profile_photo, created_at, updated_at)
                VALUES (:user_id, :phone, :district, :crops_selling, :full_address, :profile_photo, NOW(), NOW())";

        $params = [
            'user_id' => $userId,
            'phone' => $data['phone'] ?? null,
            'district' => $data['district'] ?? null,
            'crops_selling' => $data['crops_selling'] ?? null,
            'full_address' => $data['full_address'] ?? null,
            'profile_photo' => $data['profile_photo'] ?? null
        ];

        return $this->write($sql, $params);
    }

    /**
     * Update farmer profile
     */
    public function updateProfile($userId, $data)
    {
        $allowed = ['phone', 'district', 'crops_selling', 'full_address', 'profile_photo'];
        $set = [];
        $params = ['user_id' => $userId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($set)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . ", updated_at = NOW() WHERE user_id = :user_id";

        return $this->write($sql, $params);
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

        // Validate phone (required, exactly 10 digits)
        $cleanPhone = preg_replace('/\D/', '', $data['phone'] ?? '');
        if ($cleanPhone === '') {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^\d{10}$/', $cleanPhone)) {
            $errors['phone'] = 'Phone number must be exactly 10 digits';
        }

        // Validate district (if provided)
        if (!empty($data['district'])) {
            $validDistricts = [
                'Ampara',
                'Anuradhapura',
                'Badulla',
                'Batticaloa',
                'Colombo',
                'Galle',
                'Gampaha',
                'Jaffna',
                'Kalutara',
                'Kandy',
                'Kegalle',
                'Kilinochchi',
                'Kurunegala',
                'Mannar',
                'Matale',
                'Matara',
                'Mullaitivu',
                'Nuwara Eliya',
                'Polonnaruwa',
                'Puttalam',
                'Ratnapura',
                'Trincomalee',
                'Vavuniya'
            ];

            if (!in_array($data['district'], $validDistricts)) {
                $errors['district'] = 'Please select a valid district';
            }
        }

        // Validate crops selling (optional but if provided, must be valid)
        if (!empty($data['crops_selling'])) {
            if (strlen($data['crops_selling']) < 3) {
                $errors['crops_selling'] = 'Crops information must be at least 3 characters';
            } elseif (strlen($data['crops_selling']) > 500) {
                $errors['crops_selling'] = 'Crops information is too long (max 500 characters)';
            }
        }

        // Validate full address (optional but if provided, must be valid)
        if (!empty($data['full_address'])) {
            if (strlen($data['full_address']) < 5) {
                $errors['full_address'] = 'Address must be at least 5 characters';
            } elseif (strlen($data['full_address']) > 500) {
                $errors['full_address'] = 'Address is too long (max 500 characters)';
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
     * Get all orders that contain this farmer's products
     */
    public function getFarmerOrders($farmerId)
    {
        $sql = "SELECT DISTINCT o.*, 
                u.name as buyer_name, 
                u.email as buyer_email,
                d.district_name,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND farmer_id = :farmer_id) as my_items_count,
                (SELECT SUM(product_price * quantity) FROM order_items WHERE order_id = o.id AND farmer_id = :farmer_id) as my_order_total
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN users u ON o.buyer_id = u.id
                LEFT JOIN districts d ON o.delivery_district_id = d.id
                WHERE oi.farmer_id = :farmer_id
                ORDER BY o.created_at DESC";

        $result = $this->query($sql, ['farmer_id' => $farmerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get delivery requests related to this farmer's orders
     */
    public function getFarmerDeliveryRequests($farmerId, $status = null)
    {
        $sql = "SELECT dr.*,
                       o.payment_status,
                       o.order_total,
                       o.delivery_city,
                       b.name AS buyer_name,
                       t.name AS transporter_name
                FROM delivery_requests dr
                INNER JOIN orders o ON o.id = dr.order_id
                LEFT JOIN users b ON b.id = dr.buyer_id
                LEFT JOIN users t ON t.id = dr.transporter_id
                WHERE dr.farmer_id = :farmer_id";

        $params = ['farmer_id' => $farmerId];

        if (!empty($status)) {
            $sql .= " AND dr.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY dr.created_at DESC";

        $result = $this->query($sql, $params);
        return is_array($result) ? $result : [];
    }

    /**
     * Summary counts for farmer deliveries
     */
    public function getFarmerDeliverySummary($farmerId)
    {
        $sql = "SELECT
                    COUNT(*) AS total_deliveries,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_deliveries,
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_deliveries,
                    SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) AS in_transit_deliveries,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_deliveries
                FROM delivery_requests
                WHERE farmer_id = :farmer_id";

        $result = $this->get_row($sql, ['farmer_id' => $farmerId]);

        if (!$result) {
            return (object)[
                'total_deliveries' => 0,
                'pending_deliveries' => 0,
                'accepted_deliveries' => 0,
                'in_transit_deliveries' => 0,
                'delivered_deliveries' => 0,
            ];
        }

        return $result;
    }

    /**
     * Get order items for this farmer only
     */
    public function getFarmerOrderItems($orderId, $farmerId)
    {
        $sql = "SELECT oi.*, p.image as product_image
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id AND oi.farmer_id = :farmer_id";

        $result = $this->query($sql, ['order_id' => $orderId, 'farmer_id' => $farmerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Verify that an order item belongs to this farmer
     */
    public function verifyOrderItemOwnership($itemId, $farmerId)
    {
        $sql = "SELECT id FROM order_items WHERE id = :item_id AND farmer_id = :farmer_id";
        $result = $this->get_row($sql, ['item_id' => $itemId, 'farmer_id' => $farmerId]);
        return $result !== false;
    }

    /**
     * Verify a farmer has at least one item in this order.
     */
    public function verifyFarmerOrderOwnership($orderId, $farmerId)
    {
        $sql = "SELECT id FROM order_items WHERE order_id = :order_id AND farmer_id = :farmer_id LIMIT 1";
        $result = $this->get_row($sql, [
            'order_id' => $orderId,
            'farmer_id' => $farmerId,
        ]);

        return $result !== false;
    }

    /**
     * Update order status from farmer workflow with guarded transitions.
     */
    public function updateFarmerOrderStatus($orderId, $farmerId, $newStatus)
    {
        $allowedStatuses = ['ready_for_pickup'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            return false;
        }

        if (!$this->verifyFarmerOrderOwnership($orderId, $farmerId)) {
            return false;
        }

        $currentOrder = $this->get_row(
            "SELECT status FROM orders WHERE id = :order_id LIMIT 1",
            ['order_id' => $orderId]
        );

        if (!$currentOrder || empty($currentOrder->status)) {
            return false;
        }

        $transitionMap = [
            'processing' => 'ready_for_pickup',
            'ready_for_pickup' => null,
            'shipped' => null,
            'delivered' => null,
            'cancelled' => null,
        ];

        $currentStatus = strtolower((string)$currentOrder->status);
        $nextAllowed = $transitionMap[$currentStatus] ?? null;

        if ($nextAllowed !== $newStatus) {
            return false;
        }

        $sql = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :order_id";
        return $this->write($sql, [
            'status' => $newStatus,
            'order_id' => $orderId,
        ]);
    }

    /**
     * Get total earnings for farmer
     */
    public function getTotalEarnings($farmerId)
    {
        $sql = "SELECT COALESCE(SUM(oi.product_price * oi.quantity), 0) as total
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id 
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')";

        $result = $this->get_row($sql, ['farmer_id' => $farmerId]);
        return $result ? $result->total : 0;
    }

    /**
     * Get monthly earnings for farmer
     */
    public function getMonthlyEarnings($farmerId)
    {
        $sql = "SELECT COALESCE(SUM(oi.product_price * oi.quantity), 0) as total
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id 
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND MONTH(o.created_at) = MONTH(CURRENT_DATE())
                AND YEAR(o.created_at) = YEAR(CURRENT_DATE())";

        $result = $this->get_row($sql, ['farmer_id' => $farmerId]);
        return $result ? $result->total : 0;
    }

    /**
     * Get weekly earnings for farmer
     */
    public function getWeeklyEarnings($farmerId)
    {
        $sql = "SELECT COALESCE(SUM(oi.product_price * oi.quantity), 0) as total
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id 
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND YEARWEEK(o.created_at, 1) = YEARWEEK(CURRENT_DATE(), 1)";

        $result = $this->get_row($sql, ['farmer_id' => $farmerId]);
        return $result ? $result->total : 0;
    }

    /**
     * Get yearly earnings for farmer
     */
    public function getYearlyEarnings($farmerId)
    {
        $sql = "SELECT COALESCE(SUM(oi.product_price * oi.quantity), 0) as total
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id 
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND YEAR(o.created_at) = YEAR(CURRENT_DATE())";

        $result = $this->get_row($sql, ['farmer_id' => $farmerId]);
        return $result ? $result->total : 0;
    }

    /**
     * Get earnings breakdown by product
     */
    public function getEarningsByProduct($farmerId)
    {
        $sql = "SELECT 
                    oi.product_name,
                    oi.product_id,
                    MAX(p.image) as product_image,
                    COUNT(DISTINCT oi.order_id) as order_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.product_price * oi.quantity) as total_earnings
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.farmer_id = :farmer_id 
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                GROUP BY oi.product_id, oi.product_name
                ORDER BY total_earnings DESC
                LIMIT 10";

        $result = $this->query($sql, ['farmer_id' => $farmerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get monthly earnings breakdown by product for current month.
     */
    public function getMonthlyEarningsByProduct($farmerId, $limit = 3)
    {
        $limit = max(1, (int)$limit);

        $sql = "SELECT 
                    oi.product_name,
                    oi.product_id,
                    MAX(p.image) as product_image,
                    COUNT(DISTINCT oi.order_id) as order_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.product_price * oi.quantity) as total_earnings
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.farmer_id = :farmer_id
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND MONTH(o.created_at) = MONTH(CURRENT_DATE())
                AND YEAR(o.created_at) = YEAR(CURRENT_DATE())
                GROUP BY oi.product_id, oi.product_name
                ORDER BY total_earnings DESC
                LIMIT {$limit}";

        $result = $this->query($sql, ['farmer_id' => $farmerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get recent earnings transactions
     */
    public function getRecentEarnings($farmerId, $limit = 10)
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT 
                    o.id as order_id,
                    o.created_at as order_date,
                    COALESCE(o.updated_at, o.created_at) as transaction_date,
                    o.status,
                    o.payment_status,
                    o.delivery_city,
                    u.name as buyer_name,
                    SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT oi.product_name ORDER BY oi.product_name SEPARATOR ', '), ', ', 1) as lead_product,
                    COUNT(oi.id) as item_count,
                    SUM(oi.product_price * oi.quantity) as order_earnings
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN users u ON o.buyer_id = u.id
                WHERE oi.farmer_id = :farmer_id
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND COALESCE(o.updated_at, o.created_at) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
                GROUP BY o.id, o.created_at, o.updated_at, o.status, o.payment_status, o.delivery_city, u.name
                ORDER BY COALESCE(o.updated_at, o.created_at) DESC
                LIMIT {$limit}";

        $result = $this->query($sql, ['farmer_id' => $farmerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Daily earnings chart (last N days).
     */
    public function getDailyEarningsChart($farmerId, $days = 7)
    {
        $days = max(1, (int)$days);
        $sql = "SELECT
                    DATE(o.updated_at) as day_key,
                    SUM(oi.product_price * oi.quantity) as earnings
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND o.updated_at >= DATE_SUB(CURRENT_DATE(), INTERVAL {$days} DAY)
                GROUP BY DATE(o.updated_at)
                ORDER BY day_key ASC";

        $result = $this->query($sql, ['farmer_id' => $farmerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Yearly earnings chart (last N years).
     */
    public function getYearlyEarningsChart($farmerId, $years = 5)
    {
        $years = max(1, (int)$years);
        $sql = "SELECT
                    YEAR(o.updated_at) as year_key,
                    SUM(oi.product_price * oi.quantity) as earnings
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND o.updated_at >= DATE_SUB(CURRENT_DATE(), INTERVAL {$years} YEAR)
                GROUP BY YEAR(o.updated_at)
                ORDER BY year_key ASC";

        $result = $this->query($sql, ['farmer_id' => $farmerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get this week's order count for this farmer.
     */
    public function getWeeklyOrdersCount($farmerId)
    {
        $sql = "SELECT COUNT(DISTINCT o.id) as total
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND YEARWEEK(o.updated_at, 1) = YEARWEEK(CURRENT_DATE(), 1)";

        $result = $this->get_row($sql, ['farmer_id' => $farmerId]);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Get earnings statistics
     */
    public function getEarningsStats($farmerId)
    {
        $sql = "SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(DISTINCT oi.product_id) as products_sold,
                    SUM(oi.quantity) as total_items_sold,
                    AVG(oi.product_price * oi.quantity) as avg_order_value
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id 
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')";

        $result = $this->get_row($sql, ['farmer_id' => $farmerId]);
        return $result ?: (object)[
            'total_orders' => 0,
            'products_sold' => 0,
            'total_items_sold' => 0,
            'avg_order_value' => 0
        ];
    }

    /**
     * Get monthly earnings chart data (last 12 months)
     */
    public function getMonthlyEarningsChart($farmerId)
    {
        $sql = "SELECT 
                    DATE_FORMAT(o.created_at, '%Y-%m') as month,
                    DATE_FORMAT(o.created_at, '%b %Y') as month_label,
                    SUM(oi.product_price * oi.quantity) as earnings
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id 
                AND o.status IN ('processing', 'ready_for_pickup', 'shipped', 'delivered')
                AND o.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(o.created_at, '%Y-%m'), DATE_FORMAT(o.created_at, '%b %Y')
                ORDER BY month ASC";

        $result = $this->query($sql, ['farmer_id' => $farmerId]);
        return is_array($result) ? $result : [];
    }
}
