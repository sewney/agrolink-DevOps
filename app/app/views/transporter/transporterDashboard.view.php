<!-- Transporter Dashboard Content (embedded in transporterMain.view.php) -->

<div id="dashboard-section" class="content-section">
    <div class="content-header">
        <h1 class="content-title">Dashboard Overview</h1>
        <p class="content-subtitle">Welcome, <span id="welcomeUserName"><?php echo isset($username) ? htmlspecialchars($username) : 'Transporter'; ?></span>! Here's what's happening with your deliveries.</p>
    </div>

    <div class="dashboard-stats" style="margin-bottom: 36px;">
        <div class="stat-card">
            <div class="stat-number" id="availableDeliveries"><?php echo isset($availableRequestsCount) ? $availableRequestsCount : 0; ?></div>
            <div class="stat-label">Available Deliveries</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="activeDeliveries"><?php echo isset($earningsSummary) ? (int)($earningsSummary->active_deliveries ?? 0) : 0; ?></div>
            <div class="stat-label">Active Deliveries</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="monthlyEarnings">Rs. <?php echo isset($earningsSummary) ? number_format((float)($earningsSummary->month_earnings ?? 0), 2) : '0.00'; ?></div>
            <div class="stat-label">This Month</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="completedDeliveries"><?php echo isset($earningsSummary) ? (int)($earningsSummary->completed_deliveries ?? 0) : 0; ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>

    <!-- Current Status -->
    <div class="content-card" style="margin-bottom: 36px;">
        <div class="card-header">
            <h3 class="card-title">Current Status</h3>
        </div>
        <div class="card-content" style="padding: 28px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 36px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 24px;">
                        <?php 
                            $status = $profile->availability ?? 'available';
                            $statusText = ($status === 'available') ? 'Available' : 'Offline';
                            $statusColor = ($status === 'available') ? '#4CAF50' : '#f44336';
                        ?>
                        <div id="statusIndicator" style="width: 12px; height: 12px; border-radius: 50%; background: <?= $statusColor ?>;"></div>
                        <span style="font-weight: 600;">Status: <span id="currentStatus"><?= $statusText ?></span></span>
                    </div>
                    <div style="margin-bottom: 18px; color: #666;" id="activeVehicleInfo">
                        <strong>Vehicle:</strong> <span id="activeVehicle">
                            <?php 
                                $activeVehicleText = 'No active vehicle';
                                if (isset($vehicles) && is_array($vehicles)) {
                                    foreach ($vehicles as $v) {
                                        if ($v->status === 'active') {
                                            $activeVehicleText = htmlspecialchars($v->model ?: $v->type) . ' (' . htmlspecialchars($v->registration) . ')';
                                            break;
                                        }
                                    }
                                }
                                echo $activeVehicleText;
                            ?>
                        </span>
                    </div>
                    <div style="margin-bottom: 18px; color: #666;">
                        <strong>Current Orders:</strong> <span id="currentOrders">
                            <?php
                                $inTransit = $earningsSummary->in_transit_deliveries ?? 0;
                                echo $inTransit > 0 ? $inTransit . ' orders' : 'No active orders';
                            ?>
                        </span>
                    </div>
                    <div style="color: #666;">
                        <strong>Next Orders:</strong> <span id="nextOrders">
                            <?php
                                $accepted = $earningsSummary->accepted_deliveries ?? 0;
                                echo $accepted > 0 ? $accepted . ' orders' : 'No pending orders';
                            ?>
                        </span>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary btn-block btn-stack" onclick="toggleAvailability()">
                        <span id="availabilityBtn"><?= ($status === 'available') ? 'Go Offline' : 'Go Online' ?></span>
                    </button>
                    <button class="btn btn-outline btn-block" onclick="TransporterDashboard.showSection('available-deliveries')">
                        Find Deliveries
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 0;">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Recent Deliveries</h3>
            </div>
            <div class="card-content" id="recentDeliveries" style="padding: 24px;">
                <div style="padding: 18px; text-align: center; color: #666;">
                    No recent deliveries
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Weekly Earnings</h3>
            </div>
            <div class="card-content" style="padding: 24px;">
                <div id="weeklyEarnings" style="font-size: 3rem; font-weight: 700; color: #65b57c; margin-bottom: 28px;">Rs. 0</div>
                <div style="font-size: 0.9rem; color: #666; line-height: 1.8;">
                    <div id="weeklyCompletedDeliveries" style="margin-bottom: 16px;">0 deliveries completed</div>
                    <div id="weeklyPendingDeliveries" style="margin-bottom: 16px;">0 deliveries pending</div>
                    <div id="weeklyRating">No rating yet</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <button class="btn btn-primary btn-no-margin" onclick="TransporterDashboard.showSection('available-deliveries')">Find Deliveries</button>
                <button class="btn btn-secondary btn-no-margin" onclick="TransporterDashboard.showSection('mydeliveries')">My Deliveries</button>
                <button class="btn btn-outline btn-no-margin" onclick="TransporterDashboard.showSection('vehicle')">Vehicle Info</button>
            </div>
        </div>
    </div>
