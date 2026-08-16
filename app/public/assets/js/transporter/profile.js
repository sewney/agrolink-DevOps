// Transporter Profile Management
// Profile page handles general info; vehicle details are managed in dedicated vehicle flows.

// Ensure APP_ROOT is defined (set in transporterMain.view.php, fallback if missing)
if (typeof window.APP_ROOT === 'undefined' || !window.APP_ROOT) {
    window.APP_ROOT = 'http://localhost/agrolink/public';
    console.warn('APP_ROOT was undefined, using fallback:', window.APP_ROOT);
}

console.log('Profile.js loaded, APP_ROOT:', window.APP_ROOT);

function parseJsonResponse(response) {
    return response.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid server response');
        }
    });
}

function updateGlobalUserName(name) {
    const normalized = String(name || '').trim();
    if (!normalized) return;

    window.USER_NAME = normalized;
    if (document.body) {
        document.body.setAttribute('data-user-name', normalized);
    }

    if (typeof window.updateNavbarUserName === 'function') {
        window.updateNavbarUserName(normalized);
    }
}

// Helper function to update profile photo display and default icon
function setProfilePhoto(url) {
    const img = document.getElementById('profilePhotoDisplay');
    const defaultIcon = document.getElementById('defaultProfileIcon');
    
    if (!img) return;
    
    if (url && url.length && url !== 'undefined') {
        img.style.display = 'block';
        if (defaultIcon) defaultIcon.style.display = 'none';
        img.src = url;
    } else {
        img.style.display = 'none';
        if (defaultIcon) defaultIcon.style.display = 'flex';
    }
}

// Store original profile data for reset functionality (only declare if not exists)
if (typeof originalProfileData === 'undefined') {
    var originalProfileData = null;
}

/**
 * Load profile data from server and populate form
 */
function loadProfileData() {
    const form = document.getElementById('profileForm');
    if (!form) {
        console.error('Profile form not found');
        return;
    }

    console.log('Loading transporter profile...');

    fetch(`${window.APP_ROOT}/transporterprofile?ajax=1&t=${Date.now()}`, {
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-cache'
    })
    .then(r => {
        if (!r.ok) throw new Error('Failed to fetch profile');
        return parseJsonResponse(r);
    })
    .then(res => {
        if (res.success && res.profile) {
            const profile = res.profile;
            
            console.log('Profile loaded:', profile);

            // Populate form fields
            const nameField = document.getElementById('profileName');
            const emailField = document.getElementById('profileEmail');
            const phoneField = document.getElementById('profilePhone');
            const districtField = document.getElementById('profileDistrict');
            const fullAddressField = document.getElementById('profileFullAddress');
            const companyNameField = document.getElementById('profileCompanyName');
            const availabilityField = document.getElementById('profileAvailability');
            
            if (nameField) nameField.value = profile.name || '';
            if (emailField) emailField.value = profile.email || '';
            const accountSettingsEmailField = document.getElementById('accountSettingsEmail');
            if (accountSettingsEmailField) accountSettingsEmailField.value = profile.email || '';
            if (phoneField) phoneField.value = profile.phone || '';
            if (districtField) districtField.value = profile.district || '';
            if (fullAddressField) fullAddressField.value = profile.full_address || '';
            if (companyNameField) companyNameField.value = profile.company_name || '';
            if (availabilityField) availabilityField.value = profile.availability || '';
            
            // Update header display (name and email in header section)
            const displayName = document.getElementById('profileDisplayName');
            const displayEmail = document.getElementById('profileDisplayEmail');
            if (displayName) displayName.textContent = profile.name || '';
            if (displayEmail) displayEmail.textContent = profile.email || '';
            updateGlobalUserName(profile.name || '');
            
            // Store original data for reset
            if (!originalProfileData) {
                originalProfileData = { 
                    name: profile.name || '',
                    email: profile.email || '',
                    phone: profile.phone || '',
                    district: profile.district || '',
                    full_address: profile.full_address || '',
                    company_name: profile.company_name || '',
                    availability: profile.availability || ''
                };
            }
            
            // Set profile photo
            if (res.photoUrl) {
                setProfilePhoto(res.photoUrl);
            } else {
                setProfilePhoto(null);
            }
        } else {
            console.error('Invalid profile response:', res);
            showNotification(res.message || 'Failed to load profile', 'error');
        }
    })
    .catch(err => {
        console.error('Profile load error:', err);
        showNotification('Error loading profile data', 'error');
    });
}

