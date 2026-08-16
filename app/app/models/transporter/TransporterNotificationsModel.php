<?php

class TransporterNotificationsModel
{
    private $store;
    private $settingsStore;

    public function __construct()
    {
        $this->store = new NotificationsModel();
        $this->settingsStore = new NotificationSettingsModel();
    }

    public function getDefaultSettings()
    {
        return [
            'deliveries' => true,
            'reviews' => true,
            'system' => true,
            'email_notifications' => true,
        ];
    }

    public function getSettings($transporterId)
    {
        return $this->settingsStore->getSettings((int)$transporterId, $this->getDefaultSettings());
    }

    public function saveSettings($transporterId, $settings)
    {
        return $this->settingsStore->saveSettings((int)$transporterId, (array)$settings, $this->getDefaultSettings());
    }

    private function getManagedTypes()
    {
        return [
            'deliveries',
            'reviews',
            'system',
        ];
    }

    public function getNotifications($transporterId, $filter = 'all')
    {
        $transporterId = (int)$transporterId;
        if ($transporterId <= 0) {
            return [];
        }

        $this->store->syncNotifications($transporterId, $this->buildAllNotifications($transporterId), $this->getManagedTypes());

        $settings = $this->getSettings($transporterId);
        $allowedTypes = $this->getEnabledTypes($settings);
        if (empty($allowedTypes)) {
            return [];
        }

        return $this->store->listNotifications($transporterId, $filter, $allowedTypes);
    }

    public function getUnreadCount($transporterId)
    {
        $transporterId = (int)$transporterId;
        if ($transporterId <= 0) {
            return 0;
        }

        $this->store->syncNotifications($transporterId, $this->buildAllNotifications($transporterId), $this->getManagedTypes());

        $settings = $this->getSettings($transporterId);
        $allowedTypes = $this->getEnabledTypes($settings);
        if (empty($allowedTypes)) {
            return 0;
        }

        return $this->store->getUnreadCount($transporterId, $allowedTypes);
    }

    public function markAllAsRead($transporterId)
    {
        $transporterId = (int)$transporterId;
        if ($transporterId <= 0) {
            return false;
        }

        return $this->store->markAllAsRead($transporterId);
    }

    public function markAsRead($transporterId, $notificationId)
    {
        $transporterId = (int)$transporterId;
        $notificationId = (int)$notificationId;

        if ($transporterId <= 0 || $notificationId <= 0) {
            return false;
        }

        return $this->store->markAsRead($transporterId, $notificationId);
    }

    private function getEnabledTypes(array $settings)
    {
        $types = [];
        foreach ($settings as $key => $enabled) {
            if ($key === 'email_notifications') {
                continue;
            }
            if (!empty($enabled)) {
                $types[] = $key;
            }
        }

        return $types;
    }

    private function asArray($rows)
    {
        return is_array($rows) ? $rows : [];
    }

    private function formatStatusText($status)
    {
        $status = strtolower(trim((string)$status));
        if ($status === '') {
            return 'Updated';
        }

        return ucwords(str_replace('_', ' ', $status));
    }

    private function buildAllNotifications($transporterId)
    {
        $items = [];
        $items = array_merge($items, $this->getDeliveryNotifications($transporterId));
        $items = array_merge($items, $this->getReviewNotifications($transporterId));
        $items = array_merge($items, $this->getSystemNotifications($transporterId));

        return $items;
    }