</div>

<!-- Available Deliveries Section -->
<div id="available-deliveries-section" class="content-section" style="display: none;">
    <div class="content-header">
        <h1 class="content-title">Available Deliveries</h1>
        <button class="btn btn-outline btn-sm" onclick="TransporterDashboard.refreshDeliveries()">Refresh</button>
    </div>

    <div class="content-card delivery-filter-card">
        <div class="delivery-filter-row">
            <div class="transporter-filter-shell">
                <div class="transporter-filter-grid">
                    <div class="transporter-filter-group">
                        <label for="locationFilter">Pickup</label>
                        <select id="locationFilter" class="form-control transporter-filter-control">
                            <option value="">All Locations</option>
                            <option value="colombo">Colombo</option>
                            <option value="kandy">Kandy</option>
                            <option value="galle">Galle</option>
                            <option value="matale">Matale</option>
                            <option value="anuradhapura">Anuradhapura</option>
                        </select>
                    </div>
                    <div class="transporter-filter-group">
                        <label for="distanceFilter">Max Distance</label>
                        <select id="distanceFilter" class="form-control transporter-filter-control">
                            <option value="">Any Distance</option>
                            <option value="10">Within 10km</option>
                            <option value="25">Within 25km</option>
                            <option value="50">Within 50km</option>
                            <option value="100">Within 100km</option>
                        </select>
                    </div>
                    <div class="transporter-filter-group">
                        <label for="weightFilter">Max Weight</label>
                        <select id="weightFilter" class="form-control transporter-filter-control">
                            <option value="">Any Weight</option>
                            <option value="10">Up to 10kg</option>
                            <option value="25">Up to 25kg</option>
                            <option value="50">Up to 50kg</option>
                            <option value="100">Up to 100kg</option>
                        </select>
                    </div>
                    <div class="transporter-filter-group">
                        <label for="paymentFilter">Min Payment</label>
                        <select id="paymentFilter" class="form-control transporter-filter-control">
                            <option value="">Any Payment</option>
                            <option value="500">Rs. 500+</option>
                            <option value="1000">Rs. 1000+</option>
                            <option value="1500">Rs. 1500+</option>
                            <option value="2000">Rs. 2000+</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card transporter-requests-card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Delivery Requests</h3>
        </div>
        <div class="card-content">
            <div id="availableDeliveriesList" class="transporter-request-list">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<div id="mydeliveries-section" class="content-section" style="display: none;">
    <h1 style="margin-bottom: 24px; font-size: 2rem;">My Deliveries</h1>

    <div class="content-card delivery-filter-card">
        <div class="delivery-filter-row">
            <div class="delivery-filter-group" role="tablist" aria-label="Delivery filters">
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link active" data-status="running" onclick="TransporterDashboard.filterMyDeliveries('running')">Running</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="pending" onclick="TransporterDashboard.filterMyDeliveries('pending')">Pending</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="accepted" onclick="TransporterDashboard.filterMyDeliveries('accepted')">Accepted</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="in-transit" onclick="TransporterDashboard.filterMyDeliveries('in-transit')">In Transit</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="delivered" onclick="TransporterDashboard.filterMyDeliveries('delivered')">Delivered</button>
                <button type="button" class="delivery-filter-link transporter-delivery-filter-link" data-status="all" onclick="TransporterDashboard.filterMyDeliveries('all')">All</button>
            </div>
        </div>
    </div>

    <div class="table-container" style="padding: 24px; background: #fff; border-radius: 12px;">
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
            <tbody id="myDeliveriesTableBody">
            </tbody>
        </table>
    </div>
</div>

<div id="schedule-section" class="content-section" style="display: none;">
    <h1 style="margin-bottom: 24px;">Delivery Schedule</h1>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Upcoming (Next 3 Days)</h3>
        </div>
        <div class="card-content">
            <div id="scheduleCalendar" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>

    <div class="content-card" style="margin-top: 20px;">
        <div class="card-header">
            <h3 class="card-title">Today's Deliveries</h3>
        </div>
        <div class="card-content">
            <div id="todaySchedule">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
