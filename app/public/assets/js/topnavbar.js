function getUserDropdown() {
    return document.getElementById('userDropdown');
}

function getUserSection() {
    return document.querySelector('.user-section');
}

function getUserNameElements() {
    return document.querySelectorAll('.user-section .user-name');
}

function getUserAvatarElement() {
    return document.getElementById('userAvatar');
}

function toInitials(name) {
    const cleanName = String(name || '').trim();
    if (!cleanName) {
        return 'U';
    }

    const words = cleanName.split(/\s+/).filter(Boolean);
    if (words.length === 1) {
        return words[0].charAt(0).toUpperCase();
    }

    return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
}

function applyNavbarUserName(name) {
    const normalized = String(name || '').trim();
    if (!normalized) {
        return;
    }

    getUserNameElements().forEach(el => {
        el.textContent = normalized;
    });

    const avatar = getUserAvatarElement();
    if (avatar) {
        avatar.textContent = toInitials(normalized);
    }

    if (document.body) {
        document.body.setAttribute('data-user-name', normalized);
    }
    window.USER_NAME = normalized;
}

window.updateNavbarUserName = applyNavbarUserName;

function setToggleExpanded(isOpen) {
    const toggle = document.querySelector('.nav-user-toggle');
    if (toggle) {
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
}

// Navbar dropdown functionality
function toggleUserDropdown() {
    const dropdown = getUserDropdown();
    if (!dropdown) {
        return;
    }

    const isOpen = dropdown.classList.toggle('show');
    setToggleExpanded(isOpen);
}

// Close dropdown when clicking outside.
document.addEventListener('click', function(event) {
    const userSection = getUserSection();
    const dropdown = getUserDropdown();

    if (!userSection || !dropdown) {
        return;
    }

    if (!userSection.contains(event.target)) {
        dropdown.classList.remove('show');
        setToggleExpanded(false);
    }
});

// Close dropdown when pressing Escape.
document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') {
        return;
    }

    const dropdown = getUserDropdown();
    if (dropdown) {
        dropdown.classList.remove('show');
        setToggleExpanded(false);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const seededName = (document.body && document.body.getAttribute('data-user-name')) || window.USER_NAME || '';
    if (seededName) {
        applyNavbarUserName(seededName);
    }
});