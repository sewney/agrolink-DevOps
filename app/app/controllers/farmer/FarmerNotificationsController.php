<?php

class FarmerNotificationsController
{
    use Controller;

    private $notificationsModel;

    public function __construct()
    {
        $this->notificationsModel = new FarmerNotificationsModel();
    }

    private function isAuthorizedFarmer()
    {
        return hasRole('farmer');
    }

    private function getFarmerId()
    {
        return (int)(authUserId() ?? 0);
    }

    public function index()
    {
        if (!$this->isAuthorizedFarmer()) {
            return redirect('login');
        }

        $farmerId = $this->getFarmerId();
        $notifications = $this->notificationsModel->getNotifications($farmerId, 'all');
        $settings = $this->notificationsModel->getSettings($farmerId);
        $unreadCount = $this->notificationsModel->getUnreadCount($farmerId);

        $data = [
            'pageTitle' => 'Notifications',
            'activePage' => 'notifications',
            'pageStyles' => ['notifications.css'],
            'notificationUnreadCount' => $unreadCount,
            'notifications' => $notifications,
            'notificationSettings' => $settings,
            'contentView' => '../app/views/farmer/farmerNotifications.view.php',
            'pageScript' => 'farmerNotifications.js',
        ];

        $this->view('farmer/farmerSidebar', $data);
    }

    public function list()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedFarmer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $farmerId = $this->getFarmerId();
        $filter = trim((string)($_GET['filter'] ?? 'all'));

        echo json_encode([
            'success' => true,
            'notifications' => $this->notificationsModel->getNotifications($farmerId, $filter),
            'settings' => $this->notificationsModel->getSettings($farmerId),
            'unreadCount' => $this->notificationsModel->getUnreadCount($farmerId),
        ]);
        exit;
    }

    public function markAllAsRead()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedFarmer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $farmerId = $this->getFarmerId();
        // Ensure generated notifications are synced before marking all as read.
        $this->notificationsModel->getNotifications($farmerId, 'all');
        $this->notificationsModel->markAllAsRead($farmerId);

        echo json_encode([
            'success' => true,
            'unreadCount' => $this->notificationsModel->getUnreadCount($farmerId),
            'notifications' => $this->notificationsModel->getNotifications($farmerId, 'all'),
        ]);
        exit;
    }

    public function markRead()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedFarmer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $farmerId = $this->getFarmerId();
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Invalid notification id']);
            exit;
        }

        $updated = $this->notificationsModel->markAsRead($farmerId, $notificationId);

        echo json_encode([
            'success' => (bool)$updated,
            'unreadCount' => $this->notificationsModel->getUnreadCount($farmerId),
        ]);
        exit;
    }

    public function unreadCount()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedFarmer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $farmerId = $this->getFarmerId();
        echo json_encode([
            'success' => true,
            'unreadCount' => $this->notificationsModel->getUnreadCount($farmerId),
        ]);
        exit;
    }

    public function saveSettings()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedFarmer()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $farmerId = $this->getFarmerId();

        $payload = [
            'orders' => !empty($_POST['orders']),
            'crop_requests' => !empty($_POST['crop_requests']),
            'deliveries' => !empty($_POST['deliveries']),
            'reviews' => !empty($_POST['reviews']),
            'system' => !empty($_POST['system']),
            'email_notifications' => !empty($_POST['email_notifications']),
        ];

        $settings = $this->notificationsModel->saveSettings($farmerId, $payload);

        echo json_encode([
            'success' => true,
            'message' => 'Notification settings saved',
            'settings' => $settings,
            'notifications' => $this->notificationsModel->getNotifications($farmerId, 'all'),
            'unreadCount' => $this->notificationsModel->getUnreadCount($farmerId),
        ]);
        exit;
    }
}
