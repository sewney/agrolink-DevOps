<?php
$isHomePage = isset($isHomePage) ? (bool)$isHomePage : false;
$isAuthenticated = isLoggedIn();
$userName = $isAuthenticated ? authUserName() : '';
$userRole = $isAuthenticated ? authUserRole() : '';
$userInitials = $isAuthenticated ? authUserInitials() : 'U';
$dashboardPath = authDashboardPath();
$profilePath = authProfilePath();
?>

<?php if ($isHomePage): ?>
    <header class="header">
        <nav class="nav container">
            <div class="logo">
                <img src="<?= ROOT ?>/assets/imgs/Logo 2.svg" alt="AgroLink" style="height: 60px;">
            </div>

            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </div>

            <?php if ($isAuthenticated): ?>
                <div class="user-section nav-user-section">
                    <button type="button" class="user-info nav-user-toggle" onclick="toggleUserDropdown(this)" aria-expanded="false" aria-label="Open user menu">
                        <div class="user-avatar" id="userAvatar"><?= esc($userInitials) ?></div>
                        <div class="user-details">
                            <div class="user-name" style="text-transform: capitalize;"><?= esc($userName !== '' ? $userName : 'User') ?></div>
                            <div class="user-role"><?= esc(ucfirst($userRole !== '' ? $userRole : 'User')) ?></div>
                        </div>
                        <span class="dropdown-caret" aria-hidden="true"></span>
                    </button>

                    <div id="userDropdown" class="user-dropdown">
                        <a href="<?= ROOT ?>/<?= $dashboardPath ?>" class="dropdown-item">Dashboard</a>
                        <?php if ($profilePath !== $dashboardPath): ?>
                            <a href="<?= ROOT ?>/<?= $profilePath ?>" class="dropdown-item">Profile</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="<?= ROOT ?>/logout" class="dropdown-logout-form">
                            <button type="submit" class="dropdown-item logout-item dropdown-logout-btn">Logout</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="nav-actions">
                    <a href="<?= ROOT ?>/login" class="btn btn-secondary login-link">Login</a>
                    <a href="<?= ROOT ?>/register" class="btn btn-primary">Register</a>
                </div>
            <?php endif; ?>
        </nav>
    </header>
<?php else: ?>
    <nav class="top-navbar">
        <div class="logo-section">
            <img src="<?= ROOT ?>/assets/imgs/Logo.png" alt="AgroLink">
        </div>

        <?php if ($isAuthenticated): ?>
            <div class="user-section">
                <button type="button" class="user-info nav-user-toggle" onclick="toggleUserDropdown(this)" aria-expanded="false" aria-label="Open user menu">
                    <div class="user-avatar" id="userAvatar"><?= esc($userInitials) ?></div>
                    <div class="user-details">
                        <div class="user-name" id="adminName" style="text-transform: capitalize;"><?= esc(ucwords($userName !== '' ? $userName : 'User')) ?></div>
                        <div class="user-role"><?= esc(ucfirst($userRole !== '' ? $userRole : 'User')) ?></div>
                    </div>
                    <span class="dropdown-caret" aria-hidden="true"></span>
                </button>

                <div id="userDropdown" class="user-dropdown">
                    <a href="<?= ROOT ?>/<?= $dashboardPath ?>" class="dropdown-item">Dashboard</a>
                    <?php if ($profilePath !== $dashboardPath): ?>
                        <a href="<?= ROOT ?>/<?= $profilePath ?>" class="dropdown-item">Profile</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="<?= ROOT ?>/logout" class="dropdown-logout-form">
                        <button type="submit" class="dropdown-item logout-item dropdown-logout-btn">Logout</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="nav-actions">
                <a href="<?= ROOT ?>/login" class="btn btn-secondary login-link">Login</a>
                <a href="<?= ROOT ?>/register" class="btn btn-primary">Register</a>
            </div>
        <?php endif; ?>
    </nav>
<?php endif; ?>