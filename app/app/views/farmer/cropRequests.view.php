<?php
// Content-only view for farmer crop requests list. Rendered inside farmerMain.
?>

<div class="content-section crop-requests-page">
    <div class="content-header">
        <h1 class="content-title">Buyer Crop Requests</h1>
    </div>

    <?php $successMessage = flash('success'); ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert success">
            <?= htmlspecialchars((string)$successMessage) ?>
        </div>
    <?php endif; ?>

    <?php $errorMessage = flash('error'); ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert">
            <?= htmlspecialchars((string)$errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($requests)): ?>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Crop Name</th>
                            <th>Quantity</th>
                            <th>Target Price</th>
                            <th>Delivery Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request):
                            $status = !empty($request->status) ? $request->status : 'active';
                            $statusKey = strtolower((string)$status);
                            $allowedStatus = ['active', 'accepted', 'declined', 'completed'];
                            if (!in_array($statusKey, $allowedStatus, true)) {
                                $statusKey = 'active';
                            }
                            $statusLabel = ucfirst(str_replace('_', ' ', $statusKey));
                            $cropNameDisplay = ucfirst(trim((string)$request->crop_name));
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cropNameDisplay) ?></strong></td>
                                <td><?= htmlspecialchars($request->quantity) ?> units</td>
                                <td><strong>Rs. <?= number_format((float)$request->target_price, 2) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($request->delivery_date)) ?></td>
                                <td><?= htmlspecialchars($request->location) ?></td>
                                <td>
                                    <span class="crop-request-status crop-request-status--<?= $statusKey ?>">
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </span>
                                </td>
                                <td class="crop-request-actions-cell">
                                    <a href="<?= ROOT ?>/farmercroprequests/show/<?= (int)$request->id ?>" class="btn btn-sm btn-outline crop-request-view-btn">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="crop-request-empty-state">
                <h3>No Crop Requests Available</h3>
                <p>There are no buyer crop requests at the moment. Check back later.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