/**
 * Save profile data to server
 */
function saveProfileData() {
    const form = document.getElementById('profileForm');
    if (!form) return;
    
    console.log('Saving transporter profile...');
    
    // Clear previous errors
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(el => {
        el.textContent = '';
        el.classList.remove('show');
    });

    // Collect form data
    const formData = new FormData();
    formData.append('name', document.getElementById('profileName')?.value?.trim() || '');
    formData.append('phone', document.getElementById('profilePhone')?.value?.trim() || '');
    formData.append('district', document.getElementById('profileDistrict')?.value?.trim() || '');
    formData.append('full_address', document.getElementById('profileFullAddress')?.value?.trim() || '');
    formData.append('company_name', document.getElementById('profileCompanyName')?.value?.trim() || '');
    formData.append('availability', document.getElementById('profileAvailability')?.value?.trim() || '');

    // Get button and disable it
    const saveBtn = document.getElementById('saveProfileBtn');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Saving...';
    }

    fetch(`${window.APP_ROOT}/transporterprofile/saveProfile`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(parseJsonResponse)
    .then(res => {
        if (res.success) {
            console.log('Profile saved successfully');
            showNotification(res.message || 'Profile updated successfully', 'success');
            
            // Update original data
            originalProfileData = {
                name: formData.get('name'),
                email: document.getElementById('profileEmail')?.value || '',
                phone: formData.get('phone'),
                district: formData.get('district'),
                full_address: formData.get('full_address'),
                company_name: formData.get('company_name'),
                availability: formData.get('availability')
            };
            
            // Update display header
            const displayName = document.getElementById('profileDisplayName');
            if (displayName) displayName.textContent = formData.get('name');
            updateGlobalUserName(formData.get('name'));
        } else {
            console.error('Save profile failed:', res);
            
            // Display field-specific errors
            if (res.errors) {
                Object.keys(res.errors).forEach(field => {
                    const errorEl = document.getElementById(`error-${field}`);
                    if (errorEl) {
                        errorEl.textContent = res.errors[field];
                        errorEl.classList.add('show');
                    }
                });
            }
            
            showNotification(res.message || 'Failed to update profile', 'error');
        }
    })
    .catch(err => {
        console.error('Profile save error:', err);
        showNotification('Error updating profile', 'error');
    })
    .finally(() => {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" /><polyline points="17 21 17 13 7 13 7 21" /><polyline points="7 3 7 8 15 8" /></svg> Save Changes';
        }
    });
}

/**
 * Reset profile form to original values
 */
function resetProfileForm() {
    if (!originalProfileData) {
        console.warn('No original profile data to reset');
        return;
    }
    
    const nameField = document.getElementById('profileName');
    const phoneField = document.getElementById('profilePhone');
    const districtField = document.getElementById('profileDistrict');
    const fullAddressField = document.getElementById('profileFullAddress');
    const companyNameField = document.getElementById('profileCompanyName');
    const availabilityField = document.getElementById('profileAvailability');
    
    if (nameField) nameField.value = originalProfileData.name || '';
    if (phoneField) phoneField.value = originalProfileData.phone || '';
    if (districtField) districtField.value = originalProfileData.district || '';
    if (fullAddressField) fullAddressField.value = originalProfileData.full_address || '';
    if (companyNameField) companyNameField.value = originalProfileData.company_name || '';
    if (availabilityField) availabilityField.value = originalProfileData.availability || '';
    
    // Clear errors
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(el => {
        el.textContent = '';
        el.classList.remove('show');
    });
    
    showNotification('Form reset to original values', 'info');
}

/**
 * Upload profile photo
 */
