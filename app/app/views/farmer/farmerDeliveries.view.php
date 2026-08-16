<div class="content-section farmer-deliveries-page">
    <div class="content-header">
        <h1 class="content-title">Running Deliveries</h1>
        <p class="content-subtitle">Track transporter progress for your outgoing orders.</p>
    </div>

    <div class="dashboard-stats farmer-deliveries-stats">
        <div class="stat-card">
            <div class="stat-number"><?= (int)($deliverySummary->accepted_deliveries ?? 0) ?></div>
            <div class="stat-label">Accepted</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)($deliverySummary->in_transit_deliveries ?? 0) ?></div>
            <div class="stat-label">In Transit</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)($deliverySummary->delivered_deliveries ?? 0) ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)($deliverySummary->total_deliveries ?? 0) ?></div>
            <div class="stat-label">Total Deliveries</div>
        </div>
    </div>

    <?php $activeFilter = $filter ?? 'running'; ?>
    <div class="content-card delivery-filter-card">
        <div class="delivery-filter-row">
            <div class="delivery-filter-group" role="tablist" aria-label="Delivery filters">
                <a class="delivery-filter-link <?= $activeFilter === 'running' ? 'active' : '' ?>" href="<?= ROOT ?>/farmerdeliveries?status=running">Running</a>
                <a class="delivery-filter-link <?= $activeFilter === 'pending' ? 'active' : '' ?>" href="<?= ROOT ?>/farmerdeliveries?status=pending">Pending</a>
                <a class="delivery-filter-link <?= $activeFilter === 'accepted' ? 'active' : '' ?>" href="<?= ROOT ?>/farmerdeliveries?status=accepted">Accepted</a>
                <a class="delivery-filter-link <?= $activeFilter === 'in_transit' ? 'active' : '' ?>" href="<?= ROOT ?>/farmerdeliveries?status=in_transit">In Transit</a>
                <a class="delivery-filter-link <?= $activeFilter === 'delivered' ? 'active' : '' ?>" href="<?= ROOT ?>/farmerdeliveries?status=delivered">Delivered</a>
                <a class="delivery-filter-link <?= $activeFilter === 'cancelled' ? 'active' : '' ?>" href="<?= ROOT ?>/farmerdeliveries?status=cancelled">Cancelled</a>
                <a class="delivery-filter-link <?= $activeFilter === 'all' ? 'active' : '' ?>" href="<?= ROOT ?>/farmerdeliveries?status=all">All</a>
            </div>
        </div>
    </div>

    <?php if (empty($deliveries)): ?>
        <div class="empty-state farmer-deliveries-empty">
            <div class="farmer-deliveries-empty-icon">🚚</div>
            <h3>No deliveries found</h3>
            <p>Deliveries will appear here once transport requests are created.</p>
        </div>
    <?php else: ?>
        <div class="table-container farmer-deliveries-table-wrap">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Buyer</th>
                            <th>Transporter</th>
                            <th>Route</th>
                            <th>Weight</th>
                            <th>Status</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $delivery): ?>
                            <tr>
                                <td>#<?= (int)$delivery->order_id ?></td>
                                <td><?= htmlspecialchars($delivery->buyer_name ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($delivery->transporter_name ?? 'Unassigned') ?></td>
                                <td>
                                    <div class="farmer-delivery-route-main">
                                        <?= htmlspecialchars($delivery->farmer_city ?? 'Unknown') ?> → <?= htmlspecialchars($delivery->buyer_city ?? 'Unknown') ?>
                                    </div>
                                    <div class="farmer-delivery-route-sub">
                                        <?= isset($delivery->distance_km) ? number_format((float)$delivery->distance_km, 1) . ' km' : 'Distance N/A' ?>
                                    </div>
                                </td>
                                <td><?= number_format((float)($delivery->total_weight_kg ?? 0), 2) ?> kg</td>
                                <td>
                                    <span class="order-status status-<?= htmlspecialchars($delivery->status ?? 'pending') ?>">
                                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($delivery->status ?? 'pending'))) ?>
                                    </span>
                                </td>
                                <td><?= !empty($delivery->updated_at) ? date('M d, Y H:i', strtotime($delivery->updated_at)) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>