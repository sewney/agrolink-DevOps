<?php

class OrderModel
{
    use Database;

    protected $table = 'orders';
    protected $orderItemsTable = 'order_items';
    private $hasOrderItemPickupAddressColumn = null;
    private $hasProductDeletedAtColumn = null;

    private function supportsOrderItemPickupAddress(): bool
    {
        if ($this->hasOrderItemPickupAddressColumn !== null) {
            return $this->hasOrderItemPickupAddressColumn;
        }

        $result = $this->query("SHOW COLUMNS FROM {$this->orderItemsTable} LIKE 'product_full_address'");
        $this->hasOrderItemPickupAddressColumn = is_array($result) && !empty($result);
        return $this->hasOrderItemPickupAddressColumn;
    }

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
     * Create a new order
     */
    public function createOrder($orderData)
    {
        $sql = "INSERT INTO {$this->table} 
            (buyer_id, total_amount, shipping_cost, order_total, payment_status,
                 delivery_address, delivery_city, delivery_district_id, delivery_town_id,
                 delivery_phone, status, created_at)
            VALUES (:buyer_id, :total_amount, :shipping_cost, :order_total, :payment_status,
                        :delivery_address, :delivery_city, :delivery_district_id, :delivery_town_id,
                        :delivery_phone, :status, NOW())";

        $result = $this->write($sql, $orderData);

        if ($result !== false && (int)$result > 0) {
            return (int)$result; // Returns order ID
        }

        return false;
    }

