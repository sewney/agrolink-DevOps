// AgroLink - Common Utilities & Shared Functions

// Initialize global variables from body data attributes
(function() {
    const body = document.body;
    const data = body ? body.dataset : {};

    // Preserve values seeded by inline scripts and only fall back to body data/local defaults.
    window.APP_ROOT = (data.appRoot || window.APP_ROOT || '').trim();
    window.USER_NAME = (data.userName || window.USER_NAME || '').trim();
    window.USER_EMAIL = (data.userEmail || window.USER_EMAIL || '').trim();
    window.USER_ROLE = (data.userRole || window.USER_ROLE || '').trim();
})();

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeCommon();
});

// Initialize common functionality
function initializeCommon() {
    initNavigation();
    initModals();
    updateUserState();
}

// Navigation functionality
function initNavigation() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
    
    // Active navigation links
    const currentPage = getPageName();
    const navItems = document.querySelectorAll('.nav-links a, .sidebar-menu a');
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage)) {
            item.classList.add('active');
        }
    });
}

// Get current page name
function getPageName() {
    const path = window.location.pathname;
    const page = path.split('/').pop().replace('.html', '') || 'index';
    return page;
}

// Modal functionality
function initModals() {
    // Modal triggers
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            openModal(modalId);
        });
    });
    
    // Modal close buttons
    const closeButtons = document.querySelectorAll('.modal-close, [data-modal-close]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Close modal on backdrop click
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
    
    // Prevent modal content click from closing modal
    document.querySelectorAll('.modal-content').forEach(content => {
        content.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active', 'show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active', 'show');
        document.body.style.overflow = 'auto';
    }
}

function removeFromCart(productId) {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    fetch(`${window.APP_ROOT}/cart/remove/${productId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh the page to update cart
            window.location.reload();
        } else {
            showNotification(data.message || 'Failed to remove item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to remove item', 'error');
    })
    .finally(() => {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    });
}

function updateCartQuantity(productId, change) {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    const quantityElement = document.querySelector(`[data-product-id="${productId}"] .quantity-display`);
    const newQuantity = Math.max(1, parseInt(quantityElement.textContent) + change);

    fetch(`${window.APP_ROOT}/cart/update/${productId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId, quantity: newQuantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh the page to update cart
            window.location.reload();
        } else {
            showNotification(data.message || 'Failed to update cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to update cart', 'error');
    })
    .finally(() => {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    });
}

// Form validation
function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    
    clearError(field);
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    if (field.type === 'email' && value && !isValidEmail(value)) {
        showFieldError(field, 'Please enter a valid email address');
        return false;
    }
    
    if (field.type === 'password' && value && value.length < 6) {
        showFieldError(field, 'Password must be at least 6 characters');
        return false;
    }
    
    if (field.name === 'confirmPassword') {
        const passwordField = field.form.querySelector('[name="password"]');
        if (passwordField && value !== passwordField.value) {
            showFieldError(field, 'Passwords do not match');
            return false;
        }
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    let errorElement = field.parentNode.querySelector('.form-text.error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'form-text error';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
}

function clearError(field) {
    if (typeof field === 'object' && field.target) {
        field = field.target;
    }
    
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.form-text.error');
    if (errorElement) {
        errorElement.remove();
    }
}

// User state management
function updateUserState() {
    // New PHP session-based navbar is rendered on the server.
    // Do not mutate its DOM with legacy localStorage auth behavior.
    if (document.querySelector('.nav-user-toggle') || document.getElementById('userDropdown')) {
        return;
    }

    const user = getCurrentUser();
    const userInfo = document.querySelector('.user-info');
    const loginLinks = document.querySelectorAll('.login-link');
    const logoutLinks = document.querySelectorAll('.logout-link');
    
    if (user) {
        if (userInfo) {
            userInfo.innerHTML = `<span>Welcome, ${user.name}</span>`;
        }
        
        loginLinks.forEach(link => link.style.display = 'none');
        logoutLinks.forEach(link => link.style.display = 'inline-block');
    } else {
        if (userInfo) {
            userInfo.innerHTML = '';
        }
        
        loginLinks.forEach(link => link.style.display = 'inline-block');
        logoutLinks.forEach(link => link.style.display = 'none');
    }
}

function getCurrentUser() {
    const body = document.body;
    const data = body ? body.dataset : {};

    const serverName = String(window.USER_NAME || data.userName || '').trim();
    const serverEmail = String(window.USER_EMAIL || data.userEmail || '').trim();
    const serverRole = String(window.USER_ROLE || data.userRole || '').trim();

    if (serverName !== '' || serverEmail !== '' || serverRole !== '') {
        return {
            name: serverName,
            email: serverEmail,
            role: serverRole,
            source: 'session'
        };
    }

    return null;
}

// Utility functions
function generateId() {
    return 'id_' + Math.random().toString(36).substr(2, 9);
}

function getNameFromEmail(email) {
    return email.split('@')[0].replace(/[._]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type = 'info', duration = 3200) {
    const safeMessage = String(message || '').trim();
    const compactMessage = safeMessage.length > 140
        ? `${safeMessage.slice(0, 137)}...`
        : safeMessage;

    const validTypes = ['success', 'error', 'warning', 'info'];
    const toastType = validTypes.includes(type) ? type : 'info';

    let stack = document.getElementById('toastStack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'toastStack';
        stack.className = 'toast-stack';
        stack.setAttribute('role', 'region');
        stack.setAttribute('aria-label', 'Notifications');
        document.body.appendChild(stack);
    }

    const notification = document.createElement('div');
    notification.className = `notification ${toastType}`;
    notification.setAttribute('role', toastType === 'error' ? 'alert' : 'status');
    notification.textContent = compactMessage || 'Notification';

    stack.appendChild(notification);
    requestAnimationFrame(() => notification.classList.add('is-visible'));

    const dismiss = () => {
        if (!notification.parentNode) return;
        notification.classList.remove('is-visible');
        notification.classList.add('is-leaving');
        setTimeout(() => {
            if (notification.parentNode) notification.remove();
        }, 260);
    };

    notification.addEventListener('click', dismiss);
    setTimeout(dismiss, Math.max(1500, Number(duration) || 3200));
}

window.showNotification = showNotification;

// Table sorting function
function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(table.querySelectorAll('th')).findIndex(th => th.getAttribute('data-sort') === column);
    
    if (columnIndex === -1) return;
    
    const currentDirection = table.getAttribute('data-sort-direction') || 'asc';
    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
    table.setAttribute('data-sort-direction', newDirection);
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
            return newDirection === 'asc' ? 
                aValue.localeCompare(bValue) : 
                bValue.localeCompare(aValue);
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
    
    table.querySelectorAll('th').forEach(th => th.classList.remove('sorted-asc', 'sorted-desc'));
    table.querySelector(`th[data-sort="${column}"]`).classList.add(`sorted-${newDirection}`);
}

// Floating alert system
function showFloatingAlert(message, type = 'error', duration = 5000) {
    const alertContainer = document.getElementById('floatingAlerts');
    if (!alertContainer) return;

    const alertDiv = document.createElement('div');
    alertDiv.className = `floating-alert floating-alert-${type}`;
    alertDiv.innerHTML = `
        <div class="floating-alert-content">
            <span class="floating-alert-message">${message}</span>
            <button class="floating-alert-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;

    // Add to container
    alertContainer.appendChild(alertDiv);

    // Animate in
    setTimeout(() => {
        alertDiv.classList.add('show');
    }, 10);

    // Auto remove after duration
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 300);
        }
    }, duration);
}

// Close alert when clicking anywhere on it
document.addEventListener('click', function(e) {
    if (e.target.closest('.floating-alert')) {
        const alert = e.target.closest('.floating-alert');
        alert.classList.remove('show');
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 300);
    }
});

// Export functions for global access
window.removeFromCart = removeFromCart;
window.updateCartQuantity = updateCartQuantity;
window.openModal = openModal;
window.closeModal = closeModal;
window.showNotification = showNotification;
window.validateField = validateField;
window.clearError = clearError;
window.getCurrentUser = getCurrentUser;
window.sortTable = sortTable;
