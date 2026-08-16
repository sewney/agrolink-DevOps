// Farmer Profile Management
(function () {
    'use strict';

    const APP_ROOT = window.APP_ROOT || document.body.getAttribute('data-app-root') || '';
    let originalProfileData = null;
    const maxEmailChanges = 2;
    const accountSettingsState = {
        emailChangesUsed: 0,
        emailChangesRemaining: maxEmailChanges
    };

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

    function formatDateTime(value) {
        if (!value) return '-';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '-';
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatDate(value) {
        if (!value) return '-';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        });
    }

    const profileFieldMap = {
        name: 'profileName',
        email: 'profileEmail',
        phone: 'profilePhone',
        district: 'profileDistrict',
        crops_selling: 'profileCrops',
        full_address: 'profileAddress'
    };
    const passwordFieldIds = ['currentPassword', 'newPassword', 'confirmPassword'];
    const passwordServerFieldMap = {
        current: 'currentPassword',
        new: 'newPassword',
        confirm: 'confirmPassword'
    };
    const emailFieldIds = ['newEmailAddress', 'emailChangePassword'];
    const emailServerFieldMap = {
        new_email: 'newEmailAddress',
        password: 'emailChangePassword'
    };

    function onlyDigits(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function normalizeText(value) {
        return String(value || '').trim().replace(/\s+/g, ' ');
    }

    function validatePhoneNumber(phoneValue) {
        const digits = onlyDigits(phoneValue);
        if (digits.length !== 10) {
            return { valid: false, error: 'Phone number must be exactly 10 digits' };
        }
        return { valid: true, value: digits };
    }

    function getFieldErrorContainer(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return null;
        return field.closest('.form-group') || field.parentElement;
    }

    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        const container = getFieldErrorContainer(fieldId);
        if (field) {
            field.classList.remove('error');
            field.removeAttribute('aria-invalid');
        }
        if (container) {
            const errorNode = container.querySelector(`.field-error-text[data-for="${fieldId}"]`);
            if (errorNode) errorNode.remove();
        }
    }

    function setFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const container = getFieldErrorContainer(fieldId);
        if (!field || !container || !message) return;

        clearFieldError(fieldId);
        field.classList.add('error');
        field.setAttribute('aria-invalid', 'true');

        const error = document.createElement('small');
        error.className = 'field-error-text';
        error.dataset.for = fieldId;
        error.textContent = message;
        container.appendChild(error);
    }

    function clearFormErrors(fieldIds) {
        fieldIds.forEach(clearFieldError);
    }

    function applyErrorsFromMap(errorMap, fieldMap) {
        if (!errorMap || typeof errorMap !== 'object') return;
        Object.keys(errorMap).forEach(key => {
            const fieldId = fieldMap[key];
            if (fieldId) setFieldError(fieldId, errorMap[key]);
        });
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

    function updateEmailPolicyHint() {
        const hint = document.getElementById('emailChangePolicyHint');
        if (!hint) return;

        const remaining = Math.max(0, Number(accountSettingsState.emailChangesRemaining ?? maxEmailChanges));
        const used = Math.max(0, Number(accountSettingsState.emailChangesUsed ?? 0));
        hint.textContent = `Email change policy: maximum ${maxEmailChanges} changes after account creation. Used ${used}/${maxEmailChanges}. Remaining ${remaining}.`;
    }

    function validateEmailChange(newEmail, password) {
        const errors = {};
        const normalizedEmail = String(newEmail || '').trim().toLowerCase();

        if (!normalizedEmail) {
            errors.newEmailAddress = 'New email is required';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedEmail)) {
            errors.newEmailAddress = 'Enter a valid email address';
        }

        if (!password) {
            errors.emailChangePassword = 'Password confirmation is required';
        }

        const currentEmail = (document.getElementById('accountSettingsEmail')?.value || '').trim().toLowerCase();
        if (!errors.newEmailAddress && currentEmail && normalizedEmail === currentEmail) {
            errors.newEmailAddress = 'New email must be different from current email';
        }

        return {
            valid: Object.keys(errors).length === 0,
            errors,
            normalizedEmail
        };
    }

    function validatePasswordFields(currentPassword, newPassword, confirmPassword) {
        const errors = {};

        if (!currentPassword) {
            errors.currentPassword = 'Current password is required';
        }

        if (!newPassword) {
            errors.newPassword = 'New password is required';
        } else if (newPassword.length < 8) {
            errors.newPassword = 'New password must be at least 8 characters';
        } else if (!/[A-Za-z]/.test(newPassword) || !/[0-9]/.test(newPassword)) {
            errors.newPassword = 'Use at least one letter and one number';
        }

        if (!confirmPassword) {
            errors.confirmPassword = 'Confirm password is required';
        } else if (newPassword && newPassword !== confirmPassword) {
            errors.confirmPassword = 'Passwords do not match';
        }

        return errors;
    }

    function validatePayoutDetails(data) {
        const accountName = normalizeText(data.accountName);
        const bankName = normalizeText(data.bankName);
        const accountNumber = onlyDigits(data.accountNumber);
        const branchName = normalizeText(data.branchName);
        const errors = {};

        if (!accountName) {
            errors.payoutAccountName = 'Account holder name is required';
        }
        if (!bankName) {
            errors.payoutBankName = 'Bank name is required';
        }
        if (!accountNumber) {
            errors.payoutAccountNumber = 'Account number is required';
        }
        if (!branchName) {
            errors.payoutBranchName = 'Branch name is required';
        }

        if (accountName && !/^[A-Za-z][A-Za-z\s.'-]{1,79}$/.test(accountName)) {
            errors.payoutAccountName = 'Enter a valid account holder name';
        }
        if (bankName && !/^[A-Za-z][A-Za-z0-9\s&.'-]{1,79}$/.test(bankName)) {
            errors.payoutBankName = 'Enter a valid bank name';
        }
        if (accountNumber && !/^\d{8,18}$/.test(accountNumber)) {
            errors.payoutAccountNumber = 'Account number must be 8 to 18 digits';
        }
        if (branchName && !/^[A-Za-z0-9][A-Za-z0-9\s,.'-]{1,79}$/.test(branchName)) {
            errors.payoutBranchName = 'Enter a valid branch name';
        }

        if (Object.keys(errors).length > 0) {
            return { valid: false, errors };
        }

        return {
            valid: true,
            data: {
                accountName,
                bankName,
                accountNumber,
                branchName
            }
        };
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

        const hasPhoto = !!(url && String(url).trim() && String(url) !== 'undefined');

        if (hasPhoto) {
            img.src = url;
            img.classList.remove('is-hidden');
            if (defaultIcon) defaultIcon.classList.add('is-hidden');
        } else {
            img.classList.add('is-hidden');
            if (defaultIcon) defaultIcon.classList.remove('is-hidden');
        }

        updatePhotoActionState(hasPhoto);
    }

    function populateProfile(profile, photoUrl) {
        const name = profile.name || '';
        const email = profile.email || '';

        const fields = {
            profileName: name,
            profileEmail: email,
            profilePhone: profile.phone || '',
            profileDistrict: profile.district || '',
            profileCrops: profile.crops_selling || '',
            profileAddress: profile.full_address || '',
            accountSettingsEmail: email
        };

        Object.keys(fields).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = fields[id];
        });

        const displayName = document.getElementById('profileDisplayName');
        const displayEmail = document.getElementById('profileDisplayEmail');
        const memberSince = document.getElementById('memberSinceValue');
        const lastUpdated = document.getElementById('lastUpdatedValue');

        if (displayName) displayName.textContent = name || 'Farmer';
        if (displayEmail) displayEmail.textContent = email || '';
        if (memberSince) memberSince.textContent = formatDate(profile.created_at);
        if (lastUpdated) lastUpdated.textContent = formatDateTime(profile.updated_at);
        updateGlobalUserName(name || '');

        setProfilePhoto(photoUrl || null);
    }

    function loadProfileData() {
        const form = document.getElementById('profileForm');
        if (!form) return;

        fetch(`${APP_ROOT}/farmerprofile?ajax=1&t=${Date.now()}`, {
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-cache'
        })
            .then(r => {
                if (!r.ok) throw new Error('Failed to fetch profile');
                return r.json();
            })
            .then(res => {
                if (!res.success || !res.profile) return;
                populateProfile(res.profile, res.photoUrl);
                originalProfileData = { ...res.profile };
                if (typeof res.emailChangesUsed !== 'undefined') {
                    accountSettingsState.emailChangesUsed = Number(res.emailChangesUsed) || 0;
                }
                if (typeof res.emailChangesRemaining !== 'undefined') {
                    accountSettingsState.emailChangesRemaining = Number(res.emailChangesRemaining);
                } else {
                    accountSettingsState.emailChangesRemaining = Math.max(0, maxEmailChanges - accountSettingsState.emailChangesUsed);
                }
                updateEmailPolicyHint();
            })
            .catch(err => {
                console.error('Profile load error:', err);
                profileNotify('Error loading profile data', 'error');
            });
    }

    function saveProfileData() {
        const form = document.getElementById('profileForm');
        if (!form) return;

        clearFormErrors(Object.values(profileFieldMap));

        const phoneInput = document.getElementById('profilePhone');
        const phoneCheck = validatePhoneNumber(phoneInput?.value || '');
        if (!phoneCheck.valid) {
            setFieldError('profilePhone', phoneCheck.error);
            if (phoneInput) phoneInput.focus();
            return;
        }

        if (phoneInput) phoneInput.value = phoneCheck.value;

        const formData = {
            name: document.getElementById('profileName')?.value?.trim() || '',
            email: document.getElementById('profileEmail')?.value?.trim() || '',
            phone: phoneCheck.value,
            district: document.getElementById('profileDistrict')?.value?.trim() || '',
            crops_selling: document.getElementById('profileCrops')?.value?.trim() || '',
            full_address: document.getElementById('profileAddress')?.value?.trim() || ''
        };

        const saveBtn = document.getElementById('saveProfileBtn');
        const originalText = saveBtn ? saveBtn.textContent : '';
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
        }

        fetch(`${APP_ROOT}/farmerprofile/saveProfile`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(formData)
        })
            .then(parseJsonResponse)
            .then(res => {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = originalText;
                }

                if (res.success) {
                    profileNotify('Profile updated successfully', 'success');
                    loadProfileData();
                    return;
                }

                if (res.errors && typeof res.errors === 'object') {
                    applyErrorsFromMap(res.errors, profileFieldMap);
                    const firstFieldId = profileFieldMap[Object.keys(res.errors)[0]];
                    const firstField = firstFieldId ? document.getElementById(firstFieldId) : null;
                    if (firstField) firstField.focus();
                    return;
                }

                profileNotify(res.error || 'Failed to update profile', 'error');
            })
            .catch(err => {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = originalText;
                }
                profileNotify('Error saving profile', 'error');
                console.error('Profile save error:', err);
            });
    }

    function resetProfileForm() {
        if (!originalProfileData) {
            loadProfileData();
            return;
        }
        populateProfile(originalProfileData, document.getElementById('profilePhotoDisplay')?.src || null);
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('show');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('show');
    }

    function openAccountSettingsModal() {
        const emailSource = document.getElementById('profileEmail');
        const emailTarget = document.getElementById('accountSettingsEmail');
        if (emailSource && emailTarget) emailTarget.value = emailSource.value || '';
        clearFormErrors(passwordFieldIds);
        clearFormErrors(emailFieldIds);
        clearInlineStatus('emailChangeStatus');
        clearInlineStatus('passwordChangeStatus');
        const newEmailField = document.getElementById('newEmailAddress');
        const emailPasswordField = document.getElementById('emailChangePassword');
        if (newEmailField) newEmailField.value = '';
        if (emailPasswordField) emailPasswordField.value = '';
        passwordFieldIds.forEach(id => {
            const field = document.getElementById(id);
            if (field) field.value = '';
        });
        document.querySelectorAll('.account-settings-panel').forEach(panel => panel.classList.remove('is-open'));
        document.querySelectorAll('.account-settings-toggle[data-settings-target]').forEach(btn => btn.setAttribute('aria-expanded', 'false'));
        updateEmailPolicyHint();
        openModal('accountSettingsModal');
    }

    function openChangePasswordModal() {
        openAccountSettingsModal();
        toggleSettingsPanel('passwordSettingsPanel', true);
    }

    function closeChangePasswordModal() {
        clearFormErrors(passwordFieldIds);
        clearFormErrors(emailFieldIds);
        clearInlineStatus('emailChangeStatus');
        clearInlineStatus('passwordChangeStatus');
        closeModal('accountSettingsModal');
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

    function changeEmail(event) {
        event.preventDefault();

        clearFormErrors(emailFieldIds);
        clearInlineStatus('emailChangeStatus');

        const newEmail = document.getElementById('newEmailAddress')?.value || '';
        const password = document.getElementById('emailChangePassword')?.value || '';
        const validation = validateEmailChange(newEmail, password);

        if (!validation.valid) {
            Object.keys(validation.errors).forEach(id => setFieldError(id, validation.errors[id]));
            const firstField = document.getElementById(Object.keys(validation.errors)[0]);
            if (firstField) firstField.focus();
            return;
        }

        const changeEmailBtn = document.getElementById('changeEmailBtn');
        const originalText = changeEmailBtn ? changeEmailBtn.textContent : '';
        if (changeEmailBtn) {
            changeEmailBtn.disabled = true;
            changeEmailBtn.textContent = 'Updating...';
        }

        fetch(`${APP_ROOT}/farmerprofile/changeEmail`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                newEmail: validation.normalizedEmail,
                password
            })
        })
            .then(parseJsonResponse)
            .then(res => {
                if (changeEmailBtn) {
                    changeEmailBtn.disabled = false;
                    changeEmailBtn.textContent = originalText;
                }

                if (res.success) {
                    const profileEmail = document.getElementById('profileEmail');
                    const accountEmail = document.getElementById('accountSettingsEmail');
                    const displayEmail = document.getElementById('profileDisplayEmail');
                    if (profileEmail) profileEmail.value = res.email || validation.normalizedEmail;
                    if (accountEmail) accountEmail.value = res.email || validation.normalizedEmail;
                    if (displayEmail) displayEmail.textContent = res.email || validation.normalizedEmail;

                    const newEmailField = document.getElementById('newEmailAddress');
                    const passwordField = document.getElementById('emailChangePassword');
                    if (newEmailField) newEmailField.value = '';
                    if (passwordField) passwordField.value = '';

                    accountSettingsState.emailChangesUsed = Number(res.emailChangesUsed ?? accountSettingsState.emailChangesUsed);
                    accountSettingsState.emailChangesRemaining = Number(res.emailChangesRemaining ?? Math.max(0, maxEmailChanges - accountSettingsState.emailChangesUsed));
                    updateEmailPolicyHint();

                    setInlineStatus('emailChangeStatus', res.message || 'Email changed successfully. Continue using your new email to log in.', 'success');
                    profileNotify('Email updated successfully', 'success');
                    return;
                }

                if (res.errors && typeof res.errors === 'object') {
                    applyErrorsFromMap(res.errors, emailServerFieldMap);
                    if (res.errors.limit) setInlineStatus('emailChangeStatus', res.errors.limit, 'error');
                    const firstFieldId = emailServerFieldMap[Object.keys(res.errors)[0]];
                    const firstField = firstFieldId ? document.getElementById(firstFieldId) : null;
                    if (firstField) firstField.focus();
                    if (typeof res.emailChangesUsed !== 'undefined') {
                        accountSettingsState.emailChangesUsed = Number(res.emailChangesUsed) || accountSettingsState.emailChangesUsed;
                    }
                    if (typeof res.emailChangesRemaining !== 'undefined') {
                        accountSettingsState.emailChangesRemaining = Number(res.emailChangesRemaining);
                    }
                    updateEmailPolicyHint();
                    return;
                }

                setInlineStatus('emailChangeStatus', res.error || 'Failed to change email', 'error');
            })
            .catch(err => {
                if (changeEmailBtn) {
                    changeEmailBtn.disabled = false;
                    changeEmailBtn.textContent = originalText;
                }
                setInlineStatus('emailChangeStatus', 'Error changing email', 'error');
                console.error('Change email error:', err);
            });
    }

    function changePassword() {
        const currentPassword = document.getElementById('currentPassword')?.value || '';
        const newPassword = document.getElementById('newPassword')?.value || '';
        const confirmPassword = document.getElementById('confirmPassword')?.value || '';

        clearFormErrors(passwordFieldIds);
        clearInlineStatus('passwordChangeStatus');
        const passwordErrors = validatePasswordFields(currentPassword, newPassword, confirmPassword);
        if (Object.keys(passwordErrors).length > 0) {
            Object.keys(passwordErrors).forEach(fieldId => {
                setFieldError(fieldId, passwordErrors[fieldId]);
            });
            const firstField = document.getElementById(Object.keys(passwordErrors)[0]);
            if (firstField) firstField.focus();
            return;
        }

        const changeBtn = document.getElementById('changePasswordBtn');
        const originalText = changeBtn ? changeBtn.textContent : '';
        if (changeBtn) {
            changeBtn.disabled = true;
            changeBtn.textContent = 'Updating...';
        }

        fetch(`${APP_ROOT}/farmerprofile/changePassword`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                currentPassword,
                newPassword,
                confirmPassword
            })
        })
            .then(parseJsonResponse)
            .then(res => {
                if (changeBtn) {
                    changeBtn.disabled = false;
                    changeBtn.textContent = originalText;
                }

                if (res.success) {
                    profileNotify('Password changed successfully', 'success');
                    passwordFieldIds.forEach(id => {
                        const field = document.getElementById(id);
                        if (field) field.value = '';
                    });
                    clearFormErrors(passwordFieldIds);
                    setInlineStatus('passwordChangeStatus', res.message || 'Password changed successfully.', 'success');
                    return;
                }

                if (res.errors && typeof res.errors === 'object') {
                    applyErrorsFromMap(res.errors, passwordServerFieldMap);
                    const firstFieldId = passwordServerFieldMap[Object.keys(res.errors)[0]];
                    const firstField = firstFieldId ? document.getElementById(firstFieldId) : null;
                    if (firstField) firstField.focus();
                    setInlineStatus('passwordChangeStatus', 'Please correct the highlighted fields.', 'error');
                    return;
                }
                setInlineStatus('passwordChangeStatus', res.error || 'Failed to change password', 'error');
                profileNotify(res.error || 'Failed to change password', 'error');
            })
            .catch(err => {
                if (changeBtn) {
                    changeBtn.disabled = false;
                    changeBtn.textContent = originalText;
                }
                setInlineStatus('passwordChangeStatus', 'Error changing password', 'error');
                profileNotify('Error changing password', 'error');
                console.error('Change password error:', err);
            });
    }

    function triggerProfilePhotoUpload() {
        let input = document.getElementById('profilePhotoInput');
        if (!input) {
            input = document.createElement('input');
            input.type = 'file';
            input.id = 'profilePhotoInput';
            input.accept = 'image/*';
            input.style.display = 'none';
            document.body.appendChild(input);

            input.addEventListener('change', function (e) {
                const file = e.target.files && e.target.files[0];
                if (!file) return;

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    profileNotify('Please select a valid image file (JPG, PNG, or WebP)', 'error');
                    input.value = '';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    profileNotify('Image size must be less than 5MB', 'error');
                    input.value = '';
                    return;
                }

                uploadProfilePhoto(file);
            });
        }
        input.click();
    }

    function uploadProfilePhoto(file) {
        const formData = new FormData();
        formData.append('photo', file);

        fetch(`${APP_ROOT}/farmerprofile/uploadPhoto`, {
            method: 'POST',
            credentials: 'include',
            body: formData
        })
            .then(parseJsonResponse)
            .then(res => {
                if (res.success && res.photoUrl) {
                    setProfilePhoto(`${res.photoUrl}?t=${Date.now()}`);
                    profileNotify('Profile photo updated successfully', 'success');
                    return;
                }
                profileNotify(res.error || 'Failed to upload photo', 'error');
            })
            .catch(err => {
                profileNotify('Error uploading photo', 'error');
                console.error('Photo upload error:', err);
            })
            .finally(() => {
                const input = document.getElementById('profilePhotoInput');
                if (input) input.value = '';
            });
    }

    function removeProfilePhoto() {
        if (!confirm('Remove your profile photo?')) return;

        fetch(`${APP_ROOT}/farmerprofile/removePhoto`, {
            method: 'POST',
            credentials: 'include'
        })
            .then(parseJsonResponse)
            .then(res => {
                if (res.success) {
                    setProfilePhoto(null);
                    profileNotify('Profile photo removed successfully', 'success');
                    return;
                }
                profileNotify(res.error || 'Failed to remove photo', 'error');
            })
            .catch(err => {
                profileNotify('Error removing photo', 'error');
                console.error('Photo remove error:', err);
            });
    }

    function populatePayoutDetails(account) {
        const map = {
            payoutAccountName: account?.account_holder_name || '',
            payoutBankName: account?.bank_name || '',
            payoutAccountNumber: account?.account_number || '',
            payoutBranchName: account?.branch_name || ''
        };

        Object.keys(map).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = map[id];
        });
    }

    function loadPayoutDetails() {
        fetch(`${APP_ROOT}/farmerprofile/getPayoutAccount`, {
            method: 'GET',
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(parseJsonResponse)
            .then(res => {
                if (!res.success) {
                    return;
                }
                populatePayoutDetails(res.account || null);
            })
            .catch(err => {
                console.error('Failed to load payout details:', err);
            });
    }

    function savePayoutDetails(event) {
        event.preventDefault();

        clearFormErrors([
            'payoutAccountName',
            'payoutBankName',
            'payoutAccountNumber',
            'payoutBranchName'
        ]);

        const data = {
            accountName: document.getElementById('payoutAccountName')?.value?.trim() || '',
            bankName: document.getElementById('payoutBankName')?.value?.trim() || '',
            accountNumber: document.getElementById('payoutAccountNumber')?.value?.trim() || '',
            branchName: document.getElementById('payoutBranchName')?.value?.trim() || ''
        };

        const validation = validatePayoutDetails(data);
        if (!validation.valid) {
            Object.keys(validation.errors || {}).forEach(fieldId => {
                setFieldError(fieldId, validation.errors[fieldId]);
            });
            const firstErrorFieldId = Object.keys(validation.errors || {})[0];
            const firstErrorField = firstErrorFieldId ? document.getElementById(firstErrorFieldId) : null;
            if (firstErrorField) firstErrorField.focus();
            return;
        }

        data.accountName = validation.data.accountName;
        data.bankName = validation.data.bankName;
        data.accountNumber = validation.data.accountNumber;
        data.branchName = validation.data.branchName;

        const payoutAccountName = document.getElementById('payoutAccountName');
        const payoutBankName = document.getElementById('payoutBankName');
        const payoutAccountNumber = document.getElementById('payoutAccountNumber');
        const payoutBranchName = document.getElementById('payoutBranchName');
        if (payoutAccountName) payoutAccountName.value = data.accountName;
        if (payoutBankName) payoutBankName.value = data.bankName;
        if (payoutAccountNumber) payoutAccountNumber.value = data.accountNumber;
        if (payoutBranchName) payoutBranchName.value = data.branchName;

        const submitBtn = event.currentTarget?.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

        fetch(`${APP_ROOT}/farmerprofile/savePayoutAccount`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                account_holder_name: data.accountName,
                bank_name: data.bankName,
                account_number: data.accountNumber,
                branch_name: data.branchName
            })
        })
            .then(parseJsonResponse)
            .then(res => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }

                if (res.success) {
                    populatePayoutDetails(res.account || null);
                    profileNotify(res.message || 'Bank details saved', 'success');
                    closeModal('payoutDetailsModal');
                    return;
                }

                if (res.errors && typeof res.errors === 'object') {
                    const payoutServerFieldMap = {
                        account_holder_name: 'payoutAccountName',
                        bank_name: 'payoutBankName',
                        account_number: 'payoutAccountNumber',
                        branch_name: 'payoutBranchName'
                    };
                    applyErrorsFromMap(res.errors, payoutServerFieldMap);
                    const firstKey = Object.keys(res.errors)[0];
                    const firstField = payoutServerFieldMap[firstKey] ? document.getElementById(payoutServerFieldMap[firstKey]) : null;
                    if (firstField) firstField.focus();
                    return;
                }

                profileNotify(res.error || 'Failed to save payout account', 'error');
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
                profileNotify('Error saving payout details', 'error');
                console.error('Payout save error:', err);
            });
    }

    function openPayoutDetailsModal() {
        openModal('payoutDetailsModal');
        loadPayoutDetails();
    }

    function confirmDeactivateAccount() {
        const deactivateBtn = document.getElementById('confirmDeactivateBtn');
        const originalText = deactivateBtn ? deactivateBtn.textContent : '';

        if (deactivateBtn) {
            deactivateBtn.disabled = true;
            deactivateBtn.textContent = 'Processing...';
        }

        fetch(`${APP_ROOT}/farmerprofile/requestDeactivation`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams()
        })
            .then(parseJsonResponse)
            .then(res => {
                if (deactivateBtn) {
                    deactivateBtn.disabled = false;
                    deactivateBtn.textContent = originalText;
                }

                if (res.success) {
                    profileNotify(res.message || 'Account deactivated successfully.', 'success');
                    if (res.redirect) {
                        window.location.href = res.redirect;
                    }
                    return;
                }

                profileNotify(res.error || 'Failed to deactivate account', 'error');
            })
            .catch(err => {
                if (deactivateBtn) {
                    deactivateBtn.disabled = false;
                    deactivateBtn.textContent = originalText;
                }
                profileNotify('Error requesting deactivation', 'error');
                console.error('Deactivation error:', err);
            });
    }

    function initializeProfileFunctionality() {
        loadProfileData();

        const phoneInput = document.getElementById('profilePhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function () {
                this.value = onlyDigits(this.value).slice(0, 10);
                clearFieldError('profilePhone');
            });
        }

        const accountNumberInput = document.getElementById('payoutAccountNumber');
        if (accountNumberInput) {
            accountNumberInput.addEventListener('input', function () {
                this.value = onlyDigits(this.value).slice(0, 18);
                clearFieldError('payoutAccountNumber');
            });
        }

        ['profileName', 'profileDistrict', 'profileCrops', 'profileAddress'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () { clearFieldError(id); });
            if (el.tagName === 'SELECT') {
                el.addEventListener('change', function () { clearFieldError(id); });
            }
        });

        ['payoutAccountName', 'payoutBankName', 'payoutBranchName'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () { clearFieldError(id); });
        });

        emailFieldIds.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () {
                clearFieldError(id);
                if (id === 'newEmailAddress') this.value = this.value.trimStart();
                clearInlineStatus('emailChangeStatus');
            });
        });

        passwordFieldIds.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () { clearFieldError(id); });
        });

        const addPhotoBtn = document.getElementById('addPhotoBtn');
        const changePhotoBtn = document.getElementById('changePhotoBtn');
        const removePhotoBtn = document.getElementById('removePhotoBtn');
        if (addPhotoBtn) addPhotoBtn.addEventListener('click', triggerProfilePhotoUpload);
        if (changePhotoBtn) changePhotoBtn.addEventListener('click', triggerProfilePhotoUpload);
        if (removePhotoBtn) removePhotoBtn.addEventListener('click', removeProfilePhoto);

        const shortcutCards = document.querySelectorAll('.profile-shortcut-card[data-open-modal]');
        shortcutCards.forEach(card => {
            card.addEventListener('click', function () {
                const target = this.getAttribute('data-open-modal');
                if (target === 'accountSettingsModal') {
                    openAccountSettingsModal();
                } else if (target === 'payoutDetailsModal') {
                    openPayoutDetailsModal();
                } else {
                    openModal(target);
                }
            });
        });

        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        openModalButtons.forEach(btn => {
            if (btn.classList.contains('profile-shortcut-card')) return;
            btn.addEventListener('click', function () {
                openModal(this.getAttribute('data-open-modal'));
            });
        });

        const closeModalButtons = document.querySelectorAll('[data-close-modal]');
        closeModalButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                closeModal(this.getAttribute('data-close-modal'));
            });
        });

        document.querySelectorAll('.modal.farmer-profile-modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) modal.classList.remove('show');
            });
        });

        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', function (e) {
                e.preventDefault();
                changePassword();
            });
        }

        const changeEmailForm = document.getElementById('changeEmailForm');
        if (changeEmailForm) {
            changeEmailForm.addEventListener('submit', changeEmail);
        }

        document.querySelectorAll('.account-settings-toggle[data-settings-target]').forEach(btn => {
            btn.addEventListener('click', function () {
                const targetId = this.getAttribute('data-settings-target');
                if (targetId) toggleSettingsPanel(targetId);
            });
        });

        const payoutForm = document.getElementById('payoutDetailsForm');
        if (payoutForm) payoutForm.addEventListener('submit', savePayoutDetails);

        const deactivateBtn = document.getElementById('confirmDeactivateBtn');
        if (deactivateBtn) deactivateBtn.addEventListener('click', confirmDeactivateAccount);
    }

    function profileNotify(message, type) {
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) existingNotification.remove();

        const notification = document.createElement('div');
        notification.className = `notification ${type || 'info'}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 14px 20px;
            color: white;
            border-radius: 8px;
            z-index: 10001;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 2800);
    }

    window.FarmerProfile = {
        saveProfileData,
        resetProfileForm,
        openAccountSettingsModal,
        openPayoutDetailsModal,
        openChangePasswordModal,
        closeChangePasswordModal,
        changeEmail,
        changePassword,
        loadProfileData,
        removeProfilePhoto,
        triggerProfilePhotoUpload,
        setProfilePhoto
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeProfileFunctionality);
    } else {
        initializeProfileFunctionality();
    }
})();
