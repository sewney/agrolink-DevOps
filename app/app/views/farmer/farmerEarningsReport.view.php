<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #222;
        }

        h1 {
            margin: 0 0 6px 0;
        }

        .meta {
            color: #666;
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
        }

        .label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .value {
            font-weight: 700;
            font-size: 18px;
            margin-top: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 13px;
        }

        th {
            background: #f5f5f5;
        }

        .section {
            margin-top: 20px;
        }

        .print-note {
            margin-top: 14px;
            color: #555;
            font-size: 13px;
        }

        @media print {
            .print-note {
                display: none;
            }

            body {
                margin: 8mm;
            }
        }
    </style>
</head>

<body>
    <h1>Farmer Earnings Report</h1>
    <div class="meta">
        Farmer: <?= htmlspecialchars((string)$farmerName) ?> |
        Generated: <?= htmlspecialchars((string)$generatedAt) ?>
    </div>

    <div class="grid">
        <div class="card">
            <div class="label">Total Earnings</div>
            <div class="value">Rs. <?= number_format((float)$summary['totalEarnings'], 2) ?></div>
        </div>
        <div class="card">
            <div class="label">This Month</div>
            <div class="value">Rs. <?= number_format((float)$summary['monthlyEarnings'], 2) ?></div>
        </div>
        <div class="card">
            <div class="label">Total Orders</div>
            <div class="value"><?= number_format((float)($summary['earningsStats']->total_orders ?? 0)) ?></div>
        </div>
    </div>

    <div class="section">
        <h3>Top Earning Products</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Orders</th>
                    <th>Quantity Sold</th>
                    <th>Total Earnings</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary['earningsByProduct'])): ?>
                    <tr>
                        <td colspan="4">No product earnings data</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($summary['earningsByProduct'] as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$product->product_name) ?></td>
                            <td><?= number_format((float)$product->order_count) ?></td>
                            <td><?= number_format((float)$product->total_quantity) ?> kg</td>
                            <td>Rs. <?= number_format((float)$product->total_earnings, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Recent Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Date/Time</th>
                    <th>Buyer</th>
                    <th>Location</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary['recentEarnings'])): ?>
                    <tr>
                        <td colspan="8">No recent transactions</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($summary['recentEarnings'] as $earning): ?>
                        <tr>
                            <td>#<?= (int)$earning->order_id ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($earning->transaction_date ?? $earning->order_date)) ?></td>
                            <td><?= htmlspecialchars((string)($earning->buyer_name ?? 'N/A')) ?></td>
                            <td><?= htmlspecialchars((string)($earning->delivery_city ?? 'N/A')) ?></td>
                            <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)($earning->payment_status ?? 'N/A')))) ?></td>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$earning->status))) ?></td>
                            <td><?= (int)$earning->item_count ?></td>
                            <td>Rs. <?= number_format((float)$earning->order_earnings, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="print-note">
        Use Print (Ctrl/Cmd+P) and choose "Save as PDF" to download this report.
    </div>

    <?php if ($autoPrint === '1'): ?>
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    <?php endif; ?>
</body>

</html>