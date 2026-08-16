(function () {
'use strict';

let availableRequests = [];

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

function loadAvailableRequests() {
    const container = document.getElementById('availableDeliveriesList');
    if (container) {
        container.innerHTML = '<div style="width: 100%; padding: 40px; text-align: center; color: #666;"><p>Loading delivery requests...</p></div>';
    }

    fetch(transporterApi('getAvailableRequests'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            if (data.success) {
                availableRequests = data.requests || [];
                displayAvailableRequests(availableRequests);
                return;
            }

            if (container) {
                container.innerHTML = '<div style="width: 100%; padding: 40px; text-align: center; color: #f44336;"><p>Failed to load delivery requests</p></div>';
            }
        })
        .catch((error) => {
            console.error('Error loading available requests:', error);
            if (container) {
                container.innerHTML = '<div style="width: 100%; padding: 40px; text-align: center; color: #f44336;"><p>Error loading delivery requests. Please try again.</p></div>';
            }
        });
}

function displayAvailableRequests(requests) {
    const container = document.getElementById('availableDeliveriesList');
    if (!container) return;

    if (!requests || requests.length === 0) {
        container.innerHTML = `
            <div style="width: 100%; padding: 40px; text-align: center; color: #666; background: #f8f9fa; border-radius: 12px;">
                <p style="margin: 0; font-size: 1.1rem;">No delivery requests available at the moment</p>
            </div>
        `;
        return;
    }

    container.innerHTML = requests.map((request) => `
        <div class="delivery-card" style="min-width: 320px; max-width: 380px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; flex-shrink: 0;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                <div>
                    <h4 style="margin: 0 0 4px 0; color: #2c3e50; font-size: 1.1rem;">Order #${request.order_id}</h4>
                    <span class="badge badge-info">${request.required_vehicle_type || 'Any Vehicle'}</span>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.4rem; font-weight: 700; color: #4caf50;">Rs. ${parseFloat(request.shipping_fee).toFixed(2)}</div>
                    <div style="font-size: 0.85rem; color: #666;">${parseFloat(request.total_weight_kg).toFixed(1)} kg</div>
                </div>
            </div>

            <div style="margin-bottom: 16px; padding: 12px; background: #f8f9fa; border-radius: 8px;">
                <div style="margin-bottom: 8px;">
                    <strong style="color: #2c3e50; font-size: 0.9rem;">📍 Pickup (Farmer)</strong>
                    <div style="color: #666; font-size: 0.9rem; margin-top: 4px;">${request.farmer_name}</div>
                    <div style="color: #666; font-size: 0.85rem;">${request.farmer_address || request.farmer_city || request.farmer_district_name || 'N/A'}</div>
                    ${request.farmer_phone ? `<div style="color: #666; font-size: 0.85rem;">📞 ${request.farmer_phone}</div>` : ''}
                </div>
            </div>

            <div style="margin-bottom: 16px; padding: 12px; background: #fff3e0; border-radius: 8px;">
                <div>
                    <strong style="color: #2c3e50; font-size: 0.9rem;">🎯 Delivery (Buyer)</strong>
                    <div style="color: #666; font-size: 0.9rem; margin-top: 4px;">${request.buyer_name}</div>
                    <div style="color: #666; font-size: 0.85rem;">${request.buyer_address || request.buyer_city || request.buyer_district_name || 'N/A'}</div>
                    ${request.buyer_phone ? `<div style="color: #666; font-size: 0.85rem;">📞 ${request.buyer_phone}</div>` : ''}
                </div>
            </div>

            ${request.distance_km ? `<div style="margin-bottom: 12px; color: #666; font-size: 0.9rem;">📏 Distance: ~${parseFloat(request.distance_km).toFixed(1)} km</div>` : ''}

            <button onclick="TransporterRequests.acceptDeliveryRequest(${request.id})" class="btn btn-primary btn-block">
                Accept Delivery
            </button>
        </div>
    `).join('');
}

function acceptDeliveryRequest(requestId) {
    if (!confirm('Are you sure you want to accept this delivery request?')) {
        return;
    }

    fetch(transporterApi(`acceptRequest/${requestId}`), {
        method: 'POST',
        credentials: 'include',
    })
        .then(parseJsonResponse)
        .then((data) => {
            if (data.success) {
                notify(data.message || 'Delivery request accepted successfully!', 'success');
                loadAvailableRequests();
            } else {
                notify(data.message || 'Failed to accept delivery request', 'error');
            }
        })
        .catch((error) => {
            console.error('Error accepting request:', error);
            notify('Failed to accept delivery request', 'error');
        });
}

function refreshDeliveries() {
    notify('Refreshing delivery requests...', 'info');
    loadAvailableRequests();
}

window.TransporterRequests = {
    refreshDeliveries,
    acceptDeliveryRequest,
};

document.addEventListener('DOMContentLoaded', function () {
    loadAvailableRequests();
});
})();