    /**
     * Add order items
     */
    public function addOrderItem($itemData)
    {
        if ($this->supportsOrderItemPickupAddress()) {
            $sql = "INSERT INTO {$this->orderItemsTable}
                (order_id, product_id, product_name, product_price, quantity, item_weight_kg, farmer_id, product_full_address, created_at)
                VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :item_weight_kg, :farmer_id, :product_full_address, NOW())";
            $itemData['product_full_address'] = trim((string)($itemData['product_full_address'] ?? ''));
        } else {
            $sql = "INSERT INTO {$this->orderItemsTable}
                (order_id, product_id, product_name, product_price, quantity, item_weight_kg, farmer_id, created_at)
                VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :item_weight_kg, :farmer_id, NOW())";
            unset($itemData['product_full_address']);
        }

        $result = $this->write($sql, $itemData);

        return $result !== false && (int)$result > 0;
    }

    /**
     * Get order by ID
     */
    public function getOrderById($orderId)
    {
        $sql = "SELECT o.*, u.name as buyer_name, u.email as buyer_email, d.district_name
                FROM {$this->table} o
                LEFT JOIN users u ON o.buyer_id = u.id
                LEFT JOIN districts d ON o.delivery_district_id = d.id
                WHERE o.id = :order_id";

        return $this->get_row($sql, ['order_id' => $orderId]);
    }

    /**
     * Get order items
     */
    public function getOrderItems($orderId)
    {
        $sql = "SELECT oi.*, p.image as product_image, u.name as farmer_name
                FROM {$this->orderItemsTable} oi
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u ON oi.farmer_id = u.id
                WHERE oi.order_id = :order_id";

        $result = $this->query($sql, ['order_id' => $orderId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get orders by buyer ID
     */
    public function getOrdersByBuyer($buyerId)
    {
        $sql = "SELECT o.*, 
                (SELECT COUNT(*) FROM {$this->orderItemsTable} WHERE order_id = o.id) as item_count
                FROM {$this->table} o
                WHERE o.buyer_id = :buyer_id
                ORDER BY o.created_at DESC";

        $result = $this->query($sql, ['buyer_id' => $buyerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :order_id";
        return $this->write($sql, ['order_id' => $orderId, 'status' => $status]);
    }

    /**
     * Update product quantity after order
     * Ensures quantity doesn't go below 0
     */
    public function updateProductQuantity($productId, $quantity)
    {
        // First check current quantity
        $sql = "SELECT quantity FROM products WHERE id = :product_id";
        if ($this->supportsProductSoftDelete()) {
            $sql .= " AND deleted_at IS NULL";
        }
        $current = $this->get_row($sql, ['product_id' => $productId]);

        if (!$current) {
            return false;
        }

        $currentQuantity = (int)($current->quantity ?? 0);
        $newQuantity = max(0, $currentQuantity - $quantity);

        $sql = "UPDATE products SET quantity = :new_quantity WHERE id = :product_id";
        if ($this->supportsProductSoftDelete()) {
            $sql .= " AND deleted_at IS NULL";
        }
        return $this->write($sql, ['product_id' => $productId, 'new_quantity' => $newQuantity]);
    }

    /**
     * Restore stock for cancelled order
     */
    public function restoreOrderStock($orderId)
    {
        $items = $this->getOrderItems($orderId);
        $affectedRows = 0;

        foreach ($items as $item) {
            // Increment quantity directly
            $sql = "UPDATE products SET quantity = quantity + :qty WHERE id = :id";
            if ($this->supportsProductSoftDelete()) {
                $sql .= " AND deleted_at IS NULL";
            }
            $result = $this->write($sql, ['qty' => $item->quantity, 'id' => $item->product_id]);

            if ($result === false) {
                return false;
            }

            $affectedRows += (int)$result;
        }

        return $affectedRows;
    }

    /**
     * Update order total weight
     */
    public function updateOrderWeight($orderId, $totalWeight)
    {
        $sql = "UPDATE {$this->table} SET total_weight_kg = :total_weight WHERE id = :order_id";
        return $this->write($sql, ['order_id' => $orderId, 'total_weight' => $totalWeight]);
    }

    /**
     * Get delivery tracking rows for a buyer.
     */
    public function getDeliveryTrackingByBuyer($buyerId)
    {
        $sql = "SELECT
                    o.id AS order_id,
                    o.created_at AS order_created_at,
                    o.status AS order_status,
                    o.order_total,
                    o.delivery_city,
                    dr.id AS delivery_request_id,
                    dr.status AS delivery_status,
                    dr.updated_at AS delivery_updated_at,
                    dr.created_at AS delivery_created_at,
                    u.name AS transporter_name
                FROM {$this->table} o
                LEFT JOIN delivery_requests dr ON dr.order_id = o.id
                LEFT JOIN users u ON u.id = dr.transporter_id
                WHERE o.buyer_id = :buyer_id
                AND o.status IN ('pending_payment', 'processing', 'ready_for_pickup', 'shipped', 'delivered')
                ORDER BY o.created_at DESC";

        $result = $this->query($sql, ['buyer_id' => $buyerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get buyer orders by ID list.
     */
    public function getOrdersByIdsForBuyer($buyerId, array $orderIds)
    {
        $buyerId = (int)$buyerId;
        $cleanOrderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds), function ($id) {
            return $id > 0;
        })));

        if ($buyerId <= 0 || empty($cleanOrderIds)) {
            return [];
        }

        [$placeholders, $idParams] = $this->buildStatusParams($cleanOrderIds, 'order_id_');
        $params = array_merge(['buyer_id' => $buyerId], $idParams);

        $sql = "SELECT *
                FROM {$this->table}
                WHERE buyer_id = :buyer_id
                  AND id IN (" . implode(', ', $placeholders) . ")";

        $rows = $this->query($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Update payment and order status for a buyer's selected orders.
     */
    public function updatePaymentResultForBuyerOrders($buyerId, array $orderIds, $paymentStatus, $orderStatus)
    {
        $buyerId = (int)$buyerId;
        $paymentStatus = strtolower(trim((string)$paymentStatus));
        $orderStatus = strtolower(trim((string)$orderStatus));
        $cleanOrderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds), function ($id) {
            return $id > 0;
        })));

        $allowedPaymentStatuses = ['pending', 'paid', 'failed'];
        $allowedOrderStatuses = ['pending_payment', 'processing', 'ready_for_pickup', 'shipped', 'delivered', 'cancelled'];

        if ($buyerId <= 0 || empty($cleanOrderIds)) {
            return false;
        }

        if (!in_array($paymentStatus, $allowedPaymentStatuses, true) || !in_array($orderStatus, $allowedOrderStatuses, true)) {
            return false;
        }

        [$placeholders, $idParams] = $this->buildStatusParams($cleanOrderIds, 'pay_order_id_');
        $params = array_merge([
            'buyer_id' => $buyerId,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
        ], $idParams);

        $sql = "UPDATE {$this->table}
                SET payment_status = :payment_status,
                    status = :order_status,
                    updated_at = NOW()
                WHERE buyer_id = :buyer_id
                  AND id IN (" . implode(', ', $placeholders) . ")";

        $result = $this->write($sql, $params);
        return $result === false ? false : (int)$result;
    }

    /**
     * Get order items eligible for writing review/complaint by buyer.
     */
    public function getReviewableItemsByBuyer($buyerId)
    {
        $sql = "SELECT
                    oi.order_id,
                    oi.product_id,
                    oi.product_name,
                    oi.farmer_id,
                    oi.quantity,
                    oi.product_price,
                    (
                        SELECT COUNT(*)
                        FROM {$this->orderItemsTable} oi_count
                        WHERE oi_count.order_id = oi.order_id
                    ) AS order_item_count,
                    (
                        SELECT COALESCE(SUM(oi_qty.quantity), 0)
                        FROM {$this->orderItemsTable} oi_qty
                        WHERE oi_qty.order_id = oi.order_id
                    ) AS order_total_quantity,
                    o.status AS order_status,
                    o.created_at AS order_created_at,
                    p.image AS product_image,
                    fu.name AS farmer_name
                FROM {$this->orderItemsTable} oi
                INNER JOIN {$this->table} o ON o.id = oi.order_id
                LEFT JOIN products p ON p.id = oi.product_id
                LEFT JOIN users fu ON fu.id = oi.farmer_id
                LEFT JOIN reviews r
                    ON r.order_id = oi.order_id
                    AND r.product_id = oi.product_id
                    AND r.buyer_id = :buyer_id
                    AND r.farmer_id = oi.farmer_id
                WHERE o.buyer_id = :buyer_id
                AND o.status = 'delivered'
                AND r.id IS NULL
                ORDER BY o.created_at DESC";

        $result = $this->query($sql, ['buyer_id' => $buyerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get orders eligible for transporter review by buyer.
     */
    public function getReviewableTransportersByBuyer($buyerId)
    {
        $sql = "SELECT
                    o.id AS order_id,
                    o.status AS order_status,
                    o.created_at AS order_created_at,
                    dr.transporter_id,
                    tu.name AS transporter_name,
                    oi.product_id,
                    oi.product_name,
                    os.order_item_count,
                    os.order_total_quantity
                FROM {$this->table} o
                INNER JOIN delivery_requests dr ON dr.order_id = o.id
                INNER JOIN users tu ON tu.id = dr.transporter_id
                INNER JOIN (
                    SELECT order_id, MIN(product_id) AS product_id
                    FROM {$this->orderItemsTable}
                    GROUP BY order_id
                ) first_item ON first_item.order_id = o.id
                INNER JOIN {$this->orderItemsTable} oi
                    ON oi.order_id = first_item.order_id
                    AND oi.product_id = first_item.product_id
                LEFT JOIN (
                    SELECT
                        order_id,
                        COUNT(*) AS order_item_count,
                        COALESCE(SUM(quantity), 0) AS order_total_quantity
                    FROM {$this->orderItemsTable}
                    GROUP BY order_id
                ) os ON os.order_id = o.id
                LEFT JOIN reviews r
                    ON r.order_id = o.id
                    AND r.product_id = oi.product_id
                    AND r.buyer_id = :buyer_id
                    AND r.farmer_id = dr.transporter_id
                WHERE o.buyer_id = :buyer_id
                AND o.status = 'delivered'
                AND dr.transporter_id IS NOT NULL
                AND r.id IS NULL
                ORDER BY o.created_at DESC";

        $result = $this->query($sql, ['buyer_id' => $buyerId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Validate a buyer order and return transporter context for review submission.
     */
    public function getOrderWithTransporterForBuyer($orderId, $buyerId)
    {
        $sql = "SELECT
                    o.id AS order_id,
                    o.status AS order_status,
                    dr.transporter_id,
                    tu.name AS transporter_name,
                    first_item.product_id
                FROM {$this->table} o
                INNER JOIN delivery_requests dr ON dr.order_id = o.id
                INNER JOIN users tu ON tu.id = dr.transporter_id
                INNER JOIN (
                    SELECT order_id, MIN(product_id) AS product_id
                    FROM {$this->orderItemsTable}
                    GROUP BY order_id
                ) first_item ON first_item.order_id = o.id
                WHERE o.id = :order_id
                AND o.buyer_id = :buyer_id
                LIMIT 1";

        return $this->get_row($sql, [
            'order_id' => $orderId,
            'buyer_id' => $buyerId,
        ]);
    }

    /**
     * Validate an order item belongs to a buyer and fetch its context.
     */
    public function getOrderItemForBuyer($orderId, $productId, $buyerId)
    {
        $sql = "SELECT oi.*, o.status AS order_status, o.buyer_id
                FROM {$this->orderItemsTable} oi
                INNER JOIN {$this->table} o ON o.id = oi.order_id
                WHERE oi.order_id = :order_id
                AND oi.product_id = :product_id
                AND o.buyer_id = :buyer_id
                LIMIT 1";

        return $this->get_row($sql, [
            'order_id' => $orderId,
            'product_id' => $productId,
            'buyer_id' => $buyerId,
        ]);
    }

    public function hasDeliveryRequestByOrderInStatuses($orderId, array $statuses)
    {
        $orderId = (int)$orderId;
        $statuses = array_values(array_unique(array_filter(array_map('strval', $statuses), function ($status) {
            return trim($status) !== '';
        })));

        if ($orderId <= 0 || empty($statuses)) {
            return false;
        }

        [$placeholders, $statusParams] = $this->buildStatusParams($statuses, 'dr_status_');
        $params = array_merge(['order_id' => $orderId], $statusParams);

        $sql = "SELECT id
                FROM delivery_requests
                WHERE order_id = :order_id
                  AND status IN (" . implode(', ', $placeholders) . ")
                LIMIT 1";

        $row = $this->get_row($sql, $params);
        return $row !== false;
    }

    public function cancelOrderAndPendingDeliveryRequests($orderId)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            return false;
        }

        if (!$this->beginTransaction()) {
            return false;
        }

        try {
            $orderUpdated = $this->write(
                "UPDATE {$this->table}
                 SET status = 'cancelled',
                     updated_at = NOW()
                 WHERE id = :order_id
                   AND status IN ('pending_payment', 'processing', 'ready_for_pickup')",
                ['order_id' => $orderId]
            );

            if ($orderUpdated === false) {
                $this->rollBack();
                return false;
            }

            $deliveryUpdated = $this->write(
                "UPDATE delivery_requests
                 SET status = 'cancelled',
                     updated_at = NOW()
                 WHERE order_id = :order_id
                   AND status IN ('pending', 'accepted')",
                ['order_id' => $orderId]
            );

            if ($deliveryUpdated === false) {
                $this->rollBack();
                return false;
            }

            if (!$this->commit()) {
                $this->rollBack();
                return false;
            }

            return true;
        } catch (Throwable $e) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }
            error_log('OrderModel::cancelOrderAndPendingDeliveryRequests error: ' . $e->getMessage());
            return false;
        }
    }

    private function buildStatusParams(array $statuses, $prefix)
    {
        $placeholders = [];
        $params = [];

        foreach (array_values($statuses) as $index => $status) {
            $key = $prefix . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (string)$status;
        }

        return [$placeholders, $params];
    }

    public function countBuyerOrdersByStatuses($buyerId, array $statuses)
    {
        $buyerId = (int)$buyerId;
        if ($buyerId <= 0 || empty($statuses)) {
            return 0;
        }

        [$placeholders, $statusParams] = $this->buildStatusParams($statuses, 'b_status_');
        $params = array_merge(['buyer_id' => $buyerId], $statusParams);

        $sql = "SELECT COUNT(*) AS total
                FROM {$this->table}
                WHERE buyer_id = :buyer_id
                  AND status IN (" . implode(', ', $placeholders) . ")";

        $row = $this->get_row($sql, $params);
        return (int)($row->total ?? 0);
    }

    public function countFarmerOrdersByStatuses($farmerId, array $statuses)
    {
        $farmerId = (int)$farmerId;
        if ($farmerId <= 0 || empty($statuses)) {
            return 0;
        }

        [$placeholders, $statusParams] = $this->buildStatusParams($statuses, 'f_status_');
        $params = array_merge(['farmer_id' => $farmerId], $statusParams);

        $sql = "SELECT COUNT(DISTINCT o.id) AS total
                FROM {$this->table} o
                INNER JOIN {$this->orderItemsTable} oi ON oi.order_id = o.id
                WHERE oi.farmer_id = :farmer_id
                  AND o.status IN (" . implode(', ', $placeholders) . ")";

        $row = $this->get_row($sql, $params);
        return (int)($row->total ?? 0);
    }
}
