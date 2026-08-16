<?php
$monthlyChange = (float)($monthlyChangePercent ?? 0);
$monthlyChangeText = ($monthlyChange >= 0 ? '+' : '') . number_format($monthlyChange, 1) . '%';
$monthlyChangeClass = $monthlyChange >= 0 ? 'positive' : 'negative';
?>

<div class="content-section earnings-modern">
    <div class="content-header earnings-header">
        <h1 class="content-title">My Earnings</h1>
        <p class="content-subtitle">Track your income and financial performance</p>
        <button type="button" class="btn btn-secondary" id="downloadEarningsReportBtn">Download Report</button>
    </div>

    <div class="earnings-modern-stats">
        <div class="earnings-modern-card">
            <div class="metric-block">
                <div class="metric-label">Total Earnings</div>
                <div class="metric-value">Rs. <?= number_format((float)$totalEarnings, 2) ?></div>
            </div>
        </div>
        <div class="earnings-modern-card">
            <div class="metric-block">
                <div class="metric-label">This Month</div>
                <div class="metric-value">Rs. <?= number_format((float)$monthlyEarnings, 2) ?></div>
            </div>
        </div>
        <div class="earnings-modern-card">
            <div class="metric-block">
                <div class="metric-label">Total Orders</div>
                <div class="metric-value"><?= number_format((float)($earningsStats->total_orders ?? 0)) ?></div>
            </div>
        </div>
    </div>

    <div class="content-card earnings-chart-card">
        <div class="earnings-card-header earnings-chart-header">
            <h3 class="card-title">Earnings Overview</h3>
            <select class="form-control chart-range-select" id="earningsRangeSelect">
                <option value="daily">Last 7 Days</option>
                <option value="monthly" selected>Last 12 Months</option>
                <option value="yearly">Last 5 Years</option>
            </select>
            <div class="period-tabs" id="earningsPeriodTabs">
                <button type="button" class="earnings-tab-btn" data-period="daily">Daily</button>
                <button type="button" class="earnings-tab-btn active" data-period="monthly">Monthly</button>
                <button type="button" class="earnings-tab-btn" data-period="yearly">Yearly</button>
            </div>
        </div>
        <div class="chart-shell">
            <div class="chart-grid" id="earningsChartGrid">
            </div>
            <div class="chart-legend"><span class="dot"></span> Total Earnings</div>
        </div>
    </div>

    <div class="content-card">
        <div class="earnings-card-header">
            <h3 class="card-title">Recent Transactions</h3>
        </div>
        <div class="card-content">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentEarnings)): ?>
                        <tr>
                            <td colspan="5">No recent transactions.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentEarnings as $earning): ?>
                            <tr>
                                <td>#ORD-<?= (int)$earning->order_id ?></td>
                                <td><?= htmlspecialchars((string)($earning->lead_product ?? 'Order Items')) ?></td>
                                <td><?= date('F d, Y', strtotime($earning->transaction_date ?? $earning->order_date)) ?></td>
                                <td class="earnings-amount">Rs. <?= number_format((float)$earning->order_earnings, 2) ?></td>
                                <td>
                                    <span class="status-pill"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$earning->status))) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$chartPayload = json_encode([
    'daily' => array_map(function ($p) {
        return [
            'label' => $p['label'] ?? '',
            'fullLabel' => $p['fullLabel'] ?? ($p['label'] ?? ''),
            'earnings' => (float)($p['earnings'] ?? 0),
        ];
    }, $dailyChart ?? []),
    'monthly' => array_map(function ($p) {
        return [
            'label' => $p['label'] ?? '',
            'fullLabel' => $p['month'] ?? ($p['label'] ?? ''),
            'earnings' => (float)($p['earnings'] ?? 0),
        ];
    }, $monthlyChart ?? []),
    'yearly' => array_map(function ($p) {
        return [
            'label' => $p['label'] ?? '',
            'fullLabel' => $p['fullLabel'] ?? ($p['label'] ?? ''),
            'earnings' => (float)($p['earnings'] ?? 0),
        ];
    }, $yearlyChart ?? []),
]);
?>
<div
    id="earningsChartData"
    data-chart="<?= htmlspecialchars((string)$chartPayload, ENT_QUOTES, 'UTF-8') ?>"
    hidden></div>