function uploadProfilePhoto() {
    const fileInput = document.getElementById('profilePhotoFileInput');
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        console.error('No file selected');
        return;
    }
    
    const file = fileInput.files[0];
    console.log('Uploading photo:', file.name, file.size, 'bytes');
    
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showNotification('File too large. Maximum size is 5MB', 'error');
        fileInput.value = '';
        return;
    }
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showNotification('Invalid file type. Use JPG, PNG, or WEBP', 'error');
        fileInput.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('photo', file);
    
    // Show loading state
    const photoWrapper = document.getElementById('profilePhotoWrapper');
    if (photoWrapper) {
        photoWrapper.style.opacity = '0.6';
        photoWrapper.style.pointerEvents = 'none';
    }
    
    fetch(`${window.APP_ROOT}/transporterprofile/uploadPhoto`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(parseJsonResponse)
    .then(res => {
        if (res.success && res.photoUrl) {
            console.log('Photo uploaded:', res.photoUrl);
            setProfilePhoto(res.photoUrl);
            showNotification(res.message || 'Photo uploaded successfully', 'success');
        } else {
            console.error('Photo upload failed:', res);
            showNotification(res.message || 'Failed to upload photo', 'error');
        }
    })
    .catch(err => {
        console.error('Photo upload error:', err);
        showNotification('Error uploading photo', 'error');
    })
    .finally(() => {
        fileInput.value = '';
        if (photoWrapper) {
            photoWrapper.style.opacity = '1';
            photoWrapper.style.pointerEvents = 'auto';
        }
    });
}

/**
 * Remove profile photo
 */
