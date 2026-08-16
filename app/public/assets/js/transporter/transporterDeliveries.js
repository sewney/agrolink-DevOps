(function () {
'use strict';

let myDeliveries = [];

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

function loadMyDeliveries(status) {
    const query = status ? `?status=${encodeURIComponent(status)}` : '';

    fetch(transporterApi(`getMyRequests${query}`), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            if (!data.success) {
                notify('Failed to load deliveries', 'error');
                return;
            }

            myDeliveries = data.requests || [];
            displayMyDeliveries(myDeliveries);
        })
        .catch((error) => {
            console.error('Error loading my deliveries:', error);
            notify('Failed to load deliveries', 'error');
        });
}

function displayMyDeliveries(deliveries) {
    const tbody = document.getElementById('myDeliveriesTableBody');
    if (!tbody) return;

    if (!deliveries || deliveries.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                    No deliveries found
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = deliveries.map((delivery) => {
        const safeStatus = String(delivery.status || 'pending').toLowerCase().replace(/[^a-z0-9_-]/g, '');
        const statusLabel = String(delivery.status || 'pending').replace(/_/g, ' ').toUpperCase();

        return `
            <tr>
                <td>#${delivery.order_id}</td>
                <td>
                    <div style="font-size: 0.9rem;">${delivery.farmer_city || delivery.farmer_district_name || 'N/A'}</div>
                    <div style="font-size: 0.85rem; color: #666;">to ${delivery.buyer_city || delivery.buyer_district_name || 'N/A'}</div>
                </td>
                <td>${delivery.distance_km ? `${parseFloat(delivery.distance_km).toFixed(1)} km` : 'N/A'}</td>
                <td>${parseFloat(delivery.total_weight_kg).toFixed(1)} kg</td>
                <td>Rs. ${parseFloat(delivery.shipping_fee).toFixed(2)}</td>
                <td>
                    <span class="order-status ${safeStatus}">
                        ${statusLabel}
                    </span>
                </td>
                <td>${delivery.created_at ? new Date(delivery.created_at).toLocaleDateString() : 'N/A'}</td>
                <td>
                    ${delivery.status === 'accepted' ? `
                        <button onclick="TransporterDeliveries.updateDeliveryStatus(${delivery.id}, 'in_transit')" class="btn btn-sm btn-primary">Start Transit</button>
                    ` : ''}
                    ${delivery.status === 'in_transit' ? `
                        <button onclick="TransporterDeliveries.updateDeliveryStatus(${delivery.id}, 'delivered')" class="btn btn-sm btn-success">Mark Delivered</button>
                    ` : ''}
                    ${delivery.status === 'delivered' ? `
                        <span class="badge badge-success">Completed</span>
                    ` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

function updateDeliveryStatus(deliveryId, newStatus) {
    const confirmMessages = {
        in_transit: 'Mark this delivery as in transit?',
        delivered: 'Mark this delivery as delivered?',
        cancelled: 'Cancel this delivery?',
    };

    if (!confirm(confirmMessages[newStatus] || 'Update delivery status?')) {
        return;
    }

    fetch(transporterApi(`updateDeliveryStatus/${deliveryId}`), {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `status=${encodeURIComponent(newStatus)}`,
    })
        .then(parseJsonResponse)
        .then((data) => {
            if (!data.success) {
                notify(data.message || 'Failed to update delivery status', 'error');
                return;
            }

            notify(data.message || 'Delivery status updated successfully', 'success');

            const activeBtn = document.querySelector('.transporter-delivery-filter-link.active');
            const activeStatus = activeBtn ? String(activeBtn.dataset.status || '').trim() : 'running';
            const filterStatus = activeStatus === 'all' ? null : activeStatus.replace('-', '_');
            loadMyDeliveries(filterStatus);
        })
        .catch((error) => {
            console.error('Error updating delivery status:', error);
            notify('Failed to update delivery status', 'error');
        });
}

function filterMyDeliveries(status) {
    document.querySelectorAll('.transporter-delivery-filter-link').forEach((btn) => {
        btn.classList.remove('active');
    });

    const selectedBtn = document.querySelector(`.transporter-delivery-filter-link[data-status="${status}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('active');
    }

    const filterStatus = status === 'all' ? null : status.replace('-', '_');
    loadMyDeliveries(filterStatus);
}

window.TransporterDeliveries = {
    filterMyDeliveries,
    updateDeliveryStatus,
};

document.addEventListener('DOMContentLoaded', function () {
    filterMyDeliveries('running');
});
})();
