<div class="content-section transporter-deliveries-page">
    <h1 style="margin-bottom: 24px; font-size: 2rem;">My Deliveries</h1>

    <div class="content-card delivery-filter-card">
        <div class="delivery-filter-row">
            <div class="delivery-filter-group" role="tablist" aria-label="Delivery filters">
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="accepted" onclick="TransporterDeliveries.filterMyDeliveries('accepted')">Accepted</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="in-transit" onclick="TransporterDeliveries.filterMyDeliveries('in-transit')">In Transit</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="delivered" onclick="TransporterDeliveries.filterMyDeliveries('delivered')">Delivered</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="cancelled" onclick="TransporterDeliveries.filterMyDeliveries('cancelled')">Cancelled</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="all" onclick="TransporterDeliveries.filterMyDeliveries('all')">All</button>
            </div>
        </div>
    </div>

    <div class="table-container transporter-deliveries-table-wrap" style="padding: 24px; background: #fff; border-radius: 12px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Route</th>
                    <th>Distance</th>
                    <th>Weight</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Deadline</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="myDeliveriesTableBody"></tbody>
        </table>
    </div>
</div>