<?php

class ReviewModel
{
    use Database;

    protected $table = 'reviews';

    // Create a new review
    public function createReview($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (order_id, product_id, buyer_id, farmer_id, rating, comment, created_at) 
                VALUES (:order_id, :product_id, :buyer_id, :farmer_id, :rating, :comment, NOW())";

        return $this->write($sql, $data);
    }

    // Check if a review already exists for an order item
    public function exists($orderId, $productId, $buyerId)
    {
        $sql = "SELECT id FROM {$this->table} 
                WHERE order_id = :order_id AND product_id = :product_id AND buyer_id = :buyer_id";

        $result = $this->get_row($sql, [
            'order_id' => $orderId,
            'product_id' => $productId,
            'buyer_id' => $buyerId
        ]);

        return $result ? true : false;
    }

    // Check if a review exists for a specific target user (farmer/transporter)
    public function existsForTarget($orderId, $productId, $buyerId, $targetUserId)
    {
        $sql = "SELECT id FROM {$this->table}
                WHERE order_id = :order_id
                AND product_id = :product_id
                AND buyer_id = :buyer_id
                AND farmer_id = :target_user_id";

        $result = $this->get_row($sql, [
            'order_id' => $orderId,
            'product_id' => $productId,
            'buyer_id' => $buyerId,
            'target_user_id' => $targetUserId,
        ]);

        return $result ? true : false;
    }

    // Get reviews by buyer ID
    public function getReviewsByBuyer($buyerId)
    {
        $sql = "SELECT
                    r.*,
                    (
                        SELECT oi_name.product_name
                        FROM order_items oi_name
                        WHERE oi_name.order_id = r.order_id
                        AND oi_name.product_id = r.product_id
                        LIMIT 1
                    ) as order_item_name,
                    p.name as product_name,
                    p.image as product_image,
                    u.name as farmer_name,
                    u.role as target_role,
                    u.name as target_name,
                    (
                        SELECT COALESCE(SUM(oi.quantity), 0)
                        FROM order_items oi
                        WHERE oi.order_id = r.order_id
                        AND oi.product_id = r.product_id
                    ) as reviewed_quantity,
                    (
                        SELECT COUNT(*)
                        FROM order_items oi2
                        WHERE oi2.order_id = r.order_id
                    ) as order_item_count,
                    (
                        SELECT COALESCE(SUM(oi3.quantity), 0)
                        FROM order_items oi3
                        WHERE oi3.order_id = r.order_id
                    ) as order_total_quantity
                FROM {$this->table} r
                JOIN products p ON r.product_id = p.id
                JOIN users u ON r.farmer_id = u.id
                WHERE r.buyer_id = :buyer_id
                ORDER BY r.created_at DESC";

        return $this->query($sql, ['buyer_id' => $buyerId]);
    }

    // Get reviews for a farmer
    public function getReviewsByFarmer($farmerId)
    {
        $sql = "SELECT
                    r.*,
                    p.name as product_name,
                    u.name as buyer_name,
                    u.email as buyer_email,
                    (
                        SELECT COALESCE(SUM(oi.quantity), 0)
                        FROM order_items oi
                        WHERE oi.order_id = r.order_id
                        AND oi.product_id = r.product_id
                        AND oi.farmer_id = r.farmer_id
                    ) as reviewed_quantity,
                    (
                        SELECT COUNT(*)
                        FROM order_items oi2
                        WHERE oi2.order_id = r.order_id
                        AND oi2.farmer_id = r.farmer_id
                    ) as order_product_count,
                    (
                        SELECT GROUP_CONCAT(DISTINCT oi4.product_name ORDER BY oi4.product_name SEPARATOR ', ')
                        FROM order_items oi4
                        WHERE oi4.order_id = r.order_id
                        AND oi4.farmer_id = r.farmer_id
                    ) as order_products
                FROM {$this->table} r
                JOIN products p ON r.product_id = p.id
                JOIN users u ON r.buyer_id = u.id
                JOIN users target_user ON r.farmer_id = target_user.id
                WHERE r.farmer_id = :farmer_id
                AND target_user.role = 'farmer'
                AND EXISTS (
                    SELECT 1
                    FROM order_items oi3
                    WHERE oi3.order_id = r.order_id
                    AND oi3.product_id = r.product_id
                    AND oi3.farmer_id = r.farmer_id
                )
                ORDER BY r.created_at DESC";

        return $this->query($sql, ['farmer_id' => $farmerId]);
    }

    // Get reviews for a transporter (stored in farmer_id field as target user id)
    public function getReviewsByTransporter($transporterId)
    {
        $sql = "SELECT
                    r.*, 
                    p.name as product_name,
                    u.name as buyer_name,
                    u.email as buyer_email,
                    dr.id as delivery_id,
                    dr.id as delivery_request_id,
                    dr.status as delivery_status,
                    dr.farmer_city,
                    dr.buyer_city,
                    fd.district_name as farmer_district_name,
                    bd.district_name as buyer_district_name,
                    (
                        SELECT COALESCE(SUM(oi.quantity), 0)
                        FROM order_items oi
                        WHERE oi.order_id = r.order_id
                        AND oi.product_id = r.product_id
                    ) as reviewed_quantity
                FROM {$this->table} r
                LEFT JOIN products p ON r.product_id = p.id
                JOIN users u ON r.buyer_id = u.id
                JOIN users t ON r.farmer_id = t.id
                LEFT JOIN delivery_requests dr ON dr.id = (
                    SELECT dr2.id
                    FROM delivery_requests dr2
                    WHERE dr2.order_id = r.order_id
                    AND dr2.transporter_id = r.farmer_id
                    ORDER BY dr2.id DESC
                    LIMIT 1
                )
                LEFT JOIN districts fd ON fd.id = dr.farmer_district_id
                LEFT JOIN districts bd ON bd.id = dr.buyer_district_id
                WHERE r.farmer_id = :transporter_id
                AND t.role = 'transporter'
                ORDER BY r.created_at DESC";

        return $this->query($sql, ['transporter_id' => $transporterId]);
    }

    // Add a reply to a review
    public function replyToReview($reviewId, $reply)
    {
        $sql = "UPDATE {$this->table} 
                SET reply = :reply, replied_at = NOW() 
                WHERE id = :id";

        return $this->write($sql, ['reply' => $reply, 'id' => $reviewId]);
    }

    public function getReviewById($reviewId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        return $this->get_row($sql, ['id' => $reviewId]);
    }
}
