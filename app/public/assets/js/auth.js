// Authentication - Login & Register Pages

document.addEventListener('DOMContentLoaded', function() {
    initAuthForms();
});

function initAuthForms() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Registration forms
    const registerForms = document.querySelectorAll('[id^="register"]');
    registerForms.forEach(form => {
        form.addEventListener('submit', handleRegistration);
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearError);
        });
    });
}

/*function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const email = formData.get('email');
    const password = formData.get('password');
    const role = formData.get('role');
    
    if (email && password && role) {
        const user = {
            id: generateId(),
            email: email,
            role: role,
            name: getNameFromEmail(email),
            loginTime: new Date().toISOString()
        };
        
        localStorage.setItem('agrolink_user', JSON.stringify(user));
        showNotification('Login successful! Redirecting...', 'success');
        
        setTimeout(() => {
            redirectToDashboard(role);
        }, 1500);
    } else {
        showNotification('Please fill in all fields', 'error');
    }
}*/

function handleRegistration(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    if (!data.email || !data.password || !data.name) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
    if (data.password !== data.confirmPassword) {
        showNotification('Passwords do not match', 'error');
        return;
    }
    
    const user = {
        id: generateId(),
        ...data,
        registrationTime: new Date().toISOString()
    };
    
    localStorage.setItem('agrolink_user', JSON.stringify(user));
    showNotification('Registration successful! Redirecting to login...', 'success');
    
    setTimeout(() => {
        window.location.href = 'login.html';
    }, 1500);
}

function generateId() {
    return 'id_' + Math.random().toString(36).substr(2, 9);
}

function getNameFromEmail(email) {
    return email.split('@')[0].replace(/[._]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function redirectToDashboard(role) {
    const dashboardUrls = {
        farmer: 'farmerDashboard.php',
        buyer: 'buyerDashboard.php',
        transporter: 'transporterDashboard.php',
        admin: 'adminDashboard.php'
    };
    
    const url = dashboardUrls[role] || 'index.html';
    window.location.href = url;
}