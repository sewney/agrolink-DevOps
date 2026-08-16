<div class="content-section farmer-dashboard-modern">
    <div class="content-header">
        <h1 class="content-title">Dashboard Overview</h1>
        <p class="content-subtitle">Welcome back! Here's what's happening with your farm.</p>
    </div>

    <div class="dashboard-stats farmer-dashboard-stats">
        <div class="stat-card">
            <div class="stat-number"><?= (int)($activeProducts ?? 0) ?></div>
            <div class="stat-label">Active Products</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)($activeOrders ?? 0) ?></div>
            <div class="stat-label">Active Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)($runningDeliveries ?? 0) ?></div>
            <div class="stat-label">Running Deliveries</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)($newCropRequests ?? 0) ?></div>
            <div class="stat-label">New Crop Requests</div>
        </div>
    </div>

    <div class="farmer-dashboard-row">
        <div class="content-card farmer-dashboard-card">
            <div class="farmer-dashboard-card-header">
                <h3>Recent Orders</h3>
            </div>
            <div class="farmer-dashboard-card-body">
                <?php if (empty($recentOrders)): ?>
                    <div class="farmer-dashboard-empty">No recent orders yet.</div>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <?php $status = strtolower((string)($order->status ?? 'pending')); ?>
                        <div class="farmer-recent-order-item">
                            <div>
                                <div class="farmer-recent-order-title">Order #<?= (int)$order->id ?></div>
                                <div class="farmer-recent-order-date"><?= date('M d, Y', strtotime((string)$order->created_at)) ?></div>
                            </div>
                            <div class="farmer-recent-order-side">
                                <span class="status-badge status-<?= htmlspecialchars($status) ?>">
                                    <?= strtoupper(str_replace('_', ' ', htmlspecialchars($status))) ?>
                                </span>
                                <div class="farmer-recent-order-earnings">
                                    Rs. <?= number_format((float)($order->my_order_total ?? 0), 2) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-card farmer-dashboard-card">
            <div class="farmer-dashboard-card-header">
                <h3>Delivery Status</h3>
            </div>
            <div class="farmer-dashboard-card-body">
                <div class="farmer-delivery-quick-grid">
                    <div class="farmer-delivery-quick-card">
                        <div class="farmer-delivery-quick-value"><?= (int)($deliverySummary->accepted_deliveries ?? 0) ?></div>
                        <div class="farmer-delivery-quick-label">Accepted</div>
                    </div>
                    <div class="farmer-delivery-quick-card">
                        <div class="farmer-delivery-quick-value"><?= (int)($deliverySummary->in_transit_deliveries ?? 0) ?></div>
                        <div class="farmer-delivery-quick-label">In Transit</div>
                    </div>
                    <div class="farmer-delivery-quick-card">
                        <div class="farmer-delivery-quick-value"><?= (int)($deliverySummary->delivered_deliveries ?? 0) ?></div>
                        <div class="farmer-delivery-quick-label">Delivered</div>
                    </div>
                    <div class="farmer-delivery-quick-card">
                        <div class="farmer-delivery-quick-value"><?= (int)($deliverySummary->total_deliveries ?? 0) ?></div>
                        <div class="farmer-delivery-quick-label">Total</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card farmer-dashboard-card">
        <div class="farmer-dashboard-card-header">
            <h3>Top Products (This Month)</h3>
        </div>
        <div class="farmer-dashboard-card-body">
            <?php if (empty($topProducts)): ?>
                <div class="farmer-dashboard-empty">No product earnings data for this month yet.</div>
            <?php else: ?>
                <?php foreach ($topProducts as $product): ?>
                    <?php
                    $img = !empty($product->product_image)
                        ? ROOT . '/assets/images/products/' . rawurlencode((string)$product->product_image)
                        : ROOT . '/assets/images/default-product.svg';
                    ?>
                    <div class="farmer-top-product-item">
                        <div class="farmer-top-product-main">
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars((string)$product->product_name) ?>">
                            <div>
                                <div class="farmer-top-product-name"><?= htmlspecialchars((string)$product->product_name) ?></div>
                                <div class="farmer-top-product-meta"><?= (int)$product->order_count ?> order(s)</div>
                            </div>
                        </div>
                        <div class="farmer-top-product-amount">Rs. <?= number_format((float)$product->total_earnings, 2) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>