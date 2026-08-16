<div class="buyer-reviews-page">
    <div class="content-header">
        <h1 class="content-title">My Reviews & Complaints</h1>
        <p class="content-subtitle">Review farmers after confirmation, and transporters once orders are shipped</p>
    </div>

    <div class="content-card buyer-feedback-composer-card">
        <div class="card-header">
            <h3 class="card-title">Write New Feedback</h3>
        </div>
        <div class="card-content">
            <div class="buyer-feedback-sections">
                <div>
                    <h4 class="buyer-feedback-section-title">Farmer Reviews</h4>
                    <?php if (empty($reviewableItems)): ?>
                        <div class="buyer-feedback-empty">
                            No farmer reviews pending right now.
                        </div>
                    <?php else: ?>
                        <div class="buyer-feedback-pending-list">
                            <?php foreach ($reviewableItems as $item): ?>
                                <?php $orderStatusClass = strtolower((string)($item->order_status ?? 'pending')); ?>
                                <div class="buyer-feedback-pending-item">
                                    <div>
                                        <div class="buyer-feedback-pending-title">Order #ORD-<?= (int)$item->order_id ?></div>
                                        <div class="buyer-feedback-pending-meta">
                                            <span>Item: <?= htmlspecialchars($item->product_name) ?></span>
                                            <span>Qty: <?= htmlspecialchars(rtrim(rtrim(number_format((float)($item->quantity ?? 0), 2, '.', ''), '0'), '.')) ?> kg</span>
                                            <span>Items: <?= (int)($item->order_item_count ?? 1) ?></span>
                                            <span class="order-status buyer-reviews-status <?= htmlspecialchars($orderStatusClass) ?>">
                                                <?= strtoupper(str_replace('_', ' ', $orderStatusClass)) ?>
                                            </span>
                                            <span>Farmer: <?= htmlspecialchars($item->farmer_name ?? 'Unknown') ?></span>
                                        </div>
                                    </div>
                                    <button
                                        class="btn btn-primary buyer-feedback-pending-action"
                                        onclick="BuyerReviews.openReviewModal('farmer', <?= (int)$item->order_id ?>, <?= (int)$item->product_id ?>, <?= (int)$item->farmer_id ?>, '<?= addslashes(htmlspecialchars($item->product_name)) ?>', '<?= addslashes(htmlspecialchars($item->farmer_name ?? 'Farmer')) ?>')">
                                        Review Farmer
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <h4 class="buyer-feedback-section-title">Transporter Reviews</h4>
                    <?php if (empty($reviewableTransporters ?? [])): ?>
                        <div class="buyer-feedback-empty">
                            No transporter reviews pending right now.
                        </div>
                    <?php else: ?>
                        <div class="buyer-feedback-pending-list">
                            <?php foreach (($reviewableTransporters ?? []) as $item): ?>
                                <?php $orderStatusClass = strtolower((string)($item->order_status ?? 'pending')); ?>
                                <div class="buyer-feedback-pending-item">
                                    <div>
                                        <div class="buyer-feedback-pending-title">Order #ORD-<?= (int)$item->order_id ?></div>
                                        <div class="buyer-feedback-pending-meta">
                                            <span>Items: <?= (int)($item->order_item_count ?? 1) ?></span>
                                            <span>Qty: <?= htmlspecialchars(rtrim(rtrim(number_format((float)($item->order_total_quantity ?? 0), 2, '.', ''), '0'), '.')) ?> kg</span>
                                            <span class="order-status buyer-reviews-status <?= htmlspecialchars($orderStatusClass) ?>">
                                                <?= strtoupper(str_replace('_', ' ', $orderStatusClass)) ?>
                                            </span>
                                            <span>Transporter: <?= htmlspecialchars($item->transporter_name ?? 'Unknown') ?></span>
                                        </div>
                                    </div>
                                    <button
                                        class="btn btn-primary buyer-feedback-pending-action"
                                        onclick="BuyerReviews.openReviewModal('transporter', <?= (int)$item->order_id ?>, <?= (int)$item->product_id ?>, <?= (int)$item->transporter_id ?>, '<?= addslashes(htmlspecialchars($item->product_name ?? ('Order #' . (int)$item->order_id))) ?>', '<?= addslashes(htmlspecialchars($item->transporter_name ?? 'Transporter')) ?>')">
                                        Review Transporter
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="reviews-container">
        <?php if (empty($reviews)): ?>
            <div class="empty-state buyer-reviews-empty-state">
                <div class="buyer-reviews-empty-icon">📝</div>
                <h3>No Reviews Yet</h3>
                <p>Your submitted reviews and complaints will appear here.</p>
            </div>
        <?php else: ?>
            <div class="buyer-reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <?php $isComplaint = (int)$review->rating <= 2; ?>
                    <?php $displayOrderItemName = $review->order_item_name ?: $review->product_name; ?>
                    <div class="review-card buyer-review-card">
                        <div class="buyer-review-header">
                            <div class="buyer-review-main">
                                <?php if (!empty($review->product_image) && file_exists("assets/images/products/" . $review->product_image)): ?>
                                    <img
                                        src="<?= ROOT ?>/assets/images/products/<?= htmlspecialchars($review->product_image) ?>"
                                        alt="<?= htmlspecialchars($review->product_name) ?>"
                                        class="buyer-review-thumb">
                                <?php else: ?>
                                    <div class="buyer-review-placeholder">🌱</div>
                                <?php endif; ?>

                                <div>
                                    <h4 class="buyer-review-meta-title">Order #ORD-<?= (int)$review->order_id ?></h4>
                                    <p class="buyer-review-meta-sub">
                                        Item: <span><?= htmlspecialchars($displayOrderItemName) ?></span>
                                        • Qty:
                                        <span>
                                            <?php
                                            $displayQty = (float)($review->reviewed_quantity ?? 0);
                                            if ($displayQty <= 0) {
                                                $displayQty = (float)($review->order_total_quantity ?? 0);
                                            }
                                            echo htmlspecialchars(rtrim(rtrim(number_format($displayQty, 2, '.', ''), '0'), '.'));
                                            ?>
                                        </span> kg
                                        • Items: <span><?= (int)($review->order_item_count ?? 1) ?></span>
                                    </p>
                                    <p class="buyer-review-meta-sub">
                                        <?php if (($review->target_role ?? 'farmer') === 'transporter'): ?>
                                            Transporter: <span><?= htmlspecialchars($review->target_name ?? $review->farmer_name) ?></span>
                                        <?php else: ?>
                                            Farmer: <span><?= htmlspecialchars($review->target_name ?? $review->farmer_name) ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="buyer-review-date">
                                        Reviewed on <?= date('M d, Y', strtotime($review->created_at)) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="buyer-review-rating">
                                <div class="buyer-review-rating-stars">
                                    <?= str_repeat('★', (int)$review->rating) . str_repeat('☆', 5 - (int)$review->rating) ?>
                                </div>
                                <?php if ($isComplaint): ?>
                                    <span class="buyer-review-complaint-badge">Complaint</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="buyer-review-comment <?= $isComplaint ? 'is-complaint' : '' ?>">
                            <p class="buyer-review-comment-text">
                                "<?= nl2br(htmlspecialchars($review->comment)) ?>"
                            </p>
                        </div>

                        <?php if (!empty($review->reply)): ?>
                            <div class="buyer-review-reply">
                                <p class="buyer-review-reply-title">Response from Farmer</p>
                                <p class="buyer-review-reply-text"><?= nl2br(htmlspecialchars($review->reply)) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="review-modal" class="modal buyer-review-modal">
    <div class="modal-content buyer-review-modal-content">
        <span class="close-modal buyer-review-modal-close" onclick="BuyerReviews.closeReviewModal()">&times;</span>
        <h2 class="buyer-review-modal-title">Write Review / Complaint</h2>
        <p id="review-product-name" class="buyer-review-modal-subtitle"></p>

        <form id="review-form" onsubmit="BuyerReviews.submitReview(event)">
            <input type="hidden" id="review-type" name="review_type" value="farmer">
            <input type="hidden" id="review-order-id" name="order_id">
            <input type="hidden" id="review-product-id" name="product_id">
            <input type="hidden" id="review-farmer-id" name="farmer_id">
            <input type="hidden" id="review-transporter-id" name="transporter_id">

            <div class="buyer-review-modal-rating">
                <label class="buyer-review-modal-label">Rate this product</label>
                <div class="rating-stars buyer-review-modal-stars">
                    <span onclick="BuyerReviews.setRating(1)" data-val="1">★</span>
                    <span onclick="BuyerReviews.setRating(2)" data-val="2">★</span>
                    <span onclick="BuyerReviews.setRating(3)" data-val="3">★</span>
                    <span onclick="BuyerReviews.setRating(4)" data-val="4">★</span>
                    <span onclick="BuyerReviews.setRating(5)" data-val="5">★</span>
                </div>
                <input type="hidden" id="review-rating" name="rating" required>
            </div>

            <div class="buyer-review-modal-field">
                <label for="review-comment" class="buyer-review-modal-label">Your feedback</label>
                <textarea
                    id="review-comment"
                    name="comment"
                    rows="4"
                    class="buyer-review-modal-textarea"
                    placeholder="Describe your experience. Use low ratings for complaints."
                    required></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-form-width">Submit</button>
        </form>
    </div>
