<?php

class BuyerTrackingController
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

        $userId = authUserId();
        $orderFilter = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        $trackingRows = $this->orderModel->getDeliveryTrackingByBuyer($userId);

        if ($orderFilter > 0) {
            $trackingRows = array_values(array_filter($trackingRows, function ($row) use ($orderFilter) {
                return (int)$row->order_id === $orderFilter;
            }));
        }

        $data = [
            'pageTitle' => 'Order Tracking',
            'activePage' => 'tracking',
            'trackingRows' => $trackingRows,
            'orderFilter' => $orderFilter,
            'pageStyles' => 'tracking.css',
            'contentView' => 'buyer/tracking.view.php'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }
}
