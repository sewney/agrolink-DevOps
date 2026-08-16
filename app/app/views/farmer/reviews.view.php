<?php
$reviewsList = is_array($reviews ?? null) ? $reviews : [];
$totalFeedback = count($reviewsList);
$totalRating = 0;
$complaintsCount = 0;

foreach ($reviewsList as $reviewItem) {
    $ratingValue = (int)($reviewItem->rating ?? 0);
    $totalRating += $ratingValue;
    if ($ratingValue > 0 && $ratingValue <= 2) {
        $complaintsCount++;
    }
}

$averageRating = $totalFeedback > 0 ? ($totalRating / $totalFeedback) : 0;
?>

<div class="content-section farmer-reviews-page">
    <div class="content-header">
        <h1 class="content-title">Customer Reviews</h1>
        <p class="content-subtitle">Product feedback from buyers</p>
    </div>

    <div class="dashboard-stats" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-number"><?= number_format($averageRating, 1) ?></div>
            <div class="stat-label">Average Rating</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)$complaintsCount ?></div>
            <div class="stat-label">Complaints</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)$totalFeedback ?></div>
            <div class="stat-label">Total Feedback</div>
        </div>
    </div>

    <div class="reviews-container">
        <?php if (empty($reviewsList)): ?>
            <div class="empty-state farmer-reviews-empty">
                <div class="farmer-reviews-empty-icon">⭐</div>
                <h3>No Reviews Yet</h3>
                <p>You haven't received any product reviews yet.</p>
            </div>
        <?php else: ?>
            <div class="reviews-grid farmer-reviews-grid">
                <?php foreach ($reviewsList as $review): ?>
                    <?php
                    $rating = (int)($review->rating ?? 0);
                    $isComplaint = $rating > 0 && $rating <= 2;
                    $buyerName = trim((string)($review->buyer_name ?? 'Buyer'));
                    $buyerInitial = strtoupper(substr($buyerName !== '' ? $buyerName : 'B', 0, 1));
                    $orderNumber = (int)($review->order_id ?? 0);
                    $quantity = (float)($review->reviewed_quantity ?? 0);
                    $productsLabel = trim((string)($review->order_products ?? ''));
                    if ($productsLabel === '') {
                        $productsLabel = (string)($review->product_name ?? '-');
                    }
                    ?>

                    <div class="review-card farmer-review-card">
                        <div class="farmer-review-header">
                            <div class="farmer-review-person">
                                <div class="buyer-avatar"><?= htmlspecialchars($buyerInitial) ?></div>
                                <div class="farmer-review-meta">
                                    <h4>Order #<?= $orderNumber > 0 ? $orderNumber : '-' ?></h4>
                                    <p>Reviewed by <strong><?= htmlspecialchars($buyerName) ?></strong></p>
                                    <p class="farmer-review-date"><?= date('M d, Y', strtotime((string)$review->created_at)) ?></p>
                                </div>
                            </div>

                            <div class="farmer-review-rating">
                                <div class="star-rating">
                                    <?= str_repeat('★', max(0, min(5, $rating))) . str_repeat('☆', max(0, 5 - $rating)) ?>
                                </div>
                                <?php if ($isComplaint): ?>
                                    <span class="feedback-badge negative">Complaint</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="farmer-review-facts">
                            <div class="farmer-review-fact">
                                <span class="farmer-review-fact-label">Order Number:</span>
                                <span class="farmer-review-fact-value">#<?= $orderNumber > 0 ? $orderNumber : '-' ?></span>
                            </div>
                            <div class="farmer-review-fact">
                                <span class="farmer-review-fact-label">Products:</span>
                                <span class="farmer-review-fact-value"><?= htmlspecialchars($productsLabel) ?></span>
                            </div>
                            <div class="farmer-review-fact">
                                <span class="farmer-review-fact-label">Quantities:</span>
                                <span class="farmer-review-fact-value"><?= rtrim(rtrim(number_format($quantity, 2), '0'), '.') ?></span>
                            </div>
                        </div>

                        <div class="review-body farmer-review-body <?= $isComplaint ? 'is-complaint' : '' ?>">
                            <p><?= nl2br(htmlspecialchars((string)$review->comment)) ?></p>
                        </div>

                        <?php if (!empty($review->reply)): ?>
                            <div class="farmer-reply">
                                <p class="farmer-reply-title">Your Reply</p>
                                <p class="farmer-reply-text"><?= nl2br(htmlspecialchars((string)$review->reply)) ?></p>
                                <span class="farmer-reply-date">Replied on <?= date('M d, Y', strtotime((string)$review->replied_at)) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="farmer-review-actions">
                                <button class="btn btn-sm btn-outline" onclick="toggleReplyForm(<?= (int)$review->id ?>)">Reply to Review</button>

                                <form id="reply-form-<?= (int)$review->id ?>" class="reply-form farmer-reply-form" onsubmit="submitReply(event, <?= (int)$review->id ?>)">
                                    <textarea name="reply" class="form-control" rows="3" placeholder="Write your response..." required></textarea>
                                    <div class="farmer-reply-form-actions">
                                        <button type="submit" class="btn btn-sm btn-primary">Post Reply</button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleReplyForm(<?= (int)$review->id ?>)">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleReplyForm(id) {
        const form = document.getElementById('reply-form-' + id);
        if (!form) return;
        form.classList.toggle('show');
    }

    function submitReply(e, reviewId) {
        e.preventDefault();

        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        const replyText = form.querySelector('textarea').value;

        btn.disabled = true;
        btn.textContent = 'Posting...';

        const formData = new FormData();
        formData.append('review_id', reviewId);
        formData.append('reply', replyText);

        fetch('<?= ROOT ?>/farmerreviews/reply', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                    return;
                }
                showNotification(data.message || 'Failed to post reply', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            });
    }
</script>