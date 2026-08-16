<?php
// Content-only view for crop request details. Rendered inside farmerMain.
?>

<div class="content-section crop-request-details-page">
    <!-- Header Section -->
    <div class="content-header crop-request-details-header">
        <div>
            <h1 class="content-title">Crop Request Details</h1>
            <p class="content-subtitle">Review the buyer's crop request information</p>
        </div>
        <a href="<?= ROOT ?>/farmercroprequests" class="btn btn-outline">
            Back to Requests
        </a>
    </div>

    <!-- Display Messages -->
    <?php $successMessage = flash('success'); ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success crop-request-detail-alert crop-request-detail-alert-success">
            <?= htmlspecialchars((string)$successMessage) ?>
        </div>
    <?php endif; ?>

    <?php $errorMessage = flash('error'); ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger crop-request-detail-alert crop-request-detail-alert-danger">
            <?= htmlspecialchars((string)$errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Details Card -->
    <div class="content-card">
        <!-- Status Badge -->
        <div class="crop-request-status-wrap">
            <?php
            // Default to 'active' if status is empty or null
            $status = !empty($request->status) ? strtolower((string)$request->status) : 'active';
            $allowedStatuses = ['active', 'accepted', 'declined', 'completed'];
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'active';
            }
            ?>
            <span class="crop-request-detail-status crop-request-status crop-request-status--<?= htmlspecialchars($status) ?>">
                Status: <?= ucfirst($status) ?>
            </span>
        </div>

        <!-- Details Grid -->
        <div class="crop-request-details-grid">
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
        <?php if ($status === 'active'): ?>
            <div class="crop-request-detail-actions">
                <a href="<?= ROOT ?>/farmercroprequests/accept/<?= $request->id ?>" class="btn btn-primary">
                    Accept Request
                </a>
                <a href="<?= ROOT ?>/farmercroprequests/reject/<?= $request->id ?>" class="btn btn-danger"
                    onclick="return systemConfirmNavigate(event, 'Are you sure you want to reject this request?', 'Reject Request')">
                    Reject Request
                </a>
            </div>
        <?php else: ?>
            <div class="crop-request-detail-note">
                <p>This request has already been <?= $request->status ?>.</p>
            </div>
        <?php endif; ?>

        <?php if ($status === 'accepted'): ?>
            <div class="crop-request-next-step">
                <h4>Next Step Workflow</h4>
                <p>
                    1. Create a product listing using this request details.
                    2. Buyer places the order.
                    3. Delivery request appears for transporters and your delivery page.
                </p>
                <a
                    href="<?= ROOT ?>/farmerproducts?from_request_id=<?= (int)$request->id ?>&crop_name=<?= urlencode((string)$request->crop_name) ?>&quantity=<?= urlencode((string)$request->quantity) ?>&target_price=<?= urlencode((string)$request->target_price) ?>&location=<?= urlencode((string)$request->location) ?>"
                    class="btn btn-primary">
                    Create Product From This Request
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= ROOT ?>/assets/js/systemDialog.js"></script>
