(function () {
    'use strict';

    const APP_ROOT = window.APP_ROOT || document.body.getAttribute('data-app-root') || '';
    const seedEl = document.getElementById('buyerNotificationsSeed');
    const seed = seedEl ? JSON.parse(seedEl.textContent || '{}') : {};

    let notifications = Array.isArray(seed.notifications) ? seed.notifications : [];
    let unreadCount = Number(seed.unreadCount || 0);
    let activeFilter = 'all';
    let knownNotificationKeys = new Set();

    function notificationKey(item) {
        if (!item || typeof item !== 'object') return '';
        return String(item.event_key || item.id || '').trim();
    }

    function rebuildKnownKeys() {
        knownNotificationKeys = new Set(
            notifications
                .map(notificationKey)
                .filter(Boolean)
        );
    }

    function notifyUser(message, type) {
        const safeMessage = String(message || '').trim();
        const compactMessage = safeMessage.length > 80
            ? `${safeMessage.slice(0, 77)}...`
            : safeMessage;

        if (typeof window.showNotification === 'function') {
            window.showNotification(compactMessage || 'Notification', type || 'info');
            return;
        }

        const existing = document.querySelector('.notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `notification ${type || 'info'}`;
        toast.textContent = compactMessage || 'Notification';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 360px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2200);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toRoute(link) {
        if (!link || link === '#') return '#';

        const root = APP_ROOT.replace(/\/$/, '');
        const rootPath = root.replace(/^https?:\/\/[^/]+/i, '').replace(/^\//, '');
        let clean = String(link).trim();

        if (/^https?:\/\//i.test(clean)) {
            try {
                const parsed = new URL(clean, window.location.origin);
                if (parsed.origin !== window.location.origin) {
                    return clean;
                }
                clean = `${parsed.pathname}${parsed.search}${parsed.hash}`;
            } catch (error) {
                return clean;
            }
        }

        clean = clean.replace(/^\.\//, '').replace(/^\//, '');

        if (rootPath !== '' && clean.toLowerCase().startsWith(`${rootPath.toLowerCase()}/`)) {
            clean = clean.slice(rootPath.length + 1);
        }

        const publicIndex = clean.toLowerCase().lastIndexOf('public/');
        if (publicIndex >= 0) {
            clean = clean.slice(publicIndex + 7);
        }

        clean = clean.replace(/^index\.php\/?/i, '');
        clean = clean.replace(/^buyer\/buyerdashboard/i, 'buyerdashboard');
        clean = clean.replace(/^buyer\/buyernotifications/i, 'buyernotifications');

        if (clean === '') return '#';
        return `${root}/${clean}`;
    }

    function timeAgo(value) {
        const ts = new Date(value).getTime();
        if (!ts) return 'Just now';

        const diff = Math.max(0, Date.now() - ts);
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes} min${minutes > 1 ? 's' : ''} ago`;
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        return `${days} day${days > 1 ? 's' : ''} ago`;
    }

    function iconFor(type) {
        const map = {
            new_products: '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M12 11v6"/><path d="M9 14h6"/>',
            tracking: '<path d="M3 7h13v10H3z"/><path d="M16 10h4l3 3v4h-7z"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="18.5" cy="17.5" r="1.5"/>',
            review_replies: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h8"/><path d="M8 14h5"/>',
            request_replies: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13h6"/><path d="M9 17h4"/>',
            order_updates: '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/>',
            system: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><circle cx="12" cy="16" r="1"/>'
        };

        return map[type] || map.system;
    }

    function filteredNotifications() {
        if (activeFilter === 'all') return notifications;
        if (activeFilter === 'unread') return notifications.filter(item => !item.is_read);
        return notifications.filter(item => item.category === activeFilter);
    }

    function renderNotifications() {
        const list = document.getElementById('buyerNotificationsList');
        if (!list) return;

        const rows = filteredNotifications();
        if (!rows.length) {
            list.innerHTML = '<div class="notifications-empty">No notifications found for this filter.</div>';
            return;
        }

        list.innerHTML = rows.map(item => {
            const unreadClass = item.is_read ? '' : 'is-unread';
            const link = toRoute(item.link);
            const notificationId = Number(item.id || 0);

            return `
                <a class="notification-row ${unreadClass}" href="${escapeHtml(link)}" data-notification-id="${notificationId > 0 ? notificationId : ''}">
                    <div class="notification-type-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${iconFor(item.icon || item.category)}</svg>
                    </div>
                    <div class="notification-row-content">
                        <div class="notification-row-title-line">
                            <h4>${escapeHtml(item.title || 'Notification')}</h4>
                            ${item.is_read ? '' : '<span class="notification-unread-dot"></span>'}
                        </div>
                        <p>${escapeHtml(item.message || '')}</p>
                    </div>
                </a>
            `;
        }).join('');
    }

    function markNotificationRead(notificationId) {
        const id = Number(notificationId || 0);
        if (!id) return;

        const notification = notifications.find(item => Number(item.id || 0) === id);
        if (!notification || notification.is_read) return;

        notification.is_read = true;
        unreadCount = Math.max(0, unreadCount - 1);
        updateSidebarBadge();
        renderNotifications();

        const payload = new URLSearchParams({ notification_id: String(id) });
        const endpoint = `${APP_ROOT}/buyernotifications/markRead`;

        if (navigator.sendBeacon) {
            const blob = new Blob([payload.toString()], { type: 'application/x-www-form-urlencoded;charset=UTF-8' });
            navigator.sendBeacon(endpoint, blob);
            return;
        }

        fetch(endpoint, {
            method: 'POST',
            credentials: 'include',
            keepalive: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: payload.toString()
        }).catch(err => console.error('Buyer notification mark-read error:', err));
    }

    function updateActiveFilterUi() {
        document.querySelectorAll('.notification-filter-tab').forEach(tab => {
            tab.classList.toggle('is-active', tab.getAttribute('data-filter') === activeFilter);
        });
    }

    function updateSidebarBadge() {
        const badge = document.getElementById('buyerNotificationBadge');
        if (!badge) return;

        badge.textContent = String(unreadCount);
        badge.classList.toggle('is-hidden', unreadCount <= 0);
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('show');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('show');
    }

    function markAllAsRead() {
        fetch(`${APP_ROOT}/buyernotifications/markAllAsRead`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;

                notifications = Array.isArray(res.notifications)
                    ? res.notifications
                    : notifications.map(item => ({ ...item, is_read: true }));
                unreadCount = Number(res.unreadCount || 0);
                rebuildKnownKeys();

                updateSidebarBadge();
                renderNotifications();
            })
            .catch(err => console.error('Buyer notifications mark-all-read error:', err));
    }

    function saveSettings(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = new FormData(form);

        fetch(`${APP_ROOT}/buyernotifications/saveSettings`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;

                notifications = Array.isArray(res.notifications) ? res.notifications : [];
                unreadCount = Number(res.unreadCount || 0);
                activeFilter = 'all';
                rebuildKnownKeys();

                updateActiveFilterUi();
                updateSidebarBadge();
                renderNotifications();
                closeModal('buyerNotificationSettingsModal');
            })
            .catch(err => console.error('Buyer notification settings save error:', err));
    }

    function pollNotifications() {
        fetch(`${APP_ROOT}/buyernotifications/list?filter=all`, {
            method: 'GET',
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success || !Array.isArray(res.notifications)) return;

                const incoming = res.notifications;
                const newItems = incoming.filter(item => {
                    const key = notificationKey(item);
                    return key !== '' && !knownNotificationKeys.has(key);
                });

                notifications = incoming;
                unreadCount = Number(res.unreadCount || 0);
                rebuildKnownKeys();
                updateSidebarBadge();
                renderNotifications();

                if (newItems.length > 0) {
                    const preview = String(newItems[0].title || 'New notification');
                    const suffix = newItems.length > 1 ? ` (+${newItems.length - 1} more)` : '';
                    notifyUser(`${preview}${suffix}`, 'info');
                }
            })
            .catch(err => console.error('Buyer notifications polling error:', err));
    }

    function init() {
        rebuildKnownKeys();
        renderNotifications();
        updateSidebarBadge();

        document.querySelectorAll('.notification-filter-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                activeFilter = this.getAttribute('data-filter') || 'all';
                updateActiveFilterUi();
                renderNotifications();
            });
        });

        const notificationsList = document.getElementById('buyerNotificationsList');
        if (notificationsList) {
            notificationsList.addEventListener('click', function (event) {
                const link = event.target.closest('a.notification-row[data-notification-id]');
                if (!link) return;
                const notificationId = Number(link.getAttribute('data-notification-id') || 0);
                if (notificationId > 0) {
                    markNotificationRead(notificationId);
                }
            });
        }

        const markAllBtn = document.getElementById('markAllBuyerNotificationsReadBtn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', markAllAsRead);
        }

        const openSettingsBtn = document.getElementById('openBuyerNotificationSettingsBtn');
        if (openSettingsBtn) {
            openSettingsBtn.addEventListener('click', function () {
                openModal('buyerNotificationSettingsModal');
            });
        }

        const settingsForm = document.getElementById('buyerNotificationSettingsForm');
        if (settingsForm) settingsForm.addEventListener('submit', saveSettings);

        const clearFilterBtn = document.getElementById('clearBuyerNotificationFilterBtn');
        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', function () {
                activeFilter = 'all';
                updateActiveFilterUi();
                renderNotifications();
            });
        }

        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', function () {
                closeModal(this.getAttribute('data-close-modal'));
            });
        });

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) modal.classList.remove('show');
            });
        });

        setInterval(pollNotifications, 30000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
