<?php

class BuyerOrdersController
{
    use Controller;

    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    public function index()
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $buyerId = (int)authUserId();
        $orders = $this->orderModel->getOrdersByBuyer($buyerId);

        $ordersWithItems = [];
        foreach ($orders as $order) {
            $orderItems = $this->orderModel->getOrderItems($order->id);
            $ordersWithItems[] = [
                'order' => $order,
                'items' => $orderItems,
            ];
        }

        $data = [
            'pageTitle' => 'My Orders',
            'activePage' => 'orders',
            'username' => authUserName(),
            'orders' => $ordersWithItems,
            'pageStyles' => 'orders.css',
            'pageScript' => 'buyerDashboard.js?v=' . time(),
            'contentView' => 'buyer/orders.view.php',
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    public function cancel()
    {
        // Set JSON header since this is an API endpoint
        header('Content-Type: application/json');

        if (!hasRole('buyer')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $orderId = $_POST['order_id'] ?? null;

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Order ID is required']);
            return;
        }

        // Get order details to verify ownership and status
        $order = $this->orderModel->getOrderById($orderId);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Verify ownership
        if ($order->buyer_id != authUserId()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to cancel this order']);
            return;
        }

        // Check if order can be cancelled by order lifecycle.
        if (!in_array($order->status, ['pending_payment', 'processing', 'ready_for_pickup'], true)) {
            echo json_encode(['success' => false, 'message' => 'This order cannot be cancelled in its current status']);
            return;
        }

        // Do not allow cancellation after delivery starts transit.
        if ($this->orderModel->hasDeliveryRequestByOrderInStatuses((int)$orderId, ['in_transit'])) {
            echo json_encode([
                'success' => false,
                'message' => 'This order cannot be cancelled because delivery is already in transit'
            ]);
            return;
        }

        // Cancel order and related delivery requests in one transaction.
        if ($this->orderModel->cancelOrderAndPendingDeliveryRequests((int)$orderId)) {
            // Restore stock
            $this->orderModel->restoreOrderStock($orderId);
            echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
        }
    }

    public function details()
    {
        // Set JSON header
        header('Content-Type: application/json');

        if (!hasRole('buyer')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $orderId = $_GET['id'] ?? null;

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Order ID is required']);
            return;
        }

        // Get order details
        $order = $this->orderModel->getOrderById($orderId);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Verify ownership
        if ($order->buyer_id != authUserId()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to view this order']);
            return;
        }

        // Get order items
        $items = $this->orderModel->getOrderItems($orderId);

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items
        ]);
    }
}
