// Buyer profile management (uses BuyerProfileController endpoints)
let buyerOriginalProfile = null;

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

function avatarPlaceholder() {
    const name = (window.USER_NAME || 'Buyer').trim() || 'Buyer';
    const encoded = encodeURIComponent(name);
    return `https://ui-avatars.com/api/?name=${encoded}&background=4CAF50&color=fff&size=180`;
}

function updatePhotoActionState(hasPhoto) {
    const addBtn = document.getElementById('addPhotoBtn');
    const changeBtn = document.getElementById('changePhotoBtn');
    const removeBtn = document.getElementById('removePhotoBtn');

    if (addBtn) addBtn.classList.toggle('is-hidden', hasPhoto);
    if (changeBtn) changeBtn.classList.toggle('is-hidden', !hasPhoto);
    if (removeBtn) removeBtn.classList.toggle('is-hidden', !hasPhoto);
}

function setProfilePhoto(url) {
    const img = document.getElementById('profilePhotoDisplay');
    const defaultIcon = document.getElementById('defaultProfileIcon');
    if (!img) return;

    const hasPhoto = !!(url && url.length && url !== 'undefined');

    if (hasPhoto) {
        img.style.display = 'block';
        if (defaultIcon) defaultIcon.style.display = 'none';
        img.src = url;
    } else {
        img.style.display = 'none';
        if (defaultIcon) defaultIcon.style.display = 'flex';
    }

    updatePhotoActionState(hasPhoto);
}

