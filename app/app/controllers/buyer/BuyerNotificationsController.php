<?php

class BuyerNotificationsController
{
    use Controller;

    private $notificationsModel;

    public function __construct()
    {
        $this->notificationsModel = new BuyerNotificationsModel();
    }

    private function isAuthorizedBuyer()
    {
        return hasRole('buyer');
    }

    private function getBuyerId()
    {
        return (int)(authUserId() ?? 0);
    }

    public function index()
    {
        if (!$this->isAuthorizedBuyer()) {
            return redirect('login');
        }

        $buyerId = $this->getBuyerId();

        $data = [
            'pageTitle' => 'Notifications',
            'activePage' => 'notifications',
            'notifications' => $this->notificationsModel->getNotifications($buyerId, 'all'),
            'notificationUnreadCount' => $this->notificationsModel->getUnreadCount($buyerId),
            'notificationSettings' => $this->notificationsModel->getSettings($buyerId),
            'contentView' => 'buyer/notifications.view.php',
            'pageStyles' => 'notifications.css',
            'pageScript' => 'buyerNotifications.js',
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    public function list()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedBuyer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $buyerId = $this->getBuyerId();
        $filter = trim((string)($_GET['filter'] ?? 'all'));

        echo json_encode([
            'success' => true,
            'notifications' => $this->notificationsModel->getNotifications($buyerId, $filter),
            'settings' => $this->notificationsModel->getSettings($buyerId),
            'unreadCount' => $this->notificationsModel->getUnreadCount($buyerId),
        ]);
        exit;
    }

    public function markAllAsRead()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedBuyer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $buyerId = $this->getBuyerId();
        // Ensure generated notifications are synced before marking all as read.
        $this->notificationsModel->getNotifications($buyerId, 'all');
        $this->notificationsModel->markAllAsRead($buyerId);

        echo json_encode([
            'success' => true,
            'notifications' => $this->notificationsModel->getNotifications($buyerId, 'all'),
            'settings' => $this->notificationsModel->getSettings($buyerId),
            'unreadCount' => $this->notificationsModel->getUnreadCount($buyerId),
        ]);
        exit;
    }

    public function markRead()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedBuyer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $buyerId = $this->getBuyerId();
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Invalid notification id']);
            exit;
        }

        $updated = $this->notificationsModel->markAsRead($buyerId, $notificationId);

        echo json_encode([
            'success' => (bool)$updated,
            'unreadCount' => $this->notificationsModel->getUnreadCount($buyerId),
        ]);
        exit;
    }

    public function unreadCount()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedBuyer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $buyerId = $this->getBuyerId();
        echo json_encode([
            'success' => true,
            'unreadCount' => $this->notificationsModel->getUnreadCount($buyerId),
        ]);
        exit;
    }

    public function saveSettings()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedBuyer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $buyerId = $this->getBuyerId();

        $payload = [
            'new_products' => !empty($_POST['new_products']),
            'tracking' => !empty($_POST['tracking']),
            'review_replies' => !empty($_POST['review_replies']),
            'request_replies' => !empty($_POST['request_replies']),
            'order_updates' => !empty($_POST['order_updates']),
            'system' => !empty($_POST['system']),
            'email_notifications' => !empty($_POST['email_notifications']),
        ];

        $settings = $this->notificationsModel->saveSettings($buyerId, $payload);

        echo json_encode([
            'success' => true,
            'message' => 'Notification settings saved',
            'settings' => $settings,
            'notifications' => $this->notificationsModel->getNotifications($buyerId, 'all'),
            'unreadCount' => $this->notificationsModel->getUnreadCount($buyerId),
        ]);
        exit;
    }
}
