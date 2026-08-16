<?php

class BuyerProfileController
{
    use Controller;

    protected $buyerModel;
    protected $userModel;
    protected $orderModel;
    protected $payoutAccountsModel;

    public function __construct()
    {
        $this->buyerModel = new BuyerModel();
        $this->userModel = new UserModel();
        $this->orderModel = new OrderModel();
        $this->payoutAccountsModel = new PayoutAccountsModel();
    }

    /**
     * Display buyer profile page
     */
    public function index()
    {
        // Check if this is an AJAX request
        if (!empty($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
            return $this->getProfileJson();
        }

        // Regular page view
        if (!hasRole('buyer')) {
            return redirect('login');
        }

        $userId = authUserId();
        $profile = $this->getOrCreateProfile($userId);
        $photoUrl = $this->extractPhotoUrl($profile);

        $profileStats = $this->buildProfileStats($userId, $profile);

        $data = [
            'pageTitle' => 'Profile',
            'activePage' => 'profile',
            'username' => authUserName(),
            'profile' => $profile,
            'photoUrl' => $photoUrl,
            'profileStats' => $profileStats,
            'pageStyles' => 'profile.css',
            'pageScript' => 'profile.js',
            'contentView' => 'buyer/buyerProfileContent.view.php'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    /**
     * Helper: Build public URL for a stored profile photo filename
     */
    private function buildPhotoUrl(?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }
        return rtrim(ROOT, '/') . '/assets/images/buyer-profiles/' . rawurlencode($filename);
    }

    /**
     * Ensure buyer profile row exists and return the latest profile data.
     */
    private function getOrCreateProfile(int $userId)
    {
        $profile = $this->buyerModel->getProfileByUserId($userId);

        if ($profile) {
            return $profile;
        }

        $this->buyerModel->createProfile($userId, []);
        return $this->buyerModel->getProfileByUserId($userId);
    }

    private function extractPhotoUrl($profile): ?string
    {
        if (!$profile || empty($profile->profile_photo)) {
            return null;
        }

        return $this->buildPhotoUrl($profile->profile_photo);
    }

    private function prepareJsonResponse(): void
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');
    }

    private function jsonResponse(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload);
        exit;
    }

    private function requireBuyerRole(): void
    {
        if (!hasRole('buyer')) {
            $this->jsonResponse(401, [
                'success' => false,
                'error' => 'Unauthorized'
            ]);
        }
    }

