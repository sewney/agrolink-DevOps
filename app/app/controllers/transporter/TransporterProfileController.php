<?php

class TransporterProfileController
{
    use Controller;

    protected $transporterModel;
    protected $userModel;
    protected $payoutAccountsModel;

    public function __construct()
    {
        $this->transporterModel = new TransporterModel();
        $this->userModel = new UserModel();
        $this->payoutAccountsModel = new PayoutAccountsModel();
    }

    /**
     * Display transporter profile page
     */
    public function index()
    {
        // Check if this is an AJAX request
        if (!empty($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
            return $this->getProfileJson();
        }

        // Regular page view
        if (!hasRole('transporter')) {
            return redirect('login');
        }

        $userId = authUserId();

        // Get transporter profile
        $profile = $this->transporterModel->getProfileByUserId($userId);

        // If no profile exists, create empty one
        if (!$profile) {
            $this->transporterModel->createProfile($userId, []);
            $profile = $this->transporterModel->getProfileByUserId($userId);
        }

        // Build photo URL
        $photoUrl = null;
        if (!empty($profile->profile_photo)) {
            $photoUrl = $this->buildPhotoUrl($profile->profile_photo);
        }

        // Load and display the profile view through transporterMain layout
        $maxEmailChanges = 2;
        $emailChangesUsed = $this->userModel->getEmailChangeCount($userId);

        $data = [
            'pageTitle' => 'Profile',
            'activePage' => 'profile',
            'username' => authUserName(),
            'profile' => $profile,
            'photoUrl' => $photoUrl,
            'emailChangesUsed' => $emailChangesUsed,
            'emailChangesRemaining' => max(0, $maxEmailChanges - $emailChangesUsed),
            'contentView' => '../app/views/transporter/transporterProfileContent.view.php',
            'pageScript' => 'profile.js'
        ];

        $this->view('transporter/transporterSidebar', $data);
    }

    /**
     * Helper: Build public URL for a stored profile photo filename
     */
    private function buildPhotoUrl(?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }
        return rtrim(ROOT, '/') . '/assets/images/transporter-profiles/' . rawurlencode($filename);
    }

    /**
     * Absolute path to transporter profile photo directory under public assets.
     */
    private function getPhotoUploadDirectory(): string
    {
        $publicPath = realpath(__DIR__ . '/../../../public');
        if ($publicPath === false) {
            throw new RuntimeException('Public directory not found');
        }

        return $publicPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'transporter-profiles';
    }