function removeProfilePhoto() {
    if (!confirm('Are you sure you want to remove your profile photo?')) {
        return;
    }
    
    console.log('Removing profile photo...');
    
    fetch(`${window.APP_ROOT}/transporterprofile/removePhoto`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(parseJsonResponse)
    .then(res => {
        if (res.success) {
            console.log('Photo removed');
            setProfilePhoto(null);
            showNotification(res.message || 'Photo removed successfully', 'success');
        } else {
            console.error('Photo removal failed:', res);
            showNotification(res.message || 'Failed to remove photo', 'error');
        }
    })
    .catch(err => {
        console.error('Photo removal error:', err);
        showNotification('Error removing photo', 'error');
    });
}

/**
 * Open change password modal
 */
function openChangePasswordModal() {
    openAccountSettingsModal();
    toggleSettingsPanel('passwordSettingsPanel', true);
}

/**
 * Close change password modal
 */
function closeChangePasswordModal() {
    closeModalCard('accountSettingsModal');
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('show');
}

function closeModalCard(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('show');
}

function clearInlineStatus(statusId) {
    const statusEl = document.getElementById(statusId);
    if (!statusEl) return;
    statusEl.textContent = '';
    statusEl.classList.remove('success', 'error');
    statusEl.classList.add('is-hidden');
}

function setInlineStatus(statusId, message, type) {
    const statusEl = document.getElementById(statusId);
    if (!statusEl) return;
    statusEl.textContent = message;
    statusEl.classList.remove('is-hidden', 'success', 'error');
    statusEl.classList.add(type === 'error' ? 'error' : 'success');
}

function toggleSettingsPanel(panelId, forceOpen) {
    const panel = document.getElementById(panelId);
    if (!panel) return;

    const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !panel.classList.contains('is-open');
    const toggleBtn = document.querySelector(`.account-settings-toggle[data-settings-target="${panelId}"]`);

    if (shouldOpen) {
        document.querySelectorAll('.account-settings-panel').forEach(otherPanel => {
            if (otherPanel.id !== panelId) otherPanel.classList.remove('is-open');
        });
        document.querySelectorAll('.account-settings-toggle[data-settings-target]').forEach(otherBtn => {
            if (otherBtn.getAttribute('data-settings-target') !== panelId) {
                otherBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    panel.classList.toggle('is-open', shouldOpen);
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
}

function openAccountSettingsModal() {
    const profileEmail = document.getElementById('profileEmail');
    const accountEmail = document.getElementById('accountSettingsEmail');
    if (profileEmail && accountEmail) {
        accountEmail.value = profileEmail.value || '';
    }

    clearInlineStatus('emailChangeStatus');
    clearInlineStatus('passwordChangeStatus');

    const emailForm = document.getElementById('changeEmailForm');
    const passwordForm = document.getElementById('changePasswordForm');
    if (emailForm) emailForm.reset();
    if (passwordForm) passwordForm.reset();

    document.querySelectorAll('.account-settings-panel').forEach(panel => panel.classList.remove('is-open'));
    document.querySelectorAll('.account-settings-toggle[data-settings-target]').forEach(btn => btn.setAttribute('aria-expanded', 'false'));

    openModal('accountSettingsModal');
}

function handleChangeEmailSubmit(event) {
    event.preventDefault();
    clearInlineStatus('emailChangeStatus');

    const newEmail = (document.getElementById('newEmailAddress')?.value || '').trim().toLowerCase();
    const password = document.getElementById('emailChangePassword')?.value || '';

    if (!newEmail) {
        setInlineStatus('emailChangeStatus', 'New email is required', 'error');
        return;
    }

    if (!password) {
        setInlineStatus('emailChangeStatus', 'Password confirmation is required', 'error');
        return;
    }

    const submitBtn = document.getElementById('changeEmailBtn');
    const originalText = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating...';
    }

    fetch(`${window.APP_ROOT}/transporterprofile/changeEmail`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            newEmail,
            password
        })
    })
        .then(parseJsonResponse)
        .then(res => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }

            if (res.success) {
                const profileEmail = document.getElementById('profileEmail');
                const accountEmail = document.getElementById('accountSettingsEmail');
                const displayEmail = document.getElementById('profileDisplayEmail');

                if (profileEmail) profileEmail.value = res.email || newEmail;
                if (accountEmail) accountEmail.value = res.email || newEmail;
                if (displayEmail) displayEmail.textContent = res.email || newEmail;

                const newEmailField = document.getElementById('newEmailAddress');
                const passwordField = document.getElementById('emailChangePassword');
                if (newEmailField) newEmailField.value = '';
                if (passwordField) passwordField.value = '';

                setInlineStatus('emailChangeStatus', res.message || 'Email updated successfully', 'success');
                showNotification('Email updated successfully', 'success');
                return;
            }

            if (res.errors && typeof res.errors === 'object') {
                const message = Object.values(res.errors)[0] || 'Validation failed';
                setInlineStatus('emailChangeStatus', String(message), 'error');
                return;
            }

            setInlineStatus('emailChangeStatus', res.error || 'Failed to update email', 'error');
        })
        .catch(err => {
            console.error('Change email error:', err);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
            setInlineStatus('emailChangeStatus', 'Error updating email', 'error');
        });
}

function populatePayoutDetails(data) {
    if (!data || typeof data !== 'object') {
        return;
    }

    const holderField = document.getElementById('payoutAccountName');
    const bankField = document.getElementById('payoutBankName');
    const branchField = document.getElementById('payoutBranchName');
    const numberField = document.getElementById('payoutAccountNumber');

    if (holderField) holderField.value = data.account_holder_name || '';
    if (bankField) bankField.value = data.bank_name || '';
    if (branchField) branchField.value = data.branch_name || '';
    if (numberField) numberField.value = data.account_number || '';
}

