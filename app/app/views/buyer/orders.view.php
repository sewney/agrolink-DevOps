<div class="content-section buyer-orders-section">
    <div class="content-header">
        <h1 class="content-title">My Orders</h1>
        <p class="content-subtitle">Track and manage your order history</p>
    </div>

    <?php if (!empty($orders) && count($orders) > 0): ?>
        <?php foreach ($orders as $orderData): ?>
            <?php
            $order = $orderData['order'];
            $items = $orderData['items'];
            $orderDate = date('M d, Y', strtotime($order->created_at));
            $statusClass = strtolower($order->status);
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <h4 class="order-title">Order #ORD-<?= $order->id ?></h4>
                        <p class="buyer-orders-placed-date">Placed on <?= $orderDate ?></p>
                    </div>
                    <span class="order-status <?= $statusClass ?>"><?= strtoupper(str_replace('_', ' ', $order->status)) ?></span>
                </div>
                <div class="order-details">
                    <div class="order-detail">
                        <span class="order-detail-label">Items</span>
                        <span class="order-detail-value"><?= count($items) ?> product(s)</span>
                    </div>
                    <div class="order-detail">
                        <span class="order-detail-label">Subtotal</span>
                        <span class="order-detail-value">Rs. <?= number_format($order->total_amount, 2) ?></span>
                    </div>
                    <div class="order-detail">
                        <span class="order-detail-label">Shipping</span>
                        <span class="order-detail-value">Rs. <?= number_format($order->shipping_cost, 2) ?></span>
                    </div>
                    <div class="order-detail">
                        <span class="order-detail-label">Total</span>
                        <span class="order-detail-value">Rs. <?= number_format($order->order_total, 2) ?></span>
                    </div>
                </div>

                <div class="buyer-orders-products-section">
                    <h5 class="buyer-orders-products-title">Products:</h5>
                    <?php foreach ($items as $item): ?>
                        <div class="buyer-orders-product-row">
                            <div>
                                <span class="buyer-orders-product-name"><?= htmlspecialchars($item->product_name) ?></span>
                                <span class="buyer-orders-product-qty"> x <?= $item->quantity ?>kg</span>
                            </div>
                            <div>
                                <span class="buyer-orders-product-price">Rs. <?= number_format($item->product_price * $item->quantity, 2) ?></span>
                                <?php if (!empty($item->farmer_name)): ?>
                                    <span class="buyer-orders-product-farmer">by <?= htmlspecialchars($item->farmer_name) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-sm btn-secondary" onclick="BuyerDashboard.viewOrderDetails(<?= $order->id ?>)">View Order Details</button>
                    <?php if ($statusClass === 'delivered' && !empty($items)): ?>
                        <a class="btn btn-sm btn-outline" href="<?= ROOT ?>/buyerreviews">
                            ★ Write Review
                        </a>
                    <?php endif; ?>
                    <?php if ($order->status === 'pending_payment'): ?>
                        <a class="btn btn-sm btn-primary" href="<?= ROOT ?>/payment/checkout?order_id=<?= (int)$order->id ?>&order_ids=<?= (int)$order->id ?>">Retry Payment</a>
                    <?php endif; ?>
                    <?php if ($order->status === 'pending_payment' || $order->status === 'processing'): ?>
                        <button class="btn btn-sm btn-danger" onclick="BuyerDashboard.cancelOrder(<?= $order->id ?>)">Cancel Order</button>
                    <?php endif; ?>
                    <?php if ($order->status === 'shipped'): ?>
                        <button class="btn btn-sm btn-secondary" onclick="BuyerDashboard.trackOrder(<?= $order->id ?>)">Track Order</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="content-card">
            <div class="card-content buyer-orders-empty-state">
                <div class="buyer-orders-empty-icon">📦</div>
                <h3 class="buyer-orders-empty-title">No Orders Yet</h3>
                <p class="buyer-orders-empty-subtitle">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                <button class="btn btn-primary" onclick="window.location.href='<?= ROOT ?>/buyerproducts'">Browse Products</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="order-details-modal" class="modal buyer-orders-modal">
    <div class="modal-content buyer-orders-modal-content">
        <span class="close-modal buyer-orders-modal-close" onclick="BuyerDashboard.closeOrderModal()">&times;</span>

        <div id="modal-body">
            <div class="buyer-orders-modal-loading">
                <div class="loader buyer-orders-loader"></div>
                <p class="buyer-orders-loader-text">Loading order details...</p>
            </div>
        </div>
    </div>
</div>