<?php

class FarmerNotificationsModel
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
            'orders' => true,
            'crop_requests' => true,
            'deliveries' => true,
            'reviews' => true,
            'system' => true,
            'email_notifications' => true,
        ];
    }

    public function getSettings($farmerId)
    {
        return $this->settingsStore->getSettings((int)$farmerId, $this->getDefaultSettings());
    }

    public function saveSettings($farmerId, $settings)
    {
        return $this->settingsStore->saveSettings((int)$farmerId, (array)$settings, $this->getDefaultSettings());
    }

    private function getManagedTypes()
    {
        return [
            'orders',
            'crop_requests',
            'deliveries',
            'reviews',
            'system',
        ];
    }

    public function getNotifications($farmerId, $filter = 'all')
    {
        $farmerId = (int)$farmerId;
        if ($farmerId <= 0) {
            return [];
        }

        $this->store->syncNotifications($farmerId, $this->buildAllNotifications($farmerId), $this->getManagedTypes());

        $settings = $this->getSettings($farmerId);
        $allowedTypes = $this->getEnabledTypes($settings);
        if (empty($allowedTypes)) {
            return [];
        }

        return $this->store->listNotifications($farmerId, $filter, $allowedTypes);
    }

    public function getUnreadCount($farmerId)
    {
        $farmerId = (int)$farmerId;
        if ($farmerId <= 0) {
            return 0;
        }

        $this->store->syncNotifications($farmerId, $this->buildAllNotifications($farmerId), $this->getManagedTypes());

        $settings = $this->getSettings($farmerId);
        $allowedTypes = $this->getEnabledTypes($settings);
        if (empty($allowedTypes)) {
            return 0;
        }

        return $this->store->getUnreadCount($farmerId, $allowedTypes);
    }

    public function markAllAsRead($farmerId)
    {
        $farmerId = (int)$farmerId;
        if ($farmerId <= 0) {
            return false;
        }

        // Ensure generated notifications are persisted before bulk mark-as-read.
        $this->store->syncNotifications($farmerId, $this->buildAllNotifications($farmerId), $this->getManagedTypes());

        return $this->store->markAllAsRead($farmerId);
    }

    public function markAsRead($farmerId, $notificationId)
    {
        $farmerId = (int)$farmerId;
        $notificationId = (int)$notificationId;

        if ($farmerId <= 0 || $notificationId <= 0) {
            return false;
        }

        return $this->store->markAsRead($farmerId, $notificationId);
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

    private function resolveTime(array $candidates)
    {
        foreach ($candidates as $candidate) {
            if (!empty($candidate) && strtotime((string)$candidate)) {
                return date('Y-m-d H:i:s', strtotime((string)$candidate));
            }
        }

        return date('Y-m-d H:i:s');
    }

    private function formatStatusText($status)
    {
        $status = strtolower(trim((string)$status));
        if ($status === '') {
            return 'Updated';
        }

        return ucwords(str_replace('_', ' ', $status));
    }

    private function buildAllNotifications($farmerId)
    {
        $items = [];
        $items = array_merge($items, $this->getOrderNotifications($farmerId));
        $items = array_merge($items, $this->getCropRequestNotifications());
        $items = array_merge($items, $this->getDeliveryNotifications($farmerId));
        $items = array_merge($items, $this->getReviewNotifications($farmerId));
        $items = array_merge($items, $this->getSystemNotifications());

        return $items;
    }

    private function getOrderNotifications($farmerId)
    {
        $farmerModel = new FarmerModel();
        $orders = $this->asArray($farmerModel->getFarmerOrders($farmerId));
        $notifications = [];

        foreach (array_slice($orders, 0, 20) as $order) {
            $orderId = (int)($order->id ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $status = strtolower((string)($order->status ?? 'pending'));
            $statusText = $this->formatStatusText($status);
            $title = in_array($status, ['pending', 'pending_payment'], true)
                ? 'New Order Received'
                : 'Order Status Updated';
            $amount = number_format((float)($order->my_order_total ?? 0), 2);

            $notifications[] = [
                'id' => 'farmer_order_' . $orderId . '_' . $status,
                'category' => 'orders',
                'icon' => 'orders',
                'title' => $title,
                'message' => 'Order #' . $orderId . ' | ' . $statusText . ' | Your earning: Rs. ' . $amount,
                'related_id' => $orderId,
                'created_at' => $this->resolveTime([
                    $order->updated_at ?? null,
                    $order->created_at ?? null,
                ]),
                'link' => 'farmerorders',
            ];
        }

        return $notifications;
    }

    private function getCropRequestNotifications()
    {
        $cropModel = new CropRequestModel();
        $requests = $this->asArray($cropModel->findAll());
        $notifications = [];

        foreach (array_slice($requests, 0, 20) as $request) {
            $requestId = (int)($request->id ?? 0);
            if ($requestId <= 0) {
                continue;
            }

            $status = strtolower((string)($request->status ?? 'pending'));
            $statusText = $this->formatStatusText($status);
            $crop = ucfirst((string)($request->crop_name ?? 'Crop'));
            $qty = (float)($request->quantity ?? 0);
            $title = $status === 'pending' ? 'New Crop Request' : 'Crop Request Updated';

            $notifications[] = [
                'id' => 'farmer_crop_' . $requestId . '_' . $status,
                'category' => 'crop_requests',
                'icon' => 'crop_requests',
                'title' => $title,
                'message' => $crop . ' | ' . rtrim(rtrim(number_format($qty, 2), '0'), '.') . ' kg | ' . $statusText,
                'related_id' => $requestId,
                'created_at' => $this->resolveTime([
                    $request->updated_at ?? null,
                    $request->created_at ?? null,
                ]),
                'link' => 'farmercroprequests',
            ];
        }

        return $notifications;
    }

    private function getDeliveryNotifications($farmerId)
    {
        $farmerModel = new FarmerModel();
        $deliveries = $this->asArray($farmerModel->getFarmerDeliveryRequests($farmerId));
        $notifications = [];

        foreach (array_slice($deliveries, 0, 20) as $delivery) {
            $deliveryId = (int)($delivery->id ?? 0);
            $orderId = (int)($delivery->order_id ?? 0);
            if ($deliveryId <= 0 && $orderId <= 0) {
                continue;
            }

            $status = strtolower((string)($delivery->status ?? 'pending'));
            $statusText = $this->formatStatusText($status);
            $title = $status === 'accepted' ? 'Delivery Accepted' : 'Delivery Update';

            $notifications[] = [
                'id' => 'farmer_delivery_' . $deliveryId . '_' . $status,
                'category' => 'deliveries',
                'icon' => 'deliveries',
                'title' => $title,
                'message' => 'Delivery #' . $deliveryId . ' for Order #' . $orderId . ' | ' . $statusText,
                'related_id' => $deliveryId > 0 ? $deliveryId : $orderId,
                'created_at' => $this->resolveTime([
                    $delivery->updated_at ?? null,
                    $delivery->created_at ?? null,
                ]),
                'link' => 'farmerdeliveries',
            ];
        }

        return $notifications;
    }

    private function getReviewNotifications($farmerId)
    {
        $reviewModel = new ReviewModel();
        $reviews = $this->asArray($reviewModel->getReviewsByFarmer($farmerId));
        $notifications = [];

        foreach (array_slice($reviews, 0, 20) as $review) {
            $reviewId = (int)($review->id ?? 0);
            if ($reviewId <= 0) {
                continue;
            }

            $rating = (int)($review->rating ?? 0);
            $buyer = (string)($review->buyer_name ?? 'Buyer');
            $product = ucfirst((string)($review->product_name ?? 'Product'));

            $notifications[] = [
                'id' => 'farmer_review_' . $reviewId,
                'category' => 'reviews',
                'icon' => 'reviews',
                'title' => 'New Review Received',
                'message' => $buyer . ' gave ' . $rating . '-star review for ' . $product,
                'related_id' => $reviewId,
                'created_at' => $this->resolveTime([
                    $review->created_at ?? null,
                ]),
                'link' => 'farmerreviews',
            ];
        }

        return $notifications;
    }

    private function getSystemNotifications()
    {
        return [
            [
                'id' => 'farmer_system_profile',
                'category' => 'system',
                'icon' => 'system',
                'title' => 'Keep Profile Updated',
                'message' => 'Ensure profile and payout details are up to date to avoid payout delays.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'link' => 'farmerprofile',
            ],
            [
                'id' => 'farmer_system_shipping',
                'category' => 'system',
                'icon' => 'system',
                'title' => 'System Update',
                'message' => 'Delivery status and earnings views were improved for better tracking.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'link' => 'farmerdashboard',
            ],
        ];
    }
}
