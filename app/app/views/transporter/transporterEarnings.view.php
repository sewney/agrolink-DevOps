<div class="content-section transporter-earnings-page">
    <div class="content-header">
        <h1 class="content-title">Earnings Overview</h1>
        <p class="content-subtitle">Live payout context from your completed transporter deliveries</p>
    </div>

    <div class="dashboard-stats" style="margin-bottom: 28px;">
        <div class="stat-card">
            <div class="stat-number" id="todayEarnings">Rs. 0</div>
            <div class="stat-label">Today (Delivered)</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="weekEarnings">Rs. 0</div>
            <div class="stat-label">This Week (7 Days)</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="monthEarningsDetail">Rs. 0</div>
            <div class="stat-label">This Month (Calendar)</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="totalEarningsDetail">Rs. 0</div>
            <div class="stat-label">Lifetime Earnings</div>
        </div>
    </div>

    <div class="grid grid-2 transporter-earnings-breakdown-grid" style="margin-top: 18px; gap: 20px;">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Current Period Breakdown</h3>
            </div>
            <div class="card-content" style="padding: 22px;">
                <div class="transporter-earnings-breakdown">
                    <div class="transporter-earnings-breakdown-row">
                        <span>Today</span>
                        <strong id="todayBreakdownValue">Rs. 0.00</strong>
                    </div>
                    <div class="transporter-earnings-breakdown-row">
                        <span>Last 7 Days</span>
                        <strong id="weekBreakdownValue">Rs. 0.00</strong>
                    </div>
                    <div class="transporter-earnings-breakdown-row">
                        <span>This Month</span>
                        <strong id="monthBreakdownValue">Rs. 0.00</strong>
                    </div>
                    <div class="transporter-earnings-breakdown-row">
                        <span>Lifetime</span>
                        <strong id="lifetimeBreakdownValue">Rs. 0.00</strong>
                    </div>
                    <div class="transporter-earnings-breakdown-total">
                        <span>Estimated Payout This Month</span>
                        <strong id="estimatedPayoutValue">Rs. 0.00</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Delivery Earnings Context</h3>
            </div>
            <div class="card-content" style="padding: 22px;">
                <div class="transporter-earnings-breakdown">
                    <div class="transporter-earnings-breakdown-row">
                        <span>Completed Deliveries</span>
                        <strong id="earningsCompletedDeliveries">0</strong>
                    </div>
                    <div class="transporter-earnings-breakdown-row">
                        <span>Active Deliveries</span>
                        <strong id="earningsActiveDeliveries">0</strong>
                    </div>
                    <div class="transporter-earnings-breakdown-row">
                        <span>Average Per Completed Delivery</span>
                        <strong id="avgPerDeliveryValue">Rs. 0.00</strong>
                    </div>
                    <div class="transporter-earnings-breakdown-note" id="earningsContextNote">
                        Based on completed deliveries currently recorded on your transporter account.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card" style="margin-top: 32px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">Payment History</h3>
            <button class="btn btn-secondary btn-sm" onclick="TransporterEarnings.exportPaymentHistory()">Export CSV</button>
        </div>
        <div style="padding: 28px;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order ID</th>
                            <th>Route</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="paymentHistoryBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>