<?php
$districts = [
    'Ampara',
    'Anuradhapura',
    'Badulla',
    'Batticaloa',
    'Colombo',
    'Galle',
    'Gampaha',
    'Jaffna',
    'Kalutara',
    'Kandy',
    'Kegalle',
    'Kilinochchi',
    'Kurunegala',
    'Mannar',
    'Matale',
    'Matara',
    'Mullaitivu',
    'Nuwara Eliya',
    'Polonnaruwa',
    'Puttalam',
    'Ratnapura',
    'Trincomalee',
    'Vavuniya'
];

$profileStats = is_array($profileStats ?? null) ? $profileStats : [];
?>

<div class="content-section profile-modern buyer-profile-modern">
    <div class="content-header">
        <h1 class="content-title">Profile</h1>
        <p class="content-subtitle">Manage details and account settings.</p>
    </div>

    <div class="profile-hero-card">
        <div class="profile-hero-avatar-wrap">
            <div id="profilePhotoWrapper" class="profile-hero-avatar">
                <img id="profilePhotoDisplay" src="<?= esc($photoUrl ?? '') ?>" alt="Profile photo" class="<?= empty($photoUrl) ? 'is-hidden' : '' ?>">
                <div id="defaultProfileIcon" class="profile-default-icon <?= !empty($photoUrl) ? 'is-hidden' : '' ?>" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>

            <div class="profile-photo-actions">
                <button type="button" id="addPhotoBtn" class="btn btn-secondary <?= !empty($photoUrl) ? 'is-hidden' : '' ?>">Add Photo</button>
                <button type="button" id="changePhotoBtn" class="btn btn-secondary <?= empty($photoUrl) ? 'is-hidden' : '' ?>">Change Photo</button>
                <button type="button" id="removePhotoBtn" class="btn btn-secondary <?= empty($photoUrl) ? 'is-hidden' : '' ?>">Remove Photo</button>
            </div>
        </div>

        <div class="profile-hero-meta">
            <h2 id="profileDisplayName"><?= esc($username ?? 'Buyer') ?></h2>
            <p id="profileDisplayEmail"><?= esc(authUserEmail()) ?></p>
            <span class="profile-role-badge">Buyer</span>
        </div>
    </div>

    <div class="content-card profile-card">
        <div class="profile-section-head">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Personal Information
            </h3>
        </div>

        <div class="profile-card-body">
            <form id="profileForm" class="profile-form-grid buyer-profile-form-grid">
                <div class="form-group">
                    <label for="profileName">Full Name *</label>
                    <input type="text" id="profileName" name="name" class="form-control" value="<?= esc($username ?? '') ?>" maxlength="100" autocomplete="name" required>
                </div>

                <div class="form-group">
                    <label for="profilePhone">Phone Number *</label>
                    <input type="tel" id="profilePhone" name="phone" class="form-control" value="<?= esc($profile->phone ?? '') ?>" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" autocomplete="tel" required>
                </div>

                <div class="form-group">
                    <label for="profileDistrict">District *</label>
                    <select id="profileDistrict" name="district" class="form-control" required>
                        <option value="">Select District</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= esc($district) ?>" <?= (($profile->district ?? '') === $district) ? 'selected' : '' ?>>
                                <?= esc($district) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="profileApartmentCode">Apartment / Building Code</label>
                    <input type="text" id="profileApartmentCode" name="apartment_code" class="form-control" value="<?= esc($profile->apartment_code ?? '') ?>" maxlength="50" autocomplete="address-line1">
                </div>

                <div class="form-group form-group-wide">
                    <label for="profileStreetName">Street Name *</label>
                    <input type="text" id="profileStreetName" name="street_name" class="form-control" value="<?= esc($profile->street_name ?? '') ?>" maxlength="100" autocomplete="address-line2" required>
                </div>

                <div class="form-group">
                    <label for="profileCity">Nearest City *</label>
                    <select id="profileCity" name="city" class="form-control" data-selected-city="<?= esc($profile->city ?? '') ?>" required>
                        <option value="">Select nearest city</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="profilePostalCode">Postal Code</label>
                    <input type="text" id="profilePostalCode" name="postal_code" class="form-control" value="<?= esc($profile->postal_code ?? '') ?>" maxlength="5" inputmode="numeric" pattern="[0-9]{5}" autocomplete="postal-code" placeholder="Optional">
                </div>

                <div class="form-group form-group-wide buyer-address-details-field">
                    <label for="profileAdditionalAddress">Additional Address Details</label>
                    <textarea id="profileAdditionalAddress" name="additional_address_details" class="form-control" rows="2" maxlength="100" placeholder="Landmark or extra delivery hint (optional)"><?= esc($profile->additional_address_details ?? '') ?></textarea>
                </div>

                <input type="email" id="profileEmail" value="<?= esc(authUserEmail()) ?>" hidden>
            </form>
        </div>

        <div class="profile-card-footer">
            <button type="button" id="saveProfileBtn" class="btn btn-primary" onclick="BuyerProfile.saveProfileData()">Save Changes</button>
            <button type="button" class="btn btn-secondary" onclick="BuyerProfile.resetProfileForm()">Reset</button>
        </div>
    </div>

    <div class="content-card profile-shortcut-card account-settings-shortcut-card" data-open-modal="accountSettingsModal">
        <div class="profile-shortcut-head">
            <h3>Account Settings</h3>
        </div>
        <p>Manage login email and password.</p>
    </div>

    <div class="content-card profile-shortcut-card" data-open-modal="refundAccountModal">
        <div class="profile-shortcut-head">
            <h3>Refund Bank Details</h3>
        </div>
        <p>Add or update bank account details for order refunds.</p>
    </div>

    <div class="content-card profile-account-info-card">
        <div class="profile-section-head">
            <h3>Profile Stats</h3>
        </div>
        <div class="profile-account-info-grid buyer-profile-stats-grid">
            <div>
                <span class="profile-account-label">Member Since</span>
                <span class="profile-account-value" id="memberSinceValue">
                    <?= !empty($profileStats['member_since']) ? date('M d, Y', strtotime($profileStats['member_since'])) : '-' ?>
                </span>
            </div>
            <div>
                <span class="profile-account-label">Total Orders</span>
                <span class="profile-account-value" id="totalOrdersValue"><?= (int)($profileStats['total_orders'] ?? 0) ?></span>
            </div>
            <div>
                <span class="profile-account-label">Active Deliveries</span>
                <span class="profile-account-value" id="activeDeliveriesValue"><?= (int)($profileStats['active_deliveries'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <div class="content-card profile-danger-card deactivate-section-card">
        <div class="profile-section-head danger">
            <h3>Deactivate Account</h3>
        </div>
        <div class="deactivate-section-body">
            <p class="deactivate-section-note">Temporarily disable this account. Contact admin when you need to reactivate it.</p>
            <button type="button" class="btn btn-danger deactivate-section-btn" data-open-modal="deactivateAccountModal">Deactivate Account</button>
        </div>
    </div>
</div>

<div id="accountSettingsModal" class="modal profile-modal">
    <div class="modal-content profile-modal-content">
        <div class="modal-header profile-modal-header">
            <h3>Account Settings</h3>
            <button type="button" class="modal-close" data-close-modal="accountSettingsModal" aria-label="Close">×</button>
        </div>
        <div class="modal-body account-settings-modal-body">
            <div class="account-settings-section">
                <button type="button" class="account-settings-toggle" data-settings-target="emailSettingsPanel" aria-expanded="false">
                    <span class="account-settings-toggle-main">
                        <span class="account-settings-toggle-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6h16v12H4z"></path>
                                <path d="m4 7 8 6 8-6"></path>
                            </svg>
                        </span>
                        <span class="account-settings-toggle-label">Change Email</span>
                    </span>
                    <span class="account-settings-toggle-arrow" aria-hidden="true">⌄</span>
                </button>
                <div id="emailSettingsPanel" class="account-settings-panel">
                    <div class="form-group">
                        <label for="accountSettingsEmail">Current Email</label>
                        <input id="accountSettingsEmail" class="form-control" type="email" readonly>
                    </div>
                    <form id="changeEmailForm">
                        <div class="form-group">
                            <label for="newEmailAddress">New Email Address *</label>
                            <input id="newEmailAddress" name="newEmailAddress" class="form-control" type="email" maxlength="100" autocomplete="email" required>
                        </div>
                        <div class="form-group">
                            <label for="emailChangePassword">Confirm Password *</label>
                            <input id="emailChangePassword" name="emailChangePassword" class="form-control" type="password" autocomplete="current-password" required>
                            <small class="form-hint">Enter your account password to confirm email change.</small>
                        </div>
                        <small class="form-hint" id="emailChangePolicyHint">You can change email up to 2 times after account creation.</small>
                        <div id="emailChangeStatus" class="settings-inline-status is-hidden"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-close-modal="accountSettingsModal">Cancel</button>
                            <button type="submit" id="changeEmailBtn" class="btn btn-primary">Update Email</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="account-settings-section">
                <button type="button" class="account-settings-toggle" data-settings-target="passwordSettingsPanel" aria-expanded="false">
                    <span class="account-settings-toggle-main">
                        <span class="account-settings-toggle-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="11" width="16" height="9" rx="2"></rect>
                                <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                            </svg>
                        </span>
                        <span class="account-settings-toggle-label">Change Password</span>
                    </span>
                    <span class="account-settings-toggle-arrow" aria-hidden="true">⌄</span>
                </button>
                <div id="passwordSettingsPanel" class="account-settings-panel">
                    <form id="changePasswordForm">
                        <div class="form-group">
                            <label for="currentPassword">Current Password *</label>
                            <input type="password" id="currentPassword" name="currentPassword" class="form-control" autocomplete="current-password" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password *</label>
                            <input type="password" id="newPassword" name="newPassword" class="form-control" minlength="8" autocomplete="new-password" required>
                            <small class="form-hint">Minimum 8 characters with at least one letter and one number.</small>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password *</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div id="passwordChangeStatus" class="settings-inline-status is-hidden"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-close-modal="accountSettingsModal">Cancel</button>
                            <button type="submit" id="changePasswordBtn" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="refundAccountModal" class="modal profile-modal">
    <div class="modal-content profile-modal-content">
        <div class="modal-header profile-modal-header">
            <h3>Add or Change Bank Details</h3>
            <button type="button" class="modal-close" data-close-modal="refundAccountModal" aria-label="Close">×</button>
        </div>
        <div class="modal-body">
            <p class="payout-helper-text">Order refunds will be transferred to this bank account.</p>
            <form id="refundAccountForm">
                <div class="form-group">
                    <label for="refundAccountName">Account Holder Name *</label>
                    <input type="text" id="refundAccountName" class="form-control" maxlength="80" required>
                </div>
                <div class="form-group">
                    <label for="refundBankName">Bank Name *</label>
                    <input type="text" id="refundBankName" class="form-control" maxlength="80" required>
                </div>
                <div class="form-group">
                    <label for="refundAccountNumber">Account Number *</label>
                    <input type="text" id="refundAccountNumber" class="form-control" maxlength="18" inputmode="numeric" pattern="[0-9]{8,18}" required>
                </div>
                <div class="form-group">
                    <label for="refundBranchName">Branch Name *</label>
                    <input type="text" id="refundBranchName" class="form-control" maxlength="80" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-close-modal="refundAccountModal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Bank Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deactivateAccountModal" class="modal profile-modal deactivate-modal">
    <div class="modal-content profile-modal-content deactivate-modal-content">
        <div class="modal-header profile-modal-header danger deactivate-modal-header">
            <h3>Deactivate Buyer Account</h3>
            <button type="button" class="modal-close" data-close-modal="deactivateAccountModal" aria-label="Close">×</button>
        </div>
        <div class="modal-body deactivate-modal-body">
            <div class="deactivate-warning-box">
                <span class="deactivate-warning-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 22 21H2L12 3Z"></path>
                        <path d="M12 9v5"></path>
                        <path d="M12 17.5h.01"></path>
                    </svg>
                </span>
                <p class="deactivate-warning-text">
                    Your account will become unavailable for placing new orders. To reactivate the account, you must contact admin.
                </p>
            </div>
            <div class="modal-footer deactivate-modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal="deactivateAccountModal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeactivateBtn">Confirm Deactivation</button>
            </div>
        </div>
    </div>
</div>