// Transporter Dashboard scripts: dashboard-only logic + page redirects.
(function () {
'use strict';

function getBaseUrl() {
    const origin = String(window.location.origin || '').replace(/\/+$/, '');
    const path = String(window.location.pathname || '');

    const publicMatch = path.match(/^(.*\/public)(?:\/|$)/i);
    if (publicMatch && publicMatch[1]) {
        return origin + publicMatch[1];
    }

    if (window.APP_ROOT) {
        return String(window.APP_ROOT).replace(/\/+$/, '');
    }

    const scripts = document.querySelectorAll('script[src*="/assets/"]');
    if (scripts.length > 0) {
        const scriptSrc = String(scripts[0].getAttribute('src') || '');
        return scriptSrc.substring(0, scriptSrc.indexOf('/assets')).replace(/\/+$/, '');
    }

    return '';
}

function navigateTo(path) {
    const base = getBaseUrl();
    if (!base) return;
    window.location.href = `${base}${path}`;
}

function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    if (notification) {
        notification.textContent = String(message || '');
        notification.className = `notification ${type} show`;
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3500);
        return;
    }

    const notificationDiv = document.createElement('div');
    notificationDiv.className = `notification ${type} show`;
    notificationDiv.textContent = String(message || '');
    notificationDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 15px 20px; background: ' +
        (type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3') +
        '; color: white; border-radius: 4px; z-index: 10000; box-shadow: 0 2px 5px rgba(0,0,0,0.2);';
    document.body.appendChild(notificationDiv);

    setTimeout(() => {
        notificationDiv.remove();
    }, 3500);
}

function updateDashboardStats(earnings) {
    const weeklyEarnings = document.getElementById('weeklyEarnings');
    if (weeklyEarnings && earnings.week_earnings) {
        weeklyEarnings.textContent = `Rs. ${parseFloat(earnings.week_earnings).toFixed(2)}`;
    }

    const activeDeliveries = document.getElementById('activeDeliveries');
    if (activeDeliveries && earnings.active_deliveries !== undefined) {
        activeDeliveries.textContent = earnings.active_deliveries;
    }

    const completedDeliveries = document.getElementById('completedDeliveries');
    if (completedDeliveries && earnings.completed_deliveries !== undefined) {
        completedDeliveries.textContent = earnings.completed_deliveries;
    }

    const monthlyEarnings = document.getElementById('monthlyEarnings');
    if (monthlyEarnings && earnings.month_earnings) {
        monthlyEarnings.textContent = `Rs. ${parseFloat(earnings.month_earnings).toFixed(2)}`;
    }

    const weeklyCompletedDeliveries = document.getElementById('weeklyCompletedDeliveries');
    if (weeklyCompletedDeliveries && earnings.completed_deliveries !== undefined) {
        const count = earnings.completed_deliveries || 0;
        weeklyCompletedDeliveries.textContent = `${count} ${count === 1 ? 'delivery' : 'deliveries'} completed`;
    }

    const weeklyPendingDeliveries = document.getElementById('weeklyPendingDeliveries');
    if (weeklyPendingDeliveries && earnings.active_deliveries !== undefined) {
        const count = earnings.active_deliveries || 0;
        weeklyPendingDeliveries.textContent = `${count} ${count === 1 ? 'delivery' : 'deliveries'} pending`;
    }

    const currentOrders = document.getElementById('currentOrders');
    if (currentOrders && earnings.in_transit_deliveries !== undefined) {
        currentOrders.textContent = earnings.in_transit_deliveries > 0 ? earnings.in_transit_deliveries + ' orders' : 'No active orders';
    }

    const nextOrders = document.getElementById('nextOrders');
    if (nextOrders && earnings.accepted_deliveries !== undefined) {
        nextOrders.textContent = earnings.accepted_deliveries > 0 ? earnings.accepted_deliveries + ' orders' : 'No pending orders';
    }
}

function displayRecentDeliveries(deliveries) {
    const container = document.getElementById('recentDeliveries');
    if (!container) return;

    if (!deliveries || deliveries.length === 0) {
        container.innerHTML = '<div style="padding: 18px; text-align: center; color: #666;">No recent deliveries</div>';
        return;
    }

    container.innerHTML = deliveries.map((delivery) => `
        <div style="padding: 12px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">Order #${delivery.order_id}</div>
                <div style="font-size: 0.85rem; color: #666;">${delivery.farmer_city || delivery.farmer_district_name} -> ${delivery.buyer_city || delivery.buyer_district_name}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 700; color: #4caf50;">Rs. ${parseFloat(delivery.shipping_fee).toFixed(2)}</div>
                <div style="font-size: 0.8rem; color: #666;">${new Date(delivery.updated_at).toLocaleDateString()}</div>
            </div>
        </div>
    `).join('');
}

function loadDashboardData() {
    fetch(getBaseUrl() + '/transporterdashboard/getEarnings', { credentials: 'include' })
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.earnings) {
                updateDashboardStats(data.earnings);
            }
        })
        .catch((error) => console.error('Error loading dashboard stats:', error));
}

