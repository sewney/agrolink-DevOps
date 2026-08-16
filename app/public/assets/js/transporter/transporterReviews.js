(function () {
'use strict';

function notify(message, type) {
    const text = String(message || '').trim() || 'Notification';

    if (typeof window.showNotification === 'function') {
        window.showNotification(text, type || 'info');
        return;
    }

    const notification = document.getElementById('notification');
    if (notification) {
        notification.textContent = text;
        notification.className = `notification ${type || 'info'} show`;
        setTimeout(() => notification.classList.remove('show'), 2200);
        return;
    }

    alert(text);
}

function getTransporterApiBase() {
    const origin = String(window.location.origin || '').replace(/\/+$/, '');
    const path = String(window.location.pathname || '');
    const publicMatch = path.match(/^(.*\/public)(?:\/|$)/i);
    if (publicMatch && publicMatch[1]) {
        return origin + publicMatch[1];
    }

    const appRoot = String(window.APP_ROOT || '').replace(/\/+$/, '');
    if (appRoot) {
        return appRoot;
    }

    return origin;
}

function transporterApi(path) {
    const cleanPath = String(path || '').replace(/^\/+/, '');
    return `${getTransporterApiBase()}/transporterdashboard/${cleanPath}`;
}

function parseJsonResponse(response) {
    return response.text().then((raw) => {
        try {
            return JSON.parse(raw);
        } catch (error) {
            throw new Error('Invalid JSON response');
        }
    });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';

    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return String(text).replace(/[&<>"']/g, (m) => map[m]);
}

function loadFeedbackReviews() {
    const unifiedEl = document.getElementById('feedbackUnifiedList');

    fetch(transporterApi('getFeedbackReviews'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            const reviews = (data.success && Array.isArray(data.reviews)) ? data.reviews : [];
            const totalEl = document.getElementById('feedbackTotalCount');
            const avgEl = document.getElementById('feedbackAvgRating');
            const complaintsCountEl = document.getElementById('feedbackComplaintCount');

            const complaints = reviews.filter((r) => Number(r.rating) <= 2);
            const avg = reviews.length
                ? (reviews.reduce((sum, r) => sum + Number(r.rating || 0), 0) / reviews.length)
                : 0;

            if (totalEl) totalEl.textContent = String(reviews.length);
            if (avgEl) avgEl.textContent = avg.toFixed(1);
            if (complaintsCountEl) complaintsCountEl.textContent = String(complaints.length);

            const renderCard = (item) => {
                const rating = Number(item.rating || 0);
                const isComplaint = rating <= 2;
                const boundedRating = Math.min(Math.max(rating, 0), 5);
                const stars = `${'&#9733;'.repeat(boundedRating)}${'&#9734;'.repeat(Math.max(0, 5 - boundedRating))}`;
                const buyerName = escapeHtml(item.buyer_name || 'Buyer');
                const buyerInitial = buyerName.charAt(0).toUpperCase();
                const created = item.created_at ? new Date(item.created_at).toLocaleDateString() : '-';
                const orderText = item.order_id ? `#${item.order_id}` : '-';
                const productText = escapeHtml(item.product_name || 'Order Item');
                const deliveryLabel = String(item.delivery_id || item.delivery_request_id || '').trim();
                const deliveryText = deliveryLabel !== '' ? `#${escapeHtml(deliveryLabel)}` : '-';
                const routeFrom = escapeHtml(item.farmer_city || item.pickup_city || item.farmer_district_name || 'Pickup unavailable');
                const routeTo = escapeHtml(item.buyer_city || item.dropoff_city || item.buyer_district_name || 'Dropoff unavailable');
                const deliveryStatus = escapeHtml(String(item.delivery_status || item.order_status || 'unknown').replace('_', ' '));
                const reviewedQty = Number(item.reviewed_quantity || 0);
                const quantityText = Number.isFinite(reviewedQty)
                    ? reviewedQty.toLocaleString(undefined, { maximumFractionDigits: 2 })
                    : '-';

                return `
                    <div class="review-card transporter-review-card">
                        <div class="transporter-review-header">
                            <div class="transporter-review-person">
                                <div class="buyer-avatar">${buyerInitial}</div>
                                <div class="transporter-review-meta">
                                    <h4>Order ${orderText}</h4>
                                    <p>Feedback by <strong>${buyerName}</strong></p>
                                    <p class="transporter-review-date">${created}</p>
                                </div>
                            </div>
                            <div class="transporter-review-rating">
                                <div class="star-rating">${stars}</div>
                                ${isComplaint ? '<span class="feedback-badge negative">Complaint</span>' : ''}
                            </div>
                        </div>

                        <div class="transporter-review-facts">
                            <div class="transporter-review-fact">
                                <span class="transporter-review-fact-label">Order Number</span>
                                <span class="transporter-review-fact-value">${orderText}</span>
                            </div>
                            <div class="transporter-review-fact">
                                <span class="transporter-review-fact-label">Products</span>
                                <span class="transporter-review-fact-value">${productText}</span>
                            </div>
                            <div class="transporter-review-fact">
                                <span class="transporter-review-fact-label">Quantities</span>
                                <span class="transporter-review-fact-value">${quantityText}</span>
                            </div>
                            <div class="transporter-review-fact">
                                <span class="transporter-review-fact-label">Delivery ID</span>
                                <span class="transporter-review-fact-value">${deliveryText}</span>
                            </div>
                            <div class="transporter-review-fact">
                                <span class="transporter-review-fact-label">Route</span>
                                <span class="transporter-review-fact-value">${routeFrom} - ${routeTo}</span>
                            </div>
                            <div class="transporter-review-fact">
                                <span class="transporter-review-fact-label">Status</span>
                                <span class="transporter-review-fact-value">${deliveryStatus}</span>
                            </div>
                        </div>

                        <div class="transporter-review-body ${isComplaint ? 'is-complaint' : ''}">
                            <p>${escapeHtml(item.comment || '')}</p>
                        </div>
                    </div>
                `;
            };

            if (unifiedEl) {
                unifiedEl.innerHTML = reviews.length
                    ? `<div class="reviews-grid transporter-feedback-grid">${reviews.map(renderCard).join('')}</div>`
                    : '<div class="transporter-feedback-empty">No feedback yet</div>';
            }
        })
        .catch((error) => {
            console.error('Error loading feedback reviews:', error);
            if (unifiedEl) {
                unifiedEl.innerHTML = '<div class="transporter-feedback-empty">Failed to load feedback. Please refresh.</div>';
            }
            notify('Failed to load reviews', 'error');
        });
}

window.TransporterReviews = {
    reload: loadFeedbackReviews,
};

document.addEventListener('DOMContentLoaded', function () {
    loadFeedbackReviews();
});
})();
