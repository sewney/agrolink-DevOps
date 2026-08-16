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

function formatLkr(amount) {
    return `Rs. ${Number(amount || 0).toFixed(2)}`;
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = value;
    }
}

function updateEarningsStats(earnings) {
    const today = Number(earnings.today_earnings || 0);
    const week = Number(earnings.week_earnings || 0);
    const month = Number(earnings.month_earnings || 0);
    const total = Number(earnings.total_earnings || 0);
    const completed = Number(earnings.completed_deliveries || 0);
    const active = Number(earnings.active_deliveries || 0);
    const avgPerDelivery = completed > 0 ? total / completed : 0;

    setText('todayEarnings', formatLkr(today));
    setText('weekEarnings', formatLkr(week));
    setText('monthEarningsDetail', formatLkr(month));
    setText('totalEarningsDetail', formatLkr(total));

    setText('todayBreakdownValue', formatLkr(today));
    setText('weekBreakdownValue', formatLkr(week));
    setText('monthBreakdownValue', formatLkr(month));
    setText('lifetimeBreakdownValue', formatLkr(total));
    setText('estimatedPayoutValue', formatLkr(month));

    setText('earningsCompletedDeliveries', String(completed));
    setText('earningsActiveDeliveries', String(active));
    setText('avgPerDeliveryValue', formatLkr(avgPerDelivery));

    const context = document.getElementById('earningsContextNote');
    if (context) {
        const suffix = completed === 1 ? 'delivery' : 'deliveries';
        context.textContent = completed > 0
            ? `Based on ${completed} completed ${suffix} currently recorded on your transporter account.`
            : 'Based on completed deliveries currently recorded on your transporter account.';
    }
}

function loadEarningsData() {
    fetch(transporterApi('getEarnings'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            if (data.success && data.earnings) {
                updateEarningsStats(data.earnings);
                return;
            }

            notify('Failed to load earnings', 'error');
        })
        .catch((error) => {
            console.error('Error loading earnings:', error);
            notify('Failed to load earnings', 'error');
        });
}

function displayPaymentHistory(deliveries) {
    const tbody = document.getElementById('paymentHistoryBody');
    if (!tbody) return;

    if (!deliveries || deliveries.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                    No payment history yet
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = deliveries.map((delivery) => {
        const date = new Date(delivery.updated_at);
        const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        return `
            <tr>
                <td>${formattedDate}</td>
                <td>#${delivery.order_id}</td>
                <td>
                    <div style="font-size: 0.9rem;">${delivery.farmer_city || delivery.farmer_district_name || 'N/A'}</div>
                    <div style="font-size: 0.85rem; color: #666;">to ${delivery.buyer_city || delivery.buyer_district_name || 'N/A'}</div>
                </td>
                <td>Rs. ${parseFloat(delivery.shipping_fee).toFixed(2)}</td>
                <td><span class="badge badge-success">Completed</span></td>
            </tr>
        `;
    }).join('');
}

function loadPaymentHistory() {
    fetch(transporterApi('getMyRequests?status=delivered'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            if (!data.success) {
                displayPaymentHistory([]);
                return;
            }

            const deliveries = data.requests || [];
            displayPaymentHistory(deliveries);
        })
        .catch((error) => {
            console.error('Error loading payment history:', error);
            displayPaymentHistory([]);
        });
}

function convertToCSV(deliveries) {
    const headers = ['Date', 'Order ID', 'From', 'To', 'Payment', 'Status'];
    const rows = deliveries.map((d) => [
        new Date(d.updated_at).toLocaleDateString(),
        d.order_id,
        d.farmer_city || d.farmer_district_name || 'N/A',
        d.buyer_city || d.buyer_district_name || 'N/A',
        parseFloat(d.shipping_fee).toFixed(2),
        'Completed',
    ]);

    return [
        headers.join(','),
        ...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
    ].join('\n');
}

function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function exportPaymentHistory() {
    fetch(transporterApi('getMyRequests?status=delivered'), { credentials: 'include' })
        .then(parseJsonResponse)
        .then((data) => {
            if (data.success && data.requests && data.requests.length > 0) {
                const csv = convertToCSV(data.requests);
                downloadCSV(csv, 'payment_history.csv');
                notify('Payment history exported successfully', 'success');
                return;
            }

            notify('No payment history to export', 'info');
        })
        .catch((error) => {
            console.error('Error exporting payment history:', error);
            notify('Failed to export payment history', 'error');
        });
}

window.TransporterEarnings = {
    exportPaymentHistory,
};

document.addEventListener('DOMContentLoaded', function () {
    loadEarningsData();
    loadPaymentHistory();
});
})();
