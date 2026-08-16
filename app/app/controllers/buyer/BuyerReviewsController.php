<?php

class BuyerReviewsController
{
    use Controller;

    private $reviewModel;
    private $orderModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
        $this->orderModel = new OrderModel();
    }

    public function index()
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $userId = authUserId();
        $reviews = $this->reviewModel->getReviewsByBuyer($userId);
        $reviewableItems = $this->orderModel->getReviewableItemsByBuyer($userId);
        $reviewableTransporters = $this->orderModel->getReviewableTransportersByBuyer($userId);

        $data = [
            'pageTitle' => 'My Reviews',
            'activePage' => 'reviews',
            'reviews' => $reviews,
            'reviewableItems' => $reviewableItems,
            'reviewableTransporters' => $reviewableTransporters,
            'pageStyles' => 'reviews.css',
            'contentView' => 'buyer/reviews.view.php'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    public function submit()
    {
        header('Content-Type: application/json');

        if (!hasRole('buyer')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $buyerId = authUserId();
        $reviewType = strtolower(trim($_POST['review_type'] ?? 'farmer'));
        $productId = $_POST['product_id'] ?? null;
        $orderId = $_POST['order_id'] ?? null;
        $farmerId = $_POST['farmer_id'] ?? null;
        $transporterId = $_POST['transporter_id'] ?? null;
        $rating = $_POST['rating'] ?? null;
        $comment = trim($_POST['comment'] ?? '');

        if (!$orderId || !$rating || !$comment) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        if ($reviewType === 'transporter') {
            if (!$transporterId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Transporter is required']);
                exit;
            }

            $orderContext = $this->orderModel->getOrderWithTransporterForBuyer($orderId, $buyerId);
            if (!$orderContext) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'This order is not available for transporter review']);
                exit;
            }

            if ((int)$orderContext->transporter_id !== (int)$transporterId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid transporter for selected order']);
                exit;
            }

            if ($orderContext->order_status !== 'delivered') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Transporter reviews are available after the order is delivered']);
                exit;
            }

            $canonicalProductId = (int)$orderContext->product_id;
            if ($this->reviewModel->existsForTarget($orderId, $canonicalProductId, $buyerId, $transporterId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'You have already reviewed this transporter for this order']);
                exit;
            }

            $data = [
                'order_id' => $orderId,
                'product_id' => $canonicalProductId,
                'buyer_id' => $buyerId,
                'farmer_id' => $transporterId,
                'rating' => $rating,
                'comment' => $comment
            ];

            if ($this->reviewModel->createReview($data)) {
                echo json_encode(['success' => true, 'message' => 'Transporter review submitted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to submit transporter review']);
            }
            exit;
        }

        if (!$productId || !$farmerId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product and farmer are required']);
            exit;
        }

        // Validate ownership and allowed order state before creating farmer review/complaint
        $orderItem = $this->orderModel->getOrderItemForBuyer($orderId, $productId, $buyerId);
        if (!$orderItem) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'This order item is not available for review']);
            exit;
        }

        if ((int)$orderItem->farmer_id !== (int)$farmerId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid farmer for selected product']);
            exit;
        }

        if ($orderItem->order_status !== 'delivered') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Reviews can be written once the order is delivered']);
            exit;
        }

        if ($this->reviewModel->existsForTarget($orderId, $productId, $buyerId, $farmerId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
            exit;
        }

        $data = [
            'order_id' => $orderId,
            'product_id' => $productId,
            'buyer_id' => $buyerId,
            'farmer_id' => $farmerId,
            'rating' => $rating,
            'comment' => $comment
        ];

        if ($this->reviewModel->createReview($data)) {
            echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
        }
        exit;
    }
}