    private function requirePostRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(405, [
                'success' => false,
                'error' => 'Method not allowed'
            ]);
        }
    }

    private function getBuyerPhotoUploadDir(bool $createIfMissing = false): string
    {
        $publicPath = realpath(__DIR__ . '/../../../public');
        if ($publicPath === false) {
            throw new RuntimeException('Public directory not found');
        }

        $uploadDir = $publicPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'buyer-profiles' . DIRECTORY_SEPARATOR;

        if ($createIfMissing && !is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('Failed to create upload directory');
            }
        }

        return $uploadDir;
    }

    /**
     * Get profile data as JSON (for AJAX requests)
     */
    private function getProfileJson()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();

        $userId = authUserId();
        $profile = $this->getOrCreateProfile($userId);
        $photoUrl = $this->extractPhotoUrl($profile);

        $maxEmailChanges = 2;
        $emailChangesUsed = $this->userModel->getEmailChangeCount($userId);
        $profileStats = $this->buildProfileStats($userId, $profile);

        $this->jsonResponse(200, [
            'success' => true,
            'profile' => $profile,
            'photoUrl' => $photoUrl,
            'profileStats' => $profileStats,
            'emailChangesUsed' => $emailChangesUsed,
            'emailChangesRemaining' => max(0, $maxEmailChanges - $emailChangesUsed)
        ]);
    }

    private function buildProfileStats($userId, $profile)
    {
        $user = $this->userModel->findById($userId);
        $orders = $this->orderModel->getOrdersByBuyer($userId);
        $trackingRows = $this->orderModel->getDeliveryTrackingByBuyer($userId);
        $activeDeliveries = 0;

        if (is_array($trackingRows)) {
            foreach ($trackingRows as $row) {
                $deliveryStatus = strtolower(trim((string)($row->delivery_status ?? '')));
                $orderStatus = strtolower(trim((string)($row->order_status ?? '')));

                if (in_array($deliveryStatus, ['pending', 'accepted', 'in_transit'], true)) {
                    $activeDeliveries++;
                    continue;
                }

                if ($deliveryStatus === '' && in_array($orderStatus, ['processing', 'ready_for_pickup', 'shipped'], true)) {
                    $activeDeliveries++;
                }
            }
        }

        return [
            'member_since' => $user->created_at ?? ($profile->created_at ?? null),
            'total_orders' => is_array($orders) ? count($orders) : 0,
            'active_deliveries' => $activeDeliveries,
            'account_status' => ucfirst((string)($user->status ?? 'active')),
        ];
    }

    /**
     * Save/Update buyer profile via AJAX
     */
    public function saveProfile()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();
        $this->requirePostRequest();

        try {
            $userId = authUserId();

            // Get POST data
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'district' => trim($_POST['district'] ?? ''),
                'apartment_code' => trim($_POST['apartment_code'] ?? ''),
                'street_name' => trim($_POST['street_name'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'postal_code' => trim($_POST['postal_code'] ?? ''),
                'additional_address_details' => trim($_POST['additional_address_details'] ?? ''),
            ];

            // Validate profile data at model level
            $validation = $this->buyerModel->validateProfile($data);

            if ($validation !== true) {
                error_log("Buyer Profile Validation Failed: " . json_encode($validation));
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validation
                ]);
            }

            // Name is editable from profile form; email changes are handled via Account Settings flow.
            if (!empty($data['name'])) {
                $this->userModel->update($userId, ['name' => $data['name']]);
                setAuthUserName((string)$data['name']);
            }

            // Prepare profile data (exclude name and email from buyer profile update)
            $profileData = [
                'phone' => $data['phone'],
                'district' => $data['district'],
                'apartment_code' => $data['apartment_code'],
                'street_name' => $data['street_name'],
                'city' => $data['city'],
                'postal_code' => $data['postal_code'],
                'additional_address_details' => $data['additional_address_details'],
            ];

            // Update profile (creates if doesn't exist)
            $result = $this->buyerModel->updateProfile($userId, $profileData);

            if ($result === false) {
                $this->jsonResponse(500, [
                    'success' => false,
                    'error' => 'Failed to update profile - database error'
                ]);
            }

            // Get updated profile
            $profile = $this->buyerModel->getProfileByUserId($userId);

            if (!$profile) {
                $this->jsonResponse(500, [
                    'success' => false,
                    'error' => 'Profile updated but failed to retrieve'
                ]);
            }

            $this->jsonResponse(200, [
                'success' => true,
                'message' => 'Profile updated successfully',
                'profile' => $profile
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(500, [
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Upload profile photo via AJAX
     * Strategy: Store only filename in database, construct full URL at display time
     */
    public function uploadPhoto()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();
        $this->requirePostRequest();

        try {
            $userId = authUserId();

            // Validate file upload
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'No file uploaded'
                ]);
            }

            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds php.ini upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
                ];

                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => $uploadErrors[$_FILES['photo']['error']] ?? 'Unknown upload error'
                ]);
            }

            $filename = $_FILES['photo']['name'];
            $filesize = $_FILES['photo']['size'];
            $tmpName = $_FILES['photo']['tmp_name'];

            // Validate extension
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'Invalid image format. Allowed: ' . implode(', ', $allowed)
                ]);
            }

            // Validate size (max 5MB)
            if ($filesize > 5 * 1024 * 1024) {
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'Image size must be less than 5MB'
                ]);
            }

            // Validate image content
            if (!is_uploaded_file($tmpName)) {
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'Invalid upload'
                ]);
            }

            $imgInfo = @getimagesize($tmpName);
            if ($imgInfo === false) {
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'File is not a valid image'
                ]);
            }

            // Generate unique filename
            $uniqueFilename = 'profile_photo_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

            $uploadDir = $this->getBuyerPhotoUploadDir(true);

            // Move uploaded file
            if (!move_uploaded_file($tmpName, $uploadDir . $uniqueFilename)) {
                $this->jsonResponse(500, [
                    'success' => false,
                    'error' => 'Failed to save image to server'
                ]);
            }

            // Delete old profile photo if exists (handle full URL vs filename safely)
            $oldFilename = $this->buyerModel->getOldPhotoFilename($userId);
            if ($oldFilename) {
                $oldBasename = basename($oldFilename);
                $oldPath = $uploadDir . $oldBasename;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Update database with new filename (only filename)
            $result = $this->buyerModel->updateProfilePhoto($userId, $uniqueFilename);

            if ($result) {
                $this->jsonResponse(200, [
                    'success' => true,
                    'message' => 'Profile photo uploaded successfully',
                    'filename' => $uniqueFilename,
                    'photoUrl' => $this->buildPhotoUrl($uniqueFilename)
                ]);
            } else {
                // Delete the uploaded file if database update fails
                @unlink($uploadDir . $uniqueFilename);

                $this->jsonResponse(500, [
                    'success' => false,
                    'error' => 'Failed to update profile photo in database'
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse(500, [
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove profile photo
     */
    public function removePhoto()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();
        $this->requirePostRequest();

        try {
            $userId = authUserId();

            // Get current photo filename
            $currentPhoto = $this->buyerModel->getOldPhotoFilename($userId);

            // Delete physical file if it exists
            if ($currentPhoto) {
                try {
                    $uploadDir = $this->getBuyerPhotoUploadDir();
                    $filePath = $uploadDir . basename($currentPhoto);

                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                } catch (RuntimeException $e) {
                    // Keep DB cleanup functional even if path resolution fails.
                }
            }

            // Update database to remove photo reference
            $result = $this->buyerModel->removeProfilePhoto($userId);

            if ($result) {
                $this->jsonResponse(200, [
                    'success' => true,
                    'message' => 'Profile photo removed successfully'
                ]);
            } else {
                $this->jsonResponse(500, [
                    'success' => false,
                    'error' => 'Failed to remove profile photo from database'
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse(500, [
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Change password via AJAX
     */
    public function changePassword()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();
        $this->requirePostRequest();

        try {
            $userId = authUserId();

            $currentPassword = $_POST['currentPassword'] ?? '';
            $newPassword = $_POST['newPassword'] ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';

            // Validate at model level
            $validation = $this->buyerModel->validatePasswordChange(
                $userId,
                $currentPassword,
                $newPassword,
                $confirmPassword
            );

            if ($validation !== true) {
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validation
                ]);
            }

            // Change password
            $result = $this->userModel->updatePassword($userId, $newPassword);

            if ($result) {
                $this->jsonResponse(200, [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } else {
                $this->jsonResponse(500, [
                    'success' => false,
                    'error' => 'Failed to change password'
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse(500, [
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Change email via AJAX (requires password confirmation)
     */
    public function changeEmail()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();
        $this->requirePostRequest();

        try {
            $userId = (int)authUserId();
            $newEmail = strtolower(trim($_POST['newEmail'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $maxEmailChanges = 2;
            $errors = [];

            $currentUser = $this->userModel->first(['id' => $userId]);
            if (!$currentUser) {
                $this->jsonResponse(404, ['success' => false, 'error' => 'User not found']);
            }

            $emailChangesUsed = $this->userModel->getEmailChangeCount($userId);
            $emailChangesRemaining = max(0, $maxEmailChanges - $emailChangesUsed);

            if ($emailChangesRemaining <= 0) {
                $errors['limit'] = 'Email change limit reached. You can only change email 2 times after account creation.';
            }

            if ($newEmail === '') {
                $errors['new_email'] = 'New email is required';
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['new_email'] = 'Please enter a valid email address';
            } elseif (strcasecmp($newEmail, (string)$currentUser->email) === 0) {
                $errors['new_email'] = 'New email must be different from current email';
            } else {
                $existingUser = $this->userModel->findByEmail($newEmail);
                if ($existingUser && (int)$existingUser->id !== $userId) {
                    $errors['new_email'] = 'This email is already used by another account';
                }
            }

            if ($password === '') {
                $errors['password'] = 'Password confirmation is required';
            } elseif (!password_verify($password, (string)$currentUser->password)) {
                $errors['password'] = 'Current password is incorrect';
            }

            if (!empty($errors)) {
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $errors,
                    'emailChangesUsed' => $emailChangesUsed,
                    'emailChangesRemaining' => $emailChangesRemaining
                ]);
            }

            $updated = $this->userModel->changeEmailWithAudit($userId, $newEmail);
            if (!$updated) {
                $this->jsonResponse(500, [
                    'success' => false,
                    'error' => 'Failed to update email'
                ]);
            }
            setAuthUserEmail((string)$newEmail);

            $emailChangesUsed = $this->userModel->getEmailChangeCount($userId);
            $emailChangesRemaining = max(0, $maxEmailChanges - $emailChangesUsed);

            $this->jsonResponse(200, [
                'success' => true,
                'message' => 'Email updated successfully.',
                'email' => $newEmail,
                'sessionUpdated' => true,
                'emailChangesUsed' => $emailChangesUsed,
                'emailChangesRemaining' => $emailChangesRemaining
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(500, [
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    public function getRefundAccount()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();

        $userId = (int)authUserId();
        $account = $this->payoutAccountsModel->getDefaultAccountByUserId($userId);

        $this->jsonResponse(200, [
            'success' => true,
            'account' => $account,
        ]);
    }

    public function saveRefundAccount()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();
        $this->requirePostRequest();

        $userId = (int)authUserId();
        $payload = [
            'account_holder_name' => trim((string)($_POST['account_holder_name'] ?? '')),
            'bank_name' => trim((string)($_POST['bank_name'] ?? '')),
            'branch_name' => trim((string)($_POST['branch_name'] ?? '')),
            'account_number' => trim((string)($_POST['account_number'] ?? '')),
            'is_default' => 1,
        ];

        $result = $this->payoutAccountsModel->saveDefaultAccount($userId, $payload);
        if (empty($result['success'])) {
            if (!empty($result['errors'])) {
                $this->jsonResponse(422, [
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $result['errors'],
                ]);
            }

            $this->jsonResponse(500, [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to save refund account',
            ]);
        }

        $this->jsonResponse(200, [
            'success' => true,
            'message' => 'Refund account saved successfully',
            'account' => $result['account'] ?? null,
        ]);
    }

    /**
     * Deactivate buyer account.
     */
    public function requestDeactivation()
    {
        $this->prepareJsonResponse();
        $this->requireBuyerRole();
        $this->requirePostRequest();

        $userId = (int)authUserId();

        $blockingStatuses = ['pending_payment', 'processing', 'ready_for_pickup', 'shipped'];
        $activeOrderCount = $this->orderModel->countBuyerOrdersByStatuses($userId, $blockingStatuses);
        if ($activeOrderCount > 0) {
            $this->jsonResponse(409, [
                'success' => false,
                'error' => 'Cannot deactivate account while active orders exist.',
                'blockedType' => 'orders',
                'blockedCount' => $activeOrderCount,
                'activeOrderCount' => $activeOrderCount,
            ]);
        }

        $deactivated = $this->userModel->deactivateAccount($userId, '');
        if (!$deactivated) {
            $this->jsonResponse(500, [
                'success' => false,
                'error' => 'Failed to deactivate account',
            ]);
        }

        clearAuthSession();

        $this->jsonResponse(200, [
            'success' => true,
            'message' => 'Account deactivated successfully.',
            'redirect' => ROOT . '/login?deactivated=1',
        ]);
    }
}