function loadPayoutDetails() {
    fetch(`${window.APP_ROOT}/transporterprofile/getPayoutAccount`, {
        method: 'GET',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(parseJsonResponse)
        .then(res => {
            if (res.success) {
                populatePayoutDetails(res.account || null);
            }
        })
        .catch(err => {
            console.error('Load payout details error:', err);
        });
}

function savePayoutDetails(event) {
    if (event) {
        event.preventDefault();
    }

    const bankName = (document.getElementById('payoutBankName')?.value || '').trim();
    const branchName = (document.getElementById('payoutBranchName')?.value || '').trim();
    const accountHolder = (document.getElementById('payoutAccountName')?.value || '').trim();
    const accountNumber = (document.getElementById('payoutAccountNumber')?.value || '').trim();

    if (!bankName || !accountHolder || !accountNumber) {
        showNotification('Please fill bank name, account holder, and account number', 'error');
        return;
    }

    if (!/^\d{8,30}$/.test(accountNumber)) {
        showNotification('Account number must be 8-30 digits', 'error');
        return;
    }

    const saveBtn = event?.submitter || document.querySelector('#payoutDetailsForm button[type="submit"]');
    const originalText = saveBtn ? saveBtn.textContent : '';
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
    }

    fetch(`${window.APP_ROOT}/transporterprofile/savePayoutAccount`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            account_holder_name: accountHolder,
            bank_name: bankName,
            branch_name: branchName,
            account_number: accountNumber
        })
    })
        .then(parseJsonResponse)
        .then(res => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }

            if (res.success) {
                showNotification(res.message || 'Payout account saved successfully', 'success');
                populatePayoutDetails(res.account || null);
                closeModalCard('payoutDetailsModal');
                return;
            }

            if (res.errors && typeof res.errors === 'object') {
                const message = Object.values(res.errors)[0] || 'Validation failed';
                showNotification(String(message), 'error');
                return;
            }

            showNotification(res.error || 'Failed to save payout account', 'error');
        })
        .catch(err => {
            console.error('Save payout details error:', err);
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }
            showNotification('Error saving payout details', 'error');
        });
}

function confirmDeactivateAccount() {
    const btn = document.getElementById('confirmDeactivateBtn');
    const originalText = btn ? btn.textContent : '';

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processing...';
    }

    fetch(`${window.APP_ROOT}/transporterprofile/requestDeactivation`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams()
    })
        .then(parseJsonResponse)
        .then(res => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
            }

            if (res.success) {
                showNotification(res.message || 'Account deactivated successfully', 'success');
                if (res.redirect) {
                    window.location.href = res.redirect;
                }
                return;
            }

            showNotification(res.error || 'Failed to deactivate account', 'error');
        })
        .catch(err => {
            console.error('Deactivate account error:', err);
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
            }
            showNotification('Error processing deactivation request', 'error');
        });
}

/**
 * Submit password change
 */