function formatDateLabel(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(String(dateStr).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function loadNearestCities(district, selectedCity = '') {
    const cityField = document.getElementById('profileCity');
    if (!cityField) return;

    cityField.innerHTML = '<option value="" selected disabled>Loading...</option>';
    cityField.disabled = true;

    if (!district) {
        cityField.innerHTML = '<option value="">Select nearest city</option>';
        return;
    }

    fetch(`${window.APP_ROOT}/Checkout/getTownsByDistrictName?district=${encodeURIComponent(district)}`)
        .then(response => response.json())
        .then(data => {
            cityField.innerHTML = '<option value="">Select nearest city</option>';
            cityField.disabled = false;

            if (data.success && data.towns) {
                data.towns.forEach(town => {
                    const option = document.createElement('option');
                    option.value = town.town_name;
                    option.textContent = town.town_name;
                    cityField.appendChild(option);
                });

                if (selectedCity) {
                    cityField.value = selectedCity;
                }
            } else {
                cityField.innerHTML = '<option value="">No cities found</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching towns:', error);
            cityField.innerHTML = '<option value="">Error loading cities</option>';
            cityField.disabled = false;
        });
}

function populateProfileForm(profile, photoUrl, profileStats) {
    const nameField = document.getElementById('profileName');
    const emailField = document.getElementById('profileEmail');
    const phoneField = document.getElementById('profilePhone');
    const districtField = document.getElementById('profileDistrict');
    const apartmentField = document.getElementById('profileApartmentCode');
    const streetNameField = document.getElementById('profileStreetName');
    const cityField = document.getElementById('profileCity');
    const postalField = document.getElementById('profilePostalCode');
    const addressField = document.getElementById('profileAdditionalAddress');
    const accountSettingsEmail = document.getElementById('accountSettingsEmail');

    if (nameField) nameField.value = profile.name || '';
    if (emailField) emailField.value = profile.email || '';
    if (accountSettingsEmail) accountSettingsEmail.value = profile.email || '';
    if (phoneField) phoneField.value = profile.phone || '';
    if (apartmentField) apartmentField.value = profile.apartment_code || '';
    if (streetNameField) streetNameField.value = profile.street_name || '';
    if (postalField) postalField.value = profile.postal_code || '';
    if (addressField) addressField.value = profile.additional_address_details || '';

    if (districtField) {
        districtField.value = profile.district || '';
        loadNearestCities(profile.district || '', profile.city || '');
    } else if (cityField) {
        cityField.innerHTML = `<option value="${profile.city || ''}" selected>${profile.city || 'Select nearest city'}</option>`;
    }

    const displayName = document.getElementById('profileDisplayName');
    const displayEmail = document.getElementById('profileDisplayEmail');
    if (displayName) displayName.textContent = profile.name || '';
    if (displayEmail) displayEmail.textContent = profile.email || '';
    updateGlobalUserName(profile.name || '');

    const resolvedPhoto = photoUrl || profile.profile_photo_url || profile.profile_photo;
    setProfilePhoto(resolvedPhoto);
    updateProfileStatistics(profileStats || profile);

    buyerOriginalProfile = {
        name: profile.name || '',
        email: profile.email || '',
        phone: profile.phone || '',
        district: profile.district || '',
        apartment_code: profile.apartment_code || '',
        street_name: profile.street_name || '',
        city: profile.city || '',
        postal_code: profile.postal_code || '',
        additional_address_details: profile.additional_address_details || '',
        created_at: profile.created_at || '',
        orders_count: profile.total_orders ?? profile.orders_count ?? 0,
        active_deliveries: profile.active_deliveries ?? 0,
        profile_photo: resolvedPhoto
    };
}

function updateProfileStatistics(stats) {
    const statMember = document.getElementById('memberSinceValue');
    const statOrders = document.getElementById('totalOrdersValue');
    const statActiveDeliveries = document.getElementById('activeDeliveriesValue');

    const memberSince = formatDateLabel(stats.member_since || stats.created_at || stats.joined_at || '');
    const totalOrders = stats.total_orders ?? stats.orders_count ?? 0;
    const activeDeliveries = stats.active_deliveries ?? 0;

    if (statMember) statMember.textContent = memberSince;
    if (statOrders) statOrders.textContent = String(totalOrders);
    if (statActiveDeliveries) statActiveDeliveries.textContent = String(activeDeliveries);
}

function clearFieldErrors(scope) {
    const selector = scope ? `${scope} .error-message` : '.error-message';
    document.querySelectorAll(selector).forEach(el => {
        el.textContent = '';
        el.classList.remove('show');
    });
}

function loadProfileData() {
    const url = `${window.APP_ROOT}/buyerprofile?ajax=1&t=${Date.now()}`;
    fetch(url, {
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-cache'
    })
        .then(r => {
            if (!r.ok) throw new Error('Failed to load profile');
            return parseJsonResponse(r);
        })
        .then(res => {
            if (res.success && res.profile) {
                populateProfileForm(res.profile, res.photoUrl, res.profileStats || null);
            } else {
                showNotification(res.error || 'Could not load profile', 'error');
            }
        })
        .catch(err => {
            console.error('Profile load error:', err);
            showNotification('Unable to load profile', 'error');
        });
}

function saveProfileData() {
    const form = document.getElementById('profileForm');
    if (!form) return;
    clearFieldErrors();

    const formData = new FormData(form);
    const data = {
        name: formData.get('name') || '',
        email: formData.get('email') || '',
        phone: formData.get('phone') || '',
        district: formData.get('district') || '',
        apartment_code: formData.get('apartment_code') || '',
        street_name: formData.get('street_name') || '',
        city: formData.get('city') || '',
        postal_code: formData.get('postal_code') || '',
        additional_address_details: formData.get('additional_address_details') || ''
    };

    const saveBtn = document.getElementById('saveProfileBtn');
    const originalText = saveBtn ? saveBtn.textContent : '';
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
    }

    fetch(`${window.APP_ROOT}/buyerprofile/saveProfile`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
        .then(parseJsonResponse)
        .then(res => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }

            if (res.success) {
                updateGlobalUserName(data.name);
                showNotification(res.message || 'Profile updated', 'success');
                loadProfileData();
                return;
            }

            if (res.errors && typeof res.errors === 'object') {
                let errorMessages = [];
                Object.keys(res.errors).forEach(field => {
                    errorMessages.push(res.errors[field]);
                    const errorEl = document.getElementById(`error-${field}`);
                    if (errorEl) {
                        errorEl.textContent = res.errors[field];
                        errorEl.classList.add('show');
                    }
                });
                showNotification('Validation Error: ' + errorMessages.join(' | '), 'error');
            } else {
                showNotification(res.error || 'Failed to save profile', 'error');
            }
        })
        .catch(err => {
            console.error('Profile save error:', err);
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            }
            showNotification('Unable to save profile', 'error');
        });
}

function resetProfileForm() {
    clearFieldErrors();
    if (!buyerOriginalProfile) {
        loadProfileData();
        return;
    }
    populateProfileForm(buyerOriginalProfile, buyerOriginalProfile.profile_photo || null);
}

function triggerProfilePhotoUpload() {
    let input = document.getElementById('buyerProfilePhotoInput');
    if (!input) {
        input = document.createElement('input');
        input.type = 'file';
        input.id = 'buyerProfilePhotoInput';
        input.accept = 'image/*';
        input.style.display = 'none';
        document.body.appendChild(input);
    }
    input.onchange = () => {
        const file = input.files && input.files[0];
        if (file) uploadSelectedPhoto(file);
        input.value = '';
    };
    input.click();
}

