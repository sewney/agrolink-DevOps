<?php

class TransporterNotificationsController
{
    use Controller;

    private $notificationsModel;

    public function __construct()
    {
        $this->notificationsModel = new TransporterNotificationsModel();
    }

    private function isAuthorizedTransporter()
    {
        return hasRole('transporter');
    }

    private function getTransporterId()
    {
        return (int)(authUserId() ?? 0);
    }

    public function index()
    {
        if (!$this->isAuthorizedTransporter()) {
            return redirect('login');
        }

        $transporterId = $this->getTransporterId();

        $data = [
            'pageTitle' => 'Notifications',
            'activePage' => 'notifications',
            'notifications' => $this->notificationsModel->getNotifications($transporterId, 'all'),
            'notificationUnreadCount' => $this->notificationsModel->getUnreadCount($transporterId),
            'notificationSettings' => $this->notificationsModel->getSettings($transporterId),
            'contentView' => '../app/views/transporter/transporterNotifications.view.php',
            'pageScript' => 'transporterNotifications.js',
        ];

        $this->view('transporter/transporterSidebar', $data);
    }

    public function list()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedTransporter()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $transporterId = $this->getTransporterId();
        $filter = trim((string)($_GET['filter'] ?? 'all'));

        echo json_encode([
            'success' => true,
            'notifications' => $this->notificationsModel->getNotifications($transporterId, $filter),
            'settings' => $this->notificationsModel->getSettings($transporterId),
            'unreadCount' => $this->notificationsModel->getUnreadCount($transporterId),
        ]);
        exit;
    }

    public function markAllAsRead()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedTransporter()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $transporterId = $this->getTransporterId();
        $this->notificationsModel->getNotifications($transporterId, 'all');
        $this->notificationsModel->markAllAsRead($transporterId);

        echo json_encode([
            'success' => true,
            'notifications' => $this->notificationsModel->getNotifications($transporterId, 'all'),
            'settings' => $this->notificationsModel->getSettings($transporterId),
            'unreadCount' => $this->notificationsModel->getUnreadCount($transporterId),
        ]);
        exit;
    }

    public function markRead()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedTransporter()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $transporterId = $this->getTransporterId();
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Invalid notification id']);
            exit;
        }

        $updated = $this->notificationsModel->markAsRead($transporterId, $notificationId);

        echo json_encode([
            'success' => (bool)$updated,
            'unreadCount' => $this->notificationsModel->getUnreadCount($transporterId),
        ]);
        exit;
    }

    public function unreadCount()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedTransporter()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $transporterId = $this->getTransporterId();
        echo json_encode([
            'success' => true,
            'unreadCount' => $this->notificationsModel->getUnreadCount($transporterId),
        ]);
        exit;
    }

    public function saveSettings()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!$this->isAuthorizedTransporter()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $transporterId = $this->getTransporterId();
        $payload = [
            'deliveries' => !empty($_POST['deliveries']),
            'reviews' => !empty($_POST['reviews']),
            'system' => !empty($_POST['system']),
            'email_notifications' => !empty($_POST['email_notifications']),
        ];

        $settings = $this->notificationsModel->saveSettings($transporterId, $payload);

        echo json_encode([
            'success' => true,
            'message' => 'Notification settings saved',
            'settings' => $settings,
            'notifications' => $this->notificationsModel->getNotifications($transporterId, 'all'),
            'unreadCount' => $this->notificationsModel->getUnreadCount($transporterId),
        ]);
        exit;
    }
}