    private function getDeliveryNotifications($transporterId)
    {
        $transporterModel = new TransporterModel();
        $deliveries = $this->asArray($transporterModel->getMyDeliveryRequests($transporterId));
        $notifications = [];

        $titleMap = [
            'accepted' => 'Delivery Assigned',
            'in_transit' => 'Delivery In Transit',
            'delivered' => 'Delivery Completed',
            'cancelled' => 'Delivery Cancelled',
        ];

        foreach (array_slice($deliveries, 0, 25) as $delivery) {
            $deliveryId = (int)($delivery->id ?? 0);
            $orderId = (int)($delivery->order_id ?? 0);
            $status = strtolower(trim((string)($delivery->status ?? 'accepted')));
            $statusText = $this->formatStatusText($status);
            $payment = number_format((float)($delivery->shipping_fee ?? 0), 2);

            if ($deliveryId <= 0 && $orderId <= 0) {
                continue;
            }

            $notifications[] = [
                'id' => 'transporter_delivery_' . ($deliveryId > 0 ? $deliveryId : $orderId) . '_' . $status,
                'category' => 'deliveries',
                'icon' => 'deliveries',
                'title' => $titleMap[$status] ?? 'Delivery Update',
                'message' => 'Order #' . ($orderId > 0 ? $orderId : '-') . ' | ' . $statusText . ' | Payment: Rs. ' . $payment,
                'related_id' => $deliveryId > 0 ? $deliveryId : $orderId,
                'created_at' => $delivery->updated_at ?? $delivery->created_at ?? date('Y-m-d H:i:s'),
                'link' => 'transporterdashboard?section=mydeliveries',
            ];
        }

        return $notifications;
    }

    private function getReviewNotifications($transporterId)
    {
        $reviewModel = new ReviewModel();
        $reviews = $this->asArray($reviewModel->getReviewsByTransporter($transporterId));
        $notifications = [];

        foreach (array_slice($reviews, 0, 20) as $review) {
            $reviewId = (int)($review->id ?? 0);
            if ($reviewId <= 0) {
                continue;
            }

            $rating = (int)($review->rating ?? 0);
            $buyer = trim((string)($review->buyer_name ?? 'Buyer'));
            $product = trim((string)($review->product_name ?? 'order item'));

            $notifications[] = [
                'id' => 'transporter_review_' . $reviewId,
                'category' => 'reviews',
                'icon' => 'reviews',
                'title' => 'New Feedback Received',
                'message' => $buyer . ' gave ' . $rating . '-star feedback for ' . $product,
                'related_id' => $reviewId,
                'created_at' => $review->created_at ?? date('Y-m-d H:i:s'),
                'link' => 'transporterdashboard?section=feedback',
            ];
        }

        return $notifications;
    }

    private function getSystemNotifications($transporterId)
    {
        $notifications = [];

        $transporterModel = new TransporterModel();
        $vehicleModel = new VehicleModel();
        $payoutModel = new PayoutAccountsModel();

        $profile = $transporterModel->getProfileByUserId($transporterId);
        $profileObj = is_object($profile) ? $profile : null;

        $missingFields = [];
        if (trim((string)($profileObj->phone ?? '')) === '') $missingFields[] = 'phone number';
        if (trim((string)($profileObj->district ?? '')) === '') $missingFields[] = 'district';

        if (!empty($missingFields)) {
            $notifications[] = [
                'id' => 'transporter_system_profile_' . $transporterId,
                'category' => 'system',
                'icon' => 'system',
                'title' => 'Complete Profile Details',
                'message' => 'Please add ' . implode(', ', array_slice($missingFields, 0, 3)) . ' to keep deliveries running smoothly.',
                'created_at' => date('Y-m-d H:i:s'),
                'link' => 'transporterprofile',
            ];
        }

        $vehicles = $this->asArray($vehicleModel->getByUserId($transporterId));
        if (empty($vehicles)) {
            $notifications[] = [
                'id' => 'transporter_system_vehicles_' . $transporterId,
                'category' => 'system',
                'icon' => 'system',
                'title' => 'Add Your First Vehicle',
                'message' => 'Add at least one vehicle to start accepting delivery requests.',
                'created_at' => date('Y-m-d H:i:s'),
                'link' => 'transporterdashboard?section=vehicle',
            ];
        }

        $payoutAccount = $payoutModel->getDefaultAccountByUserId($transporterId);
        if (!$payoutAccount) {
            $notifications[] = [
                'id' => 'transporter_system_payout_' . $transporterId,
                'category' => 'system',
                'icon' => 'system',
                'title' => 'Add Payout Account',
                'message' => 'Set your payout account details to receive delivery earnings without delays.',
                'created_at' => date('Y-m-d H:i:s'),
                'link' => 'transporterprofile',
            ];
        }

        return $notifications;
    }
}
