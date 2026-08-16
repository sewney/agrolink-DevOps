<div class="content-section farmer-orders-modern">
    <div class="content-header">
        <h1 class="content-title">My Orders</h1>
        <p class="content-subtitle">Orders containing your products</p>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state farmer-orders-empty">
            <div class="farmer-orders-empty-icon">📦</div>
            <h3>No Orders Yet</h3>
            <p>You don't have any orders containing your products yet.</p>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php foreach ($orders as $order): ?>
                <div class="order-card" data-order-id="<?= htmlspecialchars($order->id) ?>">
                    <div class="order-header">
                        <div class="order-title-block">
                            <h3 class="order-title">Order #<?= htmlspecialchars($order->id) ?></h3>
                            <span class="order-date"><?= date('M d, Y', strtotime($order->created_at)) ?></span>
                        </div>
                        <span class="order-status status-<?= htmlspecialchars($order->status) ?>">
                            <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order->status))) ?>
                        </span>
                    </div>

                    <div class="order-body">
                        <div class="order-info-grid">
                            <div class="info-item">
                                <span class="info-label">Items</span>
                                <span class="info-value"><?= htmlspecialchars($order->my_items_count) ?> item(s)</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Your Earnings</span>
                                <span class="info-value order-earnings-value">Rs. <?= number_format($order->my_order_total, 2) ?></span>
                            </div>
                        </div>

                    </div>

                    <div class="order-footer">
                        <button class="btn btn-secondary btn-view-details" data-order-id="<?= htmlspecialchars($order->id) ?>" onclick="FarmerOrders.viewOrderDetails(<?= (int)$order->id ?>, this)">
                            View Order Details
                        </button>

                        <?php $status = strtolower((string)$order->status); ?>
                        <?php if ($status === 'processing'): ?>
                            <button class="btn btn-primary" onclick="FarmerOrders.updateOrderStatus(<?= (int)$order->id ?>, 'ready_for_pickup')">Mark Ready for Pickup</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Order Details</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="orderDetailsContent">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>
</div>