function submitChangePassword() {
    const form = document.getElementById('changePasswordForm');
    if (!form) return;

    clearInlineStatus('passwordChangeStatus');
    
    // Clear errors
    const errorElements = form.querySelectorAll('.error-message');
    errorElements.forEach(el => {
        el.textContent = '';
        el.classList.remove('show');
    });
    
    const currentPassword = document.getElementById('currentPassword')?.value || '';
    const newPassword = document.getElementById('newPassword')?.value || '';
    const confirmPassword = document.getElementById('confirmPassword')?.value || '';
    
    // Basic validation
    if (!currentPassword) {
        setInlineStatus('passwordChangeStatus', 'Current password is required', 'error');
        return;
    }
    
    if (!newPassword || newPassword.length < 8) {
        setInlineStatus('passwordChangeStatus', 'New password must be at least 8 characters', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        setInlineStatus('passwordChangeStatus', 'Passwords do not match', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);
    
    fetch(`${window.APP_ROOT}/transporterprofile/changePassword`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(parseJsonResponse)
    .then(res => {
        if (res.success) {
            setInlineStatus('passwordChangeStatus', res.message || 'Password changed successfully', 'success');
            showNotification(res.message || 'Password changed successfully', 'success');
            form.reset();
        } else {
            // Display field-specific errors
            if (res.errors) {
                const message = Object.values(res.errors)[0] || 'Validation failed';
                setInlineStatus('passwordChangeStatus', String(message), 'error');
            } else {
                setInlineStatus('passwordChangeStatus', res.message || 'Failed to change password', 'error');
            }
        }
    })
    .catch(err => {
        console.error('Password change error:', err);
        showNotification('Error changing password', 'error');
    });
}

// Namespaced API for inline handlers
window.TransporterProfile = {
    saveProfileData,
    resetProfileForm,
    openChangePasswordModal,
    closeChangePasswordModal,
    submitChangePassword,
    handleChangeEmailSubmit,
    openAccountSettingsModal,
    savePayoutDetails,
    loadPayoutDetails,
    confirmDeactivateAccount,
    removeProfilePhoto,
    uploadProfilePhoto,
    loadProfileData,
    setProfilePhoto
};

// Backward-compatible aliases (temporary)
window.saveProfileData = window.TransporterProfile.saveProfileData;
window.resetProfileForm = window.TransporterProfile.resetProfileForm;
window.openChangePasswordModal = window.TransporterProfile.openChangePasswordModal;
window.closeChangePasswordModal = window.TransporterProfile.closeChangePasswordModal;
window.removeProfilePhoto = window.TransporterProfile.removeProfilePhoto;

// Event listeners setup
document.addEventListener('DOMContentLoaded', function() {
    console.log('Transporter profile page loaded, APP_ROOT:', window.APP_ROOT);
    
    // Load profile data on page load
    loadProfileData();
    loadPayoutDetails();

    const saveBtn = document.getElementById('saveProfileBtn');
    if (saveBtn) saveBtn.addEventListener('click', saveProfileData);

    const resetBtn = document.getElementById('resetProfileBtn');
    if (resetBtn) resetBtn.addEventListener('click', resetProfileForm);
    
    const addPhotoBtn = document.getElementById('addPhotoBtn');
    const changePhotoBtn = document.getElementById('changePhotoBtn');
    if (addPhotoBtn) addPhotoBtn.addEventListener('click', function() {
        const fileInput = document.getElementById('profilePhotoFileInput');
        if (fileInput) fileInput.click();
    });
    if (changePhotoBtn) changePhotoBtn.addEventListener('click', function() {
        const fileInput = document.getElementById('profilePhotoFileInput');
        if (fileInput) fileInput.click();
    });
    
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    if (removePhotoBtn) removePhotoBtn.addEventListener('click', removeProfilePhoto);
    
    // File input change handler
    const fileInput = document.getElementById('profilePhotoFileInput');
    if (fileInput) {
        fileInput.addEventListener('change', uploadProfilePhoto);
    }

    document.querySelectorAll('.profile-shortcut-card[data-open-modal]').forEach(card => {
        card.addEventListener('click', function() {
            const target = this.getAttribute('data-open-modal');
            if (target === 'accountSettingsModal') {
                openAccountSettingsModal();
            } else {
                openModal(target);
            }
        });
    });

    document.querySelectorAll('[data-open-modal]').forEach(btn => {
        if (btn.classList.contains('profile-shortcut-card')) return;
        btn.addEventListener('click', function() {
            const target = this.getAttribute('data-open-modal');
            if (target === 'accountSettingsModal') {
                openAccountSettingsModal();
            } else {
                openModal(target);
            }
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModalCard(this.getAttribute('data-close-modal'));
        });
    });

    document.querySelectorAll('.modal.profile-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    });

    document.querySelectorAll('.account-settings-toggle[data-settings-target]').forEach(btn => {
        btn.addEventListener('click', function() {
            const target = this.getAttribute('data-settings-target');
            if (target) toggleSettingsPanel(target);
        });
    });
    
    // Change password form submit handler
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitChangePassword();
        });
    }

    const changeEmailForm = document.getElementById('changeEmailForm');
    if (changeEmailForm) {
        changeEmailForm.addEventListener('submit', handleChangeEmailSubmit);
    }

    const payoutDetailsForm = document.getElementById('payoutDetailsForm');
    if (payoutDetailsForm) {
        payoutDetailsForm.addEventListener('submit', savePayoutDetails);
    }

    const confirmDeactivateBtn = document.getElementById('confirmDeactivateBtn');
    if (confirmDeactivateBtn) confirmDeactivateBtn.addEventListener('click', confirmDeactivateAccount);
    
    // Close modal on outside click
    const modal = document.getElementById('accountSettingsModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeChangePasswordModal();
            }
        });
    }
});
