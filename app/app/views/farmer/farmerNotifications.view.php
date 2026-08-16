<?php
$notifications = is_array($notifications ?? null) ? $notifications : [];
$notificationSettings = is_array($notificationSettings ?? null) ? $notificationSettings : [];
?>

<div class="content-section notifications-page farmer-notifications-page">
    <div class="content-header notifications-header">
        <div>
            <h1 class="content-title">Notifications</h1>
            <p class="content-subtitle">Stay updated with orders, deliveries, crop requests, reviews, and system updates</p>
        </div>
        <div class="notifications-header-actions">
            <button type="button" id="markAllNotificationsReadBtn" class="btn btn-secondary">Mark All as Read</button>
            <button type="button" id="openNotificationSettingsBtn" class="btn btn-primary">Notification Settings</button>
        </div>
    </div>

    <div class="content-card notification-filter-card">
        <div class="notification-filters-bar">
            <div class="notification-filters" id="notificationFilters">
                <button type="button" class="notification-filter-tab is-active" data-filter="all">All</button>
                <button type="button" class="notification-filter-tab" data-filter="unread">Unread</button>
                <button type="button" class="notification-filter-tab" data-filter="orders">Orders</button>
                <button type="button" class="notification-filter-tab" data-filter="deliveries">Deliveries</button>
                <button type="button" class="notification-filter-tab" data-filter="reviews">Reviews</button>
                <button type="button" class="notification-filter-tab" data-filter="crop_requests">Crop Requests</button>
                <button type="button" class="notification-filter-tab" data-filter="system">System</button>
            </div>
            <button type="button" class="btn btn-outline btn-sm notification-filter-clear" id="clearFarmerNotificationFilterBtn">Clear</button>
        </div>
    </div>

    <div class="content-card notification-list-card">
        <div id="notificationsList" class="notifications-list"></div>
    </div>
</div>

<div id="notificationSettingsModal" class="modal profile-modal">
    <div class="modal-content profile-modal-content notification-settings-modal-content">
        <div class="modal-header profile-modal-header">
            <h3>Notification Settings</h3>
            <button type="button" class="modal-close" data-close-modal="notificationSettingsModal" aria-label="Close">×</button>
        </div>
        <div class="modal-body">
            <form id="notificationSettingsForm" class="notification-settings-form">
                <label class="notification-setting-row">
                    <span>Orders</span>
                    <input type="checkbox" name="orders" <?= !empty($notificationSettings['orders']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>Crop Requests</span>
                    <input type="checkbox" name="crop_requests" <?= !empty($notificationSettings['crop_requests']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>Delivery Updates</span>
                    <input type="checkbox" name="deliveries" <?= !empty($notificationSettings['deliveries']) ? 'checked' : '' ?>>
                </label>
                <label class="notification-setting-row">
                    <span>Reviews &amp; Complaints</span>
                    <input type="checkbox" name="reviews" <?= !empty($notificationSettings['reviews']) ? 'checked' : '' ?>>
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
                    <button type="button" class="btn btn-secondary" data-close-modal="notificationSettingsModal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script id="farmerNotificationsSeed" type="application/json">
    <?= json_encode([
        'notifications' => $notifications,
        'settings' => $notificationSettings,
        'unreadCount' => (int)($notificationUnreadCount ?? 0),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>