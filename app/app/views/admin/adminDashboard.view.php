<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgroLink</title>
    <!-- <link rel="stylesheet" href="<?= ROOT ?>/assets/css/style2.css"> -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/admin/dashboard.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/admin/verifications.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/admin/products.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/admin/payments.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/admin/disputes.css">
</head>

<body>
    <?php
    $isHomePage = false;
    include '../app/views/shared/topnavbar.view.php';
    ?>

    <!-- Dashboard Layout -->
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3 class="sidebar-title">Admin Dashboard</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#dashboard" class="menu-link active" data-section="dashboard">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" />
                                <rect x="14" y="3" width="7" height="7" />
                                <rect x="14" y="14" width="7" height="7" />
                                <rect x="3" y="14" width="7" height="7" />
                            </svg>
                        </div>
                        Dashboard
                    </a></li>
                <li><a href="#users" class="menu-link" data-section="users">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                        Users
                    </a></li>
                <?php if (($role ?? '') === 'superadmin'): ?>
                    <li><a href="#admins" class="menu-link" data-section="admins">
                            <div class="menu-icon">
                                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="8.5" cy="7" r="4" />
                                    <path d="M20 8v6" />
                                    <path d="M23 11h-6" />
                                </svg>
                            </div>
                            Admins
                        </a></li>
                <?php endif; ?>
                <li><a href="#verifications" class="menu-link" data-section="verifications">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4" />
                                <path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z" />
                            </svg>
                        </div>
                        Verifications
                        <span id="pending-badge"
                            style="background:#e74c3c;color:#fff;font-size:10px;padding:2px 6px;border-radius:10px;margin-left:auto;display:none;">0</span>
                    </a></li>
                <li><a href="#orders" class="menu-link" data-section="orders">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1" />
                            </svg>
                        </div>
                        Orders
                    </a></li>
                <li><a href="#products" class="menu-link" data-section="products">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                            </svg>
                        </div>
                        Products
                    </a></li>
                <li><a href="#vehicles" class="menu-link" data-section="vehicles">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 17h18" />
                                <path d="M5 17l1-5h12l1 5" />
                                <path d="M7 12l2-6h6l2 6" />
                                <circle cx="7" cy="17" r="2" />
                                <circle cx="17" cy="17" r="2" />
                            </svg>
                        </div>
                        Vehicles
                    </a></li>
                <li><a href="#reviews" class="menu-link" data-section="reviews">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
                                <path d="M7 9h10" />
                                <path d="M7 13h6" />
                            </svg>
                        </div>
                        Reviews
                    </a></li>
                <li><a href="#payments" class="menu-link" data-section="payments">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2" ry="2" />
                                <line x1="2" y1="10" x2="22" y2="10" />
                            </svg>
                        </div>
                        Payments
                    </a></li>
                <li><a href="#disputes" class="menu-link" data-section="disputes">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v18" />
                                <path d="M5 12h14" />
                            </svg>
                        </div>
                        Disputes
                    </a></li>
                <li><a href="#analytics" class="menu-link" data-section="analytics">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="20" x2="12" y2="10" />
                                <line x1="18" y1="20" x2="18" y2="4" />
                                <line x1="6" y1="20" x2="6" y2="14" />
                            </svg>
                        </div>
                        Analytics
                    </a></li>
                <li><a href="#notifications" class="menu-link" data-section="notifications">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
                        </div>
                        Notifications
                    </a></li>
                <!-- <li><a href="#settings" class="menu-link" data-section="settings">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3" />
                                <path
                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .66.26 1.3.73 1.77.47.47 1.11.73 1.77.73H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                            </svg>
                        </div>
                        Settings
                    </a></li> -->
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Overview -->
            <div id="dashboard-section" class="content-section">
                <div class="content-header">
                    <h1 class="content-title">System Overview</h1>
                    <p class="content-subtitle">Welcome back! Monitor your platform's performance and activities.</p>
                </div>

                <!-- Key Metrics -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalUsers"><?= ($farmers + $buyers + $transporters + $admins) ?>
                        </div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="activeOrders"><?= ($orders) ?></div>
                        <div class="stat-label">Active Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="dashboardTotalRevenue">Rs. 0</div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="dashboardPlatformCommission">Rs. 0</div>
                        <div class="stat-label">Platform Commission</div>
                    </div>
                </div>

                <!-- User Summary Card -->
                <div class="content-card" style="margin-top: var(--spacing-xl);">
                    <div class="card-header">
                        <h3 class="card-title">User Summary</h3>
                    </div>
                    <div class="card-content">
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; padding: 20px;">
                            <div style="text-align: center; padding: 24px; background: #f5f5f5; border-radius: 12px;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 8px;"
                                    id="farmerCount"><?= (int)($farmers ?? 0) ?></div>
                                <div style="font-size: 1rem; color: #2c3e50; font-weight: 600;">Farmers</div>
                            </div>
                            <div style="text-align: center; padding: 24px; background: #f5f5f5; border-radius: 12px;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 8px;"
                                    id="buyerCount"><?= (int)($buyers ?? 0) ?></div>
                                <div style="font-size: 1rem; color: #2c3e50; font-weight: 600;">Buyers</div>
                            </div>
                            <div style="text-align: center; padding: 24px; background: #f5f5f5; border-radius: 12px;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 8px;"
                                    id="transporterCount"><?= (int)($transporters ?? 0) ?></div>
                                <div style="font-size: 1rem; color: #2c3e50; font-weight: 600;">Transporters</div>
                            </div>
                            <div style="text-align: center; padding: 24px; background: #f5f5f5; border-radius: 12px;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 8px;"
                                    id="adminCount"><?= (int)($admins ?? 0) ?></div>
                                <div style="font-size: 1rem; color: #2c3e50; font-weight: 600;">Admins</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: var(--spacing-xl);">
                    <div class="content-card">
                        <div class="card-header"
                            style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                            <div>
                                <h3 class="card-title">Recent Orders</h3>
                                <p class="content-subtitle" style="margin:0;">Latest customer purchases</p>
                            </div>
                            <a href="#orders" class="btn btn-secondary"
                                onclick="showSection('orders'); return false;">View all</a>
                        </div>
                        <div class="card-content" id="recentOrders">
                            <?php $recentOrders = $recent_orders ?? []; ?>
                            <?php if (empty($recentOrders)): ?>
                                <div style="padding: 18px; text-align: center; color: #666;">No recent orders</div>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $o): ?>
                                    <?php
                                    $orderId = (int) ($o->order_id ?? 0);
                                    $buyerName = trim((string) ($o->buyer_name ?? 'Buyer'));
                                    $farmerCount = (int) ($o->farmer_count ?? 0);
                                    $farmerNames = trim((string) ($o->farmer_names ?? ''));
                                    $farmerLabel = $farmerCount > 1 ? 'Multiple Farmers' : ($farmerNames !== '' ? $farmerNames : 'Farmer');
                                    $orderTotal = (float) ($o->order_total ?? 0);
                                    $orderStatus = strtolower(trim((string) ($o->status ?? '')));

                                    $statusClass = 'badge-secondary';
                                    if (in_array($orderStatus, ['pending', 'pending_payment'], true))
                                        $statusClass = 'badge-warning';
                                    elseif (in_array($orderStatus, ['processing', 'shipped', 'confirmed'], true))
                                        $statusClass = 'badge-info';
                                    elseif ($orderStatus === 'delivered')
                                        $statusClass = 'badge-success';
                                    elseif ($orderStatus === 'cancelled')
                                        $statusClass = 'badge-danger';
                                    ?>
                                    <div
                                        style="padding: 14px 0; border-bottom: 1px solid var(--light-gray); display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                                        <div>
                                            <div style="font-weight: var(--font-weight-bold);">#ORD-<?= $orderId ?></div>
                                            <div style="font-size: 0.9rem; color: var(--dark-gray);">
                                                <?= esc($buyerName) ?> → <?= esc($farmerLabel) ?> - Rs.
                                                <?= number_format($orderTotal, 2) ?>
                                            </div>
                                        </div>
                                        <span class="badge <?= $statusClass ?>"
                                            style="text-transform:capitalize; white-space:nowrap;">
                                            <?= esc($orderStatus !== '' ? str_replace('_', ' ', $orderStatus) : 'unknown') ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="card-header"
                            style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                            <div>
                                <h3 class="card-title">New User Registrations</h3>
                                <p class="content-subtitle" style="margin:0;">Recently joined users</p>
                            </div>
                            <a href="#users" class="btn btn-secondary"
                                onclick="showSection('users'); return false;">View all</a>
                        </div>
                        <div class="card-content" id="newRegistrations">
                            <?php $recentRegistrations = $recent_registrations ?? []; ?>
                            <?php if (empty($recentRegistrations)): ?>
                                <div style="padding: 18px; text-align: center; color: #666;">No new registrations</div>
                            <?php else: ?>
                                <?php foreach ($recentRegistrations as $u): ?>
                                    <?php
                                    $userName = trim((string) ($u->name ?? 'User'));
                                    $userRoleLabel = trim((string) ($u->role ?? 'user'));
                                    $verification = strtolower(trim((string) ($u->verification_status ?? '')));

                                    $verificationClass = 'badge-secondary';
                                    if ($verification === 'approved')
                                        $verificationClass = 'badge-success';
                                    elseif ($verification === 'not_required')
                                        $verificationClass = 'badge-info';
                                    elseif ($verification === 'pending')
                                        $verificationClass = 'badge-warning';
                                    elseif ($verification === 'rejected')
                                        $verificationClass = 'badge-danger';
                                    ?>
                                    <div
                                        style="padding: 14px 0; border-bottom: 1px solid var(--light-gray); display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                                        <div>
                                            <div style="font-weight: var(--font-weight-bold);"><?= esc($userName) ?></div>
                                            <div style="font-size: 0.9rem; color: var(--dark-gray);">
                                                <?= esc(ucfirst($userRoleLabel !== '' ? $userRoleLabel : 'user')) ?>
                                            </div>
                                        </div>
                                        <span class="badge <?= $verificationClass ?>"
                                            style="text-transform:capitalize; white-space:nowrap;">
                                            <?= esc($verification !== '' ? str_replace('_', ' ', $verification) : 'unknown') ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Management -->
            <div id="users-section" class="content-section" style="display: none;">
                <div
                    style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--spacing-lg);">
                    <h1>User Management</h1>
                    <div style="display:flex;gap:10px;">
                        <button class="btn btn-secondary" onclick="generateReport('users')">📄 Export Report</button>
                        <button class="btn btn-primary" onclick="openAddUserModal()">➕ Add User</button>
                    </div>
                </div>

                <div class="filters">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="userSearch">Search Users</label>
                            <input type="text" id="userSearch" class="form-control"
                                placeholder="Search by name, email, or ID...">
                        </div>
                        <div class="filter-group">
                            <label for="roleFilter">Role</label>
                            <select id="roleFilter" class="form-control">
                                <option value="">All Roles</option>
                                <option value="farmer">Farmers</option>
                                <option value="buyer">Buyers</option>
                                <option value="transporter">Transporters</option>
                                <option value="admin">Admins</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th data-sort="id">User ID</th>
                                <th data-sort="name">Name</th>
                                <th data-sort="email">Email</th>
                                <th data-sort="role">Role</th>
                                <th data-sort="status">Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <div class="message" id="message"></div>
                        <tbody id="usersTableBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Admin Management (Superadmin only) -->
            <?php if (($role ?? '') === 'superadmin'): ?>
                <div id="admins-section" class="content-section" style="display: none;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--spacing-lg);">
                        <h1>Admin Management</h1>
                        <div style="display:flex;gap:10px;">
                            <button class="btn btn-primary" onclick="openAddAdminModal()">➕ Add Admin</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th data-sort="id">User ID</th>
                                    <th data-sort="name">Name</th>
                                    <th data-sort="email">Email</th>
                                    <th data-sort="role">Role</th>
                                    <th data-sort="status">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Verification Management -->
            <div id="verifications-section" class="content-section" style="display: none;">
                <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h1 class="content-title">Account Verifications</h1>
                        <p class="content-subtitle">Review submitted documents for farmers and transporters.</p>
                    </div>
                    <button class="btn btn-secondary" onclick="generateReport('verifications')">📄 Export
                        Report</button>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="vPendingCount">0</div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="vApprovedCount">0</div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="vRejectedCount">0</div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <div style="display:flex;gap:8px;margin:1.5rem 0 1rem;">
                    <button class="btn btn-primary v-tab-btn active" data-filter="pending"
                        onclick="setVerificationFilter('pending')">Pending</button>
                    <button class="btn btn-secondary v-tab-btn" data-filter="approved"
                        onclick="setVerificationFilter('approved')">Approved</button>
                    <button class="btn btn-secondary v-tab-btn" data-filter="rejected"
                        onclick="setVerificationFilter('rejected')">Rejected</button>
                    <button class="btn btn-secondary v-tab-btn" data-filter="all"
                        onclick="setVerificationFilter('all')">All</button>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="verificationsTableBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Order Management -->
            <div id="orders-section" class="content-section" style="display: none;">
                <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <h1 class="content-title">Order Management</h1>
                    <button class="btn btn-secondary" onclick="generateReport('orders')">📄 Export Report</button>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalOrdersCount">0</div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="processingOrdersCount">0</div>
                        <div class="stat-label">Processing</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="completedOrdersCount">0</div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <!-- <div class="stat-card">
                        <div class="stat-number" id="averageOrderValue">Rs. 0</div>
                        <div class="stat-label">Avg Order Value</div>
                    </div> -->
                </div>

                <div class="filters" style="margin-top: var(--spacing-xl);">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="orderSearch">Search Orders</label>
                            <input type="text" id="orderSearch" class="form-control"
                                placeholder="Search by order ID, buyer, or farmer...">
                        </div>
                        <div class="filter-group">
                            <label for="orderStatusFilter">Status</label>
                            <select id="orderStatusFilter" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th data-sort="id">Order ID</th>
                                <th data-sort="buyer">Buyer</th>
                                <th data-sort="farmer">Farmer</th>
                                <th data-sort="total">Total</th>
                                <th data-sort="status">Status</th>
                                <th data-sort="date">Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Product Management -->
            <div id="products-section" class="content-section" style="display: none;">
                <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h1 class="content-title">Product Management</h1>
                        <p class="content-subtitle">Overview of all products listed on the platform</p>
                    </div>
                    <button class="btn btn-secondary" onclick="generateReport('products')">📄 Export Report</button>
                </div>

                <!-- Product Statistics -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalProducts">0</div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="outOfStockProducts">0</div>
                        <div class="stat-label">Out of Stock</div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-container" style="margin-top: var(--spacing-xl);">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product Image</th>
                                <th>Product Name</th>
                                <th>Farmer</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr>
                                <td colspan="7" style="text-align:center;padding:2rem;">Loading products...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vehicles -->
            <div id="vehicles-section" class="content-section" style="display: none;">
                <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h1 class="content-title">Vehicles</h1>
                        <p class="content-subtitle">View all registered transporter vehicles.</p>
                    </div>
                    <button class="btn btn-secondary" onclick="generateReport('vehicles')">📄 Export Report</button>
                </div>

                <div class="filters" style="margin-top: var(--spacing-xl);">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="vehicleSearch">Search Vehicles</label>
                            <input type="text" id="vehicleSearch" class="form-control"
                                placeholder="Search by registration, transporter name, or email...">
                        </div>
                        <div class="filter-group">
                            <label for="vehicleStatusFilter">Status</label>
                            <select id="vehicleStatusFilter" class="form-control">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-container" style="margin-top: var(--spacing-xl);">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vehicle ID</th>
                                <th>Transporter</th>
                                <th>Registration</th>
                                <th>Type</th>
                                <th>Capacity</th>
                                <th>Fuel</th>
                                <th>Model</th>
                                <th>Status</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                        <tbody id="vehiclesTableBody">
                            <tr>
                                <td colspan="9" style="text-align:center;padding:2rem;">Loading vehicles...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reviews -->
            <div id="reviews-section" class="content-section" style="display: none;">
                <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h1 class="content-title">Reviews</h1>
                        <p class="content-subtitle">All buyer reviews and complaints for farmers and transporters.</p>
                    </div>
                    <button class="btn btn-secondary" onclick="generateReport('reviews')">📄 Export Report</button>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="reviewsTotalCount">0</div>
                        <div class="stat-label">Total Reviews</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="reviewsAvgRating">0</div>
                        <div class="stat-label">Avg Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="reviewsComplaints">0</div>
                        <div class="stat-label">Complaints (≤2★)</div>
                    </div>
                </div>

                <div class="filters" style="margin-top: var(--spacing-xl);">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="reviewSearch">Search Reviews</label>
                            <input type="text" id="reviewSearch" class="form-control"
                                placeholder="Search by buyer, target, product, order, or comment...">
                        </div>
                        <div class="filter-group">
                            <label for="reviewTargetRoleFilter">Target Role</label>
                            <select id="reviewTargetRoleFilter" class="form-control">
                                <option value="">All</option>
                                <option value="farmer">Farmer</option>
                                <option value="transporter">Transporter</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="reviewRatingFilter">Rating</label>
                            <select id="reviewRatingFilter" class="form-control">
                                <option value="">All</option>
                                <option value="5">5 stars</option>
                                <option value="4">4 stars</option>
                                <option value="3">3 stars</option>
                                <option value="2">2 stars</option>
                                <option value="1">1 star</option>
                                <option value="complaint">Complaints (≤2★)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-container" style="margin-top: var(--spacing-xl);">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Review ID</th>
                                <th>Date</th>
                                <th>Buyer</th>
                                <th>Target</th>
                                <th>Product</th>
                                <th>Rating</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody id="reviewsTableBody">
                            <tr>
                                <td colspan="7" style="text-align:center;padding:2rem;">Loading reviews...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payments & Finance -->
            <div id="payments-section" class="content-section" style="display: none;">
                <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h1 class="content-title">Payments & Finance</h1>
                        <p class="content-subtitle">Track and manage all platform payments</p>
                    </div>
                    <button class="btn btn-secondary" onclick="generateReport('payments')">📄 Export Report</button>
                </div>

                <!-- Payment Statistics -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalRevenue">Rs. 0</div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalTransactions">0</div>
                        <div class="stat-label">Total Transactions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="platformCommission">Rs. 0</div>
                        <div class="stat-label">Platform Commission</div>
                    </div>
                </div>

                <!-- Payment Filters -->
                <div class="filters" style="margin-top: var(--spacing-xl);">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="paymentTxnSearch">Search Payments</label>
                            <input type="text" id="paymentTxnSearch" class="form-control"
                                placeholder="Search by order ID, buyer, or transaction ID...">
                        </div>
                        <div class="filter-group">
                            <label for="paymentTxnStatusFilter">Status</label>
                            <select id="paymentTxnStatusFilter" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="filters-row" style="margin-top: 10px;">
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-secondary" onclick="resetPaymentFilters()">Reset Filters</button>
                        </div>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="content-card" style="margin-top: var(--spacing-xl);">
                    <div class="card-header">
                        <h3 class="card-title">Payment Transactions</h3>
                    </div>
                    <div style="padding: var(--spacing-lg);">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Order ID</th>
                                        <th>Buyer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
                                    <tr>
                                        <td colspan="7" style="text-align:center;padding:2rem;">Loading payments...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disputes -->
            <div id="disputes-section" class="content-section" style="display: none;">
                <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <h1 class="content-title">Cancelled Orders - Disputes</h1>
                        <p class="content-subtitle">Review cancelled orders and revise recorded payment totals</p>
                    </div>
                    <button class="btn btn-secondary" onclick="generateReport('disputes')">📄 Export Report</button>
                </div>

                <!-- Cancelled order dispute statistics -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalCancelledOrders">0</div>
                        <div class="stat-label">Cancelled Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="unrevisedCancelledOrders">0</div>
                        <div class="stat-label">Not Revised</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="revisedCancelledOrders">0</div>
                        <div class="stat-label">Revised</div>
                    </div>
                </div>

                <!-- Dispute Priority Summary -->
                <!-- <div class="stats-container" style="margin-top: var(--spacing-xl); width: 100%;">
                    <div class="stats-grid-3">
                        <div class="stat-card card text-center" style="border-left: 4px solid #e74c3c;">
                            <div class="stat-content">
                                <div class="stat-icon">🔴</div>
                                <h4>High Priority</h4>
                                <div class="stat-number" id="highPriorityDisputes">0</div>
                            </div>
                        </div>
                        <div class="stat-card card text-center" style="border-left: 4px solid #f39c12;">
                            <div class="stat-content">
                                <div class="stat-icon">🟡</div>
                                <h4>Medium Priority</h4>
                                <div class="stat-number" id="mediumPriorityDisputes">0</div>
                            </div>
                        </div>
                        <div class="stat-card card text-center" style="border-left: 4px solid #3498db;">
                            <div class="stat-content">
                                <div class="stat-icon">🔵</div>
                                <h4>Low Priority</h4>
                                <div class="stat-number" id="lowPriorityDisputes">0</div>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Dispute Categories -->
                <!-- <div class="stats-container" style="margin-top: var(--spacing-xl); width: 100%;">
                    <div class="stats-grid-4">
                        <div class="stat-card card text-center">
                            <div class="stat-content">
                                <div class="stat-icon">📦</div>
                                <h4>Order Issues</h4>
                                <div class="stat-number" id="orderIssues">0</div>
                            </div>
                        </div>
                        <div class="stat-card card text-center">
                            <div class="stat-content">
                                <div class="stat-icon">💰</div>
                                <h4>Payment Issues</h4>
                                <div class="stat-number" id="paymentIssues">0</div>
                            </div>
                        </div>
                        <div class="stat-card card text-center">
                            <div class="stat-content">
                                <div class="stat-icon">🚚</div>
                                <h4>Delivery Issues</h4>
                                <div class="stat-number" id="deliveryIssues">0</div>
                            </div>
                        </div>
                        <div class="stat-card card text-center">
                            <div class="stat-content">
                                <div class="stat-icon">⭐</div>
                                <h4>Quality Issues</h4>
                                <div class="stat-number" id="qualityIssues">0</div>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Filters -->
                <div class="filters" style="margin-top: var(--spacing-xl);">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="cancelledOrderSearch">Search Cancelled Orders</label>
                            <input type="text" id="cancelledOrderSearch" class="form-control"
                                placeholder="Search by order ID, buyer name, or email...">
                        </div>
                        <div class="filter-group">
                            <label for="revisionStatusFilter">Revision Status</label>
                            <select id="revisionStatusFilter" class="form-control">
                                <option value="">All</option>
                                <option value="unrevised">Not Revised</option>
                                <option value="revised">Revised</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="cancelledOrderPaymentMethod">Payment Method</label>
                            <select id="cancelledOrderPaymentMethod" class="form-control">
                                <option value="">All</option>
                                <option value="cash_on_delivery">Cash on Delivery</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="mobile_payment">Mobile Payment</option>
                            </select>
                        </div>
                    </div>
                    <div class="filters-row" style="margin-top: 10px;">
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-secondary" onclick="resetDisputeFilters()">Reset Filters</button>
                        </div>
                    </div>
                </div>

                <!-- Cancelled orders table -->
                <div class="table-container" style="margin-top: var(--spacing-xl);">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Buyer</th>
                                <th>Payment Method</th>
                                <th>Current Total</th>
                                <th>Revised Total</th>
                                <th>Revised By</th>
                                <th>Cancelled / Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cancelledOrdersDisputesTableBody">
                            <tr>
                                <td colspan="8" style="text-align:center;padding:2rem;">Loading cancelled orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Analytics -->
            <div id="analytics-section" class="content-section" style="display: none;">
                <div class="analytics-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <h1>Platform Analytics</h1>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <div class="period-selector">
                            <select id="analyticsPeriod" class="form-control" style="width:auto;display:inline-block;">
                                <option value="week">Last 7 Days</option>
                                <option value="month" selected>Last 30 Days</option>
                                <option value="year">Last 12 Months</option>
                            </select>
                            <button class="btn btn-primary" onclick="refreshAnalytics()">Refresh</button>
                        </div>
                        <button class="btn btn-secondary" onclick="generateReport('analytics')">📄 Export
                            Report</button>
                    </div>
                </div>

                <!-- Key Performance Indicators -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="analyticsTotalRevenue">Rs. 0</div>
                        <div class="stat-label">Total Revenue</div>
                        <div id="analyticsRevenueGrowth" class="stat-trend">+0%</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="analyticsTotalOrders">0</div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="analyticsTotalUsers">0</div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>

                <!-- Analytics Grid -->
                <div class="analytics-grid">
                    <!-- Revenue Chart -->
                    <div class="analytics-card">
                        <div class="card-header">
                            <div class="card-title">
                                <div>
                                    <h3>Revenue Trends</h3>
                                    <p>Monthly revenue tracking</p>
                                </div>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenueChart" style="max-height: 300px; width: 100%;"></canvas>
                        </div>
                    </div>

                    <!-- User Growth Chart -->
                    <div class="analytics-card">
                        <div class="card-header">
                            <div class="card-title">
                                <div>
                                    <h3>User Growth</h3>
                                    <p>New user registrations over time</p>
                                </div>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="userGrowthChart" style="max-height: 300px; width: 100%;"></canvas>
                        </div>
                    </div>

                    <!-- Category Distribution -->
                    <div class="analytics-card">
                        <div class="card-header">
                            <div class="card-title">
                                <div>
                                    <h3>Category Distribution</h3>
                                    <p>Products by category</p>
                                </div>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="categoryChart" style="max-height: 300px; width: 100%;"></canvas>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="analytics-card">
                        <div class="card-header">
                            <div class="card-title">
                                <div>
                                    <h3>Top Selling Products</h3>
                                    <p>Best performing products</p>
                                </div>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="topProductsBody">
                                    <tr>
                                        <td colspan="3" style="text-align:center;">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Details Modal -->
            <div id="paymentDetailsModal" class="modal" style="display:none;">
                <div class="modal-content" style="max-width:600px;width:95%;">
                    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;">
                        <h3>Payment Details</h3>
                        <button onclick="closeModal('paymentDetailsModal')"
                            style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
                    </div>
                    <div class="modal-body" id="paymentDetailsBody">
                        <!-- Payment details will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Dispute Details Modal -->
            <div id="disputeDetailsModal" class="modal" style="display:none;">
                <div class="modal-content" style="max-width:800px;width:95%;max-height:80vh;overflow-y:auto;">
                    <div class="modal-header"
                        style="display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #eee;">
                        <h3>Dispute Details</h3>
                        <button onclick="closeModal('disputeDetailsModal')"
                            style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
                    </div>
                    <div class="modal-body" id="disputeDetailsBody" style="padding:20px;">
                        <!-- Dispute details will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Analytics -->


            <!-- Notifications -->
            <div id="notifications-section" class="content-section" style="display: none;">
                <div class="content-header">
                    <h1 class="content-title">System Notifications</h1>
                    <button class="btn btn-primary" data-modal="sendNotificationModal">Send Notification</button>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalNotifications">0</div>
                        <div class="stat-label">Total Sent</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="deliveredNotifications">0</div>
                        <div class="stat-label">Delivered</div>
                    </div>
                </div>

                <div class="content-card" style="margin-top: var(--spacing-xl);">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                        <h3 class="card-title">Sent Notifications</h3>
                    </div>
                    <div class="card-content" style="padding:0;">
                        <div style="overflow-x:auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sent At</th>
                                        <th>Notification</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody id="notificationsSummaryBody">
                                    <tr>
                                        <td colspan="3" style="text-align:center;padding:2rem;color:var(--text-light);">
                                            Loading notifications...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div id="settings-section" class="content-section" style="display: none;">
                <div class="settings-header">
                    <h1>System Settings</h1>
                    <p>Manage platform configuration and system preferences</p>
                </div>

                <div class="content-card" style="margin-top: var(--spacing-xl);">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                        <h3 class="card-title">Platform Settings</h3>
                        <button type="button" class="btn btn-secondary" id="resetPlatformSettingsBtn">Reset</button>
                    </div>
                    <div class="card-content" style="padding: 24px;">
                        <form id="platformSettingsForm">
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label for="supportEmail">Support Email</label>
                                    <input type="email" id="supportEmail" name="support_email" class="form-control"
                                        placeholder="support@agrolink.lk">
                                    <small class="form-text">Shown in user-facing help messages.</small>
                                </div>
                                <div class="form-group">
                                    <label for="reviewSlaDays">Verification SLA (days)</label>
                                    <input type="number" id="reviewSlaDays" name="verification_sla_days"
                                        class="form-control" min="0" step="1" placeholder="2">
                                    <small class="form-text">Target time for verifying farmer/transporter
                                        accounts.</small>
                                </div>
                            </div>

                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label for="platformCommission">Platform Commission (%)</label>
                                    <input type="number" id="platformCommission" name="platform_commission"
                                        class="form-control" min="0" max="100" step="0.1" placeholder="0">
                                    <small class="form-text">Used for reporting (does not change past orders).</small>
                                </div>
                                <div class="form-group">
                                    <label for="autoCancelHours">Auto-cancel unpaid orders (hours)</label>
                                    <input type="number" id="autoCancelHours" name="auto_cancel_unpaid_hours"
                                        class="form-control" min="0" step="1" placeholder="24">
                                    <small class="form-text">0 disables auto-cancel.</small>
                                </div>
                            </div>

                            <div class="content-card"
                                style="margin-top: var(--spacing-lg); border: 1px solid var(--light-gray);">
                                <div class="card-header" style="background: transparent;">
                                    <h3 class="card-title">Maintenance</h3>
                                </div>
                                <div class="card-content" style="padding: 18px;">
                                    <div class="grid grid-2" style="align-items:end;">
                                        <div class="form-group" style="margin-bottom:0;">
                                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                                <input type="checkbox" id="maintenanceMode" name="maintenance_mode">
                                                <span style="font-weight:600;">Maintenance Mode</span>
                                            </label>
                                            <small class="form-text">Use this as an admin reminder and optionally notify
                                                users.</small>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0;">
                                            <button type="button" class="btn btn-primary" id="sendMaintenanceNoticeBtn"
                                                style="width:100%;">Send Maintenance Notice</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; gap: 12px; margin-top: var(--spacing-lg);">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                                <button type="button" class="btn btn-secondary" id="openNotificationModalBtn">Send
                                    Notification</button>
                            </div>
                        </form>

                        <div style="margin-top: 12px; font-size: 12px; color: var(--dark-gray);">
                            Note: settings are currently stored per-browser for this admin account (local). Connect them
                            to a DB table when ready.
                        </div>
                    </div>
                </div>
            </div>



            <!-- Document Review Modal -->
            <div id="docReviewModal" class="modal" style="display:none;">
                <div class="modal-content" style="max-width:720px;width:95%;">
                    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;">
                        <h3 id="docModalTitle">Review Documents</h3>
                        <button onclick="closeDocModal()"
                            style="background:none;border:none;font-size:20px;cursor:pointer;color:#666;">✕</button>
                    </div>
                    <div class="modal-body" id="docModalBody"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
            </div>
            <div class="modal-body">
                <form id="addUserForm" enctype="multipart/form-data">
                    <div class="message" id="addUserMessage"></div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="userName">Full Name *</label>
                            <input type="text" id="userName" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="userEmail">Email *</label>
                            <input type="email" id="userEmail" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="userRole">Role *</label>
                            <select id="userRole" name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="farmer">Farmer</option>
                                <option value="buyer">Buyer</option>
                                <option value="transporter">Transporter</option>
                                <?php if (($role ?? '') === 'superadmin'): ?>
                                    <option value="admin">Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="userPass">Password *</label>
                            <input type="password" id="userPass" name="password" class="form-control" required
                                minlength="8">
                            <small class="form-text">Minimum 8 characters</small>
                        </div>
                        <div class="form-group">
                            <label for="userConfirmPass">Confirm Password *</label>
                            <input type="password" id="userConfirmPass" name="confirmPassword" class="form-control"
                                required minlength="8">
                            <small class="form-text">Re-enter password to confirm</small>
                        </div>
                    </div>

                    <div id="verificationDocsFarmer" style="display:none; margin-top: 10px;">
                        <div class="content-card" style="border:1px solid var(--light-gray);">
                            <div class="card-header" style="background: transparent;">
                                <h3 class="card-title">Farmer verification documents</h3>
                            </div>
                            <div class="card-content" style="padding: 16px;">
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label for="docNic">NIC (Required)</label>
                                        <input type="file" id="docNic" name="nic" class="form-control"
                                            accept="image/jpeg,image/png,image/webp,application/pdf">
                                        <small class="form-text">Clear photo/scan (front side preferred).</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="docBank">Bank details (Required)</label>
                                        <input type="file" id="docBank" name="bank_details" class="form-control"
                                            accept="image/jpeg,image/png,image/webp,application/pdf">
                                        <small class="form-text">Statement/passbook page with name + account
                                            number.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="verificationDocsTransporter" style="display:none; margin-top: 10px;">
                        <div class="content-card" style="border:1px solid var(--light-gray);">
                            <div class="card-header" style="background: transparent;">
                                <h3 class="card-title">Transporter verification documents</h3>
                            </div>
                            <div class="card-content" style="padding: 16px;">
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label for="docDL">Driving license (Required)</label>
                                        <input type="file" id="docDL" name="driving_license" class="form-control"
                                            accept="image/jpeg,image/png,image/webp,application/pdf">
                                    </div>
                                    <div class="form-group">
                                        <label for="docIns">Vehicle insurance (Required)</label>
                                        <input type="file" id="docIns" name="vehicle_insurance" class="form-control"
                                            accept="image/jpeg,image/png,image/webp,application/pdf">
                                    </div>
                                    <div class="form-group">
                                        <label for="docRev">Vehicle revenue license (Required)</label>
                                        <input type="file" id="docRev" name="vehicle_revenue_license"
                                            class="form-control"
                                            accept="image/jpeg,image/png,image/webp,application/pdf">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="addUserFormErrors"
                        style="display: none; color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 4px;">
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: var(--spacing-lg);">
                        <button type="submit" class="btn btn-primary">Add User</button>
                        <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update User Modal -->
    <div id="updateUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update User</h3>
            </div>
            <div class="modal-body">
                <form id="updateUserForm">
                    <div class="message" id="updateUserMessage"></div>
                    <input type="hidden" id="updateUserId" name="id">
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="updateName">Full Name *</label>
                            <input type="text" id="updateName" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="updateEmail">Email *</label>
                            <input type="email" id="updateEmail" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="updateRole">Role *</label>
                            <select id="updateRole" name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="farmer">Farmer</option>
                                <option value="buyer">Buyer</option>
                                <option value="transporter">Transporter</option>
                                <?php if (($role ?? '') === 'superadmin'): ?>
                                    <option value="admin">Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="updatePass">New Password</label>
                            <input type="password" id="updatePass" name="password" class="form-control" minlength="8">
                            <small class="form-text">Leave empty to keep current password</small>
                        </div>
                    </div>
                    <div id="updateUserFormErrors"
                        style="display: none; color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 4px;">
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: var(--spacing-lg);">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <button type="button" class="btn btn-secondary" onclick="closeUpdateUserModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Send Notification Modal -->
    <div id="sendNotificationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Send Notification</h3>
            </div>
            <div class="modal-body">
                <form id="sendNotificationForm">
                    <div class="form-group">
                        <label for="notificationTitle">Title *</label>
                        <input type="text" id="notificationTitle" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="notificationMessage">Message *</label>
                        <textarea id="notificationMessage" name="message" class="form-control" rows="4"
                            required></textarea>
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="notificationRecipient">Recipient *</label>
                            <select id="notificationRecipient" name="recipient" class="form-control" required>
                                <option value="">Select Recipients</option>
                                <option value="all">All Users</option>
                                <option value="farmers">All Farmers</option>
                                <option value="buyers">All Buyers</option>
                                <option value="transporters">All Transporters</option>
                                <option value="admins">All Admins</option>
                                <option value="selected">Selected Users</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notificationType">Type *</label>
                            <select id="notificationType" name="type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="system">System</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="promotion">Promotion</option>
                                <option value="alert">Alert</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="notificationSelectedUsersGroup" style="display:none;">
                        <label for="notificationSelectedUsers">Select Users *</label>
                        <select id="notificationSelectedUsers" name="user_ids[]" class="form-control" multiple size="8">
                            <?php foreach (($users ?? []) as $u): ?>
                                <?php
                                $uid = (int) ($u->id ?? 0);
                                if ($uid <= 0)
                                    continue;
                                $uname = trim((string) ($u->name ?? 'User'));
                                $uemail = trim((string) ($u->email ?? ''));
                                $urole = trim((string) ($u->role ?? ''));
                                ?>
                                <option value="<?= $uid ?>">
                                    <?= htmlspecialchars($uname) ?>    <?= $uemail !== '' ? ' (' . htmlspecialchars($uemail) . ')' : '' ?>    <?= $urole !== '' ? ' - ' . htmlspecialchars($urole) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Hold Ctrl (Windows) / Cmd (Mac) to select multiple users.</small>
                    </div>
                    <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                        <button type="submit" class="btn btn-primary">Send Notification</button>
                        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Report Generation Modal -->
    <div id="reportModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width:480px;width:95%;">
            <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h3 id="reportModalTitle">Generate Report</h3>
                <button onclick="closeModal('reportModal')"
                    style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
            </div>
            <div class="modal-body" style="padding:20px;">
                <p style="color:#666;font-size:14px;margin-bottom:16px;" id="reportModalDesc">
                    Choose a format to export your data.
                </p>

                <!-- Date range (shown for time-based sections) -->
                <div id="reportDateRange" style="margin-bottom:16px;display:none;">
                    <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px;">Date
                        Range</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <label style="font-size:12px;color:#888;">From</label>
                            <input type="date" id="reportDateFrom" class="form-control">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#888;">To</label>
                            <input type="date" id="reportDateTo" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Columns selector -->
                <div id="reportColumnsSection" style="margin-bottom:16px;">
                    <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:8px;">Include
                        Columns</label>
                    <div id="reportColumnsGrid"
                        style="display:grid;grid-template-columns:1fr 1fr;gap:6px;max-height:180px;overflow-y:auto;padding:2px;">
                    </div>
                </div>

                <!-- Format selection -->
                <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:8px;">Export
                    Format</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                    <label
                        style="border:1px solid #ddd;border-radius:8px;padding:14px;cursor:pointer;display:flex;align-items:center;gap:10px;transition:.15s;"
                        id="fmtCsvLabel">
                        <input type="radio" name="reportFormat" value="csv" checked onchange="highlightFormat()"
                            style="accent-color:#1a7a4a;">
                        <div>
                            <div style="font-weight:600;font-size:14px;">CSV</div>
                            <div style="font-size:12px;color:#888;">Excel compatible</div>
                        </div>
                    </label>
                    <label
                        style="border:1px solid #ddd;border-radius:8px;padding:14px;cursor:pointer;display:flex;align-items:center;gap:10px;transition:.15s;"
                        id="fmtPrintLabel">
                        <input type="radio" name="reportFormat" value="print" onchange="highlightFormat()"
                            style="accent-color:#1a7a4a;">
                        <div>
                            <div style="font-weight:600;font-size:14px;">Print / PDF</div>
                            <div style="font-size:12px;color:#888;">Browser print dialog</div>
                        </div>
                    </label>
                </div>

                <div style="display:flex;gap:10px;">
                    <button class="btn btn-primary" style="flex:1;" onclick="executeReport()">Generate Report</button>
                    <button class="btn btn-secondary" onclick="closeModal('reportModal')">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= ROOT ?>/assets/js/main.js"></script>
    <script>
        let currentReviewUserId = null;
        let _currentModalUserId = null;

        // Initialize admin dashboard
        function initAdminDashboard() {
            loadDashboardData();
            loadUsers();
            <?php if (($role ?? '') === 'superadmin'): ?>
                loadAdmins();
            <?php endif; ?>
            loadVerifications();
            loadOrders();
            loadProducts();
            loadVehicles();
            loadReviews();
            loadAnalytics();
            loadPayments();
            loadDisputes();
            setupOrderFilters();
            setupProductFilters();
            setupVehicleFilters();
            setupReviewFilters();
            setupPaymentFilters();
            setupDisputeFilters();
            setupNavigation();
            setupForms();
            showSection('dashboard');
            if (typeof loadAnalytics === 'function') loadAnalytics();
        }

        // Navigation setup
        function setupNavigation() {
            const menuLinks = document.querySelectorAll('.menu-link');
            menuLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const section = this.getAttribute('data-section');
                    showSection(section);
                    menuLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        }

        // Show specific section
        function showSection(sectionName) {
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => section.style.display = 'none');
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.style.display = 'block';
            }
            const menuLinks = document.querySelectorAll('.menu-link');
            menuLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('data-section') === sectionName) {
                    link.classList.add('active');
                }
            });
            if (sectionName === 'analytics') {
                loadAnalytics();
            } else if (sectionName === 'notifications') {
                loadNotificationStats();
                if (typeof loadNotificationSummary === 'function') {
                    loadNotificationSummary();
                }
            }
        }

        // Load dashboard data
        function loadDashboardData() {
            const recentOrders = document.getElementById('recentOrders');
            if (recentOrders && recentOrders.children.length === 0) {
                recentOrders.innerHTML = '<div style="padding: 18px; text-align: center; color: #666;">No recent orders</div>';
            }

            const newRegistrations = document.getElementById('newRegistrations');
            if (newRegistrations && newRegistrations.children.length === 0) {
                newRegistrations.innerHTML = '<div style="padding: 18px; text-align: center; color: #666;">No new registrations</div>';
            }
        }

        // Load users data
        function loadUsers() {
            const tbody = document.getElementById('usersTableBody');
            let users = <?= json_encode($users) ?>;
            const signedInUser = '<?= $role ?>';

            if (signedInUser === 'admin') {
                users = users.filter(user => user.role !== 'admin' && user.role !== 'superadmin');
            } else if (signedInUser === 'superadmin') {
                users = users.filter(user => user.role !== 'superadmin');
            }

            // Filter by verification status
            users = users.filter(user => {
                return user.verification_status === 'approved' ||
                    user.verification_status === 'not_required';
            });

            // Store all users for filtering
            allUsers = users;

            // Display all users initially
            displayUsers(users);
        }

        function loadAdmins() {
            const tbody = document.getElementById('adminsTableBody');
            if (!tbody) return;

            let users = <?= json_encode($users) ?>;
            users = (users || []).filter(user => String(user.role || '').toLowerCase() === 'admin');

            users = users.filter(user => {
                return user.verification_status === 'approved' ||
                    user.verification_status === 'not_required';
            });

            displayUsers(users, 'adminsTableBody');
        }

        function displayUsers(users, tbodyId = 'usersTableBody') {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return;

            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#aaa;">No users found.</td></tr>';
                return;
            }

            let html = '';
            users.forEach(user => {
                const isDeactivated = (user.status && String(user.status).toLowerCase() === 'inactive') || !!user.deactivated_at;

                // Get role badge class
                let roleBadgeClass = '';
                switch (user.role) {
                    case 'farmer': roleBadgeClass = 'success'; break;
                    case 'buyer': roleBadgeClass = 'info'; break;
                    case 'transporter': roleBadgeClass = 'warning'; break;
                    default: roleBadgeClass = 'danger';
                }

                // Get verification status badge
                let verificationBadge = '';
                if (user.verification_status === 'approved') {
                    verificationBadge = '<span class="badge badge-success" style="margin-left: 8px;">✓ Approved</span>';
                } else if (user.verification_status === 'not_required') {
                    verificationBadge = '<span class="badge badge-info" style="margin-left: 8px;">Not Required</span>';
                } else if (user.verification_status === 'pending') {
                    verificationBadge = '<span class="badge badge-warning" style="margin-left: 8px;">⏳ Pending</span>';
                } else if (user.verification_status === 'rejected') {
                    verificationBadge = '<span class="badge badge-danger" style="margin-left: 8px;">❌ Rejected</span>';
                }

                html += `
            <tr>
                <td>${user.id || 'N/A'}</td>
                <td>${user.name || 'N/A'}</td>
                <td>${user.email || 'N/A'}</td>
                <td><span class="badge badge-${roleBadgeClass}">${user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'User'}</span></td>
                <td>
                    ${isDeactivated
                        ? `<span class="badge badge-secondary" title="${user.deactivated_at ? 'Deactivated at: ' + user.deactivated_at : 'Deactivated'}">Deactivated</span>`
                        : `<span class="badge badge-success">Active</span>`
                    }
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="openUpdateUserModal('${user.id}')" ${isDeactivated ? 'disabled' : ''}>Edit</button>
                    ${isDeactivated
                        ? `<button type="button" class="btn btn-sm btn-success" onclick="activateUser('${user.id}', '${user.role}')">Activate</button>`
                        : `<button type="button" class="btn btn-sm btn-danger" onclick="deleteUser('${user.id}', '${user.role}')">Deactivate</button>`
                    }
                </td>
            </tr>
        `;
            });
            tbody.innerHTML = html;
        }

        function filterUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;

            let filteredUsers = [...allUsers];

            // Apply role filter
            if (roleFilter !== '') {
                filteredUsers = filteredUsers.filter(user => user.role === roleFilter);
            }

            // Apply search filter (search by name, email, or ID)
            if (searchTerm !== '') {
                filteredUsers = filteredUsers.filter(user => {
                    return (user.name && user.name.toLowerCase().includes(searchTerm)) ||
                        (user.email && user.email.toLowerCase().includes(searchTerm)) ||
                        (user.id && user.id.toString().includes(searchTerm));
                });
            }

            // Display filtered users
            displayUsers(filteredUsers);

            // Show message if no results
            if (filteredUsers.length === 0) {
                const tbody = document.getElementById('usersTableBody');
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#aaa;">No users match your search criteria.</td></tr>';
            }
        }

        function debounce(func, delay) {
            let timeoutId;
            return function (...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        }

        function setupLiveSearch() {
            const searchInput = document.getElementById('userSearch');
            const roleSelect = document.getElementById('roleFilter');

            if (searchInput) {
                // Use debounce to avoid filtering on every keystroke
                const debouncedFilter = debounce(filterUsers, 300);
                searchInput.addEventListener('input', debouncedFilter);
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', filterUsers);
            }
        }

        // Add clear filters button functionality
        // Add clear filters button functionality - UPDATED VERSION
        function addClearFiltersButton() {
            const filtersDiv = document.querySelector('#users-section .filters');
            if (filtersDiv && !document.getElementById('clearFiltersBtn')) {
                const clearBtn = document.createElement('button');
                clearBtn.id = 'clearFiltersBtn';
                clearBtn.className = 'btn btn-secondary';
                clearBtn.textContent = 'Clear Filters';
                clearBtn.style.marginTop = '24px'; // Align with inputs (since labels take space)
                clearBtn.style.height = '38px'; // Match input height
                clearBtn.style.padding = '0 16px';
                clearBtn.style.cursor = 'pointer';
                clearBtn.onclick = function () {
                    document.getElementById('userSearch').value = '';
                    document.getElementById('roleFilter').value = '';
                    filterUsers();
                };

                const filterRow = filtersDiv.querySelector('.filters-row');
                if (filterRow) {
                    const buttonDiv = document.createElement('div');
                    buttonDiv.className = 'filter-group';
                    buttonDiv.style.display = 'flex';
                    buttonDiv.style.alignItems = 'flex-end'; // Align button at bottom
                    buttonDiv.appendChild(clearBtn);
                    filterRow.appendChild(buttonDiv);
                }
            }
        }

        // Enhanced initialization
        document.addEventListener('DOMContentLoaded', function () {
            initAdminDashboard();
            updateUserCount();
            setInterval(updateUserCount, 30000);

            // Setup live search after users are loaded
            setTimeout(() => {
                setupLiveSearch();
                addClearFiltersButton();
            }, 500);
        });

        // Load orders data
        async function loadOrders() {
            const tbody = document.getElementById('ordersTableBody');

            // Show loading state
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">Loading orders...</td></tr>';

            try {
                // Get filter values
                const status = document.getElementById('orderStatusFilter')?.value || '';
                const search = document.getElementById('orderSearch')?.value || '';

                const response = await fetch('<?= ROOT ?>/adminDashboard/getOrders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        status: status,
                        search: search
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    const msg = result.message || result.error || 'Failed to load orders';
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Error: ${escapeHtml(String(msg))}</td></tr>`;
                    return;
                }

                // Update statistics
                if (result.stats) {
                    document.getElementById('totalOrdersCount').textContent = result.stats.total_orders || 0;
                    document.getElementById('processingOrdersCount').textContent = result.stats.processing || 0;
                    document.getElementById('completedOrdersCount').textContent = result.stats.completed || 0;
                }

                allOrders = result.data;
                displayOrders(allOrders);

            } catch (error) {
                console.error('Error loading orders:', error);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Failed to load orders. Please try again.</td></tr>';
            }
        }

        function displayOrders(orders) {
            const tbody = document.getElementById('ordersTableBody');

            if (!orders || orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#aaa;">No orders found.</td></tr>';
                return;
            }

            let html = '';
            orders.forEach(order => {
                // Status badge class
                let statusClass = '';
                switch (order.order_status) {
                    case 'pending': statusClass = 'badge-warning'; break;
                    case 'processing': statusClass = 'badge-info'; break;
                    case 'shipped': statusClass = 'badge-primary'; break;
                    case 'delivered': statusClass = 'badge-success'; break;
                    case 'completed': statusClass = 'badge-success'; break;
                    case 'cancelled': statusClass = 'badge-danger'; break;
                    default: statusClass = 'badge-secondary';
                }

                // Payment status badge
                let paymentClass = '';
                switch (order.payment_status) {
                    case 'paid': paymentClass = 'badge-success'; break;
                    case 'pending': paymentClass = 'badge-warning'; break;
                    case 'failed': paymentClass = 'badge-danger'; break;
                    case 'refunded': paymentClass = 'badge-info'; break;
                    default: paymentClass = 'badge-secondary';
                }

                html += `
            <tr>
                <td><strong>#${order.order_number || order.order_id}</strong></td>
                <td>${escapeHtml(order.buyer_name || 'N/A')}</td>
                <td>${escapeHtml(order.farmer_name || 'Multiple')}</td>
                <td><strong>Rs. ${parseFloat(order.total_amount).toLocaleString()}</strong></td>
                <td><span class="badge ${statusClass}">${order.order_status ? order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1) : 'N/A'}</span></td>
                <td>${formatDate(order.order_date)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="viewOrderDetails(${order.order_id})">View</button>
                </td>
            </tr>
        `;
            });
            tbody.innerHTML = html;
        }
        /* <button class="btn btn-sm btn-secondary" onclick="updateOrderStatus(${order.order_id})">Update Status</button> */

        // Format date for display
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        async function viewOrderDetails(orderId) {
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/getOrderDetails', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ order_id: orderId })
                });

                const result = await response.json();

                if (!result.success) {
                    await systemAlert(result.message || result.error || 'Failed to load order details', 'Order Details');
                    return;
                }

                // Create modal for order details
                let modalHtml = `
            <div id="orderDetailsModal" class="modal" style="display:flex;">
                <div class="modal-content" style="max-width:800px;width:95%;">
                    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;">
                        <h3>Order Details - #${(result.order && (result.order.order_number || result.order.id)) || orderId}</h3>
                        <button onclick="closeModal('orderDetailsModal')" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
                    </div>
                    <div class="modal-body">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                            <div>
                                <h4>Buyer Information</h4>
                                <p><strong>Name:</strong> ${escapeHtml(result.order.buyer_name)}</p>
                                <p><strong>Email:</strong> ${escapeHtml(result.order.buyer_email)}</p>
                                <p><strong>Phone:</strong> ${escapeHtml(result.order.buyer_phone || 'N/A')}</p>
                                <p><strong>Address:</strong> ${escapeHtml(result.order.buyer_address || 'N/A')}</p>
                            </div>
                            <div>
                                <h4>Order Information</h4>
                                <p><strong>Total Amount:</strong> Rs. ${parseFloat(result.order.total_amount).toLocaleString()}</p>
                                <p><strong>Status:</strong> ${result.order.order_status}</p>
                                <p><strong>Payment Status:</strong> ${result.order.payment_status}</p>
                                <p><strong>Date:</strong> ${formatDate(result.order.created_at)}</p>
                            </div>
                        </div>
                        <h4>Order Items</h4>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
        `;

                result.items.forEach(item => {
                    const productImage = item.product_image
                        ? `<?= ROOT ?>/assets/images/products/${item.product_image}`
                        : `<?= ROOT ?>/assets/images/default-product.svg`;

                    const unitPrice = parseFloat(item.unit_price || 0);
                    const quantity = parseInt(item.quantity || 0, 10) || 0;
                    const lineTotal = unitPrice * quantity;

                    modalHtml += `
                <tr>
                    <td style="display:flex;align-items:center;gap:10px;">
                        <img src="${productImage}" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:8px;" onerror="this.src='<?= ROOT ?>/assets/images/default-product.svg'">
                        <div>
                            <div style="font-weight:600;">${escapeHtml(item.product_name || 'Product')}</div>
                            <div style="font-size:12px;color:#888;">${escapeHtml(item.product_category || '')}</div>
                        </div>
                    </td>
                    <td>${quantity}</td>
                    <td><strong>Rs. ${lineTotal.toLocaleString()}</strong></td>
                </tr>
            `;
                });

                modalHtml += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer" style="padding:15px;text-align:right;">
                        <button class="btn btn-secondary" onclick="closeModal('orderDetailsModal')">Close</button>
                    </div>
                </div>
            </div>
        `;

                // Remove existing modal if any
                const existingModal = document.getElementById('orderDetailsModal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                document.body.style.overflow = 'hidden';

            } catch (error) {
                console.error('Error loading order details:', error);
                showNotification('Failed to load order details', 'error');
            }
        }

        // Update order status
        async function updateOrderStatus(orderId) {
            const newStatus = await systemPrompt({
                title: 'Update Order Status',
                message: 'Enter new status (pending, processing, shipped, delivered, completed, cancelled):',
                label: 'New Status',
                placeholder: 'e.g. shipped',
                required: true,
            });

            if (!newStatus) return;

            const validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
            if (!validStatuses.includes(newStatus.toLowerCase())) {
                showNotification('Invalid status. Please use: pending, processing, shipped, delivered, completed, cancelled', 'warning');
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/updateOrderStatus', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: newStatus.toLowerCase()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Order status updated successfully!', 'success');
                    loadOrders(); // Refresh the orders list
                } else {
                    showNotification('Failed to update order status: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating order status:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Setup order filters with live filtering
        function setupOrderFilters() {
            const filters = ['orderSearch', 'orderStatusFilter'];

            filters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    element.addEventListener('change', () => loadOrders());
                    if (filterId === 'orderSearch') {
                        let timeout;
                        element.addEventListener('input', () => {
                            clearTimeout(timeout);
                            timeout = setTimeout(() => loadOrders(), 500);
                        });
                    }
                }
            });
        }

        // Modify the initAdminDashboard function to include order filters setup
        // Add this line inside initAdminDashboard after loadOrders():
        // setupOrderFilters();

        // Setup forms
        function setupForms() {
            const sendNotificationForm = document.getElementById('sendNotificationForm');
            if (sendNotificationForm) {
                const recipientSelect = document.getElementById('notificationRecipient');
                const selectedGroup = document.getElementById('notificationSelectedUsersGroup');
                const selectedUsersSelect = document.getElementById('notificationSelectedUsers');

                function toggleSelectedUsers() {
                    const mode = (recipientSelect?.value || '').toLowerCase();
                    if (mode === 'selected') {
                        if (selectedGroup) selectedGroup.style.display = 'block';
                        if (selectedUsersSelect) selectedUsersSelect.required = true;
                    } else {
                        if (selectedGroup) selectedGroup.style.display = 'none';
                        if (selectedUsersSelect) {
                            selectedUsersSelect.required = false;
                            [...selectedUsersSelect.options].forEach(opt => opt.selected = false);
                        }
                    }
                }

                if (recipientSelect) {
                    recipientSelect.addEventListener('change', toggleSelectedUsers);
                    toggleSelectedUsers();
                }

                sendNotificationForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    try {
                        const formData = new FormData(this);
                        const response = await fetch('<?= ROOT ?>/adminDashboard/sendNotification', {
                            method: 'POST',
                            body: formData,
                        });

                        const result = await response.json().catch(() => ({}));
                        if (!response.ok || !result.success) {
                            showNotification(result.message || 'Failed to send notification', 'error');
                            return;
                        }

                        const meta = (typeof result.sent !== 'undefined')
                            ? ` (sent: ${result.sent}, failed: ${result.failed || 0})`
                            : '';
                        showNotification((result.message || 'Notification sent') + meta, 'success');

                        // Update notifications stats in the UI
                        if (typeof result.sent !== 'undefined') {
                            const totalEl = document.getElementById('totalNotifications');
                            if (totalEl) {
                                const currentTotal = parseInt(totalEl.textContent) || 0;
                                totalEl.textContent = currentTotal + result.sent;
                            }
                            const deliveredEl = document.getElementById('deliveredNotifications');
                            if (deliveredEl) {
                                const currentDelivered = parseInt(deliveredEl.textContent) || 0;
                                deliveredEl.textContent = currentDelivered + result.sent;
                            }
                            if (typeof loadNotificationStats === 'function') {
                                loadNotificationStats();
                            }
                            if (typeof loadNotificationSummary === 'function') {
                                loadNotificationSummary();
                            }
                        }

                        closeModal('sendNotificationModal');
                        this.reset();
                        toggleSelectedUsers();
                    } catch (error) {
                        console.error('Error:', error);
                        showNotification('Network error. Please try again.', 'error');
                    }
                });
            }

            const settingsStorageKey = 'agrolink_admin_platform_settings_v1';

            function readSettingsFromStorage() {
                try {
                    const raw = localStorage.getItem(settingsStorageKey);
                    return raw ? JSON.parse(raw) : null;
                } catch (e) {
                    return null;
                }
            }

            function writeSettingsToStorage(settings) {
                try {
                    localStorage.setItem(settingsStorageKey, JSON.stringify(settings));
                    return true;
                } catch (e) {
                    return false;
                }
            }

            function applySettingsToForm(form, settings) {
                if (!form) return;

                const setValue = (id, value) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.value = (typeof value === 'undefined' || value === null) ? '' : value;
                };

                setValue('supportEmail', settings?.support_email);
                setValue('reviewSlaDays', settings?.verification_sla_days);
                setValue('platformCommission', settings?.platform_commission);
                setValue('autoCancelHours', settings?.auto_cancel_unpaid_hours);

                const maintenance = document.getElementById('maintenanceMode');
                if (maintenance) maintenance.checked = !!settings?.maintenance_mode;
            }

            function collectSettingsFromForm(form) {
                const fd = new FormData(form);
                return {
                    support_email: (fd.get('support_email') || '').toString().trim(),
                    verification_sla_days: parseInt(fd.get('verification_sla_days') || '0', 10) || 0,
                    platform_commission: parseFloat(fd.get('platform_commission') || '0') || 0,
                    auto_cancel_unpaid_hours: parseInt(fd.get('auto_cancel_unpaid_hours') || '0', 10) || 0,
                    maintenance_mode: fd.get('maintenance_mode') === 'on',
                };
            }

            const platformSettingsForm = document.getElementById('platformSettingsForm');
            if (platformSettingsForm) {
                const stored = readSettingsFromStorage();
                if (stored) {
                    applySettingsToForm(platformSettingsForm, stored);
                }

                platformSettingsForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const settings = collectSettingsFromForm(platformSettingsForm);
                    const ok = writeSettingsToStorage(settings);
                    showNotification(ok ? 'Platform settings saved successfully!' : 'Failed to save settings in this browser.', ok ? 'success' : 'error');
                });
            }

            const resetBtn = document.getElementById('resetPlatformSettingsBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    try {
                        localStorage.removeItem(settingsStorageKey);
                    } catch (e) { }

                    const form = document.getElementById('platformSettingsForm');
                    if (form) form.reset();
                    showNotification('Platform settings reset.', 'success');
                });
            }

            const openNotificationBtn = document.getElementById('openNotificationModalBtn');
            if (openNotificationBtn) {
                openNotificationBtn.addEventListener('click', function () {
                    if (typeof openModal === 'function') {
                        openModal('sendNotificationModal');
                    } else {
                        const modal = document.getElementById('sendNotificationModal');
                        if (modal) modal.style.display = 'block';
                    }
                });
            }

            const maintenanceNoticeBtn = document.getElementById('sendMaintenanceNoticeBtn');
            if (maintenanceNoticeBtn) {
                maintenanceNoticeBtn.addEventListener('click', function () {
                    const titleEl = document.getElementById('notificationTitle');
                    const messageEl = document.getElementById('notificationMessage');
                    const recipientEl = document.getElementById('notificationRecipient');
                    const typeEl = document.getElementById('notificationType');

                    if (titleEl) titleEl.value = 'Scheduled maintenance';
                    if (messageEl) messageEl.value = 'We will be performing maintenance shortly. Some features may be unavailable during this time.';
                    if (recipientEl) recipientEl.value = 'all';
                    if (typeEl) typeEl.value = 'maintenance';

                    const selectedGroup = document.getElementById('notificationSelectedUsersGroup');
                    if (selectedGroup) selectedGroup.style.display = 'none';

                    if (typeof openModal === 'function') {
                        openModal('sendNotificationModal');
                    } else {
                        const modal = document.getElementById('sendNotificationModal');
                        if (modal) modal.style.display = 'block';
                    }
                });
            }
        }

        // Delete user
        async function deleteUser(userId, userRole) {
            const actorRole = String('<?= $role ?? '' ?>').toLowerCase();
            if (String(userRole).toLowerCase() === 'superadmin') {
                showNotification('Cannot deactivate superadmin users.', 'error');
                return;
            }
            if (String(userRole).toLowerCase() === 'admin' && actorRole !== 'superadmin') {
                showNotification('Only superadmin users can deactivate admin accounts.', 'error');
                return;
            }

            const reason = await systemPrompt({
                title: 'Deactivate User',
                message: 'Deactivation reason (optional):',
                label: 'Reason',
                defaultValue: 'Deactivated by admin',
                placeholder: 'Optional',
                required: false,
                multiline: true,
            });
            if (reason === null) return;

            const ok = await systemConfirm('Deactivate this user? They will not be able to sign in, but their data will be kept.', 'Deactivate User');
            if (!ok) {
                return;
            }
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/deleteUser', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, reason })
                });
                const result = await response.json();
                if (result.success) {
                    showNotification('User deactivated successfully', 'success');
                    updateUserCount();
                    window.location.reload();
                } else {
                    showNotification(result.message || 'Error deactivating user', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        async function activateUser(userId, userRole) {
            const actorRole = String('<?= $role ?? '' ?>').toLowerCase();
            if (String(userRole).toLowerCase() === 'superadmin') {
                showNotification('Cannot activate superadmin users.', 'error');
                return;
            }
            if (String(userRole).toLowerCase() === 'admin' && actorRole !== 'superadmin') {
                showNotification('Only superadmin users can activate admin accounts.', 'error');
                return;
            }

            const ok = await systemConfirm('Activate this user account? They will be able to sign in again.', 'Activate User');
            if (!ok) {
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/activateUser', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                const result = await response.json();
                if (result.success) {
                    showNotification('User activated successfully', 'success');
                    updateUserCount();
                    window.location.reload();
                } else {
                    showNotification(result.message || 'Error activating user', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        function showNotification(message, type) {
            if (typeof window.showNotification === 'function' && window.showNotification !== showNotification) {
                window.showNotification(message, type || 'info');
                return;
            }
            const validTypes = ['success', 'error', 'warning', 'info'];
            const toastType = validTypes.includes(type) ? type : 'info';
            let stack = document.getElementById('toastStack');
            if (!stack) {
                stack = document.createElement('div');
                stack.id = 'toastStack';
                stack.className = 'toast-stack';
                document.body.appendChild(stack);
            }
            const el = document.createElement('div');
            el.className = `notification ${toastType}`;
            el.textContent = String(message || '').trim() || 'Notification';
            stack.appendChild(el);
            requestAnimationFrame(() => el.classList.add('is-visible'));
            setTimeout(() => {
                el.classList.remove('is-visible');
                el.classList.add('is-leaving');
                setTimeout(() => el.parentNode && el.remove(), 260);
            }, 3200);
        }

        function viewOrder(orderId) {
            showNotification('Order details modal will be implemented', 'info');
        }

        function exportUsers() {
            showNotification('Exporting users data...', 'info');
        }

        function exportTransactions() {
            showNotification('Exporting transaction data...', 'info');
        }

        function loadAnalytics() {
            document.getElementById('monthlyActiveUsers').textContent = '189';
            document.getElementById('platformGrowth').textContent = '12.5%';
            document.getElementById('userRetention').textContent = '87%';
            document.getElementById('customerSatisfaction').textContent = '94%';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('active', 'show');
            modal.style.display = '';
            document.body.style.overflow = 'auto';
        }

        // ============ SYSTEM DIALOG (NO BROWSER ALERT/CONFIRM/PROMPT) ============
        let __systemDialogResolver = null;

        function hideSystemDialog() {
            const modal = document.getElementById('systemDialogModal');
            if (!modal) return;
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showSystemDialog({ title = 'Notice', bodyHtml = '', actions = [], onClose = null } = {}) {
            const modal = document.getElementById('systemDialogModal');
            const titleEl = document.getElementById('systemDialogTitle');
            const bodyEl = document.getElementById('systemDialogBody');
            const footerEl = document.getElementById('systemDialogFooter');
            const closeBtn = document.getElementById('systemDialogCloseBtn');

            if (!modal || !titleEl || !bodyEl || !footerEl || !closeBtn) {
                // Fallback: do not use browser dialogs; use toast.
                showNotification(String(title || 'Notice') + ': ' + String(bodyHtml || ''), 'info');
                return Promise.resolve(null);
            }

            titleEl.textContent = String(title || 'Notice');
            bodyEl.innerHTML = bodyHtml || '';
            footerEl.innerHTML = '';

            // Ensure only one resolver is active.
            __systemDialogResolver = null;

            const cleanup = () => {
                closeBtn.onclick = null;
                modal.onclick = null;
                document.removeEventListener('keydown', onKeyDown);
            };

            const resolveWith = (val) => {
                const resolver = __systemDialogResolver;
                __systemDialogResolver = null;
                cleanup();
                hideSystemDialog();
                if (typeof resolver === 'function') resolver(val);
            };

            const onKeyDown = (e) => {
                if (e.key === 'Escape') {
                    if (typeof onClose === 'function') {
                        resolveWith(onClose());
                    } else {
                        resolveWith(null);
                    }
                }
            };

            closeBtn.onclick = () => {
                if (typeof onClose === 'function') {
                    resolveWith(onClose());
                } else {
                    resolveWith(null);
                }
            };

            // Click outside content closes.
            modal.onclick = (e) => {
                if (e.target === modal) {
                    if (typeof onClose === 'function') {
                        resolveWith(onClose());
                    } else {
                        resolveWith(null);
                    }
                }
            };

            actions.forEach(action => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = action.className || 'btn btn-secondary';
                btn.textContent = action.label || 'OK';
                btn.onclick = () => resolveWith(action.value);
                footerEl.appendChild(btn);
            });

            document.addEventListener('keydown', onKeyDown);

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            return new Promise(resolve => {
                __systemDialogResolver = resolve;
            });
        }

        function systemAlert(message, title = 'Notice') {
            const bodyHtml = `<p style="margin:0;line-height:1.5;color:var(--text-color);">${escapeHtml(String(message || ''))}</p>`;
            return showSystemDialog({
                title,
                bodyHtml,
                actions: [{ label: 'OK', value: true, className: 'btn btn-primary' }],
                onClose: () => true,
            });
        }

        function systemConfirm(message, title = 'Confirm') {
            const bodyHtml = `<p style="margin:0;line-height:1.5;color:var(--text-color);">${escapeHtml(String(message || ''))}</p>`;
            return showSystemDialog({
                title,
                bodyHtml,
                actions: [
                    { label: 'Cancel', value: false, className: 'btn btn-secondary' },
                    { label: 'Confirm', value: true, className: 'btn btn-primary' },
                ],
                onClose: () => false,
            }).then(val => Boolean(val));
        }

        function systemPrompt({ title = 'Input', message = '', label = '', placeholder = '', defaultValue = '', required = false, multiline = false } = {}) {
            const inputId = 'systemDialogPromptInput';
            const safeMsg = message ? `<p style="margin:0 0 10px;line-height:1.5;color:var(--text-color);">${escapeHtml(String(message))}</p>` : '';
            const safeLabel = label ? `<label for="${inputId}" style="display:block;margin:0 0 6px;font-weight:600;color:var(--text-color);">${escapeHtml(String(label))}</label>` : '';

            const inputHtml = multiline
                ? `<textarea id="${inputId}" class="form-control" rows="3" placeholder="${escapeHtml(String(placeholder || ''))}">${escapeHtml(String(defaultValue || ''))}</textarea>`
                : `<input id="${inputId}" class="form-control" type="text" value="${escapeHtml(String(defaultValue || ''))}" placeholder="${escapeHtml(String(placeholder || ''))}">`;

            const bodyHtml = `${safeMsg}${safeLabel}${inputHtml}${required ? `<div style="margin-top:6px;font-size:12px;color:var(--text-light);">This field is required.</div>` : ''}`;

            return showSystemDialog({
                title,
                bodyHtml,
                actions: [
                    { label: 'Cancel', value: null, className: 'btn btn-secondary' },
                    { label: 'OK', value: '__ok__', className: 'btn btn-primary' },
                ],
                onClose: () => null,
            }).then(val => {
                if (val !== '__ok__') return null;
                const input = document.getElementById(inputId);
                const value = input ? String(input.value || '').trim() : '';
                if (required && !value) return null;
                return value;
            });
        }

        // Verification functions
        function loadVerifications() {
            const tbody = document.getElementById('verificationsTableBody');
            let verifications = <?= json_encode($verifications ?? []) ?>;
            const pending = verifications.filter(u => u.verification_status === 'pending').length;
            const approved = verifications.filter(u => u.verification_status === 'approved').length;
            const rejected = verifications.filter(u => u.verification_status === 'rejected').length;
            document.getElementById('vPendingCount').textContent = pending;
            document.getElementById('vApprovedCount').textContent = approved;
            document.getElementById('vRejectedCount').textContent = rejected;
            const badge = document.getElementById('pending-badge');
            if (pending > 0) {
                badge.textContent = pending;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
            if (!verifications || verifications.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:#aaa;">No verifications found.</td></tr>`;
                return;
            }
            let html = '';
            verifications.forEach(user => {
                let statusClass = '';
                switch (user.verification_status) {
                    case 'pending': statusClass = 'badge-warning'; break;
                    case 'approved': statusClass = 'badge-success'; break;
                    case 'rejected': statusClass = 'badge-danger'; break;
                    default: statusClass = '';
                }
                const expectedDocs = user.role === 'farmer' ? ['NIC', 'Bank Details'] : ['Driving License', 'Vehicle Insurance', 'Revenue License'];
                const docsHtml = `<ul style="margin:0;padding-left:18px;font-size:12px;">${expectedDocs.map(d => `<li>${d}</li>`).join('')}</ul><div style="font-size:11px;color:#888;margin-top:4px;">${user.approved_docs ?? 0} approved / ${user.doc_count ?? 0} total</div>`;
                html += `
                    <tr>
                        <td>${user.user_id}</td>
                        <td><strong>${escapeHtml(user.name)}</strong></td>
                        <td style="font-size:13px;">${escapeHtml(user.email)}</td>
                        <td><span class="badge badge-${user.role === 'farmer' ? 'success' : 'warning'}">${user.role}</span></td>
                        <td><span class="badge ${statusClass}" style="text-transform:capitalize;">${user.verification_status}</span></td>
                        <td>
                        <button class="btn btn-sm btn-primary" onclick="openDocReview(${user.user_id})">Review</button>
                        ${user.verification_status === 'pending' ? `
                        <button class="btn btn-sm btn-danger" onclick="bulkReject(${user.user_id})" style="margin-left:4px;">Reject</button>
                        ` : ''}
                        </td>
                        </tr>
                        `;
            });
            tbody.innerHTML = html;
        }
        /* <button class="btn btn-sm btn-success" onclick="bulkApprove(${user.user_id})" style="margin-left:4px;">Approve</button> */
        /* <td>${docsHtml}</td> */

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Main openDocReview function - THE WORKING ONE
        async function openDocReview(userId) {
            _currentModalUserId = userId;
            const modal = document.getElementById('docReviewModal');
            const body = document.getElementById('docModalBody');

            if (!modal || !body) {
                console.error('Modal or body element not found');
                return;
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            body.innerHTML = '<p style="text-align:center;padding:2rem;color:#aaa;">Loading documents…</p>';

            try {
                // Use POST method with JSON body
                const res = await fetch(`<?= ROOT ?>/adminDashboard/getUserDocuments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await res.json();

                if (!data.success) {
                    body.innerHTML = `<p style="color:red;padding:1rem;">Error: ${data.message || 'Failed to load documents'}</p>`;
                    return;
                }

                if (!data.data || data.data.length === 0) {
                    body.innerHTML = '<p style="padding:1rem;color:#888;">No documents found for this user.</p>';
                    return;
                }

                const docs = data.data;
                const user = docs[0];
                document.getElementById('docModalTitle').textContent = `Documents — ${user.name} (${user.role})`;

                const docTypeLabels = {
                    nic: 'National Identity Card',
                    bank_details: 'Bank Account Details',
                    driving_license: 'Driving License',
                    vehicle_insurance: 'Vehicle Insurance Card',
                    vehicle_revenue_license: 'Vehicle Revenue License'
                };

                const statusIcon = {
                    pending: '',
                    approved: '',
                    rejected: ''
                };

                const html = docs.map(doc => {
                    const label = docTypeLabels[doc.doc_type] || doc.doc_type;
                    const isImg = /\.(jpg|jpeg|png|webp)$/i.test(doc.file_path);
                    const isPdf = /\.pdf$/i.test(doc.file_path);

                    const preview = isImg
                        ? `<img src="<?= ROOT ?>/${doc.file_path}" alt="${label}" style="max-width:100%;max-height:280px;border-radius:6px;border:1px solid #ddd;display:block;margin-bottom:10px;" />`
                        : isPdf
                            ? `<a href="<?= ROOT ?>/${doc.file_path}" target="_blank" class="btn btn-secondary btn-sm" style="margin-bottom:10px;">📄 Open PDF</a>`
                            : `<p style="color:#888;font-size:13px;">Preview not available</p>`;

                    const rejectionNote = doc.rejection_reason
                        ? `<div style="background:#fff0f0;border-left:3px solid #e74c3c;padding:8px 12px;border-radius:0 6px 6px 0;font-size:12px;color:#c0392b;margin-bottom:10px;">
                    <strong>Rejection reason:</strong> ${escapeHtml(doc.rejection_reason)}
                   </div>`
                        : '';

                    const statusBadge = doc.status === 'approved'
                        ? '<span style="background:#27ae60;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;">Approved</span>'
                        : doc.status === 'rejected'
                            ? '<span style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;">Rejected</span>'
                            : '<span style="background:#f39c12;color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;">Pending</span>';

                    return `
            <div style="border:1px solid #e8e8e8;border-radius:10px;padding:16px;margin-bottom:16px;background:#fff;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <span style="font-weight:600;font-size:14px;">${label}</span>
                        <span style="margin-left:8px;">${statusBadge}</span>
                    </div>
                    
                </div>
                ${rejectionNote}
                <div style="margin-top:12px;">
                    ${preview}
                </div>
                <div style="font-size:11px;color:#bbb;margin-top:12px;">
                    Uploaded: ${doc.created_at ? doc.created_at.substring(0, 16) : '—'}
                </div>
            </div>`;
                }).join('');

                const overallStatus = user.verification_status;
                const overallBtns = `
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:1rem;padding-top:1rem;border-top:2px solid #eee;">
            ${overallStatus !== 'approved' ? `<button class="btn btn-success" onclick="bulkApprove(${userId})">Verify Account</button>` : ''}
            ${overallStatus !== 'rejected' ? `<button class="btn btn-danger" onclick="bulkReject(${userId})">Reject Account</button>` : ''}
        </div>`;

                // Add status summary at the top
                const totalDocs = docs.length;
                const approvedDocs = docs.filter(d => d.status === 'approved').length;
                const pendingDocs = docs.filter(d => d.status === 'pending').length;
                const rejectedDocs = docs.filter(d => d.status === 'rejected').length;

                const summary = `
        <div style="background:#f8f9fa;padding:12px;border-radius:8px;margin-bottom:20px;display:flex;justify-content:space-around;text-align:center;display:none;">
            <div>
                <div style="font-size:20px;font-weight:bold;color:#27ae60;">${approvedDocs}</div>
                <div style="font-size:12px;color:#666;">Approved</div>
            </div>
            <div>
                <div style="font-size:20px;font-weight:bold;color:#f39c12;">${pendingDocs}</div>
                <div style="font-size:12px;color:#666;">Pending</div>
            </div>
            <div>
                <div style="font-size:20px;font-weight:bold;color:#e74c3c;">${rejectedDocs}</div>
                <div style="font-size:12px;color:#666;">Rejected</div>
            </div>
            <div>
                <div style="font-size:20px;font-weight:bold;color:#3498db;">${totalDocs}</div>
                <div style="font-size:12px;color:#666;">Total</div>
            </div>
        </div>`;

                body.innerHTML = summary + html + overallBtns;

            } catch (e) {
                console.error('Error in openDocReview:', e);
                body.innerHTML = '<p style="color:red;padding:1rem;">Failed to load documents. Please try again.</p>';
            }
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function closeDocModal() {
            document.getElementById('docReviewModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        async function reviewDoc(docId, action, reason = null) {
            try {
                const res = await fetch('<?= ROOT ?>/adminDashboard/reviewDocument', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ doc_id: docId, action, reason })
                });
                const data = await res.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                    if (_currentModalUserId) openDocReview(_currentModalUserId);
                    loadVerifications();
                } else {
                    showNotification(data.message || 'Action failed', 'error');
                }
            } catch (e) {
                showNotification('Network error', 'error');
            }
        }

        function promptReject(docId) {
            systemPrompt({
                title: 'Reject Document',
                message: 'Enter rejection reason (optional):',
                label: 'Reason',
                required: false,
                multiline: true,
            }).then(reason => {
                if (reason === null) return;
                reviewDoc(docId, 'reject', reason || null);
            });
        }

        async function bulkApprove(userId) {
            const ok = await systemConfirm('Approve this account? All pending documents will be marked approved.', 'Approve Account');
            if (!ok) return;
            const res = await fetch(`<?= ROOT ?>/adminDashboard/getUserDocuments/${userId}`);
            const data = await res.json();
            if (data.success) {
                for (const doc of data.data) {
                    if (doc.status === 'pending') {
                        await fetch('<?= ROOT ?>/adminDashboard/reviewDocument', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ doc_id: doc.id, action: 'approve', reason: null })
                        });
                    }
                }
            }
            showNotification('Account approved', 'success');
            closeDocModal();
            window.location.reload();
        }

        async function bulkReject(userId) {
            const reason = await systemPrompt({
                title: 'Reject Account',
                message: 'Reason for rejecting this account (required):',
                label: 'Reason',
                required: true,
                multiline: true,
            });
            if (!reason) {
                showNotification('A reason is required to reject an account.', 'warning');
                return;
            }
            const res = await fetch('<?= ROOT ?>/adminDashboard/setUserVerification', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, status: 'rejected' })
            });
            const data = await res.json();
            if (data.success) {
                showNotification('Account rejected', 'success');
                closeDocModal();
                window.location.reload();
            }
        }

        let currentVerificationFilter = 'pending';
        function setVerificationFilter(filter) {
            currentVerificationFilter = filter;

            const tabButtons = document.querySelectorAll('.v-tab-btn');
            tabButtons.forEach(btn => {
                if (btn.getAttribute('data-filter') === filter) {
                    btn.classList.add('active');
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-primary');
                } else {
                    btn.classList.remove('active');
                    btn.classList.add('btn-secondary');
                    btn.classList.remove('btn-primary');
                }
            });

            loadVerificationsWithFilter(filter);
        }

        function loadVerificationsWithFilter(filter) {
            const tbody = document.getElementById('verificationsTableBody');
            let verifications = <?= json_encode($verifications ?? []) ?>

            let filteredVerifications = [];
            if (filter === 'all')
                filteredVerifications = verifications;
            else
                filteredVerifications = verifications.filter(u => u.verification_status === filter);

            displayVerifications(filteredVerifications);
        }
        function displayVerifications(verifications) {
            const tbody = document.getElementById('verificationsTableBody');

            if (!verifications || verifications.lenght === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:2rem;color:#aaa;">No verifications found</td></tr>`;
                return;
            }

            let html = '';
            verifications.forEach(user => {
                let statusClass = '';
                switch (user.verification_status) {
                    case 'pending': statusClass = 'badge-warning'; break;
                    case 'approved': statusClass = 'badge-success'; break;
                    case 'rejected': statusClass = 'badge-danger'; break;
                    default: statusClass = '';
                }

                const expectedDocs = user.role === 'farmer'
                    ? ['NIC', 'Bank Details']
                    : ['Driving License', 'Vehicle Insurance', 'Revenue License'];

                const docsHtml = `<ul style="margin:0;padding-left:18px;font-size:12px;">${expectedDocs.map(d => `<li>${d}</li>`).join('')}</ul>
                         <div style="font-size:11px;color:#888;margin-top:4px;">${user.approved_docs ?? 0} approved / ${user.doc_count ?? 0} total</div>`;

                html += `
            <tr>
                <td>${user.user_id}</td>
                <td><strong>${escapeHtml(user.name)}</strong></td>
                <td style="font-size:13px;">${escapeHtml(user.email)}</td>
                <td><span class="badge badge-${user.role === 'farmer' ? 'success' : 'warning'}">${user.role}</span></td>
                <td><span class="badge ${statusClass}" style="text-transform:capitalize;">${user.verification_status}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="openDocReview(${user.user_id})">Review</button>
                    ${user.verification_status === 'pending' ? `
                    <button class="btn btn-sm btn-danger" onclick="bulkReject(${user.user_id})" style="margin-left:4px;">Reject</button>
                    ` : ''}
                </td>
            </tr>
        `;
            });
            tbody.innerHTML = html;
        }

        // ============ PRODUCTS TAB FUNCTIONS ============

        // Global variables for products
        window.allProducts = [];
        let productCategories = [];

        // Load products with filters
        async function loadProducts() {
            const tbody = document.getElementById('productsTableBody');

            // Show loading state
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">Loading products...</td>' + '</tr>';

            try {
                // Get filter values
                const search = document.getElementById('productSearch')?.value || '';
                const category = document.getElementById('categoryFilter')?.value || '';

                const response = await fetch('<?= ROOT ?>/adminDashboard/getProducts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        search: search,
                        category: category,
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    const msg = result.message || result.error || 'Failed to load products';
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Error: ${escapeHtml(String(msg))}</td></tr>`;
                    return;
                }

                // Update statistics
                if (result.stats) {
                    document.getElementById('totalProducts').textContent = result.stats.total_products || 0;
                    document.getElementById('outOfStockProducts').textContent = result.stats.out_of_stock || 0;
                }

                // Update category filter options if needed
                if (result.categories && result.categories.length > 0) {
                    const categorySelect = document.getElementById('categoryFilter');
                    const currentCategories = Array.from(categorySelect.options).map(opt => opt.value);

                    result.categories.forEach(cat => {
                        if (cat.category && !currentCategories.includes(cat.category)) {
                            const option = document.createElement('option');
                            option.value = cat.category;
                            option.textContent = cat.category.charAt(0).toUpperCase() + cat.category.slice(1);
                            categorySelect.appendChild(option);
                        }
                    });
                }

                window.allProducts = result.data;
                displayProducts(window.allProducts);

            } catch (error) {
                console.error('Error loading products:', error);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Failed to load products. Please try again.</td></tr>';
            }
        }

        // Display products in the table
        function displayProducts(products) {
            const tbody = document.getElementById('productsTableBody');

            if (!products || products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#aaa;">No products found.</td>' + '</tr>';
                return;
            }

            let html = '';
            products.forEach(product => {
                // Status badge class
                let statusClass = '';
                let statusText = '';
                switch (product.status) {
                    case 'active':
                        statusClass = 'badge-success';
                        statusText = 'Active';
                        break;
                    case 'inactive':
                        statusClass = 'badge-secondary';
                        statusText = 'Inactive';
                        break;
                    case 'pending':
                        statusClass = 'badge-warning';
                        statusText = 'Pending';
                        break;
                    case 'rejected':
                        statusClass = 'badge-danger';
                        statusText = 'Rejected';
                        break;
                    default:
                        statusClass = 'badge-secondary';
                        statusText = product.status || 'N/A';
                }

                // Stock badge
                let stockClass = product.stock > 0 ? 'badge-info' : 'badge-danger';
                let stockText = product.stock > 0 ? `In Stock (${product.stock})` : 'Out of Stock';

                // Product image
                const productImage = product.image
                    ? `<?= ROOT ?>/assets/images/products/${product.image}`
                    : `<?= ROOT ?>/assets/images/default-product.svg`;

                html += `
            <tr>
                <td style="text-align:center;">
                    <img src="${productImage}" alt="${escapeHtml(product.name)}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;" onerror="this.src='<?= ROOT ?>/assets/images/default-product.svg'">
                </td>
                <td>
                    <strong>${escapeHtml(product.name)}</strong><br>
                    <small style="color:#888;">${escapeHtml(product.description?.substring(0, 50) || 'No description')}${product.description?.length > 50 ? '...' : ''}</small>
                </td>
                <td>${escapeHtml(product.farmer_name)}</td>
                <td><span class="badge badge-primary">${escapeHtml(product.category)}</span></td>
                <td><strong>Rs. ${parseFloat(product.price).toLocaleString()}</strong></td>
                <td><span class="badge ${stockClass}">${stockText}</span></td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="viewProductDetails(${product.id})">View</button>
                    
                </td>
            </tr>
        `;
            });
            tbody.innerHTML = html;
        }
        /* 
        <button class="btn btn-sm btn-secondary" onclick="updateProductStatus(${product.id}, '${product.status}')">Update Status</button>
                            ${product.total_orders === 0 ? `<button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">Delete</button>` : ''} */

        // View product details
        async function viewProductDetails(productId) {
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/getProductDetails', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                });

                const result = await response.json();

                if (!result.success) {
                    await systemAlert(result.message || result.error || 'Failed to load product details', 'Product Details');
                    return;
                }

                const product = result.product;
                const orders = result.orders || [];

                let ordersHtml = '';
                if (orders.length > 0) {
                    ordersHtml = `
                <h4 style="margin-top:20px;">Recent Orders</h4>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Buyer</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${orders.map(order => `
                                <tr>
                                    <td>#${order.order_number || order.order_id}</td>
                                    <td>${escapeHtml(order.buyer_name)}</td>
                                    <td>${order.quantity}</td>
                                    <td>Rs. ${parseFloat(order.unit_price).toLocaleString()}</td>
                                    <td>Rs. ${(order.quantity * parseFloat(order.unit_price)).toLocaleString()}</td>
                                    <td>${order.order_status}</td>
                                    <td>${formatDate(order.order_date)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
                } else {
                    ordersHtml = '<p style="margin-top:20px;color:#888;">No orders found for this product.</p>';
                }

                const productImage = product.image
                    ? `<?= ROOT ?>/assets/images/products/${product.image}`
                    : `<?= ROOT ?>/assets/images/default-product.svg`;

                const modalHtml = `
            <div id="productDetailsModal" class="modal" style="display:flex;">
                <div class="modal-content" style="max-width:900px;width:95%;max-height:80vh;overflow-y:auto;">
                    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #eee;">
                        <h3>Product Details - ${escapeHtml(product.name)}</h3>
                        <button onclick="closeModal('productDetailsModal')" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
                    </div>
                    <div class="modal-body" style="padding:20px;">
                        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;margin-bottom:20px;">
                            <div style="text-align:center;">
                                <img src="${productImage}" 
                                     alt="${escapeHtml(product.name)}" 
                                     style="width:150px;height:150px;object-fit:cover;border-radius:10px;"
                                     onerror="this.src='<?= ROOT ?>/assets/images/default-product.svg'">
                            </div>
                            <div>
                                <h3>${escapeHtml(product.name)}</h3>
                                <p><strong>Farmer:</strong> ${escapeHtml(product.farmer_name)}</p>
                                <p><strong>Email:</strong> ${escapeHtml(product.farmer_email)}</p>
                                <p><strong>Phone:</strong> ${escapeHtml(product.farmer_phone || 'N/A')}</p>
                                <p><strong>Category:</strong> ${escapeHtml(product.category)}</p>
                                <p><strong>Price:</strong> Rs. ${parseFloat(product.price).toLocaleString()}</p>
                                <p><strong>Stock:</strong> ${product.quantity} units</p>
                                <p><strong>Status:</strong> ${product.status}</p>
                                <p><strong>Added on:</strong> ${formatDate(product.created_at)}</p>
                            </div>
                        </div>
                        <div>
                            <h4>Description</h4>
                            <p>${escapeHtml(product.description || 'No description available.')}</p>
                        </div>
                        ${ordersHtml}
                    </div>
                    <div class="modal-footer" style="padding:15px;text-align:right;border-top:1px solid #eee;">
                        <button class="btn btn-secondary" onclick="closeModal('productDetailsModal')">Close</button>
                    </div>
                </div>
            </div>
        `;

                // Remove existing modal if any
                const existingModal = document.getElementById('productDetailsModal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                document.body.style.overflow = 'hidden';

            } catch (error) {
                console.error('Error loading product details:', error);
                showNotification('Failed to load product details', 'error');
            }
        }

        // Update product status
        async function updateProductStatus(productId, currentStatus) {
            const statuses = ['active', 'inactive', 'pending', 'rejected'];
            const newStatus = await systemPrompt({
                title: 'Update Product Status',
                message: `Current status: ${currentStatus}\nEnter new status (${statuses.join(', ')}):`,
                label: 'New Status',
                placeholder: 'e.g. active',
                required: true,
            });

            if (!newStatus) return;

            if (!statuses.includes(newStatus.toLowerCase())) {
                showNotification(`Invalid status. Please use: ${statuses.join(', ')}`, 'warning');
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/updateProductStatus', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        status: newStatus.toLowerCase()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Product status updated successfully!', 'success');
                    loadProducts(); // Refresh the products list
                } else {
                    showNotification('Failed to update product status: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating product status:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Delete product
        async function deleteProduct(productId) {
            const ok = await systemConfirm('Are you sure you want to delete this product? This action cannot be undone.', 'Delete Product');
            if (!ok) {
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/deleteProduct', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Product deleted successfully!', 'success');
                    loadProducts(); // Refresh the products list
                } else {
                    showNotification('Failed to delete product: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting product:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Reset product filters
        function resetProductFilters() {
            document.getElementById('productSearch').value = '';
            document.getElementById('categoryFilter').value = '';
            loadProducts();
        }

        // Setup product filters with live filtering
        function setupProductFilters() {
            // Add filter container if not exists
            const productsSection = document.getElementById('products-section');
            if (productsSection && !document.querySelector('#products-section .product-filters')) {
                const filtersHtml = `
            <div class="filters product-filters" style="margin-top: var(--spacing-xl);">
                <div class="filters-row">
                    <div class="filter-group">
                        <label for="productSearch">Search Products</label>
                        <input type="text" id="productSearch" class="form-control" placeholder="Search by name, farmer, or description...">
                    </div>
                    <div class="filter-group">
                        <label for="categoryFilter">Category</label>
                        <select id="categoryFilter" class="form-control">
                            <option value="">All Categories</option>
                            <option value="vegetables">Vegetables</option>
                            <option value="fruits">Fruits</option>
                            <option value="grains">Grains</option>
                            <option value="dairy">Dairy</option>
                            <option value="meat">Meat</option>
                        </select>
                    </div>
                </div>
                <div class="filters-row" style="margin-top: 10px;">
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-secondary" onclick="resetProductFilters()">Reset Filters</button>
                    </div>
                </div>
            </div>
        `;

                // Insert filters after the stats
                const statsDiv = productsSection.querySelector('.dashboard-stats');
                if (statsDiv && !document.getElementById('productSearch')) {
                    statsDiv.insertAdjacentHTML('afterend', filtersHtml);
                }
            }

            const searchInput = document.getElementById('productSearch');
            const categorySelect = document.getElementById('categoryFilter');

            if (searchInput) {
                let timeout;
                searchInput.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => loadProducts(), 500);
                });
            }

            if (categorySelect) {
                categorySelect.addEventListener('change', () => loadProducts());
            }
        }

        // Update the products table headers to include image column
        function updateProductsTableHeaders() {
            const productsTable = document.querySelector('#products-section .table');
            if (productsTable) {
                const thead = productsTable.querySelector('thead');
                if (thead) {
                    thead.innerHTML = `
                <tr>
                    <th>Product Image</th>
                    <th>Product Name</th>
                    <th>Farmer</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            `;
                }
            }
        }

        // Add this to your initAdminDashboard function
        // Find the initAdminDashboard function and add loadProducts() and setupProductFilters()
        // The function should look like this (update it):
        /*
        function initAdminDashboard() {
            loadDashboardData();
            loadUsers();
            loadVerifications();
            loadOrders();
            loadProducts();  // Add this line
            setupOrderFilters();
            setupProductFilters();  // Add this line
            setupNavigation();
            setupForms();
            showSection('dashboard');
        }
        */

        // ============ VEHICLES TAB FUNCTIONS ============

        window.allVehicles = [];

        async function loadVehicles() {
            const tbody = document.getElementById('vehiclesTableBody');
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;">Loading vehicles...</td></tr>';

            try {
                const search = document.getElementById('vehicleSearch')?.value || '';
                const status = document.getElementById('vehicleStatusFilter')?.value || '';

                const response = await fetch('<?= ROOT ?>/adminDashboard/getVehicles', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ search, status })
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) {
                    const msg = result.message || result.error || response.statusText || 'Failed to load vehicles';
                    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:red;">Error: ${escapeHtml(String(msg))}</td></tr>`;
                    return;
                }

                window.allVehicles = result.data || [];
                displayVehicles(window.allVehicles);
            } catch (error) {
                console.error('Error loading vehicles:', error);
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:red;">Failed to load vehicles. Please try again.</td></tr>';
            }
        }

        function displayVehicles(vehicles) {
            const tbody = document.getElementById('vehiclesTableBody');
            if (!tbody) return;

            if (!vehicles || vehicles.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:#aaa;">No vehicles found.</td></tr>';
                return;
            }

            let html = '';
            vehicles.forEach(v => {
                const status = String(v.status || '').toLowerCase();
                const statusClass = status === 'active' ? 'badge-success' : (status === 'inactive' ? 'badge-secondary' : 'badge-secondary');
                const typeLabel = v.vehicle_type_name || v.type || 'N/A';

                html += `
                <tr>
                    <td><strong>${escapeHtml(String(v.id ?? ''))}</strong></td>
                    <td>
                        ${escapeHtml(String(v.transporter_name || 'N/A'))}<br>
                        <small style="color:#888;">${escapeHtml(String(v.transporter_email || ''))}</small>
                    </td>
                    <td>${escapeHtml(String(v.registration || 'N/A'))}</td>
                    <td>${escapeHtml(String(typeLabel))}</td>
                    <td>${escapeHtml(String(v.capacity ?? ''))}</td>
                    <td>${escapeHtml(String(v.fuel_type || ''))}</td>
                    <td>${escapeHtml(String(v.model || ''))}</td>
                    <td><span class="badge ${statusClass}" style="text-transform:capitalize;">${escapeHtml(status || 'n/a')}</span></td>
                    <td>${formatDate(v.created_at)}</td>
                </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        function setupVehicleFilters() {
            const searchEl = document.getElementById('vehicleSearch');
            const statusEl = document.getElementById('vehicleStatusFilter');

            if (searchEl) {
                let timeout;
                searchEl.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => loadVehicles(), 400);
                });
            }

            if (statusEl) {
                statusEl.addEventListener('change', () => loadVehicles());
            }
        }

        // ============ REVIEWS TAB FUNCTIONS ============

        window.allReviews = [];

        async function loadReviews() {
            const tbody = document.getElementById('reviewsTableBody');
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">Loading reviews...</td></tr>';

            try {
                const search = document.getElementById('reviewSearch')?.value || '';
                const target_role = document.getElementById('reviewTargetRoleFilter')?.value || '';
                const rating = document.getElementById('reviewRatingFilter')?.value || '';

                const response = await fetch('<?= ROOT ?>/adminDashboard/getReviews', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ search, target_role, rating })
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) {
                    const msg = result.message || result.error || response.statusText || 'Failed to load reviews';
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Error: ${escapeHtml(String(msg))}</td></tr>`;
                    return;
                }

                // Stats
                const stats = result.stats || {};
                const totalEl = document.getElementById('reviewsTotalCount');
                if (totalEl) totalEl.textContent = stats.total_reviews ?? 0;
                const avgEl = document.getElementById('reviewsAvgRating');
                if (avgEl) avgEl.textContent = stats.avg_rating ?? 0;
                const compEl = document.getElementById('reviewsComplaints');
                if (compEl) compEl.textContent = stats.complaints ?? 0;

                window.allReviews = result.data || [];
                displayReviews(window.allReviews);
            } catch (error) {
                console.error('Error loading reviews:', error);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Failed to load reviews. Please try again.</td></tr>';
            }
        }

        function displayReviews(reviews) {
            const tbody = document.getElementById('reviewsTableBody');
            if (!tbody) return;

            if (!reviews || reviews.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#aaa;">No reviews found.</td></tr>';
                return;
            }

            const stars = (n) => {
                const rating = Math.max(0, Math.min(5, Number(n || 0)));
                const filled = '★'.repeat(rating);
                const empty = '☆'.repeat(5 - rating);
                return filled + empty;
            };

            let html = '';
            reviews.forEach(r => {
                const buyer = r.buyer_name ? `${escapeHtml(r.buyer_name)}<br><small style="color:#888;">${escapeHtml(r.buyer_email || '')}</small>` : 'N/A';
                const targetRole = String(r.target_role || '').toLowerCase();
                const target = r.target_name ? `${escapeHtml(r.target_name)}<br><small style="color:#888;">${escapeHtml(targetRole || '')}</small>` : 'N/A';
                const product = escapeHtml(String(r.product_name || r.order_item_name || 'N/A'));
                const commentRaw = String(r.comment || '');
                const comment = commentRaw.length > 120 ? `${escapeHtml(commentRaw.slice(0, 120))}...` : escapeHtml(commentRaw || '—');
                const rating = Number(r.rating || 0);

                html += `
                    <tr>
                        <td><strong>${escapeHtml(String(r.id ?? ''))}</strong></td>
                        <td>${formatDate(r.created_at)}</td>
                        <td>${buyer}</td>
                        <td>${target}</td>
                        <td>${product}</td>
                        <td title="${rating}">${escapeHtml(stars(rating))}</td>
                        <td>${comment}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        function setupReviewFilters() {
            const searchEl = document.getElementById('reviewSearch');
            const roleEl = document.getElementById('reviewTargetRoleFilter');
            const ratingEl = document.getElementById('reviewRatingFilter');

            if (searchEl) {
                let timeout;
                searchEl.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => loadReviews(), 400);
                });
            }
            if (roleEl) roleEl.addEventListener('change', () => loadReviews());
            if (ratingEl) ratingEl.addEventListener('change', () => loadReviews());
        }

        // Analytics functions
        let revenueChart = null;
        let userGrowthChart = null;
        let categoryChart = null;

        async function loadAnalytics() {
            const period = document.getElementById('analyticsPeriod')?.value || 'month';

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/getAnalytics', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ period: period })
                });

                const result = await response.json();

                if (!result.success) {
                    console.error('Failed to load analytics:', result.message);
                    return;
                }

                const data = result.data;

                // Update KPIs
                const revenueEl = document.getElementById('analyticsTotalRevenue');
                if (revenueEl) revenueEl.textContent = `Rs. ${(data.order_stats?.total_revenue || 0).toLocaleString()}`;
                const ordersEl = document.getElementById('analyticsTotalOrders');
                if (ordersEl) ordersEl.textContent = data.order_stats?.total_orders || 0;
                const usersEl = document.getElementById('analyticsTotalUsers');
                if (usersEl) usersEl.textContent = data.user_stats?.total_users || 0;

                const revenueGrowth = data.growth_metrics?.revenue_growth || 0;
                const growthElement = document.getElementById('analyticsRevenueGrowth');
                if (growthElement) {
                    growthElement.textContent = `${revenueGrowth >= 0 ? '+' : ''}${revenueGrowth}%`;
                    growthElement.style.color = revenueGrowth >= 0 ? '#27ae60' : '#e74c3c';
                }

                // Update charts
                updateRevenueChart(data.monthly_revenue);
                updateUserGrowthChart(data.user_growth);
                updateCategoryChart(data.category_distribution);
                updateTopProducts(data.top_products);

            } catch (error) {
                console.error('Error loading analytics:', error);
            }
        }

        function updateRevenueChart(monthlyRevenue) {
            const ctx = document.getElementById('revenueChart')?.getContext('2d');
            if (!ctx) return;

            const labels = (monthlyRevenue || []).map(m => formatAnalyticsLabel(m.label ?? m.month ?? m.day));
            const revenues = (monthlyRevenue || []).map(m => parseFloat(m.revenue || 0));

            if (revenueChart) {
                revenueChart.destroy();
            }

            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue (Rs.)',
                        data: revenues,
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `Rs. ${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateUserGrowthChart(userGrowth) {
            const ctx = document.getElementById('userGrowthChart')?.getContext('2d');
            if (!ctx) return;

            const labels = (userGrowth || []).map(u => formatAnalyticsLabel(u.label ?? u.month ?? u.day));
            const newUsers = (userGrowth || []).map(u => parseInt(u.new_users || 0));
            const newFarmers = (userGrowth || []).map(u => parseInt(u.new_farmers || 0));
            const newBuyers = (userGrowth || []).map(u => parseInt(u.new_buyers || 0));

            if (userGrowthChart) {
                userGrowthChart.destroy();
            }

            userGrowthChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'New Farmers',
                            data: newFarmers,
                            backgroundColor: '#3498db',
                            borderRadius: 5
                        },
                        {
                            label: 'New Buyers',
                            data: newBuyers,
                            backgroundColor: '#e74c3c',
                            borderRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }

        function updateCategoryChart(categories) {
            const ctx = document.getElementById('categoryChart')?.getContext('2d');
            if (!ctx) return;

            const categoryNames = categories.map(c => c.category || 'Other');
            const productCounts = categories.map(c => parseInt(c.product_count || 0));

            if (categoryChart) {
                categoryChart.destroy();
            }

            const colors = ['#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c', '#e67e22', '#34495e'];

            categoryChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: categoryNames,
                    datasets: [{
                        data: productCounts,
                        backgroundColor: colors.slice(0, categoryNames.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const total = productCounts.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.raw / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.raw} products (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateTopProducts(products) {
            const tbody = document.getElementById('topProductsBody');
            if (!tbody) return;

            if (!products || products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No products found</td></tr>';
                return;
            }

            let html = '';
            products.slice(0, 5).forEach(product => {
                html += `
            <tr>
                <td>${escapeHtml(product.name)}</td>
                <td>${product.total_orders || 0}</td>
                <td><strong>Rs. ${parseFloat(product.total_revenue || 0).toLocaleString()}</strong></td>
            </tr>
        `;
            });
            tbody.innerHTML = html;
        }

        function refreshAnalytics() {
            loadAnalytics();
        }

        function formatAnalyticsLabel(label) {
            const val = String(label || '').trim();
            if (!val) return '';

            if (/^\\d{4}-\\d{2}-\\d{2}$/.test(val)) {
                const date = new Date(val + 'T00:00:00');
                if (!isNaN(date.getTime())) {
                    return date.toLocaleDateString('default', { month: 'short', day: 'numeric' });
                }
            }

            if (/^\\d{4}-\\d{2}$/.test(val)) {
                const date = new Date(val + '-01T00:00:00');
                if (!isNaN(date.getTime())) {
                    return date.toLocaleDateString('default', { month: 'short', year: '2-digit' });
                }
            }

            return val;
        }

        // Update the initAdminDashboard function to include analytics loading
        // Add this line inside initAdminDashboard after showSection:
        // if (typeof loadAnalytics === 'function') loadAnalytics();

        // ============ PAYMENTS TAB FUNCTIONS ============

        window.allPayments = [];

        // Load payments with filters
        async function loadPayments() {
            const tbody = document.getElementById('paymentsTableBody');

            // Show loading state
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">Loading payments...</td></tr>';

            try {
                // Get filter values
                const search = document.getElementById('paymentTxnSearch')?.value || '';
                const status = document.getElementById('paymentTxnStatusFilter')?.value || '';

                const response = await fetch('<?= ROOT ?>/adminDashboard/getPayments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        search: search,
                        status: status,
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    const msg = result.message || result.error || 'Failed to load payments';
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Error: ${escapeHtml(String(msg))}</td></tr>`;
                    return;
                }

                // Update statistics
                if (result.stats) {
                    const totalRevenueText = `Rs. ${(result.stats.total_revenue || 0).toLocaleString()}`;
                    document.getElementById('totalRevenue').textContent = totalRevenueText;
                    const dashRevenueEl = document.getElementById('dashboardTotalRevenue');
                    if (dashRevenueEl) dashRevenueEl.textContent = totalRevenueText;
                    
                    const dashCommEl = document.getElementById('dashboardPlatformCommission');
                    if (dashCommEl) dashCommEl.textContent = `Rs. ${(result.stats.platform_commission || 0).toLocaleString()}`;
                    
                    document.getElementById('totalTransactions').textContent = result.stats.total_transactions || 0;
                    document.getElementById('platformCommission').textContent = `Rs. ${(result.stats.platform_commission || 0).toLocaleString()}`;
                }

                window.allPayments = result.data;
                displayPayments(window.allPayments);

            } catch (error) {
                console.error('Error loading payments:', error);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:red;">Failed to load payments. Please try again.</td></tr>';
            }
        }

        // Display payments in the table
        function displayPayments(payments) {
            const tbody = document.getElementById('paymentsTableBody');

            if (!payments || payments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#aaa;">No payments found.</td></tr>';
                return;
            }

            let html = '';
            payments.forEach(payment => {
                // Status badge class - using order status
                let statusClass = '';
                let statusText = '';
                switch (payment.payment_status) {
                    case 'completed':
                        statusClass = 'badge-success';
                        statusText = 'Completed';
                        break;
                    case 'delivered':
                        statusClass = 'badge-success';
                        statusText = 'Delivered';
                        break;
                    case 'shipped':
                        statusClass = 'badge-info';
                        statusText = 'Shipped';
                        break;
                    case 'pending':
                        statusClass = 'badge-warning';
                        statusText = 'Pending';
                        break;
                    case 'cancelled':
                        statusClass = 'badge-danger';
                        statusText = 'Cancelled';
                        break;
                    default:
                        statusClass = 'badge-secondary';
                        statusText = payment.payment_status || 'N/A';
                }

                html += `
            <tr>
                <td><strong>${payment.transaction_id || payment.payment_id}</strong></td>
                <td>#${payment.order_id}</td>
                <td>${escapeHtml(payment.buyer_name || 'N/A')}</td>
                <td><strong>Rs. ${parseFloat(payment.amount).toLocaleString()}</strong></td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>${formatDate(payment.payment_date)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary" onclick="viewPaymentDetails(${payment.payment_id})">View</button>
                    ${payment.payment_status === 'pending' ? `<button type="button" class="btn btn-sm btn-success" onclick="updatePaymentStatus(${payment.payment_id}, 'shipped')">Mark Shipped</button>` : ''}
                </td>
            </tr>
        `;
            });
            tbody.innerHTML = html;
        }
        /* ${payment.payment_status === 'shipped' ? `<button class="btn btn-sm btn-success" onclick="updatePaymentStatus(${payment.payment_id}, 'delivered')">Mark Delivered</button>` : ''}
        ${payment.payment_status !== 'cancelled' && payment.payment_status !== 'delivered' ? `<button class="btn btn-sm btn-danger" onclick="refundPayment(${payment.payment_id})">Cancel Order</button>` : ''} */
        // View payment details
        async function viewPaymentDetails(paymentId) {
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/getPaymentDetails', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ payment_id: paymentId })
                });

                const result = await response.json();

                if (!result.success) {
                    await systemAlert(result.message || result.error || 'Failed to load payment details', 'Payment Details');
                    return;
                }

                const payment = result.payment;

                const modalHtml = `
            <div id="paymentDetailsModalInner" class="modal" style="display:flex;">
                <div class="modal-content" style="max-width:600px;width:95%;">
                    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #eee;">
                        <h3>Payment Details - #${payment.transaction_id || payment.id}</h3>
                        <button onclick="closeModal('paymentDetailsModalInner')" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
                    </div>
                    <div class="modal-body" style="padding:20px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div>
                                <p><strong>Transaction ID:</strong></p>
                                <p>${payment.transaction_id || 'N/A'}</p>
                                
                                <p><strong>Order ID:</strong></p>
                                <p>#${payment.order_number || payment.order_id}</p>
                                
                                <p><strong>Amount:</strong></p>
                                <p><strong>Rs. ${parseFloat(payment.amount).toLocaleString()}</strong></p>
                                
                                <p><strong>Payment Method:</strong></p>
                                <p>${payment.payment_method}</p>
                            </div>
                            <div>
                                <p><strong>Status:</strong></p>
                                <p><span class="badge badge-${payment.payment_status === 'completed' ? 'success' : payment.payment_status === 'pending' ? 'warning' : 'danger'}">${payment.payment_status}</span></p>
                                
                                <p><strong>Buyer Name:</strong></p>
                                <p>${escapeHtml(payment.buyer_name)}</p>
                                
                                <p><strong>Buyer Email:</strong></p>
                                <p>${escapeHtml(payment.buyer_email)}</p>
                                
                                <p><strong>Payment Date:</strong></p>
                                <p>${formatDate(payment.payment_date || payment.created_at)}</p>
                            </div>
                        </div>
                        ${payment.refund_reason ? `
                            <div style="margin-top:15px;padding:10px;background:#fff0f0;border-radius:5px;">
                                <p><strong>Refund Reason:</strong></p>
                                <p>${escapeHtml(payment.refund_reason)}</p>
                                <p><strong>Refund Date:</strong> ${formatDate(payment.refund_date)}</p>
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer" style="padding:15px;text-align:right;border-top:1px solid #eee;">
                        <button class="btn btn-secondary" onclick="closeModal('paymentDetailsModalInner')">Close</button>
                    </div>
                </div>
            </div>
        `;

                // Remove existing modal if any
                const existingModal = document.getElementById('paymentDetailsModalInner');
                if (existingModal) {
                    existingModal.remove();
                }

                document.body.insertAdjacentHTML('beforeend', modalHtml);
                document.body.style.overflow = 'hidden';

            } catch (error) {
                console.error('Error loading payment details:', error);
                showNotification('Failed to load payment details', 'error');
            }
        }

        // Update payment status
        async function updatePaymentStatus(paymentId, status) {
            const ok = await systemConfirm(`Are you sure you want to mark this payment as ${status}?`, 'Update Payment Status');
            if (!ok) {
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/updatePaymentStatus', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        status: status
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Payment status updated successfully!', 'success');
                    loadPayments(); // Refresh the payments list
                } else {
                    showNotification('Failed to update payment status: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating payment status:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Refund payment
        async function refundPayment(paymentId) {
            const reason = await systemPrompt({
                title: 'Refund Payment',
                message: 'Enter refund reason:',
                label: 'Reason',
                required: true,
                multiline: true,
            });
            if (!reason) return;

            const ok = await systemConfirm('Are you sure you want to refund this payment?', 'Refund Payment');
            if (!ok) {
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/refundPayment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        reason: reason
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Payment refunded successfully!', 'success');
                    loadPayments(); // Refresh the payments list
                } else {
                    showNotification('Failed to refund payment: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error refunding payment:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Reset payment filters
        function resetPaymentFilters() {
            document.getElementById('paymentTxnSearch').value = '';
            document.getElementById('paymentTxnStatusFilter').value = '';
            loadPayments();
        }

        // Export payments to CSV
        async function exportPayments() {
            const rows = window.allPayments || [];
            if (!rows || rows.length === 0) {
                await systemAlert('No data to export', 'Export');
                return;
            }

            let csv = 'Transaction ID,Order ID,Buyer,Amount,Status,Date\n';

            rows.forEach(payment => {
                csv += `"${payment.transaction_id || payment.payment_id}","${payment.order_number || payment.order_id}","${payment.buyer_name}",${payment.amount},"${payment.payment_status}","${payment.payment_date || payment.created_at}"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `payments_export_${new Date().toISOString().slice(0, 19)}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Setup payment filters
        function setupPaymentFilters() {
            const filters = ['paymentTxnSearch', 'paymentTxnStatusFilter'];

            filters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    if (filterId === 'paymentTxnSearch') {
                        let timeout;
                        element.addEventListener('input', () => {
                            clearTimeout(timeout);
                            timeout = setTimeout(() => loadPayments(), 500);
                        });
                    } else {
                        element.addEventListener('change', () => loadPayments());
                    }
                }
            });
        }

        // ============ DISPUTES TAB FUNCTIONS ============

        window.allDisputes = [];

        // Load disputes with filters
        async function loadDisputes() {
            const tbody = document.getElementById('cancelledOrdersDisputesTableBody');

            // Show loading state
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;">Loading cancelled orders...</td></tr>';

            try {
                // Get filter values
                const search = document.getElementById('cancelledOrderSearch')?.value || '';
                const revision_status = document.getElementById('revisionStatusFilter')?.value || '';
                const payment_method = document.getElementById('cancelledOrderPaymentMethod')?.value || '';

                const response = await fetch('<?= ROOT ?>/adminDashboard/getCancelledOrdersDisputes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        search: search,
                        revision_status: revision_status,
                        payment_method: payment_method
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    const msg = result.message || result.error || 'Failed to load cancelled orders';
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:2rem;color:red;">Error: ${escapeHtml(String(msg))}</td></tr>`;
                    return;
                }

                // Update statistics
                if (result.stats) {
                    const totalEl = document.getElementById('totalCancelledOrders');
                    if (totalEl) totalEl.textContent = result.stats.total_cancelled || 0;
                    const unrevisedEl = document.getElementById('unrevisedCancelledOrders');
                    if (unrevisedEl) unrevisedEl.textContent = result.stats.unrevised || 0;
                    const revisedEl = document.getElementById('revisedCancelledOrders');
                    if (revisedEl) revisedEl.textContent = result.stats.revised || 0;

                    // Keep legacy counters safe if they still exist in the markup.
                    const legacyIds = ['highPriorityDisputes', 'mediumPriorityDisputes', 'lowPriorityDisputes', 'orderIssues', 'paymentIssues', 'deliveryIssues', 'qualityIssues'];
                    legacyIds.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = 0;
                    });
                }

                window.allDisputes = result.data;
                displayCancelledOrdersDisputes(window.allDisputes);

            } catch (error) {
                console.error('Error loading disputes:', error);
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:red;">Failed to load cancelled orders. Please try again.</td></tr>';
            }
        }

        function formatMoney(value) {
            const num = Number(value || 0);
            return `Rs. ${num.toFixed(2)}`;
        }

        function paymentMethodLabel(method) {
            const val = String(method || '').toLowerCase();
            return val === 'card' ? 'Credit/Debit Card' :
                val === 'bank_transfer' ? 'Bank Transfer' :
                    val === 'cod' ? 'Cash on Delivery' :
                        val.charAt(0).toUpperCase() + val.slice(1);
        }

        async function loadNotificationStats() {
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/getNotificationStats');
                const data = await response.json();
                if (!response.ok || !data.success) {
                    console.warn('Failed to load notification stats:', data.message || response.statusText);
                    return;
                }

                const totalEl = document.getElementById('totalNotifications');
                const deliveredEl = document.getElementById('deliveredNotifications');
                const openRateEl = document.getElementById('openRate');
                const clickRateEl = document.getElementById('clickRate');

                if (totalEl) totalEl.textContent = data.total_sent ?? 0;
                if (deliveredEl) deliveredEl.textContent = data.delivered ?? 0;
                if (openRateEl) openRateEl.textContent = `${data.open_rate ?? 0}%`;
                if (clickRateEl) clickRateEl.textContent = `${data.click_rate ?? 0}%`;
            } catch (error) {
                console.error('Error loading notification stats:', error);
            }
        }

        async function loadNotificationSummary() {
            const tbody = document.getElementById('notificationsSummaryBody');
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--text-light);">Loading notifications...</td></tr>';

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/getNotificationSummary', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                });

                const contentType = String(response.headers.get('content-type') || '');
                let rawText = '';
                let result = null;

                if (contentType.includes('application/json')) {
                    result = await response.json().catch(() => null);
                } else {
                    rawText = await response.text().catch(() => '');
                    try {
                        result = rawText ? JSON.parse(rawText) : null;
                    } catch (e) {
                        result = null;
                    }
                }

                if (!result || typeof result !== 'object') {
                    const preview = rawText ? rawText.slice(0, 220) : '';
                    const msg = preview ? `Invalid JSON response: ${preview}` : `Invalid server response (HTTP ${response.status})`;
                    tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--danger);">Error: ${escapeHtml(String(msg))}</td></tr>`;
                    return;
                }

                if (!response.ok || result.success !== true) {
                    const msg = result.message || result.error || `Request failed (HTTP ${response.status})`;
                    tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--danger);">Error: ${escapeHtml(String(msg))}</td></tr>`;
                    return;
                }

                const rows = Array.isArray(result.data) ? result.data : [];
                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--text-light);">No notifications found.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map(n => {
                    const type = String(n.type || 'system');
                    const badgeClass = type === 'alert' ? 'badge-danger'
                        : type === 'maintenance' ? 'badge-warning'
                            : type === 'promotion' ? 'badge-info'
                                : 'badge-secondary';

                    const msg = String(n.message || '');
                    const preview = msg.length > 100 ? msg.slice(0, 100) + '...' : msg;

                    return `
                        <tr>
                            <td>${formatDate(n.sent_at)}</td>
                            <td>
                                <strong>${escapeHtml(String(n.title || ''))}</strong><br>
                                <small style="color:var(--text-light);">${escapeHtml(preview)}</small>
                            </td>
                            <td><span class="badge ${badgeClass}" style="text-transform:capitalize;">${escapeHtml(type)}</span></td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error loading notification summary:', error);
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--danger);">Failed to load notifications. Please try again.</td></tr>';
            }
        }

        // Display cancelled orders in the table
        function displayCancelledOrdersDisputes(rows) {
            const tbody = document.getElementById('cancelledOrdersDisputesTableBody');
            if (!tbody) return;

            if (!rows || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#aaa;">No cancelled orders found.</td></tr>';
                return;
            }

            let html = '';
            rows.forEach(order => {
                const hasRevision = !!order.revised_at;
                const revisedTotal = hasRevision ? formatMoney(order.revised_total_amount) : '-';
                const revisedBy = hasRevision ? escapeHtml(order.revised_by_name || 'Admin') : '-';
                const updatedAt = formatDate(order.updated_at || order.created_at);

                // Status badge styling
                let rowClass = '';
                let statusBadge = '';
                if (hasRevision) {
                    rowClass = 'style="background-color:#f0fdf4;border-left:4px solid #22c55e;"';
                    statusBadge = '<span style="display:inline-block;background:#22c55e;color:#fff;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600;">✓ REVISED</span>';
                }

                html += `
                    <tr ${rowClass}>
                        <td><strong>#${escapeHtml(order.order_id)}</strong> ${statusBadge}</td>
                        <td>${escapeHtml(order.buyer_name || 'Buyer')}<br><small>${escapeHtml(order.buyer_email || '')}</small></td>
                        <td>${escapeHtml(paymentMethodLabel(order.payment_method))}</td>
                        <td>${formatMoney(order.order_total)}</td>
                        <td>${hasRevision ? `<strong>${revisedTotal}</strong>` : '<span style="color:#999;">-</span>'}</td>
                        <td>${hasRevision ? `<small>${revisedBy}</small>` : '<span style="color:#999;">-</span>'}</td>
                        <td>${updatedAt}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="viewCancelledOrderDisputeDetails(${Number(order.order_id)})">
                                ${hasRevision ? 'View Revision' : 'Revise Payment'}
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // View cancelled order details and revise payment totals
        async function viewCancelledOrderDisputeDetails(orderId) {
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/getCancelledOrderDisputeDetails', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });

                const result = await response.json();
                if (!result.success) {
                    showNotification(result.message || 'Failed to load order details', 'error');
                    return;
                }

                const order = result.order || {};
                const items = Array.isArray(result.items) ? result.items : [];
                const revision = result.revision || null;
                const payoutAccount = result.payout_account || null;

                const itemsHtml = items.length
                    ? `
                        <h4 style="margin-top: 18px;">Order Items</h4>
                        <div class="table-container" style="margin-top:10px;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Farmer</th>
                                        <th>Unit Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map(it => `
                                        <tr>
                                            <td>${escapeHtml(it.product_name || '')}</td>
                                            <td>${escapeHtml(it.quantity || '')}</td>
                                            <td>${escapeHtml(it.farmer_name || '')}</td>
                                            <td>${formatMoney(it.product_price || 0)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `
                    : '<p style="margin-top:18px;color:#888;">No order items found.</p>';

                const revisionHtml = revision && revision.revised_at
                    ? `
                        <div style="margin-top:18px;padding:16px;border:2px solid #22c55e;border-radius:10px;background:#f0fdf4;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                                <span style="font-size:20px;">✓</span>
                                <h4 style="margin:0;color:#22c55e;">Payment Revision Applied</h4>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
                                <div>
                                    <p style="margin:0;font-size:11px;color:#666;text-transform:uppercase;">Original Total</p>
                                    <p style="margin:4px 0 0;font-size:13px;font-weight:600;color:#999;text-decoration:line-through;">${formatMoney(revision.original_total_amount)}</p>
                                </div>
                                <div>
                                    <p style="margin:0;font-size:11px;color:#666;text-transform:uppercase;">Revised Total</p>
                                    <p style="margin:4px 0 0;font-size:16px;font-weight:700;color:#22c55e;">${formatMoney(revision.revised_total_amount)}</p>
                                </div>
                                <div>
                                    <p style="margin:0;font-size:11px;color:#666;text-transform:uppercase;">Difference</p>
                                    <p style="margin:4px 0 0;font-size:13px;font-weight:600;color:#f59e0b;">
                                        ${formatMoney(Math.abs(revision.revised_total_amount - revision.original_total_amount))}
                                        <small>(${revision.revised_total_amount < revision.original_total_amount ? '↓ Reduced' : '↑ Increased'})</small>
                                    </p>
                                </div>
                            </div>
                            <div style="border-top:1px solid #d1fae5;padding-top:12px;">
                                <p style="margin:0 0 4px;font-size:11px;color:#666;text-transform:uppercase;">Revised By:</p>
                                <p style="margin:0;font-size:13px;font-weight:600;color:#059669;">${escapeHtml(revision.revised_by_name || 'Admin')}</p>
                                <p style="margin:4px 0 0;font-size:11px;color:#666;">${formatDate(revision.revised_at)}</p>
                                <p style="margin:8px 0 0;font-size:13px;color:#333;"><strong>Reason:</strong> ${escapeHtml(revision.reason || 'No reason provided')}</p>
                            </div>
                        </div>
                    `
                    : '';

                const modalHtml = `
                    <div id="cancelledOrderDisputeModal" class="modal" style="display:flex;">
                        <div class="modal-content" style="max-width:860px;width:95%;max-height:80vh;overflow-y:auto;">
                            <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #eee;">
                                <h3>Cancelled Order #${escapeHtml(order.id)}</h3>
                                <button type="button" onclick="closeModal('cancelledOrderDisputeModal')" style="background:none;border:none;font-size:20px;cursor:pointer;">X</button>
                            </div>
                            <div class="modal-body" style="padding:20px;">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                    <div>
                                        <p><strong>Buyer:</strong> ${escapeHtml(order.buyer_name || '')} <small style="color:#666;">(${escapeHtml(order.buyer_email || '')})</small></p>
                                        <p><strong>City:</strong> ${escapeHtml(order.delivery_city || '')}</p>
                                        <p><strong>Payment Method:</strong> ${escapeHtml(paymentMethodLabel(order.payment_method))}</p>
                                        <p><strong>Status:</strong> ${escapeHtml(order.status || '')}</p>
                                    </div>
                                    <div>
                                        <p><strong>Product Total:</strong> ${formatMoney(order.total_amount)}</p>
                                        <p><strong>Shipping Cost:</strong> ${formatMoney(order.shipping_cost)}</p>
                                        <p><strong>Order Total:</strong> ${formatMoney(order.order_total)}</p>
                                        <p><strong>Cancelled / Updated:</strong> ${formatDate(order.updated_at || order.created_at)}</p>
                                    </div>
                                </div>

                                ${payoutAccount ? `
                                    <div style="margin-top:18px;padding:16px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                                        <h4 style="margin:0 0 12px 0;color:#334155;font-size:15px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">Buyer Bank Details (Refund Destination)</h4>
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                            <div>
                                                <p style="margin:0 0 4px;font-size:12px;color:#64748b;">Account Holder Name</p>
                                                <p style="margin:0;font-size:14px;font-weight:500;color:#0f172a;">${escapeHtml(payoutAccount.account_holder_name)}</p>
                                            </div>
                                            <div>
                                                <p style="margin:0 0 4px;font-size:12px;color:#64748b;">Account Number</p>
                                                <p style="margin:0;font-size:14px;font-weight:500;color:#0f172a;">${escapeHtml(payoutAccount.account_number)}</p>
                                            </div>
                                            <div>
                                                <p style="margin:0 0 4px;font-size:12px;color:#64748b;">Bank Name</p>
                                                <p style="margin:0;font-size:14px;font-weight:500;color:#0f172a;">${escapeHtml(payoutAccount.bank_name)}</p>
                                            </div>
                                            <div>
                                                <p style="margin:0 0 4px;font-size:12px;color:#64748b;">Branch Name</p>
                                                <p style="margin:0;font-size:14px;font-weight:500;color:#0f172a;">${payoutAccount.branch_name ? escapeHtml(payoutAccount.branch_name) : '<span style="color:#94a3b8;font-style:italic;">Not provided</span>'}</p>
                                            </div>
                                        </div>
                                    </div>
                                ` : `
                                    <div style="margin-top:18px;padding:14px;border:1px solid #fef08a;border-radius:8px;background:#fef9c3;">
                                        <p style="margin:0;font-size:13px;color:#854d0e;">
                                            <span style="font-weight:600;">⚠️ No bank details found for this buyer.</span> 
                                            They may need to add a payout account before a refund can be processed.
                                        </p>
                                    </div>
                                `}

                                ${revisionHtml}

                                ${revision && revision.revised_at ? `
                                    <div style="margin-top:18px;padding:14px;border:1px solid #e5e7eb;border-radius:8px;background:#f3f4f6;">
                                        <p style="margin:0;font-size:13px;color:#666;">
                                            <span style="color:#6366f1;font-weight:600;">ℹ</span> This payment has already been revised. 
                                            The amounts shown above reflect the current revised values.
                                        </p>
                                    </div>
                                ` : `
                                    <h4 style="margin-top:18px;">Revise Payment Totals</h4>
                                    <div class="grid grid-2" style="margin-top:10px;">
                                        <div class="form-group">
                                            <label for="revTotalAmount">Revised Product Total</label>
                                            <input type="number" step="0.01" min="0" id="revTotalAmount" class="form-control" value="${Number(order.total_amount || 0).toFixed(2)}">
                                        </div>
                                        <div class="form-group">
                                            <label for="revShippingCost">Revised Shipping Cost</label>
                                            <input type="number" step="0.01" min="0" id="revShippingCost" class="form-control" value="${Number(order.shipping_cost || 0).toFixed(2)}">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="revReason">Reason *</label>
                                        <textarea id="revReason" class="form-control" rows="3" placeholder="Explain why the payment totals are being revised..."></textarea>
                                    </div>
                                `}

                                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;z-index:1000;">
                                    ${revision && revision.revised_at ? `
                                        <button class="btn btn-secondary" onclick="closeModal('cancelledOrderDisputeModal')">Close</button>
                                    ` : `
                                        <button class="btn btn-primary" onclick="applyPaymentRevision(${Number(order.id)})">Apply Revision</button>
                                        <button class="btn btn-secondary" onclick="closeModal('cancelledOrderDisputeModal')">Cancel</button>
                                    `}
                                </div>

                                ${itemsHtml}
                            </div>
                        </div>
                    </div>
                `;

                const existingModal = document.getElementById('cancelledOrderDisputeModal');
                if (existingModal) existingModal.remove();

                document.body.insertAdjacentHTML('beforeend', modalHtml);
                document.body.style.overflow = 'hidden';

            } catch (error) {
                console.error('Error loading cancelled order details:', error);
                showNotification('Failed to load order details', 'error');
            }
        }

        async function applyPaymentRevision(orderId) {
            const totalEl = document.getElementById('revTotalAmount');
            const shipEl = document.getElementById('revShippingCost');
            const reasonEl = document.getElementById('revReason');

            const revised_total_amount = Number(totalEl?.value || 0);
            const revised_shipping_cost = Number(shipEl?.value || 0);
            const reason = String(reasonEl?.value || '').trim();

            if (!reason) {
                showNotification('Reason is required.', 'warning');
                return;
            }

            if (Number.isNaN(revised_total_amount) || Number.isNaN(revised_shipping_cost) || revised_total_amount < 0 || revised_shipping_cost < 0) {
                showNotification('Please enter valid non-negative amounts.', 'warning');
                return;
            }

            const ok = await systemConfirm('Apply this payment revision to the cancelled order?', 'Apply Revision');
            if (!ok) {
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/reviseCancelledOrderPayment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, revised_total_amount, revised_shipping_cost, reason })
                });

                const result = await response.json();
                if (!result.success) {
                    showNotification(result.message || 'Failed to apply revision', 'error');
                    return;
                }

                // Show success message
                showNotification('✓ Payment revision applied successfully', 'success');

                // Reload the list after a short delay to show the updated status
                setTimeout(() => {
                    closeModal('cancelledOrderDisputeModal');
                    loadDisputes();
                }, 500);
            } catch (error) {
                console.error('Error applying payment revision:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Display disputes in the table
        function displayDisputes(disputes) {
            const tbody = document.getElementById('disputesTableBody');

            if (!disputes || disputes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:#aaa;">No disputes found.</td>' + '</tr>';
                return;
            }

            let html = '';
            disputes.forEach(dispute => {
                // Priority badge
                let priorityClass = '';
                let priorityIcon = '';
                switch (dispute.priority) {
                    case 'high':
                        priorityClass = 'badge-danger';
                        priorityIcon = '🔴';
                        break;
                    case 'medium':
                        priorityClass = 'badge-warning';
                        priorityIcon = '🟡';
                        break;
                    case 'low':
                        priorityClass = 'badge-info';
                        priorityIcon = '🔵';
                        break;
                    default:
                        priorityClass = 'badge-secondary';
                        priorityIcon = '⚪';
                }

                // Status badge
                let statusClass = '';
                let statusText = '';
                switch (dispute.status) {
                    case 'open':
                        statusClass = 'badge-danger';
                        statusText = 'Open';
                        break;
                    case 'in_progress':
                        statusClass = 'badge-warning';
                        statusText = 'In Progress';
                        break;
                    case 'resolved':
                        statusClass = 'badge-success';
                        statusText = 'Resolved';
                        break;
                    case 'closed':
                        statusClass = 'badge-secondary';
                        statusText = 'Closed';
                        break;
                    default:
                        statusClass = 'badge-secondary';
                        statusText = dispute.status || 'N/A';
                }

                // Type display
                let typeText = '';
                switch (dispute.type) {
                    case 'order_issue': typeText = 'Order Issue'; break;
                    case 'payment_issue': typeText = 'Payment Issue'; break;
                    case 'delivery_issue': typeText = 'Delivery Issue'; break;
                    case 'product_quality': typeText = 'Product Quality'; break;
                    default: typeText = dispute.type || 'N/A';
                }

                html += `
            <tr>
                <td><strong>#${dispute.dispute_id}</strong></td>
                <td>#${dispute.order_number}</td>
                <td>${escapeHtml(dispute.complainant_name)}<br><small>(${dispute.complainant_role})</small></td>
                <td>${escapeHtml(dispute.respondent_name)}<br><small>(${dispute.respondent_role})</small></td>
                <td>${typeText}</td>
                <td><span class="badge ${priorityClass}">${priorityIcon} ${dispute.priority ? dispute.priority.charAt(0).toUpperCase() + dispute.priority.slice(1) : 'N/A'}</span></td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>${formatDate(dispute.created_at)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewDisputeDetails(${dispute.dispute_id})">View</button>
                    ${dispute.status !== 'resolved' && dispute.status !== 'closed' ? `<button class="btn btn-sm btn-success" onclick="resolveDispute(${dispute.dispute_id})">Resolve</button>` : ''}
                </td>
            </tr>
        `;
            });
            tbody.innerHTML = html;
        }

        // View dispute details
        async function viewDisputeDetails(disputeId) {
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/getDisputeDetails', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ dispute_id: disputeId })
                });

                const result = await response.json();

                if (!result.success) {
                    showNotification('Failed to load dispute details', 'error');
                    return;
                }

                const dispute = result.dispute;
                const messages = result.messages || [];

                // Create messages HTML
                let messagesHtml = '';
                if (messages.length > 0) {
                    messagesHtml = '<h4>Conversation History</h4><div style="max-height:300px;overflow-y:auto;margin-top:10px;">';
                    messages.forEach(msg => {
                        const isAdmin = msg.sender_role === 'admin';
                        messagesHtml += `
                    <div style="margin-bottom:15px;padding:10px;background:${isAdmin ? '#e3f2fd' : '#f5f5f5'};border-radius:8px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                            <strong>${escapeHtml(msg.sender_name)} (${msg.sender_role})</strong>
                            <small style="color:#888;">${formatDate(msg.created_at)}</small>
                        </div>
                        <p style="margin:0;">${escapeHtml(msg.message)}</p>
                    </div>
                `;
                    });
                    messagesHtml += '</div>';
                }

                // Type display
                let typeText = '';
                switch (dispute.type) {
                    case 'order_issue': typeText = 'Order Issue'; break;
                    case 'payment_issue': typeText = 'Payment Issue'; break;
                    case 'delivery_issue': typeText = 'Delivery Issue'; break;
                    case 'product_quality': typeText = 'Product Quality'; break;
                    default: typeText = dispute.type || 'N/A';
                }

                const modalHtml = `
            <div id="disputeDetailsModalInner" class="modal" style="display:flex;">
                <div class="modal-content" style="max-width:800px;width:95%;max-height:80vh;overflow-y:auto;">
                    <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #eee;">
                        <h3>Dispute Details - #${dispute.dispute_id}</h3>
                        <button onclick="closeModal('disputeDetailsModalInner')" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
                    </div>
                    <div class="modal-body" style="padding:20px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
                            <div>
                                <p><strong>Order ID:</strong> #${dispute.order_number}</p>
                                <p><strong>Order Amount:</strong> Rs. ${parseFloat(dispute.order_amount).toLocaleString()}</p>
                                <p><strong>Order Status:</strong> ${dispute.order_status}</p>
                                <p><strong>Dispute Type:</strong> ${typeText}</p>
                                <p><strong>Priority:</strong> ${dispute.priority}</p>
                                <p><strong>Status:</strong> ${dispute.status}</p>
                            </div>
                            <div>
                                <p><strong>Complainant:</strong> ${escapeHtml(dispute.complainant_name)}</p>
                                <p><strong>Complainant Email:</strong> ${escapeHtml(dispute.complainant_email)}</p>
                                <p><strong>Complainant Phone:</strong> ${escapeHtml(dispute.complainant_phone || 'N/A')}</p>
                                <p><strong>Respondent:</strong> ${escapeHtml(dispute.respondent_name)}</p>
                                <p><strong>Respondent Email:</strong> ${escapeHtml(dispute.respondent_email)}</p>
                                <p><strong>Created:</strong> ${formatDate(dispute.created_at)}</p>
                            </div>
                        </div>
                        
                        <div style="margin-bottom:20px;">
                            <h4>Dispute Reason</h4>
                            <p style="padding:10px;background:#f8f9fa;border-radius:8px;">${escapeHtml(dispute.reason)}</p>
                        </div>
                        
                        ${dispute.resolution_notes ? `
                            <div style="margin-bottom:20px;">
                                <h4>Resolution Notes</h4>
                                <p style="padding:10px;background:#d4edda;border-radius:8px;">${escapeHtml(dispute.resolution_notes)}</p>
                                ${dispute.resolved_at ? `<p><small>Resolved on: ${formatDate(dispute.resolved_at)}</small></p>` : ''}
                            </div>
                        ` : ''}
                        
                        ${messagesHtml}
                        
                        ${dispute.status !== 'resolved' && dispute.status !== 'closed' ? `
                            <div style="margin-top:20px;">
                                <h4>Add Message</h4>
                                <textarea id="disputeMessage" class="form-control" rows="3" placeholder="Type your message here..."></textarea>
                                <button class="btn btn-primary" style="margin-top:10px;" onclick="addDisputeMessage(${dispute.dispute_id})">Send Message</button>
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer" style="padding:15px;text-align:right;border-top:1px solid #eee;">
                        ${dispute.status !== 'resolved' && dispute.status !== 'closed' ? `
                            <button class="btn btn-success" onclick="resolveDispute(${dispute.dispute_id})">Mark as Resolved</button>
                        ` : ''}
                        <button class="btn btn-secondary" onclick="closeModal('disputeDetailsModalInner')">Close</button>
                    </div>
                </div>
            </div>
        `;

                // Remove existing modal if any
                const existingModal = document.getElementById('disputeDetailsModalInner');
                if (existingModal) {
                    existingModal.remove();
                }

                document.body.insertAdjacentHTML('beforeend', modalHtml);
                document.body.style.overflow = 'hidden';

            } catch (error) {
                console.error('Error loading dispute details:', error);
                showNotification('Failed to load dispute details', 'error');
            }
        }

        // Add dispute message
        async function addDisputeMessage(disputeId) {
            const message = document.getElementById('disputeMessage')?.value;
            if (!message) {
                showNotification('Please enter a message', 'warning');
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/addDisputeMessage', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        dispute_id: disputeId,
                        message: message,
                        sender_id: <?= $_SESSION['USER']->id ?? 'null' ?>
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Refresh the dispute details
                    viewDisputeDetails(disputeId);
                } else {
                    showNotification('Failed to send message: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Update dispute status
        async function updateDisputeStatus(disputeId, status, resolutionNotes = null) {
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/updateDisputeStatus', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        dispute_id: disputeId,
                        status: status,
                        resolution_notes: resolutionNotes
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Dispute status updated successfully!', 'success');
                    closeModal('disputeDetailsModalInner');
                    loadDisputes(); // Refresh the disputes list
                } else {
                    showNotification('Failed to update dispute status: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating dispute status:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Resolve dispute
        async function resolveDispute(disputeId) {
            const resolution = await systemPrompt({
                title: 'Resolve Dispute',
                message: 'Enter resolution notes:',
                label: 'Resolution Notes',
                required: true,
                multiline: true,
            });
            if (!resolution) return;

            const ok = await systemConfirm('Mark this dispute as resolved?', 'Resolve Dispute');
            if (!ok) {
                return;
            }

            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/resolveDispute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        dispute_id: disputeId,
                        resolution: resolution
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Dispute resolved successfully!', 'success');
                    closeModal('disputeDetailsModalInner');
                    loadDisputes(); // Refresh the disputes list
                } else {
                    showNotification('Failed to resolve dispute: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error resolving dispute:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }

        // Reset dispute filters
        function resetDisputeFilters() {
            const searchEl = document.getElementById('cancelledOrderSearch');
            const revEl = document.getElementById('revisionStatusFilter');
            const methodEl = document.getElementById('cancelledOrderPaymentMethod');
            if (searchEl) searchEl.value = '';
            if (revEl) revEl.value = '';
            if (methodEl) methodEl.value = '';
            loadDisputes();
        }

        // Setup dispute filters
        function setupDisputeFilters() {
            const filters = ['cancelledOrderSearch', 'revisionStatusFilter', 'cancelledOrderPaymentMethod'];

            filters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    if (filterId === 'cancelledOrderSearch') {
                        let timeout;
                        element.addEventListener('input', () => {
                            clearTimeout(timeout);
                            timeout = setTimeout(() => loadDisputes(), 500);
                        });
                    } else {
                        element.addEventListener('change', () => loadDisputes());
                    }
                }
            });
        }

        // User management functions
        async function updateUserCount() {
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/updateUserCount');
                const result = await response.json();
                if (result.success) {
                    document.getElementById('totalUsers').textContent = result.userCount;
                }
            } catch (error) {
                console.error('Error loading user content:', error);
            }
        }

        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.getElementById('addUserForm').reset();
            document.getElementById('addUserMessage').style.display = 'none';
            document.getElementById('addUserFormErrors').style.display = 'none';
            const roleEl = document.getElementById('userRole');
            if (roleEl) roleEl.disabled = false;
            const titleEl = document.querySelector('#addUserModal .modal-header h3');
            if (titleEl) titleEl.textContent = 'Add New User';
        }

        function openAddAdminModal() {
            openAddUserModal();
            const titleEl = document.querySelector('#addUserModal .modal-header h3');
            if (titleEl) titleEl.textContent = 'Add New Admin';
            const roleEl = document.getElementById('userRole');
            if (roleEl) {
                roleEl.value = 'admin';
                roleEl.disabled = true;
                roleEl.dispatchEvent(new Event('change'));
            }
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('addUserForm').reset();
            const roleEl = document.getElementById('userRole');
            if (roleEl) roleEl.disabled = false;
            const titleEl = document.querySelector('#addUserModal .modal-header h3');
            if (titleEl) titleEl.textContent = 'Add New User';
        }

        async function openUpdateUserModal(userId) {
            try {
                const response = await fetch(`<?= ROOT ?>/adminDashboard/getUser/${userId}`);
                const result = await response.json();
                if (result.success) {
                    populateUpdateModal(result.data);
                    document.getElementById('updateUserModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                } else {
                    showMessage(result.message || 'Failed to load user details', 'error');
                }
            } catch (error) {
                console.error('Error loading user details:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        function closeUpdateUserModal() {
            document.getElementById('updateUserModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('updateUserForm').reset();
        }

        function populateUpdateModal(user) {
            document.getElementById('updateUserId').value = user.id;
            document.getElementById('updateName').value = user.name;
            document.getElementById('updateEmail').value = user.email;
            document.getElementById('updateRole').value = user.role;
            document.getElementById('updatePass').value = '';
            document.getElementById('updateUserMessage').style.display = 'none';
            document.getElementById('updateUserFormErrors').style.display = 'none';
        }

        // Form submissions
        document.getElementById('addUserForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            document.getElementById('addUserFormErrors').style.display = 'none';
            document.getElementById('addUserFormErrors').innerHTML = '';
            const password = document.getElementById('userPass').value;
            const confirmPassword = document.getElementById('userConfirmPass').value;
            const selectedRole = String(document.getElementById('userRole')?.value || '').toLowerCase();
            if (password !== confirmPassword) {
                document.getElementById('addUserFormErrors').innerHTML = '<strong>Error:</strong> Passwords do not match.';
                document.getElementById('addUserFormErrors').style.display = 'block';
                return;
            }

            const docErrors = [];
            if (selectedRole === 'farmer') {
                if (!document.getElementById('docNic')?.files?.length) docErrors.push('NIC document is required for Farmer accounts.');
                if (!document.getElementById('docBank')?.files?.length) docErrors.push('Bank details document is required for Farmer accounts.');
            } else if (selectedRole === 'transporter') {
                if (!document.getElementById('docDL')?.files?.length) docErrors.push('Driving license is required for Transporter accounts.');
                if (!document.getElementById('docIns')?.files?.length) docErrors.push('Vehicle insurance is required for Transporter accounts.');
                if (!document.getElementById('docRev')?.files?.length) docErrors.push('Vehicle revenue license is required for Transporter accounts.');
            }
            if (docErrors.length) {
                document.getElementById('addUserFormErrors').innerHTML = '<strong>Please fix the following errors:</strong><ul>' + docErrors.map(e => `<li>${e}</li>`).join('') + '</ul>';
                document.getElementById('addUserFormErrors').style.display = 'block';
                return;
            }

            const formData = new FormData(this);
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/register', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                showMessage(result.message, result.success ? 'success' : 'error', 'addUserMessage');
                if (result.success) {
                    showNotification('User added successfully!', 'success');
                    closeAddUserModal();
                    this.reset();
                    updateUserCount();
                    window.location.reload();
                } else if (result.errors) {
                    let errorHtml = '<strong>Please fix the following errors:</strong><ul>';
                    for (const error in result.errors) {
                        errorHtml += `<li>${result.errors[error]}</li>`;
                    }
                    errorHtml += '</ul>';
                    document.getElementById('addUserFormErrors').innerHTML = errorHtml;
                    document.getElementById('addUserFormErrors').style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error occurred. Please try again.', 'error', 'addUserMessage');
            }
        });

        // Toggle verification doc inputs based on selected role in Add User modal
        (function setupAddUserRoleDocsToggle() {
            const roleEl = document.getElementById('userRole');
            const farmerBox = document.getElementById('verificationDocsFarmer');
            const transporterBox = document.getElementById('verificationDocsTransporter');
            if (!roleEl || !farmerBox || !transporterBox) return;

            function toggle() {
                const role = String(roleEl.value || '').toLowerCase();
                farmerBox.style.display = role === 'farmer' ? 'block' : 'none';
                transporterBox.style.display = role === 'transporter' ? 'block' : 'none';
            }

            roleEl.addEventListener('change', toggle);
            toggle();
        })();

        document.getElementById('updateUserForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const errBox = document.getElementById('updateUserFormErrors');
            if (errBox) {
                errBox.style.display = 'none';
                errBox.innerHTML = '';
            }
            const formData = new FormData(this);
            try {
                const response = await fetch('<?= ROOT ?>/adminDashboard/updateUser', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showMessage('User updated successfully!', 'success', 'updateUserMessage');
                    closeUpdateUserModal();
                    loadUsers();
                    window.location.reload();
                } else {
                    showMessage(result.message || 'Failed to update user', 'error', 'updateUserMessage');
                    if (result.errors) {
                        let errorHtml = '<strong>Please fix the following errors:</strong><ul>';
                        for (const error in result.errors) {
                            errorHtml += `<li>${result.errors[error]}</li>`;
                        }
                        errorHtml += '</ul>';
                        document.getElementById('updateUserFormErrors').innerHTML = errorHtml;
                        document.getElementById('updateUserFormErrors').style.display = 'block';
                    }
                }
            } catch (error) {
                console.error('Error updating user:', error);
                showMessage('Network error while updating user', 'error', 'updateUserMessage');
            }
        });

        function showMessage(message, type, elementId = 'message') {
            const messageDiv = document.getElementById(elementId);
            messageDiv.textContent = message;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        }

        // Note: DOMContentLoaded is already handled above (line ~1468)
        // Removing duplicate initialization to prevent double event listeners

        // ============================================================
        // REPORT GENERATION SYSTEM
        // ============================================================

        let _reportSection = null;

        // Column definitions per section
        const REPORT_COLUMNS = {
            dashboard: [
                { key: 'metric', label: 'Metric' },
                { key: 'value', label: 'Value' }
            ],
            users: [
                { key: 'id', label: 'User ID' },
                { key: 'name', label: 'Name' },
                { key: 'email', label: 'Email' },
                { key: 'role', label: 'Role' },
                { key: 'verification_status', label: 'Verification' },
                { key: 'status', label: 'Status' },
                { key: 'created_at', label: 'Registered' }
            ],
            verifications: [
                { key: 'user_id', label: 'User ID' },
                { key: 'name', label: 'Name' },
                { key: 'email', label: 'Email' },
                { key: 'role', label: 'Role' },
                { key: 'verification_status', label: 'Status' },
                { key: 'doc_count', label: 'Total Docs' },
                { key: 'approved_docs', label: 'Approved' },
                { key: 'registered_at', label: 'Registered' }
            ],
            orders: [
                { key: 'order_number', label: 'Order ID' },
                { key: 'buyer_name', label: 'Buyer' },
                { key: 'farmer_name', label: 'Farmer' },
                { key: 'total_amount', label: 'Amount (Rs.)' },
                { key: 'order_status', label: 'Status' },
                { key: 'payment_status', label: 'Payment' },
                { key: 'payment_method', label: 'Pay Method' },
                { key: 'order_date', label: 'Date' }
            ],
            products: [
                { key: 'id', label: 'Product ID' },
                { key: 'name', label: 'Product Name' },
                { key: 'farmer_name', label: 'Farmer' },
                { key: 'category', label: 'Category' },
                { key: 'price', label: 'Price (Rs.)' },
                { key: 'quantity', label: 'Stock' },
                { key: 'status', label: 'Status' },
                { key: 'created_at', label: 'Listed On' }
            ],
            payments: [
                { key: 'transaction_id', label: 'Transaction ID' },
                { key: 'order_number', label: 'Order ID' },
                { key: 'buyer_name', label: 'Buyer' },
                { key: 'amount', label: 'Amount (Rs.)' },
                { key: 'payment_method', label: 'Method' },
                { key: 'payment_status', label: 'Status' },
                { key: 'payment_date', label: 'Date' }
            ],
            disputes: [
                { key: 'dispute_id', label: 'Dispute ID' },
                { key: 'order_number', label: 'Order ID' },
                { key: 'complainant_name', label: 'Complainant' },
                { key: 'respondent_name', label: 'Respondent' },
                { key: 'type', label: 'Type' },
                { key: 'priority', label: 'Priority' },
                { key: 'status', label: 'Status' },
                { key: 'created_at', label: 'Created' }
            ],
            analytics: [
                { key: 'metric', label: 'Metric' },
                { key: 'value', label: 'Value' }
            ],
            vehicles: [
                { key: 'id', label: 'Vehicle ID' },
                { key: 'transporter_name', label: 'Transporter' },
                { key: 'type', label: 'Type' },
                { key: 'model', label: 'Model' },
                { key: 'registration', label: 'Registration' },
                { key: 'capacity', label: 'Capacity' },
                { key: 'status', label: 'Status' }
            ],
            reviews: [
                { key: 'id', label: 'Review ID' },
                { key: 'order_number', label: 'Order ID' },
                { key: 'buyer_name', label: 'Buyer' },
                { key: 'reviewee_name', label: 'Reviewed User' },
                { key: 'reviewee_role', label: 'Role' },
                { key: 'rating', label: 'Rating' },
                { key: 'is_complaint', label: 'Complaint?' },
                { key: 'created_at', label: 'Date' }
            ]
        };

        const REPORT_TITLES = {
            dashboard: 'Platform Overview Report',
            users: 'User Management Report',
            verifications: 'Account Verifications Report',
            orders: 'Order Management Report',
            products: 'Product Catalogue Report',
            payments: 'Payments & Finance Report',
            disputes: 'Disputes Report',
            analytics: 'Platform Analytics Report',
            vehicles: 'Vehicles Report',
            reviews: 'Reviews Report'
        };

        // Sections that benefit from a date range filter
        const DATE_RANGE_SECTIONS = ['orders', 'payments', 'disputes', 'analytics', 'reviews'];

        function generateReport(section) {
            _reportSection = section;

            document.getElementById('reportModalTitle').textContent = REPORT_TITLES[section] || 'Generate Report';
            document.getElementById('reportModalDesc').textContent =
                `Exporting data from the ${section.charAt(0).toUpperCase() + section.slice(1)} section.`;

            // Date range
            const drDiv = document.getElementById('reportDateRange');
            drDiv.style.display = DATE_RANGE_SECTIONS.includes(section) ? 'block' : 'none';

            // Default date range: last 30 days
            const today = new Date();
            const prior = new Date(); prior.setDate(today.getDate() - 30);
            document.getElementById('reportDateTo').value = today.toISOString().slice(0, 10);
            document.getElementById('reportDateFrom').value = prior.toISOString().slice(0, 10);

            // Build column checkboxes
            const cols = REPORT_COLUMNS[section] || [];
            const grid = document.getElementById('reportColumnsGrid');
            grid.innerHTML = cols.map(col => `
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;
                       padding:4px 8px;border-radius:6px;border:1px solid #eee;background:#fafafa;">
            <input type="checkbox" name="reportCol" value="${col.key}" checked
                   style="accent-color:#1a7a4a;width:14px;height:14px;">
            ${col.label}
        </label>
    `).join('');

            // Reset format highlight
            highlightFormat();

            document.getElementById('reportModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function highlightFormat() {
            const selected = document.querySelector('input[name="reportFormat"]:checked')?.value;
            document.getElementById('fmtCsvLabel').style.borderColor = selected === 'csv' ? '#1a7a4a' : '#ddd';
            document.getElementById('fmtPrintLabel').style.borderColor = selected === 'print' ? '#1a7a4a' : '#ddd';
        }

        function executeReport() {
            const format = document.querySelector('input[name="reportFormat"]:checked')?.value || 'csv';
            const selCols = Array.from(document.querySelectorAll('input[name="reportCol"]:checked')).map(c => c.value);

            if (selCols.length === 0) {
                showNotification('Please select at least one column.', 'warning');
                return;
            }

            const dateFrom = document.getElementById('reportDateFrom').value;
            const dateTo = document.getElementById('reportDateTo').value;

            const data = getReportData(_reportSection, selCols, dateFrom, dateTo);
            const cols = (REPORT_COLUMNS[_reportSection] || []).filter(c => selCols.includes(c.key));
            const title = REPORT_TITLES[_reportSection] || 'Report';

            if (format === 'csv') {
                exportCSV(data, cols, title);
            } else {
                printReport(data, cols, title, dateFrom, dateTo);
            }
        }

        // ── Data extraction per section ────────────────────────────────────────

        function getReportData(section, selCols, dateFrom, dateTo) {
            switch (section) {
                case 'dashboard': return getDashboardReportData();
                case 'users': return filterByDate(getJsVar('allUsers') || [], 'created_at', dateFrom, dateTo);
                case 'verifications': return getJsVar('allVerifications') || getVerificationsFromPage();
                case 'orders': return filterByDate(getJsVar('allOrders') || [], 'order_date', dateFrom, dateTo);
                case 'products': return getJsVar('allProducts') || [];
                case 'payments': return filterByDate(getJsVar('allPayments') || [], 'payment_date', dateFrom, dateTo);
                case 'disputes': return filterByDate(getJsVar('allDisputes') || [], 'created_at', dateFrom, dateTo);
                case 'analytics': return getAnalyticsReportData();
                case 'vehicles': return getJsVar('allVehicles') || [];
                case 'reviews': return filterByDate(getJsVar('allReviews') || [], 'created_at', dateFrom, dateTo);
                default: return [];
            }
        }

        function getJsVar(name) {
            try { return window[name]; } catch (e) { return null; }
        }

        function filterByDate(rows, dateKey, from, to) {
            if (!from && !to) return rows;
            return rows.filter(r => {
                if (!r[dateKey]) return true;
                const d = r[dateKey].substring(0, 10);
                if (from && d < from) return false;
                if (to && d > to) return false;
                return true;
            });
        }

        function getDashboardReportData() {
            const metrics = [
                { metric: 'Total Users', value: document.getElementById('totalUsers')?.textContent || 0 },
                { metric: 'Farmers', value: document.getElementById('farmerCount')?.textContent || 0 },
                { metric: 'Buyers', value: document.getElementById('buyerCount')?.textContent || 0 },
                { metric: 'Transporters', value: document.getElementById('transporterCount')?.textContent || 0 },
                { metric: 'Admins', value: document.getElementById('adminCount')?.textContent || 0 },
                { metric: 'Pending Verifications', value: document.getElementById('vPendingCount')?.textContent || 0 },
                { metric: 'Active Orders', value: document.getElementById('activeOrders')?.textContent || 0 },
                { metric: 'Total Revenue', value: document.getElementById('dashboardTotalRevenue')?.textContent || 0 }
            ];
            return metrics;
        }

        function getVerificationsFromPage() {
            // Fall back to reading from the PHP-injected variable used by loadVerifications()
            try {
                return <?= json_encode($verifications ?? []) ?>;
            } catch (e) { return []; }
        }

        function getAnalyticsReportData() {
            return [
                { metric: 'Total Revenue', value: document.getElementById('analyticsTotalRevenue')?.textContent || 0 },
                { metric: 'Total Orders', value: document.getElementById('analyticsTotalOrders')?.textContent || 0 },
                { metric: 'Total Users', value: document.getElementById('analyticsTotalUsers')?.textContent || 0 },
                { metric: 'Total Transactions', value: document.getElementById('totalTransactions')?.textContent || 0 }
            ];
        }

        // ── CSV export ─────────────────────────────────────────────────────────

        function exportCSV(data, cols, title) {
            if (!data || data.length === 0) {
                showNotification('No data to export. Try loading this section first.', 'info');
                return;
            }

            const header = cols.map(c => `"${c.label}"`).join(',');

            const rows = data.map(row =>
                cols.map(col => {
                    let val = row[col.key] ?? '';
                    // Clean up values
                    val = String(val).replace(/"/g, '""');
                    return `"${val}"`;
                }).join(',')
            );

            const csv = [header, ...rows].join('\n');
            const bom = '\uFEFF'; // UTF-8 BOM for Excel
            const blob = new Blob([bom + csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${title.replace(/\s+/g, '_')}_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);

            closeModal('reportModal');
        }

        // ── Print / PDF export ─────────────────────────────────────────────────

        function printReport(data, cols, title, dateFrom, dateTo) {
            if (!data || data.length === 0) {
                showNotification('No data to export. Try loading this section first.', 'info');
                return;
            }

            const now = new Date().toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
            const dateStr = dateFrom && dateTo ? `${dateFrom} → ${dateTo}` : 'All time';

            // Summary stats
            const numericCols = cols.filter(c => {
                const sample = data[0]?.[c.key];
                return !isNaN(parseFloat(sample)) && isFinite(sample);
            });

            const summaryRows = numericCols.map(c => {
                const vals = data.map(r => parseFloat(r[c.key]) || 0);
                const sum = vals.reduce((a, b) => a + b, 0);
                const avg = vals.length ? (sum / vals.length) : 0;
                const max = Math.max(...vals);
                return `<tr>
            <td>${c.label}</td>
            <td>${sum.toLocaleString()}</td>
            <td>${avg.toFixed(2)}</td>
            <td>${max.toLocaleString()}</td>
        </tr>`;
            }).join('');

            const summarySection = numericCols.length > 0 ? `
        <div class="summary-box">
            <h3>Summary Statistics</h3>
            <table>
                <thead><tr><th>Column</th><th>Total</th><th>Average</th><th>Max</th></tr></thead>
                <tbody>${summaryRows}</tbody>
            </table>
        </div>` : '';

            // Table rows — stripe every other row
            const tableRows = data.map((row, i) =>
                `<tr class="${i % 2 === 0 ? 'even' : 'odd'}">
            ${cols.map(c => `<td>${escapeHtml(String(row[c.key] ?? ''))}</td>`).join('')}
        </tr>`
            ).join('');

            const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>${title}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #2c3e50; padding: 24px; }

    .report-header { display: flex; justify-content: space-between; align-items: flex-start;
                     border-bottom: 3px solid #1a7a4a; padding-bottom: 16px; margin-bottom: 20px; }
    .brand { font-size: 22px; font-weight: 700; color: #1a7a4a; letter-spacing: -0.5px; }
    .brand span { color: #2c3e50; }
    .report-meta { text-align: right; }
    .report-meta h2 { font-size: 15px; color: #2c3e50; margin-bottom: 4px; }
    .report-meta p  { font-size: 11px; color: #777; }

    .summary-box { background: #f0f9f4; border: 1px solid #c3e6cb; border-radius: 8px;
                   padding: 14px 18px; margin-bottom: 20px; }
    .summary-box h3 { font-size: 13px; color: #1a7a4a; margin-bottom: 10px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    thead th { background: #1a7a4a; color: #fff; padding: 8px 10px; text-align: left;
               font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    tbody td { padding: 7px 10px; border-bottom: 1px solid #eee; font-size: 11px; vertical-align: top; }
    tr.even { background: #fff; }
    tr.odd  { background: #f8fdf9; }

    .data-section h3 { font-size: 13px; color: #2c3e50; margin-bottom: 10px; font-weight: 600; }
    .footer { border-top: 1px solid #ddd; padding-top: 12px; margin-top: 8px;
              display: flex; justify-content: space-between; color: #aaa; font-size: 10px; }

    .count-badge { display: inline-block; background: #e8f5e9; color: #1a7a4a;
                   padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }

    @page { size: A4 landscape; margin: 15mm; }
    @media print {
        body { padding: 0; }
        button { display: none !important; }
    }
</style>
</head>
<body>

<div class="report-header">
    <div>
        <div class="brand">Agro<span>Link</span></div>
        <div style="font-size:11px;color:#777;margin-top:4px;">Sri Lanka Agricultural Marketplace</div>
    </div>
    <div class="report-meta">
        <h2>${title}</h2>
        <p>Period: ${dateStr}</p>
        <p>Generated: ${now}</p>
        <p>Total records: <strong>${data.length}</strong></p>
    </div>
</div>

${summarySection}

<div class="data-section">
    <table>
        <thead>
            <tr>${cols.map(c => `<th>${c.label}</th>`).join('')}</tr>
        </thead>
        <tbody>${tableRows}</tbody>
    </table>
</div>

<div class="footer">
    <span>AgroLink Admin Dashboard — Confidential</span>
    <span>${title} | ${now}</span>
    <span>Total: ${data.length} records</span>
</div>

<script>
    window.onload = function() {
        window.print();
        window.onafterprint = function() { window.close(); };
    };
<\/script>
</body>
</html>`;

            const win = window.open('', '_blank', 'width=1100,height=750');
            win.document.write(html);
            win.document.close();

            closeModal('reportModal');
        }
    </script>
    <script src="<?= ROOT ?>/assets/js/topnavbar.js"></script>

    <!-- System dialog modal (replaces alert/confirm/prompt) -->
    <div id="systemDialogModal" class="modal" style="display:none; z-index: 999999;">
        <div class="modal-content" style="max-width:520px;width:95%;max-height:80vh;overflow-y:auto;">
            <div class="modal-header"
                style="display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid var(--border-color);">
                <h3 id="systemDialogTitle" style="margin:0;">Notice</h3>
                <button id="systemDialogCloseBtn" type="button"
                    style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
            </div>
            <div class="modal-body" id="systemDialogBody" style="padding:20px;"></div>
            <div class="modal-footer" id="systemDialogFooter"
                style="display:flex;justify-content:flex-end;gap:10px;padding:15px;border-top:1px solid var(--border-color);">
            </div>
        </div>
    </div>
</body>

</html>