</div>
<div id="vehicle-section" class="content-section" style="display: none;">
    <div class="content-header" style="display:flex; align-items:center; justify-content:flex-start; margin-bottom: 32px;">
        <h1 class="content-title" style="margin:0;">Vehicle Management</h1>
    </div>
    <div style="margin-bottom: 36px;">
        <button class="btn btn-add-product" data-modal="addVehicleModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Vehicle
        </button>
    </div>

    <div id="myVehiclesContainer" style="margin-bottom: 40px;">

    </div>

    <div class="card" style="margin-top: 32px;">
        <div style="padding: 24px; border-bottom: 1px solid var(--medium-gray);">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #2c3e50;">All Vehicles</h3>
        </div>
        <div style="padding: 28px;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Registration</th>
                            <th>Type</th>
                            <th style="display: none;">Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="vehiclesTableBody">
                    </tbody>
                </table>
                <style>
                    /* Hide capacity column (4th column) in vehicles table */
                    #vehiclesTableBody tr td:nth-child(4) {
                        display: none;
                    }
                </style>
            </div>
        </div>
    </div>
</div>



<div id="analytics-section" class="content-section" style="display: none;">
    <div class="content-header">
        <h1 class="content-title">Analytics & Performance</h1>
        <p class="content-subtitle">Track your performance metrics and delivery statistics</p>
    </div>

    <div class="dashboard-stats" style="margin-bottom: 40px;">
        <div class="stat-card">
            <div class="stat-number">127</div>
            <div class="stat-label">Total Deliveries</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">4.8</div>
            <div class="stat-label">Average Rating</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">95%</div>
            <div class="stat-label">On-Time Rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">Rs. 541</div>
            <div class="stat-label">Avg per Delivery</div>
        </div>
    </div>

    <div class="grid grid-2" style="margin-top: 0; gap: 32px;">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Monthly Performance</h3>
            </div>
            <div class="card-content" style="padding: 10px 0;">
                <div style="display:grid; grid-template-columns: 1fr 4fr 1fr; gap:12px; align-items:center; padding:8px 12px;">
                    <div>Oct</div>
                    <div style="background:#E8F5E9; border-radius:8px; overflow:hidden;">
                        <div style="width: 78%; background:#66BB6A; color:#fff; padding:6px 8px;">78 deliveries</div>
                    </div>
                    <div style="text-align:right; font-weight:700;">Rs. 12.5k</div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 4fr 1fr; gap:12px; align-items:center; padding:8px 12px;">
                    <div>Sep</div>
                    <div style="background:#E8F5E9; border-radius:8px; overflow:hidden;">
                        <div style="width: 64%; background:#66BB6A; color:#fff; padding:6px 8px;">64 deliveries</div>
                    </div>
                    <div style="text-align:right; font-weight:700;">Rs. 10.1k</div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 4fr 1fr; gap:12px; align-items:center; padding:8px 12px;">
                    <div>Aug</div>
                    <div style="background:#E8F5E9; border-radius:8px; overflow:hidden;">
                        <div style="width: 58%; background:#66BB6A; color:#fff; padding:6px 8px;">58 deliveries</div>
                    </div>
                    <div style="text-align:right; font-weight:700;">Rs. 9.4k</div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Popular Routes</h3>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Route</th>
                                <th>Deliveries</th>
                                <th>Avg. Earning</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Colombo → Kandy</td>
                                <td>28</td>
                                <td>Rs. 820</td>
                            </tr>
                            <tr>
                                <td>Galle → Colombo</td>
                                <td>22</td>
                                <td>Rs. 1,050</td>
                            </tr>
                            <tr>
                                <td>Matale → Gampaha</td>
                                <td>16</td>
                                <td>Rs. 940</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="addVehicleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New Vehicle</h3>
            <span class="modal-close" data-modal-close>&times;</span>
        </div>
        <div class="modal-body">
            <form id="addVehicleForm">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="vehicleType">Vehicle Type *</label>
                        <select id="vehicleType" name="type" class="form-control" required>
                            <option value="">Select Type</option>
                            <?php if (!empty($vehicleTypes)): ?>
                                <?php foreach ($vehicleTypes as $vType):
                                    $slug = strtolower(str_replace(' ', '', $vType->vehicle_name));
                                ?>
                                    <option value="<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($vType->vehicle_name) ?> (<?= $vType->min_weight_kg ?>-<?= $vType->max_weight_kg ?>kg)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vehicleRegistration">Registration Number *</label>
                        <input type="text" id="vehicleRegistration" name="registration" class="form-control" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="vehicleFuelType">Fuel Type</label>
                        <select id="vehicleFuelType" name="fuel_type" class="form-control">
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vehicleModel">Vehicle Model</label>
                        <input type="text" id="vehicleModel" name="model" class="form-control" placeholder="e.g., Toyota Hiace">
                    </div>
                </div>

                <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                    <button type="submit" class="btn btn-primary">Add Vehicle</button>
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