function uploadSelectedPhoto(file) {
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
        showNotification('Please select a JPG, PNG, or WEBP image', 'error');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        showNotification('Image size must be under 5MB', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('photo', file);

    fetch(`${window.APP_ROOT}/buyerprofile/uploadPhoto`, {
        method: 'POST',
        credentials: 'include',
        body: fd
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                setProfilePhoto(res.photoUrl || avatarPlaceholder());
                showNotification(res.message || 'Profile photo updated', 'success');
            } else {
                showNotification(res.error || 'Failed to upload photo', 'error');
            }
        })
        .catch(err => {
            console.error('Photo upload error:', err);
            showNotification('Unable to upload photo', 'error');
        });
}

function removeProfilePhoto() {
    fetch(`${window.APP_ROOT}/buyerprofile/removePhoto`, {
        method: 'POST',
        credentials: 'include'
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                setProfilePhoto(null);
                showNotification(res.message || 'Profile photo removed', 'success');
            } else {
                showNotification(res.error || 'Failed to remove photo', 'error');
            }
        })
        .catch(err => {
            console.error('Remove photo error:', err);
            showNotification('Unable to remove photo', 'error');
        });
}

function openChangePasswordModal() {
    openAccountSettingsModal();
    toggleSettingsPanel('passwordSettingsPanel', true);
}

function closeChangePasswordModal() {
    closeModalCard('accountSettingsModal');
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('show');
}

function populateRefundAccount(account) {
    const map = {
        refundAccountName: account?.account_holder_name || '',
        refundBankName: account?.bank_name || '',
        refundAccountNumber: account?.account_number || '',
        refundBranchName: account?.branch_name || ''
    };
    Object.keys(map).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = map[id];
    });
}

function loadRefundAccount() {
    fetch(`${window.APP_ROOT}/buyerprofile/getRefundAccount`, {
        method: 'GET',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(parseJsonResponse)
        .then(res => {
            if (!res.success) return;
            populateRefundAccount(res.account || null);
        })
        .catch(err => {
            console.error('Failed to load refund account:', err);
        });
}

function openRefundAccountModal() {
    loadRefundAccount();
    openModal('refundAccountModal');
}

function saveRefundAccount(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const accountName = (document.getElementById('refundAccountName')?.value || '').trim();
    const bankName = (document.getElementById('refundBankName')?.value || '').trim();
    const accountNumber = (document.getElementById('refundAccountNumber')?.value || '').replace(/\s+/g, '');
    const branchName = (document.getElementById('refundBranchName')?.value || '').trim();

    if (!accountName || !bankName || !accountNumber || !branchName) {
        showNotification('Please fill in all bank detail fields', 'error');
        return;
    }
    if (!/^\d{8,18}$/.test(accountNumber)) {
        showNotification('Account number must be 8 to 18 digits', 'error');
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
    }

    fetch(`${window.APP_ROOT}/buyerprofile/saveRefundAccount`, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            account_holder_name: accountName,
            bank_name: bankName,
            account_number: accountNumber,
            branch_name: branchName
        })
    })
        .then(parseJsonResponse)
        .then(res => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }

            if (res.success) {
                populateRefundAccount(res.account || null);
                showNotification(res.message || 'Bank details saved', 'success');
                closeModalCard('refundAccountModal');
                return;
            }

            if (res.errors && typeof res.errors === 'object') {
                const firstMsg = Object.values(res.errors)[0];
                showNotification(firstMsg || 'Please fix the highlighted fields', 'error');
                return;
            }

            showNotification(res.error || 'Failed to save bank details', 'error');
        })
        .catch(err => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
            console.error('Refund account save error:', err);
            showNotification('Unable to save bank details', 'error');
        });
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

    fetch(`${window.APP_ROOT}/buyerprofile/changeEmail`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            newEmail,
            password
        })
    })
        .then(r => r.json())
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

function handleBuyerDeactivation() {
    const btn = document.getElementById('confirmDeactivateBtn');
    const originalText = btn ? btn.textContent : '';

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processing...';
    }

    fetch(`${window.APP_ROOT}/buyerprofile/requestDeactivation`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams()
    })
        .then(r => r.json())
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
            console.error('Deactivation error:', err);
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
            }
            showNotification('Error requesting deactivation', 'error');
        });
}

