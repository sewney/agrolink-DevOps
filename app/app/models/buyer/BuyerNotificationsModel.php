<?php

class BuyerNotificationsModel
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
            'new_products' => true,
            'tracking' => true,
            'review_replies' => true,
            'request_replies' => true,
            'order_updates' => true,
            'system' => true,
            'email_notifications' => true,
        ];
    }

    public function getSettings($buyerId)
    {
        return $this->settingsStore->getSettings((int)$buyerId, $this->getDefaultSettings());
    }

    public function saveSettings($buyerId, $settings)
    {
        return $this->settingsStore->saveSettings((int)$buyerId, (array)$settings, $this->getDefaultSettings());
    }

    private function getManagedTypes()
    {
        return [
            'new_products',
            'tracking',
            'review_replies',
            'request_replies',
            'order_updates',
            'system',
        ];
    }

    public function getNotifications($buyerId, $filter = 'all')
    {
        $buyerId = (int)$buyerId;
        if ($buyerId <= 0) {
            return [];
        }

        $this->store->syncNotifications($buyerId, $this->buildAllNotifications($buyerId), $this->getManagedTypes());
        $settings = $this->getSettings($buyerId);
        $allowedTypes = $this->getEnabledTypes($settings);
        if (empty($allowedTypes)) {
            return [];
        }

        return $this->store->listNotifications($buyerId, $filter, $allowedTypes);
    }

    public function getUnreadCount($buyerId)
    {
        $buyerId = (int)$buyerId;
        if ($buyerId <= 0) {
            return 0;
        }

        $this->store->syncNotifications($buyerId, $this->buildAllNotifications($buyerId), $this->getManagedTypes());

        $settings = $this->getSettings($buyerId);
        $allowedTypes = $this->getEnabledTypes($settings);
        if (empty($allowedTypes)) {
            return 0;
        }

        return $this->store->getUnreadCount($buyerId, $allowedTypes);
    }

    public function markAllAsRead($buyerId)
    {
        $buyerId = (int)$buyerId;
        if ($buyerId <= 0) {
            return false;
        }

        return $this->store->markAllAsRead($buyerId);
    }

    public function markAsRead($buyerId, $notificationId)
    {
        $buyerId = (int)$buyerId;
        $notificationId = (int)$notificationId;

        if ($buyerId <= 0 || $notificationId <= 0) {
            return false;
        }

        return $this->store->markAsRead($buyerId, $notificationId);
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

    private function buildAllNotifications($buyerId)
    {
        $items = [];
        $orderModel = new OrderModel();
        $orders = $this->asArray($orderModel->getOrdersByBuyer($buyerId));

        $items = array_merge($items, $this->getNewProductNotifications());
        $items = array_merge($items, $this->getTrackingNotifications($buyerId));
        $items = array_merge($items, $this->getReviewReplyNotifications($buyerId));
        $items = array_merge($items, $this->getRequestReplyNotifications($buyerId));
        $items = array_merge($items, $this->getOrderUpdateNotifications($orders));
        $items = array_merge($items, $this->getSystemNotifications($buyerId, $orders, $orderModel));

        return $items;
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

    private function resolveTime(array $candidates)
    {
        foreach ($candidates as $candidate) {
            if (!empty($candidate) && strtotime((string)$candidate)) {
                return date('Y-m-d H:i:s', strtotime((string)$candidate));
            }
        }

        return date('Y-m-d H:i:s');
    }

    private function getNewProductNotifications()
    {
        $productModel = new ProductsModel();
        $products = $this->asArray($productModel->getWithFarmerDetails());
        $notifications = [];

        foreach (array_slice($products, 0, 20) as $product) {
            $productId = (int)($product->id ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $productName = trim((string)($product->name ?? 'Product'));
            $farmerName = trim((string)($product->farmer_name ?? 'Farmer'));
            $price = number_format((float)($product->price ?? 0), 2);
            $createdAt = $this->resolveTime([
                $product->created_at ?? null,
                $product->listing_date ?? null,
            ]);

            $notifications[] = [
                'id' => 'new_product_' . $productId,
                'category' => 'new_products',
                'icon' => 'new_products',
                'title' => 'New Product Listed',
                'message' => $productName . ' by ' . $farmerName . ' | Rs. ' . $price . '/kg',
                'related_id' => $productId,
                'created_at' => $createdAt,
                'link' => 'buyerproducts',
            ];
        }

        return $notifications;
    }

    private function getTrackingNotifications($buyerId)
    {
        $orderModel = new OrderModel();
        $trackingRows = $this->asArray($orderModel->getDeliveryTrackingByBuyer($buyerId));
        $notifications = [];

        foreach (array_slice($trackingRows, 0, 20) as $row) {
            $orderId = (int)($row->order_id ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $deliveryStatus = strtolower(trim((string)($row->delivery_status ?? '')));
            $effectiveStatus = $deliveryStatus !== ''
                ? $deliveryStatus
                : strtolower(trim((string)($row->order_status ?? 'pending')));

            $statusText = $this->formatStatusText($effectiveStatus);
            $transporter = trim((string)($row->transporter_name ?? 'Pending Assignment'));
            $createdAt = $this->resolveTime([
                $row->delivery_updated_at ?? null,
                $row->delivery_created_at ?? null,
                $row->order_created_at ?? null,
            ]);

            $notifications[] = [
                'id' => 'tracking_' . $orderId . '_' . $effectiveStatus,
                'category' => 'tracking',
                'icon' => 'tracking',
                'title' => 'Delivery Tracking Update',
                'message' => 'Order #ORD-' . $orderId . ' | ' . $statusText . ' | Transporter: ' . $transporter,
                'related_id' => $orderId,
                'created_at' => $createdAt,
                'link' => 'buyertracking?order_id=' . $orderId,
            ];
        }

        return $notifications;
    }

    private function getReviewReplyNotifications($buyerId)
    {
        $reviewModel = new ReviewModel();
        $reviews = $this->asArray($reviewModel->getReviewsByBuyer($buyerId));
        $notifications = [];

        foreach ($reviews as $review) {
            $reply = trim((string)($review->reply ?? ''));
            if ($reply === '') {
                continue;
            }

            $reviewId = (int)($review->id ?? 0);
            if ($reviewId <= 0) {
                continue;
            }

            $targetRole = strtolower(trim((string)($review->target_role ?? 'farmer')));
            $actorLabel = $targetRole === 'transporter' ? 'Transporter' : 'Farmer';
            $actorName = trim((string)($review->target_name ?? $review->farmer_name ?? $actorLabel));
            $itemName = trim((string)($review->order_item_name ?? $review->product_name ?? 'your item'));
            $createdAt = $this->resolveTime([
                $review->replied_at ?? null,
                $review->updated_at ?? null,
                $review->created_at ?? null,
            ]);

            $notifications[] = [
                'id' => 'review_reply_' . $reviewId,
                'category' => 'review_replies',
                'icon' => 'review_replies',
                'title' => 'Review Reply Received',
                'message' => $actorLabel . ' ' . $actorName . ' replied to your review for ' . $itemName,
                'related_id' => $reviewId,
                'created_at' => $createdAt,
                'link' => 'buyerreviews',
            ];
        }

        usort($notifications, function ($a, $b) {
            $timeA = strtotime($a['created_at'] ?? '') ?: 0;
            $timeB = strtotime($b['created_at'] ?? '') ?: 0;
            return $timeB <=> $timeA;
        });

        return array_slice($notifications, 0, 20);
    }

    private function getRequestReplyNotifications($buyerId)
    {
        $cropRequestModel = new CropRequestModel();
        $requests = $this->asArray($cropRequestModel->getRequestsByBuyer($buyerId));
        $notifications = [];

        $statusTitleMap = [
            'accepted' => 'Request Accepted',
            'declined' => 'Request Declined',
            'completed' => 'Request Completed',
        ];

        foreach ($requests as $request) {
            $requestId = (int)($request->id ?? 0);
            if ($requestId <= 0) {
                continue;
            }

            $status = strtolower(trim((string)($request->status ?? 'active')));
            if (!isset($statusTitleMap[$status])) {
                continue;
            }

            $cropName = trim((string)($request->crop_name ?? 'Crop'));
            $quantity = rtrim(rtrim(number_format((float)($request->quantity ?? 0), 2), '0'), '.');
            if ($quantity === '') {
                $quantity = '0';
            }

            $createdAt = $this->resolveTime([
                $request->updated_at ?? null,
                $request->created_at ?? null,
            ]);

            $notifications[] = [
                'id' => 'request_reply_' . $requestId . '_' . $status,
                'category' => 'request_replies',
                'icon' => 'request_replies',
                'title' => $statusTitleMap[$status],
                'message' => $cropName . ' | ' . $quantity . ' kg | ' . $this->formatStatusText($status),
                'related_id' => $requestId,
                'created_at' => $createdAt,
                'link' => 'croprequest/show/' . $requestId,
            ];
        }

        usort($notifications, function ($a, $b) {
            $timeA = strtotime($a['created_at'] ?? '') ?: 0;
            $timeB = strtotime($b['created_at'] ?? '') ?: 0;
            return $timeB <=> $timeA;
        });

        return array_slice($notifications, 0, 20);
    }

    private function getOrderUpdateNotifications(array $orders)
    {
        $notifications = [];

        $statusTitleMap = [
            'pending_payment' => 'Payment Pending',
            'processing' => 'Order Processing',
            'ready_for_pickup' => 'Ready for Pickup',
            'shipped' => 'Order Shipped',
            'delivered' => 'Order Delivered',
            'cancelled' => 'Order Cancelled',
        ];

        foreach (array_slice($orders, 0, 20) as $order) {
            $orderId = (int)($order->id ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $status = strtolower(trim((string)($order->status ?? 'pending')));
            $statusText = $this->formatStatusText($status);
            $total = number_format((float)($order->order_total ?? 0), 2);
            $createdAt = $this->resolveTime([
                $order->updated_at ?? null,
                $order->created_at ?? null,
            ]);

            $notifications[] = [
                'id' => 'order_update_' . $orderId . '_' . $status,
                'category' => 'order_updates',
                'icon' => 'order_updates',
                'title' => $statusTitleMap[$status] ?? 'Order Status Updated',
                'message' => 'Order #ORD-' . $orderId . ' | ' . $statusText . ' | Total: Rs. ' . $total,
                'related_id' => $orderId,
                'created_at' => $createdAt,
                'link' => 'buyerorders',
            ];
        }

        return $notifications;
    }

    private function getSystemNotifications($buyerId, array $orders, OrderModel $orderModel)
    {
        $notifications = [];

        $buyerModel = new BuyerModel();
        $profile = $buyerModel->getProfileByUserId($buyerId);
        $profileObj = is_object($profile) ? $profile : null;

        $missingFields = [];
        $requiredFields = [
            'phone' => 'phone',
            'street_name' => 'street address',
            'city' => 'city',
            'district' => 'district',
        ];

        foreach ($requiredFields as $field => $label) {
            $value = trim((string)($profileObj->$field ?? ''));
            if ($value === '') {
                $missingFields[] = $label;
            }
        }

        if (!empty($missingFields)) {
            $missingPreview = implode(', ', array_slice($missingFields, 0, 2));
            if (count($missingFields) > 2) {
                $missingPreview .= ', ...';
            }

            $notifications[] = [
                'id' => 'system_profile_incomplete_' . $buyerId,
                'category' => 'system',
                'icon' => 'system',
                'title' => 'Complete Delivery Profile',
                'message' => 'Please add ' . $missingPreview . ' in your profile to avoid checkout and delivery issues.',
                'created_at' => $this->resolveTime([
                    $profileObj->updated_at ?? null,
                    $profileObj->created_at ?? null,
                ]),
                'link' => 'buyerprofile',
            ];
        }

        $reviewableItems = $this->asArray($orderModel->getReviewableItemsByBuyer($buyerId));
        $reviewableTransporters = $this->asArray($orderModel->getReviewableTransportersByBuyer($buyerId));
        $pendingReviewCount = count($reviewableItems) + count($reviewableTransporters);

        if ($pendingReviewCount > 0) {
            $createdAt = null;
            if (!empty($reviewableItems[0]->order_created_at)) {
                $createdAt = $reviewableItems[0]->order_created_at;
            } elseif (!empty($reviewableTransporters[0]->order_created_at)) {
                $createdAt = $reviewableTransporters[0]->order_created_at;
            }

            $notifications[] = [
                'id' => 'system_pending_feedback_' . $buyerId,
                'category' => 'system',
                'icon' => 'system',
                'title' => 'Pending Feedback Reminder',
                'message' => 'You have ' . $pendingReviewCount . ' pending review item(s). Share feedback to help improve marketplace quality.',
                'created_at' => $this->resolveTime([$createdAt]),
                'link' => 'buyerreviews',
            ];
        }

        if (empty($orders)) {
            $notifications[] = [
                'id' => 'system_no_orders_' . $buyerId,
                'category' => 'system',
                'icon' => 'system',
                'title' => 'No Orders Yet',
                'message' => 'Place your first order to start receiving delivery and order update notifications.',
                'created_at' => date('Y-m-d H:i:s'),
                'link' => 'buyerproducts',
            ];
        }

        return $notifications;
    }
}