    /**
     * Get profile data as JSON (for AJAX requests)
     */
    private function getProfileJson()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized'
            ]);
            exit;
        }

        $userId = authUserId();
        $profile = $this->transporterModel->getProfileByUserId($userId);

        if (!$profile) {
            // Create default profile if doesn't exist
            $this->transporterModel->createProfile($userId, []);
            $profile = $this->transporterModel->getProfileByUserId($userId);
        }

        // Add computed photo URL for the frontend
        $photoUrl = null;
        if (!empty($profile->profile_photo)) {
            $photoUrl = $this->buildPhotoUrl($profile->profile_photo);
        }

        $maxEmailChanges = 2;
        $emailChangesUsed = $this->userModel->getEmailChangeCount($userId);

        echo json_encode([
            'success' => true,
            'profile' => $profile,
            'photoUrl' => $photoUrl,
            'emailChangesUsed' => $emailChangesUsed,
            'emailChangesRemaining' => max(0, $maxEmailChanges - $emailChangesUsed),
        ]);
        exit;
    }

    /**
     * Save/Update transporter profile via AJAX
     */
    public function saveProfile()
    {
        if (ob_get_level()) ob_clean();
        ini_set('display_errors', '0');
        ini_set('html_errors', '0');
        header('Content-Type: application/json');

        // Check authentication
        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = authUserId();

            // Get POST data
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'district' => trim($_POST['district'] ?? ''),
                'full_address' => trim($_POST['full_address'] ?? ''),
                'company_name' => trim($_POST['company_name'] ?? ''),
                'availability' => trim($_POST['availability'] ?? '')
            ];

            // Validate profile data at model level
            $validation = $this->transporterModel->validateProfile($data);

            if ($validation !== true) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validation
                ]);
                exit;
            }

            // Name is editable from profile form; email changes should follow account settings/audit flow.
            if (!empty($data['name'])) {
                $this->userModel->update($userId, ['name' => $data['name']]);
                setAuthUserName((string)$data['name']);
            }

            // Prepare profile data (exclude name and email from transporter profile update)
            $profileData = [
                'phone' => $data['phone'],
                'district' => $data['district'],
                'full_address' => $data['full_address'],
                'company_name' => $data['company_name'],
                'availability' => $data['availability']
            ];

            // DEBUG: Log profile data
            error_log("=== TRANSPORTER PROFILE SAVE DEBUG ===");
            error_log("User ID: " . $userId);
            error_log("Profile Data: " . json_encode($profileData));

            // Check if profile exists
            $existingProfile = $this->transporterModel->getProfileByUserId($userId);
            error_log("Existing Profile: " . ($existingProfile ? "Found (ID: {$existingProfile->id})" : "Not Found"));

            if (!$existingProfile) {
                // Create profile
                $result = $this->transporterModel->createProfile($userId, $profileData);
                error_log("Create Profile Result: " . ($result ? "SUCCESS" : "FAILED"));
            } else {
                // Update profile
                $result = $this->transporterModel->updateProfile($userId, $profileData);
                error_log("Update Profile Result: " . ($result ? "SUCCESS" : "FAILED"));
            }

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update profile'
                ]);
            }
        } catch (Throwable $e) {
            error_log('Transporter profile save error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error while saving profile'
            ]);
        }

        exit;
    }

    /**
     * Upload profile photo
     */
    public function uploadPhoto()
    {
        if (ob_get_level()) ob_clean();
        ini_set('display_errors', '0');
        ini_set('html_errors', '0');
        header('Content-Type: application/json');

        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = authUserId();

            // Check if file was uploaded
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'No photo uploaded']);
                exit;
            }

            $file = $_FILES['photo'];

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);

            if (!in_array($fileType, $allowedTypes, true)) {
                echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and WebP are allowed.']);
                exit;
            }

            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5MB.']);
                exit;
            }

            // Create upload directory if it doesn't exist
            $uploadDir = $this->getPhotoUploadDirectory();
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                    echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
                    exit;
                }
            }
            if (!is_writable($uploadDir)) {
                @chmod($uploadDir, 0777);
            }
            if (!is_writable($uploadDir)) {
                echo json_encode(['success' => false, 'error' => 'Upload directory is not writable']);
                exit;
            }

            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'transporter_' . $userId . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

            // Delete old photo if exists
            $oldPhoto = $this->transporterModel->getOldPhotoFilename($userId);
            if ($oldPhoto) {
                $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $oldPhoto;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Move uploaded file with debug logging
            error_log("Attempting move_uploaded_file from {$file['tmp_name']} to {$uploadPath}");
            error_log("Upload dir exists: " . (is_dir($uploadDir) ? "yes" : "no"));
            error_log("Upload dir writable: " . (is_writable($uploadDir) ? "yes" : "no"));
            error_log("Temp file exists: " . (file_exists($file['tmp_name']) ? "yes" : "no"));

            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $error = "Failed to move uploaded file from {$file['tmp_name']} to {$uploadPath}";
                error_log("MOVE_UPLOAD_FILE ERROR: " . $error);
                echo json_encode(['success' => false, 'error' => 'Failed to save photo']);
                exit;
            }

            error_log("File moved successfully to {$uploadPath}");

            // Update database
            $this->transporterModel->updateProfilePhoto($userId, $filename);

            // Return new photo URL
            $photoUrl = $this->buildPhotoUrl($filename);

            echo json_encode([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'photoUrl' => $photoUrl
            ]);
        } catch (Throwable $e) {
            error_log('Transporter photo upload error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error while uploading photo'
            ]);
        }
        exit;
    }

    /**
     * Remove profile photo
     */
    public function removePhoto()
    {
        if (ob_get_level()) ob_clean();
        ini_set('display_errors', '0');
        ini_set('html_errors', '0');
        header('Content-Type: application/json');

        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = authUserId();

            // Get old photo filename
            $oldPhoto = $this->transporterModel->getOldPhotoFilename($userId);

            if ($oldPhoto) {
                $uploadDir = $this->getPhotoUploadDirectory();
                $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $oldPhoto;

                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Update database
            $this->transporterModel->removeProfilePhoto($userId);

            echo json_encode([
                'success' => true,
                'message' => 'Photo removed successfully'
            ]);
        } catch (Throwable $e) {
            error_log('Transporter photo remove error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error while removing photo'
            ]);
        }
        exit;
    }

    /**
     * Change email via AJAX (requires password confirmation)
     */
    public function changeEmail()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $userId = (int)authUserId();
            $newEmail = strtolower(trim((string)($_POST['newEmail'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            $maxEmailChanges = 2;
            $errors = [];

            $currentUser = $this->userModel->first(['id' => $userId]);
            if (!$currentUser) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit;
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
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $errors,
                    'emailChangesUsed' => $emailChangesUsed,
                    'emailChangesRemaining' => $emailChangesRemaining,
                ]);
                exit;
            }

            $updated = $this->userModel->changeEmailWithAudit($userId, $newEmail);
            if (!$updated) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update email'
                ]);
                exit;
            }

            setAuthUserEmail((string)$newEmail);

            $emailChangesUsed = $this->userModel->getEmailChangeCount($userId);
            $emailChangesRemaining = max(0, $maxEmailChanges - $emailChangesUsed);

            echo json_encode([
                'success' => true,
                'message' => 'Email updated successfully. Session data has been updated. Use your new email from now on.',
                'email' => $newEmail,
                'sessionUpdated' => true,
                'emailChangesUsed' => $emailChangesUsed,
                'emailChangesRemaining' => $emailChangesRemaining,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Change password
     */
    public function changePassword()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $userId = authUserId();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate passwords
        $validation = $this->transporterModel->validatePasswordChange($userId, $currentPassword, $newPassword, $confirmPassword);

        if ($validation !== true) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validation
            ]);
            exit;
        }

        $updated = $this->userModel->updatePassword($userId, $newPassword);
        if (!$updated) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update password'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
        exit;
    }

    public function getPayoutAccount()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $userId = (int)authUserId();
        $account = $this->payoutAccountsModel->getDefaultAccountByUserId($userId);

        echo json_encode([
            'success' => true,
            'account' => $account,
        ]);
        exit;
    }

    public function savePayoutAccount()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $userId = (int)authUserId();
        $payload = [
            'account_holder_name' => trim((string)($_POST['account_holder_name'] ?? $_POST['account_holder'] ?? '')),
            'bank_name' => trim((string)($_POST['bank_name'] ?? '')),
            'branch_name' => trim((string)($_POST['branch_name'] ?? '')),
            'account_number' => trim((string)($_POST['account_number'] ?? '')),
            'is_default' => 1,
        ];

        $result = $this->payoutAccountsModel->saveDefaultAccount($userId, $payload);
        if (empty($result['success'])) {
            if (!empty($result['errors'])) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $result['errors'],
                ]);
                exit;
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to save payout account',
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Payout account saved successfully',
            'account' => $result['account'] ?? null,
        ]);
        exit;
    }

    public function requestDeactivation()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('transporter')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $userId = (int)authUserId();

        $incompleteDeliveryCount = $this->transporterModel->countIncompleteDeliveries($userId);
        if ($incompleteDeliveryCount > 0) {
            $deliveryLabel = $incompleteDeliveryCount === 1 ? 'delivery is' : 'deliveries are';
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'Cannot deactivate account while ' . $incompleteDeliveryCount . ' ' . $deliveryLabel . ' still incomplete.',
                'blockedType' => 'deliveries',
                'blockedCount' => $incompleteDeliveryCount,
                'incompleteDeliveryCount' => $incompleteDeliveryCount,
            ]);
            exit;
        }

        $deactivated = $this->userModel->deactivateAccount($userId, '');
        if (!$deactivated) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to deactivate account',
            ]);
            exit;
        }

        clearAuthSession();

        echo json_encode([
            'success' => true,
            'message' => 'Account deactivated successfully.',
            'redirect' => ROOT . '/login?deactivated=1',
        ]);
        exit;
    }
}