function handleChangePasswordSubmit(event) {
    event.preventDefault();
    clearFieldErrors('#accountSettingsModal');

    const form = event.target;
    const formData = new FormData(form);
    const data = {
        currentPassword: formData.get('currentPassword') || '',
        newPassword: formData.get('newPassword') || '',
        confirmPassword: formData.get('confirmPassword') || ''
    };

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating...';
    }

    fetch(`${window.APP_ROOT}/buyerprofile/changePassword`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
        .then(r => r.json())
        .then(res => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }

            if (res.success) {
                showNotification(res.message || 'Password updated', 'success');
                closeChangePasswordModal();
                form.reset();
                return;
            }

            if (res.errors && typeof res.errors === 'object') {
                Object.keys(res.errors).forEach(field => {
                    const errorEl = document.getElementById(`error-${field}`);
                    if (errorEl) {
                        errorEl.textContent = res.errors[field];
                        errorEl.classList.add('show');
                    }
                });
                showNotification('Please fix the highlighted fields', 'error');
            } else {
                showNotification(res.error || 'Failed to update password', 'error');
            }
        })
        .catch(err => {
            console.error('Password change error:', err);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
            showNotification('Unable to update password', 'error');
        });
}

document.addEventListener('DOMContentLoaded', () => {
    loadProfileData();

    const passwordForm = document.getElementById('changePasswordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', handleChangePasswordSubmit);
    }

    const emailForm = document.getElementById('changeEmailForm');
    if (emailForm) {
        emailForm.addEventListener('submit', handleChangeEmailSubmit);
    }

    const profileDistrict = document.getElementById('profileDistrict');
    if (profileDistrict) {
        profileDistrict.addEventListener('change', function () {
            loadNearestCities(this.value);
        });
    }

    const addPhotoBtn = document.getElementById('addPhotoBtn');
    const changePhotoBtn = document.getElementById('changePhotoBtn');
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    if (addPhotoBtn) addPhotoBtn.addEventListener('click', triggerProfilePhotoUpload);
    if (changePhotoBtn) changePhotoBtn.addEventListener('click', triggerProfilePhotoUpload);
    if (removePhotoBtn) removePhotoBtn.addEventListener('click', removeProfilePhoto);

    document.querySelectorAll('.profile-shortcut-card[data-open-modal]').forEach(card => {
        card.addEventListener('click', function () {
            const target = this.getAttribute('data-open-modal');
            if (target === 'accountSettingsModal') {
                openAccountSettingsModal();
            } else if (target === 'refundAccountModal') {
                openRefundAccountModal();
            } else {
                openModal(target);
            }
        });
    });

    document.querySelectorAll('[data-open-modal]').forEach(btn => {
        if (btn.classList.contains('profile-shortcut-card')) return;
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-open-modal');
            if (target === 'accountSettingsModal') {
                openAccountSettingsModal();
            } else if (target === 'refundAccountModal') {
                openRefundAccountModal();
            } else {
                openModal(target);
            }
        });
    });

    const refundForm = document.getElementById('refundAccountForm');
    if (refundForm) refundForm.addEventListener('submit', saveRefundAccount);

    const refundAccountNumber = document.getElementById('refundAccountNumber');
    if (refundAccountNumber) {
        refundAccountNumber.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 18);
        });
    }

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function () {
            closeModalCard(this.getAttribute('data-close-modal'));
        });
    });

    document.querySelectorAll('.modal.profile-modal').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.classList.remove('show');
        });
    });

    document.querySelectorAll('.account-settings-toggle[data-settings-target]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-settings-target');
            if (targetId) toggleSettingsPanel(targetId);
        });
    });

    const deactivateBtn = document.getElementById('confirmDeactivateBtn');
    if (deactivateBtn) deactivateBtn.addEventListener('click', handleBuyerDeactivation);
});

// Namespaced API for inline handlers
window.BuyerProfile = {
    saveProfileData,
    resetProfileForm,
    triggerProfilePhotoUpload,
    removeProfilePhoto,
    openChangePasswordModal,
    closeChangePasswordModal
};

// Backward-compatible aliases (temporary)
window.saveProfileData = window.BuyerProfile.saveProfileData;
window.resetProfileForm = window.BuyerProfile.resetProfileForm;
window.triggerProfilePhotoUpload = window.BuyerProfile.triggerProfilePhotoUpload;
window.removeProfilePhoto = window.BuyerProfile.removeProfilePhoto;
window.openChangePasswordModal = window.BuyerProfile.openChangePasswordModal;
window.closeChangePasswordModal = window.BuyerProfile.closeChangePasswordModal;