function loadRecentDeliveries() {
    fetch(getBaseUrl() + '/transporterdashboard/getMyRequests?status=delivered', { credentials: 'include' })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const deliveries = data.requests || [];
                displayRecentDeliveries(deliveries.slice(0, 5));
            }
        })
        .catch((error) => console.error('Error loading recent deliveries:', error));
}

function toggleAvailability() {
    fetch(getBaseUrl() + '/transporterdashboard/toggleAvailability', {
        method: 'POST',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('availabilityBtn');
            const status = document.getElementById('currentStatus');
            const indicator = document.getElementById('statusIndicator');

            if (!btn || !status || !indicator) return;

            if (data.newStatus === 'available') {
                status.textContent = 'Available';
                btn.textContent = 'Go Offline';
                indicator.style.background = '#4CAF50';
                showNotification('You are now available for deliveries', 'success');
            } else {
                status.textContent = 'Offline';
                btn.textContent = 'Go Online';
                indicator.style.background = '#f44336';
                showNotification('You are now offline', 'info');
            }
        } else {
            showNotification(data.message || 'Failed to change status', 'error');
        }
    })
    .catch(error => {
        console.error('Error toggling availability:', error);
        showNotification('Failed to change status. Please try again.', 'error');
    });
}

function redirectForSection(sectionName) {
    const routes = {
        'available-deliveries': '/transporterrequests',
        mydeliveries: '/transporterdeliveries',
        vehicle: '/transportervehicles',
        schedule: '/transporterdeliveries',
        earnings: '/transporterearnings',
        feedback: '/transporterreviews',
        reviews: '/transporterreviews',
        notifications: '/transporternotifications',
        profile: '/transporterprofile'
    };

    const target = routes[String(sectionName || '').trim()];
    if (!target) {
        return false;
    }

    navigateTo(target);
    return true;
}

function showSection(sectionName) {
    if (sectionName === 'dashboard' || !sectionName) {
        loadDashboardData();
        loadRecentDeliveries();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    redirectForSection(sectionName);
}

function initializeTransporterDashboard() {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (section && section !== 'dashboard' && redirectForSection(section)) {
        return;
    }

    const hash = window.location.hash.substring(1);
    if (hash && hash !== 'dashboard' && redirectForSection(hash)) {
        return;
    }

    loadDashboardData();
    loadRecentDeliveries();
}

document.addEventListener('DOMContentLoaded', function () {
    initializeTransporterDashboard();
});

window.TransporterDashboard = {
    showSection,
    refreshDeliveries: function () {
        navigateTo('/transporterrequests');
    },
    filterMyDeliveries: function () {
        navigateTo('/transporterdeliveries');
    },
    updateDeliveryStatus: function () {
        navigateTo('/transporterdeliveries');
    },
    acceptDeliveryRequest: function () {
        navigateTo('/transporterrequests');
    }
};

if (typeof window.showSection !== 'function') window.showSection = showSection;
if (typeof window.toggleAvailability !== 'function') window.toggleAvailability = toggleAvailability;
if (typeof window.updateLocation !== 'function') window.updateLocation = updateLocation;
if (typeof window.showNotification !== 'function') window.showNotification = showNotification;
if (typeof window.getBaseUrl !== 'function') window.getBaseUrl = getBaseUrl;
})();
