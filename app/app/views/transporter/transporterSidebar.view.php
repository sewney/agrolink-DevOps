<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Transporter Dashboard' ?> - AgroLink</title>
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
            <link rel="stylesheet" href="<?= ROOT ?>/assets/css/transporter/<?= htmlspecialchars($styleFile) ?>">
    <?php
        }
    }
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body data-app-root="<?= ROOT ?>" data-user-name="<?= htmlspecialchars(authUserName()) ?>" data-user-email="<?= htmlspecialchars(authUserEmail()) ?>" data-user-role="<?= htmlspecialchars(authUserRole()) ?>">
    <?php
    $userId = authUserId();
    $role = authUserRole() !== '' ? authUserRole() : 'transporter';

    $notificationUnreadCount = isset($notificationUnreadCount) ? (int)$notificationUnreadCount : null;
    if ($notificationUnreadCount === null && $userId > 0 && $role === 'transporter') {
        try {
            $notificationsModel = new TransporterNotificationsModel();
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
                <li>
                    <a href="<?= ROOT ?>/transporterdashboard" class="menu-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>" data-section="dashboard">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= ROOT ?>/transportervehicles" class="menu-link <?= ($activePage ?? '') === 'vehicles' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8h-1V6c0-1.1-.9-2-2-2h-2c-1.1 0-2 .9-2 2v2H8V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v2H1v2h2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V10h2V8zM4 6h2v2H4V6zm10 0h2v2h-2V6zM4 18v-6h14v6H4z"></path>
                            </svg>
                        </div>
                        Vehicles
                    </a>
                </li>
                <li>
                    <a href="<?= ROOT ?>/transporterrequests" class="menu-link <?= ($activePage ?? '') === 'requests' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                        </div>
                        Requests
                    </a>
                </li>
                <li>
                    <a href="<?= ROOT ?>/transporterdeliveries" class="menu-link <?= ($activePage ?? '') === 'deliveries' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        Deliveries
                    </a>
                </li>
                <li>
                    <a href="<?= ROOT ?>/transporterearnings" class="menu-link <?= ($activePage ?? '') === 'earnings' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="1"></circle>
                                <path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m2.12 2.12l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m-2.12-2.12l-4.24-4.24M19.78 19.78l-4.24-4.24m-2.12-2.12l-4.24-4.24"></path>
                            </svg>
                        </div>
                        Earnings
                    </a>
                </li>
                <li>
                    <a href="<?= ROOT ?>/transporterreviews" class="menu-link <?= ($activePage ?? '') === 'reviews' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </div>
                        Reviews
                    </a>
                </li>
                <li>
                    <a href="<?= ROOT ?>/transporternotifications" class="menu-link <?= ($activePage ?? '') === 'notifications' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </div>
                        <span class="menu-label-with-badge">
                            <span>Notifications</span>
                            <span id="transporterNotificationBadge" class="notification-sidebar-badge <?= $notificationUnreadCount > 0 ? '' : 'is-hidden' ?>"><?= $notificationUnreadCount ?></span>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="<?= ROOT ?>/transporterprofile" class="menu-link <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        Profile
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <?php
            if (isset($contentView)) {
                include $contentView;
            }
            ?>
        </main>
    </div>

    <script>
        (function() {
            const seededRoot = "<?= ROOT ?>";
            const origin = String(window.location.origin || '').replace(/\/+$/, '');
            const rawRoot = String(seededRoot || '').replace(/\/+$/, '');
            const path = String(window.location.pathname || '');
            const pathPublicMatch = path.match(/^(.*\/public)(?:\/|$)/i);

            let resolvedRoot = rawRoot;

            if (pathPublicMatch && pathPublicMatch[1]) {
                resolvedRoot = origin + pathPublicMatch[1];
            }

            if (!resolvedRoot || resolvedRoot === origin) {
                const match = pathPublicMatch;
                if (match && match[1]) {
                    resolvedRoot = origin + match[1];
                } else {
                    resolvedRoot = rawRoot || origin;
                }
            }

            window.APP_ROOT = resolvedRoot;
            window.USER_NAME = <?= json_encode(authUserName()) ?>;
            window.USER_EMAIL = <?= json_encode(authUserEmail()) ?>;
            console.log('APP_ROOT set to:', window.APP_ROOT);
        })();
    </script>
    <script src="<?= ROOT ?>/assets/js/main.js"></script>
    <?php
    $resolvedPageScripts = [];
    if (isset($pageScript) && trim((string)$pageScript) !== '') {
        $resolvedPageScripts[] = trim((string)$pageScript);
    }

    $extraPageScripts = $pageScripts ?? [];
    if (is_string($extraPageScripts) && trim($extraPageScripts) !== '') {
        $extraPageScripts = [trim($extraPageScripts)];
    }

    if (is_array($extraPageScripts)) {
        foreach ($extraPageScripts as $scriptFile) {
            $scriptFile = trim((string)$scriptFile);
            if ($scriptFile === '') {
                continue;
            }
            $resolvedPageScripts[] = $scriptFile;
        }
    }

    $resolvedPageScripts = array_values(array_unique($resolvedPageScripts));
    foreach ($resolvedPageScripts as $scriptFile):
    ?>
        <script src="<?= ROOT ?>/assets/js/transporter/<?= htmlspecialchars($scriptFile) ?>"></script>
    <?php endforeach; ?>
    <script src="<?= ROOT ?>/assets/js/topnavbar.js"></script>
</body>

</html>