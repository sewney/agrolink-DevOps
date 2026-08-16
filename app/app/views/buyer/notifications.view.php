<?php
$notifications = is_array($notifications ?? null) ? $notifications : [];
$notificationSettings = is_array($notificationSettings ?? null) ? $notificationSettings : [];
?>

<div class="content-section notifications-page buyer-notifications-page">
    <div class="content-header notifications-header">
        <div>
            <h1 class="content-title">Notifications</h1>
            <p class="content-subtitle">Updates on products, orders, tracking, and replies</p>
        </div>
        <div class="notifications-header-actions">
            <button type="button" id="markAllBuyerNotificationsReadBtn" class="btn btn-secondary">Mark All as Read</button>
            <button type="button" id="openBuyerNotificationSettingsBtn" class="btn btn-primary">Notification Settings</button>
        </div>
    </div>

    <div class="content-card notification-filter-card">
        <div class="notification-filters-bar">
            <div class="notification-filters" id="buyerNotificationFilters">
                <button type="button" class="notification-filter-tab is-active" data-filter="all">All</button>
                <button type="button" class="notification-filter-tab" data-filter="unread">Unread</button>
                <button type="button" class="notification-filter-tab" data-filter="new_products">New Products</button>
                <button type="button" class="notification-filter-tab" data-filter="tracking">Tracking</button>
                <button type="button" class="notification-filter-tab" data-filter="review_replies">Review Replies</button>
                <button type="button" class="notification-filter-tab" data-filter="request_replies">Request Replies</button>
                <button type="button" class="notification-filter-tab" data-filter="order_updates">Order Updates</button>
                <button type="button" class="notification-filter-tab" data-filter="system">System</button>
            </div>
            <button type="button" class="btn btn-outline btn-sm notification-filter-clear" id="clearBuyerNotificationFilterBtn">Clear</button>
        </div>
    </div>

    <div class="content-card notification-list-card">
        <div id="buyerNotificationsList" class="notifications-list"></div>
    </div>
</div>

<div id="buyerNotificationSettingsModal" class="modal profile-modal">
    <div class="modal-content profile-modal-content notification-settings-modal-content">
        <div class="modal-header profile-modal-header">
            <h3>Notification Settings</h3>
            <button type="button" class="modal-close" data-close-modal="buyerNotificationSettingsModal" aria-label="Close">×</button>
        </div>
        <div class="modal-body">
            <form id="buyerNotificationSettingsForm" class="notification-settings-form">
                <label class="notification-setting-row">
                    <span>New Products</span>
                    <input type="checkbox" name="new_products" <?= !empty($notificationSettings['new_products']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>Tracking Updates</span>
                    <input type="checkbox" name="tracking" <?= !empty($notificationSettings['tracking']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>Review Replies</span>
                    <input type="checkbox" name="review_replies" <?= !empty($notificationSettings['review_replies']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>Request Replies</span>
                    <input type="checkbox" name="request_replies" <?= !empty($notificationSettings['request_replies']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>Order Updates</span>
                    <input type="checkbox" name="order_updates" <?= !empty($notificationSettings['order_updates']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>System</span>
                    <input type="checkbox" name="system" <?= !empty($notificationSettings['system']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>Email Notifications</span>
                    <input type="checkbox" name="email_notifications" <?= !empty($notificationSettings['email_notifications']) ? 'checked' : '' ?>>
                </label>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-close-modal="buyerNotificationSettingsModal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script id="buyerNotificationsSeed" type="application/json">
    <?= json_encode([
        'notifications' => $notifications,
        'settings' => $notificationSettings,
        'unreadCount' => (int)($notificationUnreadCount ?? 0),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>