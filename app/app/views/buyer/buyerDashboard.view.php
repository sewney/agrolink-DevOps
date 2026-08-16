<!-- Dashboard Section -->
<div id="dashboard-section" class="content-section buyer-dashboard-overview">
    <div class="content-header">
        <h1 class="content-title">Dashboard Overview</h1>
        <p class="content-subtitle">Welcome back, <?= htmlspecialchars($username) ?>! Here's what's happening with your orders.</p>
    </div>

    <!-- Stats Grid -->
    <div class="dashboard-stats buyer-dashboard-stats">
        <div class="stat-card buyer-dashboard-stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-number buyer-dashboard-stat-number"><?= $totalOrders ?? 0 ?></div>
        </div>
        <div class="stat-card buyer-dashboard-stat-card">
            <div class="stat-label">Ongoing Deliveries</div>
            <div class="stat-number buyer-dashboard-stat-number"><?= $ongoingDeliveries ?? 0 ?></div>
        </div>
        <div class="stat-card buyer-dashboard-stat-card">
            <div class="stat-label">Total Spent</div>
            <div class="stat-number buyer-dashboard-stat-number">Rs. <?= number_format($totalSpent ?? 0, 2) ?></div>
        </div>
        <div class="stat-card buyer-dashboard-stat-card">
            <div class="stat-label">Wishlist Items</div>
            <div class="stat-number buyer-dashboard-stat-number"><?= $wishlistCount ?? 0 ?></div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="content-card buyer-dashboard-recent-orders-card">
        <div class="card-header">
            <h3 class="card-title">Recent Orders</h3>
            <button class="btn btn-outline btn-sm" onclick="window.location.href='<?= ROOT ?>/buyerorders'">View All</button>
        </div>
        <div class="card-content">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Products</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders) && count($orders) > 0): ?>
                            <?php foreach (array_slice($orders, 0, 5) as $orderData): ?>
                                <?php
                                $order = $orderData['order'];
                                $items = $orderData['items'];
                                $orderDate = date('M d, Y', strtotime($order->created_at));
                                $statusClass = strtolower($order->status);
                                ?>
                                <tr>
                                    <td><strong>#ORD-<?= $order->id ?></strong></td>
                                    <td>
                                        <?php
                                        $productNames = array_slice(array_map(function ($item) {
                                            return htmlspecialchars($item->product_name);
                                        }, $items), 0, 2);
                                        echo implode(', ', $productNames);
                                        if (count($items) > 2) echo ' +' . (count($items) - 2) . ' more';
                                        ?>
                                    </td>
                                    <td><?= $order->item_count ?? count($items) ?></td>
                                    <td><strong>Rs. <?= number_format($order->order_total, 2) ?></strong></td>
                                    <td><?= $orderDate ?></td>
                                    <td><span class="order-status <?= $statusClass ?>"><?= strtoupper(str_replace('_', ' ', (string)$order->status)) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="buyer-dashboard-empty-orders-cell">
                                    No orders yet. Start shopping to see your orders here!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="content-card buyer-dashboard-quick-actions-card">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-content">
            <div class="buyer-dashboard-quick-actions-grid">
                <button class="btn btn-primary buyer-dashboard-quick-btn" onclick="window.location.href='<?= ROOT ?>/buyerproducts'">Browse Products</button>
                <button class="btn btn-secondary buyer-dashboard-quick-btn" onclick="window.location.href='<?= ROOT ?>/buyerorders'">View All Orders</button>
                <button class="btn btn-outline buyer-dashboard-quick-btn" onclick="window.location.href='<?= ROOT ?>/wishlist'">My Wishlist</button>
                <button class="btn btn-outline buyer-dashboard-quick-btn" onclick="window.location.href='<?= ROOT ?>/buyertracking'">Track Orders</button>
            </div>
        </div>
    </div>
</div>