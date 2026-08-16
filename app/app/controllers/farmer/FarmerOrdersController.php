<?php

class FarmerOrdersController
{
    use Controller;

    protected $farmerModel;
    protected $orderModel;

    public function __construct()
    {
        $this->farmerModel = new FarmerModel();
        $this->orderModel = new OrderModel();
    }

    /**
     * Display farmer orders page
     */
    public function index()
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $userId = authUserId();

        // Get all orders that contain this farmer's products
        $orders = $this->farmerModel->getFarmerOrders($userId);

        $data = [
            'pageTitle' => 'Orders',
            'activePage' => 'orders',
            'pageStyles' => ['orders.css'],
            'orders' => $orders,
            'contentView' => '../app/views/farmer/farmerOrders.view.php',
            'pageScript' => 'farmerOrders.js'
        ];

        $this->view('farmer/farmerSidebar', $data);
    }

    /**
     * Get order details (AJAX)
     */
    public function getOrderDetails()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('farmer')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $orderId = $_GET['order_id'] ?? null;
        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Order ID required']);
            exit;
        }

        $userId = authUserId();

        if (!$this->farmerModel->verifyFarmerOrderOwnership((int)$orderId, (int)$userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // Get order details
        $order = $this->orderModel->getOrderById($orderId);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }

        // Get order items for this farmer only
        $orderItems = $this->farmerModel->getFarmerOrderItems($orderId, $userId);

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $orderItems
        ]);
        exit;
    }

    /**
     * Update order status (AJAX)
     */
    public function updateOrderStatus()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('farmer')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $input['order_id'] ?? null;
        $status = $input['status'] ?? null;

        if (!$orderId || !$status) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Order ID and status are required']);
            exit;
        }

        $userId = authUserId();

        if (!$this->farmerModel->verifyFarmerOrderOwnership($orderId, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized to update this order']);
            exit;
        }

        $result = $this->farmerModel->updateFarmerOrderStatus((int)$orderId, (int)$userId, (string)$status);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid status transition']);
        }
        exit;
    }
}
