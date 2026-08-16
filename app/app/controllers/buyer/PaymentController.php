<?php

class PaymentController
{
    use Controller;

    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    private function ensureBuyer(): void
    {
        if (!hasRole('buyer')) {
            redirect('login');
            exit;
        }
    }

    private function parseOrderIds($primaryRaw, $listRaw)
    {
        $ids = [];

        $primaryId = (int)$primaryRaw;
        if ($primaryId > 0) {
            $ids[] = $primaryId;
        }

        $list = trim((string)$listRaw);
        if ($list !== '') {
            $parts = explode(',', $list);
            foreach ($parts as $part) {
                $id = (int)trim($part);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    private function isCardNumberValid($cardNumber)
    {
        return preg_match('/^\d{12,19}$/', $cardNumber) === 1;
    }

    private function isExpiryValid($monthRaw, $yearRaw)
    {
        $month = (int)preg_replace('/\D/', '', (string)$monthRaw);
        $yearDigits = preg_replace('/\D/', '', (string)$yearRaw);

        if ($month < 1 || $month > 12) {
            return false;
        }

        if (strlen($yearDigits) === 2) {
            $yearDigits = '20' . $yearDigits;
        }

        if (!preg_match('/^\d{4}$/', $yearDigits)) {
            return false;
        }

        $year = (int)$yearDigits;
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');

        if ($year < $currentYear) {
            return false;
        }

        if ($year === $currentYear && $month < $currentMonth) {
            return false;
        }

        if ($year > $currentYear + 20) {
            return false;
        }

        return true;
    }

    private function loadBuyerOrdersOrRedirect($buyerId, array $orderIds)
    {
        if (empty($orderIds)) {
            redirect('buyerorders');
            exit;
        }

        $orders = $this->orderModel->getOrdersByIdsForBuyer($buyerId, $orderIds);
        if (!is_array($orders) || count($orders) !== count($orderIds)) {
            redirect('buyerorders');
            exit;
        }

        return $orders;
    }

    private function buildOrderIdsQuery(array $orderIds)
    {
        return implode(',', array_map('intval', $orderIds));
    }

    private function paymentRedirectPath($endpoint, array $orderIds)
    {
        $primaryOrderId = (int)$orderIds[0];
        $orderIdsQuery = urlencode($this->buildOrderIdsQuery($orderIds));

        return ROOT . '/payment/' . $endpoint . '?order_id=' . $primaryOrderId . '&order_ids=' . $orderIdsQuery;
    }

    public function checkout()
    {
        $this->ensureBuyer();

        $buyerId = (int)authUserId();
        $orderIds = $this->parseOrderIds($_GET['order_id'] ?? 0, $_GET['order_ids'] ?? '');
        $orders = $this->loadBuyerOrdersOrRedirect($buyerId, $orderIds);

        foreach ($orders as $order) {
            $paymentStatus = strtolower((string)($order->payment_status ?? 'pending'));
            $orderStatus = strtolower((string)($order->status ?? ''));

            if ($paymentStatus === 'paid') {
                header('Location: ' . $this->paymentRedirectPath('success', $orderIds));
                exit;
            }

            if ($orderStatus !== 'pending_payment') {
                header('Location: ' . ROOT . '/buyerorders');
                exit;
            }
        }

        $totalAmount = 0.0;
        foreach ($orders as $order) {
            $totalAmount += (float)($order->order_total ?? 0);
        }

        $data = [
            'pageTitle' => 'SecurePay Checkout',
            'activePage' => 'orders',
            'orders' => $orders,
            'orderIds' => $orderIds,
            'orderIdsQuery' => $this->buildOrderIdsQuery($orderIds),
            'totalAmount' => $totalAmount,
            'gatewayViewMode' => 'checkout',
            'pageStyles' => ['checkout.css', 'paymentGateway.css'],
            'pageScript' => 'paymentGateway.js',
            'contentView' => 'buyer/paymentGateway.view.php',
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    public function process()
    {
        $this->ensureBuyer();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('buyerorders');
            exit;
        }

        $buyerId = (int)authUserId();
        $orderIds = $this->parseOrderIds($_POST['order_id'] ?? 0, $_POST['order_ids'] ?? '');
        $orders = $this->loadBuyerOrdersOrRedirect($buyerId, $orderIds);

        foreach ($orders as $order) {
            $paymentStatus = strtolower((string)($order->payment_status ?? 'pending'));
            $orderStatus = strtolower((string)($order->status ?? ''));

            if ($paymentStatus === 'paid') {
                header('Location: ' . $this->paymentRedirectPath('success', $orderIds));
                exit;
            }

            if ($orderStatus !== 'pending_payment') {
                header('Location: ' . ROOT . '/buyerorders');
                exit;
            }
        }

        $cardHolder = trim((string)($_POST['card_holder_name'] ?? ''));
        $cardNumber = preg_replace('/\D/', '', (string)($_POST['card_number'] ?? ''));
        $expiryMonth = trim((string)($_POST['expiry_month'] ?? ''));
        $expiryYear = trim((string)($_POST['expiry_year'] ?? ''));
        $cvv = preg_replace('/\D/', '', (string)($_POST['cvv'] ?? ''));

        $isValidHolder = (bool)preg_match('/^[A-Za-z][A-Za-z\s.\'-]{1,79}$/', $cardHolder);
        $isValidCard = $this->isCardNumberValid($cardNumber);
        $isValidExpiry = $this->isExpiryValid($expiryMonth, $expiryYear);
        $isValidCvv = (bool)preg_match('/^\d{3,4}$/', $cvv);

        if (!$isValidHolder || !$isValidCard || !$isValidExpiry || !$isValidCvv) {
            $this->orderModel->updatePaymentResultForBuyerOrders($buyerId, $orderIds, 'failed', 'pending_payment');
            header('Location: ' . $this->paymentRedirectPath('failed', $orderIds));
            exit;
        }

        // Simulate gateway processing delay.
        usleep(1800000);

        $isSuccess = true;

        if ($isSuccess) {
            $this->orderModel->updatePaymentResultForBuyerOrders($buyerId, $orderIds, 'paid', 'processing');
            header('Location: ' . $this->paymentRedirectPath('success', $orderIds));
            exit;
        }

        $this->orderModel->updatePaymentResultForBuyerOrders($buyerId, $orderIds, 'failed', 'pending_payment');
        header('Location: ' . $this->paymentRedirectPath('failed', $orderIds));
        exit;
    }

    public function success()
    {
        $this->ensureBuyer();

        $buyerId = (int)authUserId();
        $orderIds = $this->parseOrderIds($_GET['order_id'] ?? 0, $_GET['order_ids'] ?? '');
        $orders = $this->loadBuyerOrdersOrRedirect($buyerId, $orderIds);

        $totalAmount = 0.0;
        foreach ($orders as $order) {
            $totalAmount += (float)($order->order_total ?? 0);
        }

        $data = [
            'pageTitle' => 'Payment Successful',
            'activePage' => 'orders',
            'orders' => $orders,
            'totalAmount' => $totalAmount,
            'gatewayViewMode' => 'success',
            'pageStyles' => ['checkout.css', 'paymentGateway.css'],
            'contentView' => 'buyer/paymentGateway.view.php',
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    public function failed()
    {
        $this->ensureBuyer();

        $buyerId = (int)authUserId();
        $orderIds = $this->parseOrderIds($_GET['order_id'] ?? 0, $_GET['order_ids'] ?? '');
        $orders = $this->loadBuyerOrdersOrRedirect($buyerId, $orderIds);

        $totalAmount = 0.0;
        foreach ($orders as $order) {
            $totalAmount += (float)($order->order_total ?? 0);
        }

        $retryUrl = ROOT . '/payment/checkout?order_id=' . (int)$orderIds[0] . '&order_ids=' . urlencode($this->buildOrderIdsQuery($orderIds));

        $data = [
            'pageTitle' => 'Payment Failed',
            'activePage' => 'orders',
            'orders' => $orders,
            'totalAmount' => $totalAmount,
            'retryUrl' => $retryUrl,
            'gatewayViewMode' => 'failed',
            'pageStyles' => ['checkout.css', 'paymentGateway.css'],
            'contentView' => 'buyer/paymentGateway.view.php',
        ];

        $this->view('buyer/buyerSidebar', $data);
    }
}
