<?php
// Content-only view for crop requests. Rendered inside buyerMain which provides navbar, head, scripts.
?>

<div class="content-section crop-requests-page">
    <?php
    // ==================== LIST VIEW ====================
    if (isset($requests)):
    ?>
        <!-- Header Section -->
        <div class="content-header">
            <h1 class="content-title">My Crop Requests</h1>
            <p class="content-subtitle">Manage your crop requests and track their status</p>
        </div>

        <!-- Filter Card -->
        <div class="content-card">
            <div class="card-content">
                <div class="crop-request-filter-row">
                    <!-- Filter: Crop Type -->
                    <select id="filterCropType" class="form-control crop-request-filter-type">
                        <option value="">All Crop Types</option>
                        <?php
                        $cropTypes = [];
                        if (is_array($requests) || is_object($requests)) {
                            foreach ($requests as $req) {
                                if (isset($req->crop_name) && !in_array($req->crop_name, $cropTypes)) {
                                    $cropTypes[] = $req->crop_name;
                                }
                            }
                        }
                        sort($cropTypes);
                        foreach ($cropTypes as $type):
                        ?>
                            <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Filter: Status -->
                    <select id="filterStatus" class="form-control crop-request-filter-status">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="accepted">Accepted</option>
                        <option value="declined">Declined</option>
                        <option value="completed">Completed</option>
                    </select>

                    <!-- Filter: Year -->
                    <select id="filterYear" class="form-control crop-request-filter-year">
                        <option value="">All Years</option>
                        <?php
                        $years = [];
                        foreach ($requests as $req) {
                            $year = date('Y', strtotime($req->created_at));
                            if (!in_array($year, $years)) {
                                $years[] = $year;
                            }
                        }
                        rsort($years);
                        foreach ($years as $year):
                        ?>
                            <option value="<?= $year ?>" <?= $year == date('Y') ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endforeach; ?>
                    </select>

                    <a href="<?= ROOT ?>/croprequest/create" class="btn btn-primary crop-request-new-btn">
                        + New Request
                    </a>
                </div>
            </div>
        </div>

        <!-- Display Messages -->
        <?php $successMessage = flash('success'); ?>
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success crop-request-alert-success">
                <?= htmlspecialchars((string)$successMessage) ?>
            </div>
        <?php endif; ?>

        <?php $errorMessage = flash('error'); ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger crop-request-alert-error">
                <?= htmlspecialchars((string)$errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Requests Table -->
        <?php if (!empty($requests)): ?>
            <div class="content-card crop-request-table-card">
                <div class="table-responsive">
                    <table class="crop-request-table">
                        <thead>
                            <tr>
                                <th>Crop</th>
                                <th>Quantity</th>
                                <th>Submitted</th>
                                <th>Delivery Date</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th class="crop-request-actions-header">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request):
                                // Default to 'active' if status is empty or null
                                $status = !empty($request->status) ? strtolower(trim((string)$request->status)) : 'active';
                                $validStatuses = ['active', 'accepted', 'declined', 'completed'];
                                $statusClass = in_array($status, $validStatuses, true) ? $status : 'active';
                            ?>
                                <tr>
                                    <td class="crop-request-crop-cell"><?= htmlspecialchars($request->crop_name) ?></td>
                                    <td>
                                        <span class="crop-request-quantity-badge">
                                            <?= htmlspecialchars($request->quantity) ?> units
                                        </span>
                                    </td>
                                    <td class="crop-request-muted"><?= date('d M, Y', strtotime($request->created_at)) ?></td>
                                    <td><?= date('d M, Y', strtotime($request->delivery_date)) ?></td>
                                    <td class="crop-request-muted"><?= htmlspecialchars($request->location) ?></td>
                                    <td>
                                        <span class="crop-request-status crop-request-status--<?= htmlspecialchars($statusClass) ?>">
                                            <?= ucfirst($statusClass) ?>
                                        </span>
                                    </td>
                                    <td class="crop-request-actions-cell">
                                        <div class="crop-request-actions-group">
                                            <a href="<?= ROOT ?>/croprequest/edit/<?= $request->id ?>"
                                                class="btn btn-sm btn-outline crop-request-icon-btn"
                                                title="Edit">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11.5 2.5a2.121 2.121 0 0 1 3 3L6.5 13.5 2.5 14.5 3.5 10.5 11.5 2.5z" />
                                                </svg>
                                            </a>
                                            <a href="<?= ROOT ?>/croprequest/delete/<?= $request->id ?>"
                                                onclick="return systemConfirmNavigate(event, 'Are you sure you want to delete this request?', 'Delete Request')"
                                                class="btn btn-sm btn-danger crop-request-icon-btn"
                                                title="Delete">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="content-card crop-request-empty-state">
                <div class="crop-request-empty-icon">🌾</div>
                <h3 class="crop-request-empty-title">No Crop Requests Yet</h3>
                <p class="crop-request-empty-subtitle">Create your first crop request to get started!</p>
                <a href="<?= ROOT ?>/croprequest/create" class="btn btn-primary">
                    Create First Request
                </a>
            </div>
        <?php endif; ?>

    <?php
    // ==================== CREATE VIEW ====================
    elseif (isset($action) && $action === 'create'):
    ?>
        <div class="content-header">
            <h1 class="content-title">Create Crop Request</h1>
            <p class="content-subtitle">Submit a new request for the crops you need</p>
        </div>

        <!-- Display Errors -->
        <?php $createErrors = flash('errors', []); ?>
        <?php if (!empty($createErrors) && is_array($createErrors)): ?>
            <div class="alert alert-danger crop-request-alert-error">
                <strong>Please fix the following errors:</strong>
                <ul class="crop-request-error-list">
                    <?php foreach ($createErrors as $error): ?>
                        <li><?= htmlspecialchars((string)$error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="content-card">
            <form method="POST">
                <div class="form-row">
                    <!-- Crop Name -->
                    <div class="form-group">
                        <label for="crop_name">Crop Name <span class="required">*</span></label>
                        <input type="text" id="crop_name" name="crop_name" class="form-control" value="<?= esc($_POST['crop_name'] ?? '') ?>" placeholder="e.g., Tomatoes, Rice, Carrots" required>
                    </div>

                    <!-- Quantity -->
                    <div class="form-group">
                        <label for="quantity">Quantity (units) <span class="required">*</span></label>
                        <input type="number" id="quantity" name="quantity" class="form-control" value="<?= esc($_POST['quantity'] ?? '') ?>" step="0.01" placeholder="Enter quantity" required>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Target Price -->
                    <div class="form-group">
                        <label for="target_price">Target Price per Unit (Rs.) <span class="required">*</span></label>
                        <input type="number" id="target_price" name="target_price" class="form-control" value="<?= esc($_POST['target_price'] ?? '') ?>" step="0.01" placeholder="Enter your target price" required>
                    </div>

                    <!-- Delivery Date -->
                    <div class="form-group">
                        <label for="delivery_date">Delivery Date <span class="required">*</span></label>
                        <input type="date" id="delivery_date" name="delivery_date" class="form-control" value="<?= esc($_POST['delivery_date'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Location -->
                <div class="form-group">
                    <label for="location">Delivery Location <span class="required">*</span></label>
                    <input type="text" id="location" name="location" class="form-control" value="<?= esc($_POST['location'] ?? '') ?>" placeholder="City, Province" required>
                </div>

                <!-- Buttons -->
                <div class="crop-request-form-actions">
                    <button type="submit" class="btn btn-primary">
                        Create Request
                    </button>
                    <a href="<?= ROOT ?>/croprequest" class="btn btn-outline">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

    <?php
    // ==================== EDIT VIEW ====================
    elseif (isset($action) && $action === 'edit'):
    ?>
        <div class="content-header">
            <h1 class="content-title">Edit Crop Request</h1>
            <p class="content-subtitle">Update your crop request details</p>
        </div>

        <!-- Display Errors -->
        <?php $editErrors = flash('errors', []); ?>
        <?php if (!empty($editErrors) && is_array($editErrors)): ?>
            <div class="alert alert-danger crop-request-alert-error">
                <strong>Please fix the following errors:</strong>
                <ul class="crop-request-error-list">
                    <?php foreach ($editErrors as $error): ?>
                        <li><?= htmlspecialchars((string)$error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="content-card">
            <form method="POST">
                <div class="form-row">
                    <!-- Crop Name -->
                    <div class="form-group">
                        <label for="crop_name">Crop Name <span class="required">*</span></label>
                        <input type="text" id="crop_name" name="crop_name" class="form-control" value="<?= htmlspecialchars($request->crop_name) ?>" required>
                    </div>

                    <!-- Quantity -->
                    <div class="form-group">
                        <label for="quantity">Quantity (units) <span class="required">*</span></label>
                        <input type="number" id="quantity" name="quantity" class="form-control" value="<?= htmlspecialchars($request->quantity) ?>" step="0.01" required>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Target Price -->
                    <div class="form-group">
                        <label for="target_price">Target Price per Unit (Rs.) <span class="required">*</span></label>
                        <input type="number" id="target_price" name="target_price" class="form-control" value="<?= htmlspecialchars($request->target_price) ?>" step="0.01" required>
                    </div>

                    <!-- Delivery Date -->
                    <div class="form-group">
                        <label for="delivery_date">Delivery Date <span class="required">*</span></label>
                        <input type="date" id="delivery_date" name="delivery_date" class="form-control" value="<?= htmlspecialchars($request->delivery_date) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Location -->
                    <div class="form-group">
                        <label for="location">Delivery Location <span class="required">*</span></label>
                        <input type="text" id="location" name="location" class="form-control" value="<?= htmlspecialchars($request->location) ?>" placeholder="City, Province" required>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?= $request->status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="accepted" <?= $request->status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                            <option value="declined" <?= $request->status === 'declined' ? 'selected' : '' ?>>Declined</option>
                            <option value="completed" <?= $request->status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="crop-request-form-actions">
                    <button type="submit" class="btn btn-primary">
                        Update Request
                    </button>
                    <a href="<?= ROOT ?>/croprequest" class="btn btn-outline">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

    <?php
    // ==================== VIEW/SHOW VIEW ====================
    else:
    ?>
        <div class="content-header crop-request-detail-header">
            <div>
                <h1 class="content-title">Crop Request Details</h1>
                <p class="content-subtitle">View your crop request information</p>
            </div>
            <a href="<?= ROOT ?>/croprequest" class="btn btn-outline">
                Back to Requests
            </a>
        </div>

        <!-- Details Card -->
        <div class="content-card">
            <!-- Status Badge -->
            <div class="crop-request-detail-status-wrap">
                <?php
                // Default to 'active' if status is empty or null
                $status = !empty($request->status) ? strtolower(trim((string)$request->status)) : 'active';
                $validStatuses = ['active', 'accepted', 'declined', 'completed'];
                $statusClass = in_array($status, $validStatuses, true) ? $status : 'active';
                ?>
                <span class="crop-request-status crop-request-status--<?= htmlspecialchars($statusClass) ?>">
                    <?= ucfirst($statusClass) ?>
                </span>
            </div>

            <!-- Details Grid -->
            <div class="crop-request-detail-grid">
                <!-- Crop Name -->
                <div>
                    <label class="crop-request-detail-label">Crop Name</label>
                    <p class="crop-request-detail-value"><?= htmlspecialchars($request->crop_name) ?></p>
                </div>

                <!-- Quantity -->
                <div>
                    <label class="crop-request-detail-label">Quantity</label>
                    <p class="crop-request-detail-value"><?= htmlspecialchars($request->quantity) ?> units</p>
                </div>

                <!-- Target Price -->
                <div>
                    <label class="crop-request-detail-label">Target Price per Unit</label>
                    <p class="crop-request-detail-value crop-request-detail-value-price">Rs.<?= number_format($request->target_price, 2) ?></p>
                </div>

                <!-- Delivery Date -->
                <div>
                    <label class="crop-request-detail-label">Delivery Date</label>
                    <p class="crop-request-detail-value"><?= date('M d, Y', strtotime($request->delivery_date)) ?></p>
                </div>

                <!-- Location -->
                <div>
                    <label class="crop-request-detail-label">Location</label>
                    <p class="crop-request-detail-value"><?= htmlspecialchars($request->location) ?></p>
                </div>

                <!-- Created At -->
                <div>
                    <label class="crop-request-detail-label">Created On</label>
                    <p class="crop-request-detail-value"><?= date('M d, Y H:i', strtotime($request->created_at)) ?></p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="crop-request-detail-actions">
                <a href="<?= ROOT ?>/croprequest/edit/<?= $request->id ?>" class="btn btn-primary">
                    Edit Request
                </a>
                <a href="<?= ROOT ?>/croprequest/delete/<?= $request->id ?>" class="btn btn-danger" onclick="return systemConfirmNavigate(event, 'Are you sure you want to delete this request?', 'Delete Request')">
                    Delete Request
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="<?= ROOT ?>/assets/js/systemDialog.js"></script>
