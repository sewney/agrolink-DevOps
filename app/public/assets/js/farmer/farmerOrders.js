// Farmer Orders Page JavaScript
(function() {
    'use strict';

    const APP_ROOT = window.APP_ROOT || document.body.getAttribute('data-app-root') || '';

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initOrderDetailsButtons();
        initModal();
    });

    /**
     * Initialize view details buttons
     */
    function initOrderDetailsButtons() {
        const viewButtons = document.querySelectorAll('.btn-view-details');
        
        viewButtons.forEach(button => {
            if (button.dataset.bound === '1') return;
            button.dataset.bound = '1';
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                if (orderId) {
                    loadOrderDetails(orderId, this);
                }
            });
        });
    }

    function viewOrderDetails(orderId, triggerBtn = null) {
        if (!orderId) return;
        loadOrderDetails(orderId, triggerBtn);
    }

    /**
     * Load order details via AJAX
     */
    function loadOrderDetails(orderId, triggerBtn) {
        const modal = document.getElementById('orderDetailsModal');
        const contentDiv = document.getElementById('orderDetailsContent');

        if (!modal || !contentDiv) {
            console.error('Order details modal elements not found');
            showNotification('Order details view is not available on this page.', 'error');
            return;
        }
        
        // Show modal with loading state
        modal.classList.add('show');
        contentDiv.innerHTML = '<div class="loading">Loading order details...</div>';
        
        // Fetch order details
        if (triggerBtn) {
            triggerBtn.disabled = true;
        }

        fetch(`${APP_ROOT}/farmerorders/getOrderDetails?order_id=${orderId}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load order details');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                displayOrderDetails(data.order || {}, Array.isArray(data.items) ? data.items : []);
            } else {
                throw new Error((data && (data.error || data.message)) || 'Failed to load order details');
            }
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            contentDiv.innerHTML = `
                <div class="error-message">
                    <p>Failed to load order details. Please try again.</p>
                    <button class="btn-retry" onclick="location.reload()">Retry</button>
                </div>
            `;
        })
        .finally(() => {
            if (triggerBtn) triggerBtn.disabled = false;
        });
    }

    /**
     * Display order details in modal
     */
    function displayOrderDetails(order, items) {
        const contentDiv = document.getElementById('orderDetailsContent');
        if (!contentDiv) return;
        
        let itemsHtml = '';
        let total = 0;
        
        (items || []).forEach(item => {
            const unitPrice = parseFloat(item.product_price) || 0;
            const qty = parseInt(item.quantity, 10) || 0;
            const itemTotal = unitPrice * qty;
            total += itemTotal;
            const pickupAddress = escapeHtml(item.product_full_address || '');
            
            itemsHtml += `
                <div class="order-item">
                    <div class="item-details">
                        <div class="item-name">${escapeHtml(item.product_name)}</div>
                        <div class="item-meta">
                            <span>Price: LKR ${unitPrice.toFixed(2)}</span>
                            <span>Quantity: ${qty}</span>
                            ${pickupAddress ? `<span>Pickup: ${pickupAddress}</span>` : ''}
                        </div>
                    </div>
                    <div class="item-total">
                        LKR ${itemTotal.toFixed(2)}
                    </div>
                </div>
            `;
        });
        
        const safeStatus = String(order.status || 'pending');
        const safeBuyerName = escapeHtml(order.buyer_name || 'N/A');
        const safeAddress = escapeHtml(order.delivery_address || 'N/A');
        const safeCity = escapeHtml(order.delivery_city || 'N/A');
        const safeDistrict = escapeHtml(order.district_name || 'N/A');
        const safePhone = escapeHtml(order.delivery_phone || 'N/A');

        const html = `
            <div class="order-details-section">
                <div class="section-title">Order Information</div>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">#${order.id}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value">${formatDate(order.created_at)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-${safeStatus}">
                                ${safeStatus.replace('_', ' ').toUpperCase()}
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Buyer:</span>
                        <span class="detail-value">${safeBuyerName}</span>
                    </div>
                </div>
            </div>
            
            <div class="order-details-section">
                <div class="section-title">Delivery Information</div>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value">${safeAddress}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">City:</span>
                        <span class="detail-value">${safeCity}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">District:</span>
                        <span class="detail-value">${safeDistrict}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">${safePhone}</span>
                    </div>
                </div>
            </div>
            
            <div class="order-details-section">
                <div class="section-title">Your Items (${items.length})</div>
                <div class="order-items-list">
                    ${itemsHtml}
                </div>
                <div class="order-total-section">
                    <div class="total-row">
                        <span class="total-label">Your Total Earnings:</span>
                        <span class="total-value">LKR ${total.toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;
        
        contentDiv.innerHTML = html;
    }

    /**
     * Initialize modal functionality
     */
    function initModal() {
        const modal = document.getElementById('orderDetailsModal');
        const closeBtn = modal.querySelector('.modal-close');
        
        // Close on X button
        closeBtn.addEventListener('click', function() {
            modal.classList.remove('show');
        });
        
        // Close on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                modal.classList.remove('show');
            }
        });
    }

    /**
     * Update farmer order status in required sequence.
     */
    function updateOrderStatus(orderId, status) {
        const statusLabel = status.replace(/_/g, ' ').toUpperCase();
        if (!confirm(`Update this order to ${statusLabel}?`)) {
            return;
        }

        fetch(`${APP_ROOT}/farmerorders/updateOrderStatus`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Order status updated', 'success');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showNotification(data.error || 'Failed to update status', 'error');
            }
        })
        .catch(error => {
            console.error('Update order status error:', error);
            showNotification('Failed to update order status', 'error');
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Format date
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        if (Number.isNaN(date.getTime())) return 'N/A';
        const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return date.toLocaleDateString('en-US', options);
    }

    window.FarmerOrders = {
        updateOrderStatus,
        viewOrderDetails
    };

    // Backward-compatible alias (temporary)
    window.updateOrderStatus = window.FarmerOrders.updateOrderStatus;
    window.viewOrderDetails = window.FarmerOrders.viewOrderDetails;

})();