</div>

<script src="<?= ROOT ?>/assets/js/systemDialog.js"></script>
<script>
    function showFeedbackNotice(message, type = 'info') {
        if (typeof showNotification === 'function') {
            showNotification(message, type);
            return;
        }
        if (typeof systemAlert === 'function') {
            systemAlert(message, 'Notice');
            return;
        }
    }

    function openReviewModal(type, orderId, productId, targetId, productName, targetName) {
        document.getElementById('review-type').value = type;
        document.getElementById('review-product-name').textContent = (type === 'transporter' ? 'Delivery: ' : 'Product: ') + productName + ' • ' + (type === 'transporter' ? 'Transporter: ' : 'Farmer: ') + targetName;
        document.getElementById('review-order-id').value = orderId;
        document.getElementById('review-product-id').value = productId;
        document.getElementById('review-farmer-id').value = type === 'farmer' ? targetId : '';
        document.getElementById('review-transporter-id').value = type === 'transporter' ? targetId : '';
        document.getElementById('review-rating').value = '';
        document.getElementById('review-comment').value = '';
        highlightStars(0);
        document.getElementById('review-modal').style.display = 'block';
    }

    function closeReviewModal() {
        const modal = document.getElementById('review-modal');
        if (modal) modal.style.display = 'none';
    }

    function setRating(val) {
        document.getElementById('review-rating').value = val;
        highlightStars(val);
    }

    function highlightStars(val) {
        const stars = document.querySelectorAll('.rating-stars span');
        stars.forEach(star => {
            star.style.color = Number(star.dataset.val) <= Number(val) ? '#f59e0b' : '#d0d5dd';
        });
    }

    function submitReview(e) {
        e.preventDefault();
        const rating = document.getElementById('review-rating').value;
        if (!rating) {
            showFeedbackNotice('Please select a star rating', 'error');
            return;
        }

        const formData = new FormData(e.target);
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Submitting...';

        fetch('<?= ROOT ?>/buyerreviews/submit', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFeedbackNotice(data.message || 'Review submitted', 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showFeedbackNotice(data.message || 'Failed to submit review', 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            })
            .catch(() => {
                showFeedbackNotice('An error occurred while submitting review', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            });
    }

    document.querySelectorAll('.rating-stars span').forEach(star => {
        star.addEventListener('mouseover', function() {
            highlightStars(this.dataset.val);
        });
        star.addEventListener('mouseout', function() {
            highlightStars(document.getElementById('review-rating').value || 0);
        });
    });

    window.BuyerReviews = {
        openReviewModal,
        closeReviewModal,
        setRating,
        submitReview
    };

    // Backward-compatible aliases
    window.openReviewModal = window.BuyerReviews.openReviewModal;
    window.closeReviewModal = window.BuyerReviews.closeReviewModal;
    window.setRating = window.BuyerReviews.setRating;
    window.submitReview = window.BuyerReviews.submitReview;
</script>
