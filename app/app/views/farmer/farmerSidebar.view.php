<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - AgroLink</title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/shared/notifications.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/shared/profile.css">
    <?php
    $resolvedPageStyles = $pageStyles ?? [];
    if (is_string($resolvedPageStyles) && $resolvedPageStyles !== '') {
        $resolvedPageStyles = [$resolvedPageStyles];
    }
    if (is_array($resolvedPageStyles)) {
        foreach ($resolvedPageStyles as $styleFile) {
            $styleFile = trim((string)$styleFile);
            if ($styleFile === '') {
                continue;
            }
    ?>
            <link rel="stylesheet" href="<?= ROOT ?>/assets/css/farmer/<?= htmlspecialchars($styleFile) ?>">
    <?php
        }
    }
    ?>
</head>

<body data-app-root="<?= ROOT ?>" data-user-name="<?= htmlspecialchars(authUserName()) ?>" data-user-email="<?= htmlspecialchars(authUserEmail()) ?>">
    <?php
    $userId = authUserId();
    $notificationUnreadCount = isset($notificationUnreadCount) ? (int)$notificationUnreadCount : null;
    if ($notificationUnreadCount === null && $userId > 0) {
        try {
            $notificationsModel = new FarmerNotificationsModel();
            $notificationUnreadCount = $notificationsModel->getUnreadCount($userId);
        } catch (Throwable $e) {
            $notificationUnreadCount = 0;
        }
    }
    if ($notificationUnreadCount === null) {
        $notificationUnreadCount = 0;
    }
    $isHomePage = false;
    include '../app/views/shared/topnavbar.view.php';
    ?>

    <div class="dashboard">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="<?= ROOT ?>/farmerdashboard" class="menu-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        Dashboard
                    </a></li>
                <li><a href="<?= ROOT ?>/farmerproducts" class="menu-link <?= ($activePage ?? '') === 'products' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </div>
                        Products
                    </a></li>
                <li><a href="<?= ROOT ?>/farmerorders" class="menu-link <?= ($activePage ?? '') === 'orders' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1" />
                            </svg>
                        </div>
                        Orders
                    </a></li>
                <li><a href="<?= ROOT ?>/farmerearnings" class="menu-link <?= ($activePage ?? '') === 'earnings' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="8" />
                                <line x1="12" y1="8" x2="12" y2="16" />
                                <line x1="8" y1="12" x2="16" y2="12" />
                            </svg>
                        </div>
                        Earnings
                    </a></li>
                <li><a href="<?= ROOT ?>/farmerdeliveries" class="menu-link <?= ($activePage ?? '') === 'deliveries' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7h13v10H3z" />
                                <path d="M16 10h4l3 3v4h-7z" />
                                <circle cx="7.5" cy="17.5" r="1.5" />
                                <circle cx="18.5" cy="17.5" r="1.5" />
                            </svg>
                        </div>
                        Deliveries
                    </a></li>
                <li><a href="<?= ROOT ?>/farmerreviews" class="menu-link <?= ($activePage ?? '') === 'reviews' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z" />
                                <line x1="9" y1="10" x2="15" y2="10" />
                                <line x1="9" y1="14" x2="13" y2="14" />
                            </svg>
                        </div>
                        Reviews
                    </a></li>
                <li><a href="<?= ROOT ?>/farmercroprequests" class="menu-link <?= ($activePage ?? '') === 'crop-requests' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="12" y1="18" x2="12" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        Crop Requests
                    </a></li>
                <li><a href="<?= ROOT ?>/farmernotifications" class="menu-link <?= ($activePage ?? '') === 'notifications' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
                                <path d="M9 17a3 3 0 0 0 6 0"></path>
                            </svg>
                        </div>
                        <span class="menu-label-with-badge">
                            <span>Notifications</span>
                            <span id="farmerNotificationBadge" class="notification-sidebar-badge <?= $notificationUnreadCount > 0 ? '' : 'is-hidden' ?>"><?= $notificationUnreadCount ?></span>
                        </span>
                    </a></li>
                <li><a href="<?= ROOT ?>/farmerprofile" class="menu-link <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                        Profile
                    </a></li>
            </ul>
        </aside>

        <main class="main-content">
            <?php
            if (isset($contentView)) {
                $projectRoot = dirname(dirname(dirname(__DIR__)));
                $cleanPath = str_replace('../', '', $contentView);
                $viewPath = $projectRoot . DIRECTORY_SEPARATOR . $cleanPath;
                $viewPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $viewPath);

                if (file_exists($viewPath)) {
                    include $viewPath;
                } else {
                    echo "<div style=\"padding:20px;color:#c00;\">View not found: " . htmlspecialchars($contentView) . " (tried: " . htmlspecialchars($viewPath) . ")</div>";
                }
            }
            ?>
        </main>
    </div>

    <script src="<?= ROOT ?>/assets/js/main.js"></script>
    <script src="<?= ROOT ?>/assets/js/topnavbar.js"></script>
    <?php if (isset($pageScript)): ?>
        <script src="<?= ROOT ?>/assets/js/farmer/<?= $pageScript ?>"></script>
    <?php endif; ?>
</body>

</html>