<div class="content-section transporter-vehicles-page">
    <div class="content-header transporter-vehicles-header">
        <h1 class="content-title">Vehicle Management</h1>
        <p class="content-subtitle">Manage your fleet and set your active delivery vehicle.</p>
    </div>

    <div class="content-card transporter-vehicles-summary">
        <div class="card-content">
            <strong>Current Active Vehicle:</strong>
            <span id="activeVehicle">Loading...</span>
        </div>
    </div>

    <div class="transporter-vehicles-toolbar">
        <button class="btn btn-add-product" data-modal="addVehicleModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Vehicle
        </button>
    </div>

    <div id="myVehiclesContainer" style="margin-bottom: 40px;"></div>

    <div class="card" style="margin-top: 32px;">
        <div style="padding: 24px; border-bottom: 1px solid var(--medium-gray);">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #2c3e50;">All Vehicles</h3>
        </div>
        <div style="padding: 28px;">
            <div class="table-container">
                <table class="table transporter-vehicles-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Registration</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="vehiclesTableBody"></tbody>
                </table>
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
                        <input type="text" id="vehicleRegistration" name="registration" class="form-control" required pattern="^[A-Z]{2,3} \d{4}$" title="Registration must be 2 or 3 capital letters, a space, and 4 numbers (e.g. AB 1234)">
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