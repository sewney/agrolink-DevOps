<?php
class AdminDashboardController
{
    use Controller;
    use Database;

    private function requireAdminJson(): bool
    {
        return requireRole(['admin', 'superadmin'], [
            'json' => true,
            'message' => 'Admin access required',
        ]);
    }

    public function index()
    {

        $data = [];
        $user = new UserModel;
        $verificationDoc = new VerificationDocumentModel;
        requireRole(['admin', 'superadmin']);

        $data['role'] = authUserRole();
        $data['username'] = authUserName();

        $data['users'] = $user->findAll() ?: [];

        $farmers = $user->where(['role' => 'farmer'], []);
        $buyers = $user->where(['role' => 'buyer'], []);
        $transporters = $user->where(['role' => 'transporter'], []);
        $admins = $user->where(['role' => 'admin'], []);

        $data['farmers'] = is_array($farmers) ? count($farmers) : 0;
        $data['buyers'] = is_array($buyers) ? count($buyers) : 0;
        $data['transporters'] = is_array($transporters) ? count($transporters) : 0;
        $data['admins'] = is_array($admins) ? count($admins) : 0;
        $data['orders'] = $this->getActiveOrdersCount();

        $data['verifications'] = $verificationDoc->getAllVerificationsWithDocuments();

        // Dashboard widgets: latest orders and newest registrations
        $data['recent_orders'] = $this->query("
                SELECT
                    o.id AS order_id,
                    o.order_total,
                    o.status,
                    o.created_at,
                    b.name AS buyer_name,
                    COUNT(DISTINCT f.id) AS farmer_count,
                    GROUP_CONCAT(DISTINCT f.name ORDER BY f.name SEPARATOR ', ') AS farmer_names
                FROM orders o
                INNER JOIN users b ON o.buyer_id = b.id
                LEFT JOIN order_items oi ON oi.order_id = o.id
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN users f ON p.farmer_id = f.id
                GROUP BY o.id, o.order_total, o.status, o.created_at, b.name
                ORDER BY o.created_at DESC
                LIMIT 5
            ") ?: [];

        $data['recent_registrations'] = $this->query("
                SELECT id, name, email, role, verification_status, created_at
                FROM users
                ORDER BY created_at DESC
                LIMIT 5
            ") ?: [];



        /* echo $data['users']; */

        // Prepare data for the view
        /* $data = [
            'title' => 'Admin Dashboard',
            'user' => $_SESSION['USER'],
            'welcome_message' => 'Welcome to Admin Dashboard'
            ]; */

        // Load the view
        $this->view('adminDashboard', $data);
    }



    public function deleteUser()
    {
        header('Content-Type: application/json');
        if (!$this->requireAdminJson()) {
            exit;
        }

        $userModel = new UserModel();
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $_POST['user_id'] ?? ($input['user_id'] ?? null);
        $reason = trim((string) ($_POST['reason'] ?? ($input['reason'] ?? '')));

        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }

        $userToDelete = $userModel->first(['id' => $userId]);
        if (!$userToDelete) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $targetRole = strtolower(trim((string) ($userToDelete->role ?? '')));
        $actorRole = strtolower(trim((string) ($_SESSION['USER']->role ?? $_SESSION['role'] ?? '')));
        $actorId = (int) ($_SESSION['USER']->id ?? $_SESSION['user_id'] ?? 0);

        if ($actorId > 0 && (int) ($userToDelete->id ?? 0) === $actorId) {
            echo json_encode([
                'success' => false,
                'message' => 'You cannot deactivate your own account from the dashboard.'
            ]);
            exit;
        }

        if ($targetRole === 'superadmin') {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot deactivate superadmin users. Superadmin accounts are protected.'
            ]);
            exit;
        }

        if ($targetRole === 'admin' && $actorRole !== 'superadmin') {
            echo json_encode([
                'success' => false,
                'message' => 'Only superadmin users can deactivate admin accounts.'
            ]);
            exit;
        }

        $db = $this->connect();
        
        if ($targetRole === 'buyer') {
            $stmt = $db->prepare("SELECT 1 FROM orders WHERE buyer_id = ? AND status IN ('pending', 'processing', 'shipped') LIMIT 1");
            $stmt->execute([$userId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Cannot deactivate account. User has pending orders to be completed.']);
                exit;
            }
        } elseif ($targetRole === 'farmer') {
            $stmt = $db->prepare("SELECT 1 FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.farmer_id = ? AND o.status IN ('pending', 'processing', 'shipped') LIMIT 1");
            $stmt->execute([$userId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Cannot deactivate account. User has pending orders to be completed.']);
                exit;
            }
        } elseif ($targetRole === 'transporter') {
            $stmt = $db->prepare("SELECT 1 FROM delivery_requests WHERE transporter_id = ? AND status IN ('pending', 'accepted', 'picked_up', 'in_transit') LIMIT 1");
            $stmt->execute([$userId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Cannot deactivate account. User has pending deliveries to be completed.']);
                exit;
            }
        }

        if ($reason === '') {
            $reason = 'Deactivated by admin';
        }

        $reason = substr($reason, 0, 500);

        if ($userModel->deactivateAccount((int) $userId, $reason)) {
            echo json_encode(['success' => true, 'message' => 'User deactivated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to deactivate user']);
        }
        exit;
    }

    public function getUsersTable()
    {
        $userModel = new UserModel();
        $users = $userModel->findAll();

        // Render just the table rows
        foreach ($users as $user) {
            echo "<tr id='user-{$user['id']}'>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td><button class='delete-btn' data-userid='{$user['id']}'>Delete</button></td>";
            echo "</tr>";
        }
        exit;
    }

    public function addUser()
    {
        // Always set JSON header first
        header('Content-Type: application/json');

        $user = new UserModel;

        if (!$this->requireAdminJson()) {
            exit;
        }

        // Check if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $requestedRole = strtolower(trim((string) ($_POST['role'] ?? '')));
                if ($requestedRole === 'superadmin') {
                    echo json_encode(['success' => false, 'message' => 'Creating superadmin accounts is not allowed.']);
                    exit;
                }

                if ($requestedRole === 'admin' && !requireRole('superadmin', ['json' => true, 'message' => 'Superadmin access required'])) {
                    exit;
                }

                if ($user->validate($_POST)) {
                    // Insert user and get the result
                    $insertResult = $user->insert($_POST);

                    if ($insertResult) {
                        // If insert returns the user ID
                        $userId = is_numeric($insertResult) ? $insertResult : null;

                        echo json_encode([
                            'success' => true,
                            'message' => 'User created successfully',
                            'userId' => $userId
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Failed to create user in database'
                        ]);
                    }
                } else {
                    // Validation failed - return validation errors
                    echo json_encode([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $user->errors // Make sure this contains the validation errors
                    ]);
                }
            } catch (Exception $e) {
                // Handle any exceptions
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create user in database',
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Server error: ' . $e->getMessage(),
                ]);
            }
        }
        exit;
    }

    public function updateUserCount()
    {
        // Ensure clean output - no whitespace before this
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');

        try {
            // Check if user is logged in and is admin
            if (!isset($_SESSION['USER'])) {
                echo json_encode([
                    'success' => false,
                    'userCount' => 0,
                    'message' => 'Not authenticated'
                ]);
                exit;
            }

            $user = new UserModel();
            $users = $user->findAll();

            if ($users === false) {
                $users = [];
            }

            $userCount = is_array($users) ? count($users) : 0;

            echo json_encode([
                'success' => true,
                'userCount' => $userCount,
                'message' => 'User count retrieved successfully'
            ]);
        } catch (Exception $e) {
            error_log("Error in updateUserCount: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'userCount' => 0,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    public function register()
    {
        header('Content-Type: application/json');
        if (!$this->requireAdminJson()) {
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method',
            ]);
            exit;
        }

        $errors = [];

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $requestedRole = strtolower(trim((string) ($_POST['role'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '') {
            $errors['name'] = 'Full name is required';
        }

        $allowedRoles = ['buyer', 'farmer', 'transporter', 'admin'];
        if (!in_array($requestedRole, $allowedRoles, true)) {
            $errors['role'] = 'Invalid role selected';
        }

        if ($requestedRole === 'admin' && !requireRole('superadmin', ['json' => true, 'message' => 'Superadmin access required'])) {
            exit;
        }

        $userModel = new UserModel();
        if (!$userModel->validate(['email' => $email, 'password' => $password])) {
            $errors = array_merge($errors, $userModel->errors ?? []);
        }

        // Verify required documents for farmer/transporter (same as registration)
        $requiredDocs = [];
        if ($requestedRole === 'farmer') {
            $requiredDocs = ['nic', 'bank_details'];
        } elseif ($requestedRole === 'transporter') {
            $requiredDocs = ['driving_license', 'vehicle_insurance', 'vehicle_revenue_license'];
        }

        foreach ($requiredDocs as $docKey) {
            if (empty($_FILES[$docKey]) || !isset($_FILES[$docKey]['error']) || $_FILES[$docKey]['error'] === UPLOAD_ERR_NO_FILE) {
                $errors[$docKey] = 'This document is required.';
            }
        }

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ]);
            exit;
        }

        $db = $this->connect();
        $savedFiles = [];

        try {
            $db->beginTransaction();

            $userId = $userModel->insert([
                'name' => $name,
                'email' => $email,
                'role' => $requestedRole,
                'password' => $password,
            ]);

            if (!$userId || !is_numeric($userId) || (int) $userId <= 0) {
                throw new Exception('Registration failed');
            }
            $userId = (int) $userId;

            if ($requestedRole === 'buyer') {
                (new BuyerModel())->createProfile($userId, []);
                $userModel->setVerificationStatus($userId, 'not_required');
            } elseif ($requestedRole === 'farmer') {
                (new FarmerModel())->createProfile($userId, []);
                $this->saveVerificationDocsForUser($userId, [
                    'nic' => $_FILES['nic'] ?? null,
                    'bank_details' => $_FILES['bank_details'] ?? null,
                ], $savedFiles);
                $userModel->setVerificationStatus($userId, 'pending');
            } elseif ($requestedRole === 'transporter') {
                (new TransporterModel())->createProfile($userId, []);
                $this->saveVerificationDocsForUser($userId, [
                    'driving_license' => $_FILES['driving_license'] ?? null,
                    'vehicle_insurance' => $_FILES['vehicle_insurance'] ?? null,
                    'vehicle_revenue_license' => $_FILES['vehicle_revenue_license'] ?? null,
                ], $savedFiles);
                $userModel->setVerificationStatus($userId, 'pending');
            } else {
                // admin
                $userModel->setVerificationStatus($userId, 'not_required');
            }

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Registration successful',
                'userId' => $userId,
            ]);
            exit;
        } catch (Exception $e) {
            try {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            } catch (Exception $ignored) {
            }

            // Clean up moved files if any
            foreach ($savedFiles as $path) {
                if (is_string($path) && $path !== '' && file_exists($path)) {
                    @unlink($path);
                }
            }

            error_log('User registration error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
            exit;
        }
    }

    private function verificationUploadDir(): string
    {
        $publicPath = realpath(__DIR__ . '/../../../public');
        if ($publicPath === false) {
            $publicPath = dirname(__DIR__, 3) . '/public';
        }

        return rtrim($publicPath, '/\\')
            . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'verification'
            . DIRECTORY_SEPARATOR;
    }

    /**
     * @param array<string, mixed> $files
     * @param array<int, string> $savedFiles collects absolute file paths moved to disk
     */
    private function saveVerificationDocsForUser(int $userId, array $files, array &$savedFiles): void
    {
        $uploadDir = $this->verificationUploadDir();
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new Exception('Upload directory could not be created');
            }
        }

        if (!is_writable($uploadDir)) {
            @chmod($uploadDir, 0775);
        }

        if (!is_writable($uploadDir)) {
            throw new Exception('Upload directory is not writable');
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $allowedMimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $docModel = new VerificationDocumentModel();

        foreach ($files as $docType => $file) {
            if (empty($file) || !is_array($file) || !isset($file['error'])) {
                throw new Exception("Missing file for {$docType}");
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload failed for {$docType}");
            }

            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                throw new Exception("Invalid uploaded file for {$docType}");
            }

            $clientExt = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            $detectedMime = '';

            if (function_exists('finfo_open')) {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $detected = @finfo_file($finfo, $file['tmp_name']);
                    if (is_string($detected)) {
                        $detectedMime = $detected;
                    }
                    @finfo_close($finfo);
                }
            }

            $resolvedExt = $allowedMimeToExt[$detectedMime] ?? $clientExt;
            if (!in_array($resolvedExt, $allowedExt, true)) {
                throw new Exception("Unsupported file type for {$docType}");
            }

            $filename = "doc_{$userId}_{$docType}_" . uniqid('', true) . ".{$resolvedExt}";
            $dest = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                throw new Exception("Failed to save {$docType}");
            }

            $savedFiles[] = $dest;

            $filePath = 'assets/uploads/verification/' . $filename;
            $insertId = $docModel->insert([
                'user_id' => $userId,
                'doc_type' => (string) $docType,
                'file_path' => $filePath,
                'status' => 'pending',
            ]);

            if ($insertId <= 0) {
                @unlink($dest);
                throw new Exception("Failed to record {$docType} in database");
            }
        }
    }

    /* public function getUser($id)
    {
        header('Content-Type: application/json');
        if (!$this->requireAdminJson()) {
            exit;
        }

        try {
            $userModel = new UserModel();
            $user = $userModel->first(['id' => $id]);

            $result = $userModel->delete($userId);
            if($result){
                echo json_encode([
                    'success'=>true,
                    'message'=>'User delete success'
                ]);
            }else{
                echo json_encode([
                    'success'=>false,
                    'message'=>'User delete failed'
                ]);
            }
        } catch (Exception $e) {
            error_log('Error deleting user: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while deleting the user'
            ]);    
        }
        exit;
    }  */

    public function getUser($id)
    {
        header('Content-Type: application/json');

        try {
            $userModel = new UserModel;
            $user = $userModel->first(['id' => $id]);

            if ($user) {
                echo json_encode([
                    'success' => true,
                    'data' => $user
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found',
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load user details here',
            ]);
        }
        exit;
    }

    public function updateUser()
    {
        header('Content-Type: application/json');
        if (!$this->requireAdminJson()) {
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request',
            ]);
            exit;
        }

        try {
            $userId = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $newRole = strtolower(trim((string) ($_POST['role'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');

            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                exit;
            }

            if (!isset($_SESSION['USER'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized. Please login again.'
                ]);
                exit;
            }

            $userModel = new UserModel();
            $existing = $userModel->first(['id' => $userId]);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            $actorRole = strtolower(trim((string) ($_SESSION['USER']->role ?? $_SESSION['role'] ?? '')));
            $targetRole = strtolower(trim((string) ($existing->role ?? '')));

            if ($targetRole === 'superadmin') {
                echo json_encode(['success' => false, 'message' => 'Superadmin accounts are protected and cannot be edited.']);
                exit;
            }

            if ($targetRole === 'admin' && $actorRole !== 'superadmin') {
                echo json_encode(['success' => false, 'message' => 'Only superadmin users can edit admin accounts.']);
                exit;
            }

            if ($newRole === 'superadmin') {
                echo json_encode(['success' => false, 'message' => 'Changing a user role to superadmin is not allowed.']);
                exit;
            }

            if ($newRole === 'admin' && $actorRole !== 'superadmin') {
                echo json_encode(['success' => false, 'message' => 'Superadmin access required to assign admin role.']);
                exit;
            }

            $errors = [];

            if ($name === '') {
                $errors['name'] = 'Name is required';
            }

            if ($email === '') {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email is incorrect';
            } else {
                $emailOwner = $userModel->findByEmail($email);
                if ($emailOwner && (int) ($emailOwner->id ?? 0) !== $userId) {
                    $errors['email'] = 'This email is already registered. Please use a different email.';
                }
            }

            $allowedRoles = ['farmer', 'buyer', 'transporter', 'admin'];
            if ($newRole === '' || !in_array($newRole, $allowedRoles, true)) {
                $errors['role'] = 'Invalid role selected';
            }

            if ($password !== '' && strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters long';
            }

            if (!empty($errors)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors,
                ]);
                exit;
            }

            $updateData = [
                'name' => $name,
                'email' => $email,
                'role' => $newRole,
            ];

            if ($password !== '') {
                $updateData['password'] = $password;
            }

            $result = $userModel->update($userId, $updateData);
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully',
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update user',
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while updating the user: ' . $e->getMessage(),
            ]);
        }
        exit;
    }

    public function activateUser()
    {
        header('Content-Type: application/json');
        if (!$this->requireAdminJson()) {
            exit;
        }

        $userModel = new UserModel();
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $_POST['user_id'] ?? ($input['user_id'] ?? null);

        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }

        $userToActivate = $userModel->first(['id' => $userId]);
        if (!$userToActivate) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $targetRole = strtolower(trim((string) ($userToActivate->role ?? '')));
        $actorRole = strtolower(trim((string) ($_SESSION['USER']->role ?? $_SESSION['role'] ?? '')));

        if ($targetRole === 'superadmin') {
            echo json_encode([
                'success' => false,
                'message' => 'Superadmin accounts are protected.'
            ]);
            exit;
        }

        if ($targetRole === 'admin' && $actorRole !== 'superadmin') {
            echo json_encode([
                'success' => false,
                'message' => 'Only superadmin users can activate admin accounts.'
            ]);
            exit;
        }

        if ($userModel->activateAccount((int) $userId)) {
            echo json_encode(['success' => true, 'message' => 'User activated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to activate user']);
        }
        exit;
    }

    public function getVerifications()
    {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            SELECT 
                u.id as user_id,
                u.name,
                u.email,
                u.role,
                u.verification_status,
                u.created_at as registered_at,
                COUNT(vd.id) as doc_count,
                SUM(CASE WHEN vd.status = 'approved' THEN 1 ELSE 0 END) as approved_docs,
                SUM(CASE WHEN vd.status = 'rejected' THEN 1 ELSE 0 END) as rejected_docs
            FROM users u
            LEFT JOIN verification_documents vd ON u.id = vd.user_id
            WHERE u.role IN ('farmer', 'transporter')
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");

        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 👉 OUTPUT HTML ROWS (same as getUsersTable)
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['verification_status']}</td>";
            echo "<td>{$user['doc_count']}</td>";
            echo "<td>{$user['approved_docs']}</td>";
            echo "<td>{$user['rejected_docs']}</td>";
            echo "<td>
                    <button onclick='viewDocs({$user['user_id']})'>View</button>
                </td>";
            echo "</tr>";
        }

        exit;
    }

    public function getUserDocuments($userId = null)
    {
        header('Content-Type: application/json');

        try {
            // Accept from URL segment (GET) OR JSON body (POST)
            if (!$userId) {
                $input = json_decode(file_get_contents('php://input'), true);
                $userId = $input['user_id'] ?? null;
            }

            // Also try from URL parameter if using router
            if (!$userId && isset($this->params['id'])) {
                $userId = $this->params['id'];
            }

            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }

            // Use the query method from the Database trait
            $query = "
                    SELECT vd.*, u.name, u.email, u.role, u.verification_status
                    FROM verification_documents vd
                    JOIN users u ON u.id = vd.user_id
                    WHERE vd.user_id = ?
                    ORDER BY vd.created_at DESC
                ";

            $docs = $this->query($query, [$userId]);

            if (!$docs) {
                $docs = [];
            }

            // Convert to array if needed (since query returns objects)
            $docsArray = [];
            foreach ($docs as $doc) {
                $docsArray[] = (array) $doc;
            }

            // Add document counts
            $totalDocs = count($docsArray);
            $approvedDocs = 0;
            foreach ($docsArray as $doc) {
                if ($doc['status'] === 'approved') {
                    $approvedDocs++;
                }
            }

            // Add user info to the first document
            if (!empty($docsArray)) {
                $docsArray[0]['doc_count'] = $totalDocs;
                $docsArray[0]['approved_docs'] = $approvedDocs;
            }

            echo json_encode(['success' => true, 'data' => $docsArray]);
        } catch (Exception $e) {
            error_log("Error in getUserDocuments: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function reviewDocument()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $docId = $input['doc_id'] ?? null;
            $action = $input['action'] ?? null;
            $reason = $input['reason'] ?? null;

            if (!$docId || !$action) {
                echo json_encode(['success' => false, 'message' => 'Document ID and action required']);
                exit;
            }

            $status = $action === 'approve' ? 'approved' : 'rejected';

            // Update the document status
            $updateQuery = "UPDATE verification_documents SET status = ?, rejection_reason = ? WHERE id = ?";
            $result = $this->write($updateQuery, [$status, $reason, $docId]);

            if ($result) {
                // Check if all documents for this user are approved
                $getUserQuery = "SELECT user_id FROM verification_documents WHERE id = ?";
                $doc = $this->query($getUserQuery, [$docId]);

                if ($doc && !empty($doc)) {
                    $userId = $doc[0]->user_id;
                    $db = $this->connect();
                    $this->syncUserVerificationStatus((int) $userId, $db);
                }

                echo json_encode(['success' => true, 'message' => 'Document ' . $action . 'd successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update document']);
            }
        } catch (Exception $e) {
            error_log("Error in reviewDocument: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function setUserVerification()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['user_id'] ?? null;
            $status = $input['status'] ?? null;

            if (!$userId || !$status) {
                echo json_encode(['success' => false, 'message' => 'User ID and status required']);
                exit;
            }

            $query = "UPDATE users SET verification_status = ? WHERE id = ?";
            $result = $this->write($query, [$status, $userId]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User verification status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
            }
        } catch (Exception $e) {
            error_log("Error in setUserVerification: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function syncUserVerificationStatus(int $userId, $db): void
    {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM verification_documents
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($counts['total'] == 0)
            return;

        if ($counts['rejected'] > 0) {
            $newStatus = 'rejected';
        } elseif ($counts['approved'] == $counts['total']) {
            $newStatus = 'approved';
        } else {
            $newStatus = 'pending';
        }

        $db->prepare("UPDATE users SET verification_status = ? WHERE id = ?")
            ->execute([$newStatus, $userId]);
    }

    // Add this method to your AdminDashboardController.php

    public function getOrders()
    {
        header('Content-Type: application/json');

        try {
            $db = $this->connect();

            // Get filter parameters
            $input = json_decode(file_get_contents('php://input'), true);
            $status = $input['status'] ?? '';
            $paymentStatus = $input['payment_status'] ?? '';
            $dateRange = $input['date_range'] ?? '';
            $search = $input['search'] ?? '';

            // Build the query
            $query = "
                SELECT 
                    o.id as order_id,
                    o.total_amount,
                    o.status as order_status,
                    o.created_at as order_date,
                    b.name as buyer_name,
                    b.id as buyer_id,
                    f.name as farmer_name,
                    f.id as farmer_id,
                    (
                        SELECT COUNT(*) 
                        FROM order_items oi 
                        WHERE oi.order_id = o.id
                    ) as item_count
                FROM orders o
                JOIN users b ON o.buyer_id = b.id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                JOIN users f ON p.farmer_id = f.id
                WHERE 1=1
            ";

            $params = [];

            // Apply status filter
            if (!empty($status)) {
                $query .= " AND o.status = :status";
                $params[':status'] = $status;
            }

            // Apply payment status filter
            if (!empty($paymentStatus)) {
                $query .= " AND o.payment_status = :payment_status";
                $params[':payment_status'] = $paymentStatus;
            }

            // Apply date range filter
            if (!empty($dateRange)) {
                switch ($dateRange) {
                    case 'today':
                        $query .= " AND DATE(o.created_at) = CURDATE()";
                        break;
                    case 'week':
                        $query .= " AND YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
                        break;
                    case 'month':
                        $query .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
                        break;
                    case 'quarter':
                        $query .= " AND QUARTER(o.created_at) = QUARTER(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
                        break;
                }
            }

            // Apply search filter
            if (!empty($search)) {
                $query .= " AND (CAST(o.id AS CHAR) LIKE :search OR b.name LIKE :search OR f.name LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            $query .= " GROUP BY o.id ORDER BY o.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get statistics (match the same filters/search used for the table)
            $statsWhere = "WHERE 1=1";
            $statsParams = [];

            if (!empty($status)) {
                $statsWhere .= " AND o.status = :status";
                $statsParams[':status'] = $status;
            }
            if (!empty($paymentStatus)) {
                $statsWhere .= " AND o.payment_status = :payment_status";
                $statsParams[':payment_status'] = $paymentStatus;
            }
            if (!empty($dateRange)) {
                switch ($dateRange) {
                    case 'today':
                        $statsWhere .= " AND DATE(o.created_at) = CURDATE()";
                        break;
                    case 'week':
                        $statsWhere .= " AND YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
                        break;
                    case 'month':
                        $statsWhere .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
                        break;
                    case 'quarter':
                        $statsWhere .= " AND QUARTER(o.created_at) = QUARTER(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
                        break;
                }
            }
            if (!empty($search)) {
                $statsWhere .= " AND (
                        CAST(o.id AS CHAR) LIKE :search
                        OR b.name LIKE :search
                        OR EXISTS (
                            SELECT 1
                            FROM order_items oi2
                            JOIN products p2 ON p2.id = oi2.product_id
                            JOIN users f2 ON f2.id = p2.farmer_id
                            WHERE oi2.order_id = o.id
                              AND f2.name LIKE :search
                        )
                    )";
                $statsParams[':search'] = "%{$search}%";
            }

            $statsQuery = "
                SELECT
                    COUNT(*) AS total_orders,
                    SUM(CASE WHEN o.status IN ('confirmed','processing','shipped') THEN 1 ELSE 0 END) AS processing,
                    SUM(CASE WHEN o.status IN ('delivered','completed') THEN 1 ELSE 0 END) AS completed
                FROM orders o
                JOIN users b ON o.buyer_id = b.id
                {$statsWhere}
            ";

            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute($statsParams);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                'success' => true,
                'data' => $orders,
                'stats' => [
                    'total_orders' => (int) ($stats['total_orders'] ?? 0),
                    'processing' => (int) ($stats['processing'] ?? 0),
                    'completed' => (int) ($stats['completed'] ?? 0),
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error in getOrders: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getOrderDetails($orderId = null)
    {
        header('Content-Type: application/json');

        try {
            if (!$orderId) {
                $input = json_decode(file_get_contents('php://input'), true);
                $orderId = $input['order_id'] ?? null;
            }

            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'Order ID required']);
                exit;
            }

            $db = $this->connect();

            $query = "
                SELECT 
                    o.*,
                    b.name as buyer_name,
                    b.email as buyer_email,
                    bp.phone as buyer_phone,
                    COALESCE(
                        NULLIF(TRIM(CONCAT_WS(', ', bp.apartment_code, bp.street_name, bp.city, bp.district, bp.postal_code, bp.additional_address_details)), ''),
                        o.delivery_address
                    ) as buyer_address
                FROM orders o
                JOIN users b ON o.buyer_id = b.id
                LEFT JOIN buyer_profiles bp ON bp.user_id = b.id
                WHERE o.id = ?
                LIMIT 1
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get order items
            $itemsQuery = "
                SELECT 
                    oi.product_id,
                    COALESCE(p.name, oi.product_name) as product_name,
                    p.image as product_image,
                    p.category as product_category,
                    oi.quantity,
                    oi.product_price as unit_price,
                    u.name as farmer_name
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                JOIN users u ON u.id = oi.farmer_id
                WHERE oi.order_id = ?
            ";

            $itemsStmt = $db->prepare($itemsQuery);
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function updateOrderStatus()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $orderId = $input['order_id'] ?? null;
            $status = $input['status'] ?? null;

            if (!$orderId || !$status) {
                echo json_encode(['success' => false, 'message' => 'Order ID and status required']);
                exit;
            }

            $db = $this->connect();

            $query = "UPDATE orders SET status = :status WHERE id = :order_id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([':status' => $status, ':order_id' => $orderId]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getActiveOrdersCount()
    {
        try {
            $result = $this->query("
                    SELECT COUNT(*) as count
                    FROM orders
                ");

            return !empty($result) ? (int) $result[0]->count : 0;
        } catch (Exception $e) {
            error_log("Error counting orders: " . $e->getMessage());
            return 0;
        }
    }

    // Add these methods to your AdminDashboardController class

    public function getProducts()
    {
        header('Content-Type: application/json');

        try {
            $db = $this->connect();

            // Get filter parameters
            $input = json_decode(file_get_contents('php://input'), true);
            $search = $input['search'] ?? '';
            $category = $input['category'] ?? '';
            $status = $input['status'] ?? '';
            $minPrice = $input['min_price'] ?? '';
            $maxPrice = $input['max_price'] ?? '';

            // Build the query
            $query = "
                SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.price,
                    p.quantity as stock,
                    p.category,
                    p.image,
                    p.created_at,
                    u.name as farmer_name,
                    u.id as farmer_id,
                    (
                        SELECT COUNT(*) 
                        FROM order_items oi 
                        WHERE oi.product_id = p.id
                    ) as total_orders
                FROM products p
                JOIN users u ON p.farmer_id = u.id
                WHERE 1=1
                AND p.deleted_at IS NULL
            ";

            $params = [];

            // Apply search filter
            if (!empty($search)) {
                $query .= " AND (p.name LIKE :search OR p.description LIKE :search OR u.name LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            // Apply category filter
            if (!empty($category)) {
                $query .= " AND p.category = :category";
                $params[':category'] = $category;
            }

            // Apply status filter
            /* if (!empty($status)) {
                $query .= " AND p.status = :status";
                $params[':status'] = $status;
            } */

            // Apply price range filter
            if (!empty($minPrice)) {
                $query .= " AND p.price >= :min_price";
                $params[':min_price'] = $minPrice;
            }

            if (!empty($maxPrice)) {
                $query .= " AND p.price <= :max_price";
                $params[':max_price'] = $maxPrice;
            }

            $query .= " ORDER BY p.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get statistics
            $statsQuery = "
                SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock
                FROM products
                WHERE deleted_at IS NULL
            ";

            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // Get category counts for filter options
            $categoryQuery = "
                SELECT 
                    category,
                    COUNT(*) as count
                FROM products
                WHERE category IS NOT NULL AND category != ''
                AND deleted_at IS NULL
                GROUP BY category
                ORDER BY count DESC
            ";

            $categoryStmt = $db->prepare($categoryQuery);
            $categoryStmt->execute();
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $products,
                'stats' => [
                    'total_products' => (int) ($stats['total_products'] ?? 0),
                    'active_products' => (int) ($stats['active_products'] ?? 0),
                    'out_of_stock' => (int) ($stats['out_of_stock'] ?? 0),
                ],
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            error_log("Error in getProducts: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getProductDetails($productId = null)
    {
        header('Content-Type: application/json');

        try {
            if (!$productId) {
                $input = json_decode(file_get_contents('php://input'), true);
                $productId = $input['product_id'] ?? null;
            }

            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                exit;
            }

            $db = $this->connect();

            // Get product details with farmer info
            $query = "
                SELECT 
                    p.*,
                    u.name as farmer_name,
                    u.email as farmer_email,
                    fp.phone as farmer_phone,
                    fp.full_address as farmer_address
                FROM products p
                JOIN users u ON p.farmer_id = u.id
                LEFT JOIN farmer_profiles fp ON fp.user_id = u.id
                WHERE p.id = ?
                AND p.deleted_at IS NULL
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }

            // Get order history for this product
            $orderQuery = "
                SELECT 
                    o.id as order_id,
                    CONCAT('ORD-', o.id) as order_number,
                    o.total_amount,
                    o.status as order_status,
                    o.created_at as order_date,
                    oi.quantity,
                    oi.product_price as unit_price,
                    u.name as buyer_name,
                    u.email as buyer_email
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN users u ON o.buyer_id = u.id
                WHERE oi.product_id = ?
                ORDER BY o.created_at DESC
                LIMIT 20
            ";

            $orderStmt = $db->prepare($orderQuery);
            $orderStmt->execute([$productId]);
            $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'product' => $product,
                'orders' => $orders
            ]);
        } catch (Exception $e) {
            error_log("Error in getProductDetails: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getVehicles()
    {
        header('Content-Type: application/json');
        if (!$this->requireAdminJson()) {
            exit;
        }

        try {
            $db = $this->connect();

            $input = json_decode(file_get_contents('php://input'), true);
            $search = trim((string)($input['search'] ?? ''));
            $status = strtolower(trim((string)($input['status'] ?? '')));

            $query = "
                SELECT
                    v.id,
                    v.transporter_id,
                    u.name AS transporter_name,
                    u.email AS transporter_email,
                    v.registration,
                    v.type,
                    v.vehicle_type_id,
                    vt.vehicle_name AS vehicle_type_name,
                    v.capacity,
                    v.fuel_type,
                    v.model,
                    v.status,
                    v.created_at
                FROM vehicles v
                JOIN users u ON u.id = v.transporter_id
                LEFT JOIN vehicle_types vt ON vt.id = v.vehicle_type_id
                WHERE 1=1
                AND v.deleted_at IS NULL
            ";

            $params = [];

            if ($search !== '') {
                $query .= " AND (v.registration LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            if ($status !== '') {
                $allowed = ['active', 'inactive'];
                if (in_array($status, $allowed, true)) {
                    $query .= " AND v.status = :status";
                    $params[':status'] = $status;
                }
            }

            $query .= " ORDER BY v.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $vehicles ?: [],
            ]);
        } catch (Exception $e) {
            error_log("Error in getVehicles: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getReviews()
    {
        header('Content-Type: application/json');
        if (!$this->requireAdminJson()) {
            exit;
        }

        try {
            $db = $this->connect();

            $input = json_decode(file_get_contents('php://input'), true);
            $search = trim((string)($input['search'] ?? ''));
            $targetRole = strtolower(trim((string)($input['target_role'] ?? '')));
            $ratingFilter = trim((string)($input['rating'] ?? ''));

            $where = "WHERE 1=1";
            $params = [];

            if ($search !== '') {
                $where .= " AND (
                        CAST(r.id AS CHAR) LIKE :search
                        OR CAST(r.order_id AS CHAR) LIKE :search
                        OR b.name LIKE :search
                        OR b.email LIKE :search
                        OR t.name LIKE :search
                        OR t.email LIKE :search
                        OR p.name LIKE :search
                        OR r.comment LIKE :search
                    )";
                $params[':search'] = "%{$search}%";
            }

            if (in_array($targetRole, ['farmer', 'transporter'], true)) {
                $where .= " AND t.role = :target_role";
                $params[':target_role'] = $targetRole;
            }

            if ($ratingFilter !== '') {
                if ($ratingFilter === 'complaint') {
                    $where .= " AND r.rating <= 2";
                } elseif (ctype_digit($ratingFilter)) {
                    $rating = (int)$ratingFilter;
                    if ($rating >= 1 && $rating <= 5) {
                        $where .= " AND r.rating = :rating";
                        $params[':rating'] = $rating;
                    }
                }
            }

            $query = "
                SELECT
                    r.*,
                    p.name AS product_name,
                    p.image AS product_image,
                    b.name AS buyer_name,
                    b.email AS buyer_email,
                    t.name AS target_name,
                    t.email AS target_email,
                    t.role AS target_role
                FROM reviews r
                LEFT JOIN products p ON p.id = r.product_id
                JOIN users b ON b.id = r.buyer_id
                JOIN users t ON t.id = r.farmer_id
                {$where}
                ORDER BY r.created_at DESC
            ";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $total = count($rows);
            $sum = 0;
            $complaints = 0;
            foreach ($rows as $r) {
                $rating = (int)($r['rating'] ?? 0);
                $sum += $rating;
                if ($rating > 0 && $rating <= 2) {
                    $complaints++;
                }
            }

            $avg = $total > 0 ? round($sum / $total, 2) : 0;

            echo json_encode([
                'success' => true,
                'data' => $rows,
                'stats' => [
                    'total_reviews' => $total,
                    'avg_rating' => $avg,
                    'complaints' => $complaints,
                ],
            ]);
        } catch (Exception $e) {
            error_log("Error in getReviews: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function updateProductStatus()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = $input['product_id'] ?? null;
            $status = $input['status'] ?? null;

            if (!$productId || !$status) {
                echo json_encode(['success' => false, 'message' => 'Product ID and status required']);
                exit;
            }

            $validStatuses = ['active', 'inactive', 'pending', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status. Must be: ' . implode(', ', $validStatuses)]);
                exit;
            }

            $db = $this->connect();

            $query = "UPDATE products SET status = :status WHERE id = :product_id AND deleted_at IS NULL";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([':status' => $status, ':product_id' => $productId]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Product status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update product status']);
            }
        } catch (Exception $e) {
            error_log("Error in updateProductStatus: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deleteProduct()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = $input['product_id'] ?? null;

            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                exit;
            }

            $db = $this->connect();

            // Block deletion only when there are ongoing orders using this product.
            $checkQuery = "SELECT COUNT(*) as order_count
                           FROM order_items oi
                           INNER JOIN orders o ON o.id = oi.order_id
                           WHERE oi.product_id = ?
                           AND o.status NOT IN ('delivered', 'cancelled')";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$productId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($result['order_count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot delete product with ongoing orders. ' . $result['order_count'] . ' active orders found.'
                ]);
                exit;
            }

            // Soft-delete the product
            $query = "UPDATE products SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL";
            $stmt = $db->prepare($query);
            $success = $stmt->execute([$productId]);

            if ($success && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found or already deleted']);
            }
        } catch (Exception $e) {
            error_log("Error in deleteProduct: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Add these methods to your AdminDashboardController class

    public function getAnalytics()
    {
        header('Content-Type: application/json');

        try {
            $db = $this->connect();

            // Get date range from request
            $input = json_decode(file_get_contents('php://input'), true);
            $period = $input['period'] ?? 'month';

            // User statistics
            $userStatsQuery = "
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'farmer' THEN 1 ELSE 0 END) as total_farmers,
                    SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as total_buyers,
                    SUM(CASE WHEN role = 'transporter' THEN 1 ELSE 0 END) as total_transporters,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                    SUM(CASE WHEN WEEK(created_at) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as new_this_week,
                    SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as new_this_month
                FROM users
            ";

            $userStatsStmt = $db->prepare($userStatsQuery);
            $userStatsStmt->execute();
            $userStats = $userStatsStmt->fetch(PDO::FETCH_ASSOC);

            // Order statistics
            $orderStatsQuery = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value
                FROM orders
                WHERE status != 'cancelled'
            ";

            $orderStatsStmt = $db->prepare($orderStatsQuery);
            $orderStatsStmt->execute();
            $orderStats = $orderStatsStmt->fetch(PDO::FETCH_ASSOC);

            // Product statistics
            $productStatsQuery = "
                SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as active_products,
                    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    0 as pending_approval,
                    AVG(price) as avg_price,
                    SUM(price * quantity) as inventory_value
                FROM products
                WHERE deleted_at IS NULL
            ";

            $productStatsStmt = $db->prepare($productStatsQuery);
            $productStatsStmt->execute();
            $productStats = $productStatsStmt->fetch(PDO::FETCH_ASSOC);

            // Monthly revenue for chart
            $monthlyRevenueQuery = "
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as order_count,
                    SUM(total_amount) as revenue
                FROM orders
                WHERE (status = 'completed' OR status = 'delivered')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ";

            $monthlyRevenueStmt = $db->prepare($monthlyRevenueQuery);
            $monthlyRevenueStmt->execute();
            $monthlyRevenue = $monthlyRevenueStmt->fetchAll(PDO::FETCH_ASSOC);

            // User growth over time
            $userGrowthQuery = "
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as new_users,
                    SUM(CASE WHEN role = 'farmer' THEN 1 ELSE 0 END) as new_farmers,
                    SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as new_buyers
                FROM users
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ";

            $userGrowthStmt = $db->prepare($userGrowthQuery);
            $userGrowthStmt->execute();
            $userGrowth = $userGrowthStmt->fetchAll(PDO::FETCH_ASSOC);

            // Top selling products
            $topProductsQuery = "
                SELECT 
                    p.id,
                    p.name,
                    p.price,
                    p.image,
                    COUNT(oi.id) as total_orders,
                    SUM(oi.quantity) as total_quantity_sold,
                    SUM(p.price * oi.quantity) as total_revenue
                FROM products p
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('completed', 'delivered')
                AND p.deleted_at IS NULL
                GROUP BY p.id
                ORDER BY total_revenue DESC
                LIMIT 10
            ";

            $topProductsStmt = $db->prepare($topProductsQuery);
            $topProductsStmt->execute();
            $topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Category distribution
            $categoryDistributionQuery = "
                SELECT 
                    category,
                    COUNT(*) as product_count,
                    SUM(price * quantity) as total_value
                FROM products
                WHERE category IS NOT NULL AND category != ''
                AND deleted_at IS NULL
                GROUP BY category
                ORDER BY product_count DESC
            ";

            $categoryStmt = $db->prepare($categoryDistributionQuery);
            $categoryStmt->execute();
            $categoryDistribution = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

            // FIXED: Recent activities - removed order_number reference
            $recentOrdersQuery = "
                SELECT 
                    'order' as type,
                    o.id as id,
                    CONCAT('ORD-', o.id) as reference,
                    o.total_amount as amount,
                    o.status,
                    o.created_at as date,
                    b.name as user_name
                FROM orders o
                JOIN users b ON o.buyer_id = b.id
                ORDER BY o.created_at DESC
                LIMIT 5
            ";

            $recentOrdersStmt = $db->prepare($recentOrdersQuery);
            $recentOrdersStmt->execute();
            $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

            $recentUsersQuery = "
                SELECT 
                    'user' as type,
                    id,
                    name as reference,
                    role,
                    created_at as date,
                    verification_status
                FROM users
                ORDER BY created_at DESC
                LIMIT 5
            ";

            $recentUsersStmt = $db->prepare($recentUsersQuery);
            $recentUsersStmt->execute();
            $recentUsers = $recentUsersStmt->fetchAll(PDO::FETCH_ASSOC);

            // Combine recent activities
            $recentActivities = array_merge($recentOrders, $recentUsers);
            usort($recentActivities, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            $recentActivities = array_slice($recentActivities, 0, 10);

            // Calculate growth percentages
            $previousMonthRevenue = 0;
            $currentMonthRevenue = 0;

            foreach ($monthlyRevenue as $mr) {
                if ($mr['month'] == date('Y-m', strtotime('-1 month'))) {
                    $previousMonthRevenue = $mr['revenue'];
                }
                if ($mr['month'] == date('Y-m')) {
                    $currentMonthRevenue = $mr['revenue'];
                }
            }

            $revenueGrowth = $previousMonthRevenue > 0
                ? round((($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 1)
                : 0;

            echo json_encode([
                'success' => true,
                'data' => [
                    'user_stats' => $userStats,
                    'order_stats' => $orderStats,
                    'product_stats' => $productStats,
                    'monthly_revenue' => $monthlyRevenue,
                    'user_growth' => $userGrowth,
                    'top_products' => $topProducts,
                    'category_distribution' => $categoryDistribution,
                    'recent_activities' => $recentActivities,
                    'growth_metrics' => [
                        'revenue_growth' => $revenueGrowth,
                        'user_growth' => $userGrowth ? round((($userStats['new_this_month'] ?? 0) / max(1, ($userStats['total_users'] - ($userStats['new_this_month'] ?? 0)))) * 100, 1) : 0
                    ]
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error in getAnalytics: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getRevenueChart()
    {
        header('Content-Type: application/json');

        try {
            $db = $this->connect();

            $input = json_decode(file_get_contents('php://input'), true);
            $year = $input['year'] ?? date('Y');

            $query = "
                SELECT 
                    MONTH(created_at) as month,
                    COUNT(*) as order_count,
                    SUM(total_amount) as revenue
                FROM orders
                WHERE YEAR(created_at) = ? AND (status = 'completed' OR status = 'delivered')
                GROUP BY MONTH(created_at)
                ORDER BY month ASC
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([$year]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fill in missing months
            $monthlyData = [];
            for ($i = 1; $i <= 12; $i++) {
                $found = false;
                foreach ($data as $row) {
                    if ((int) $row['month'] === $i) {
                        $monthlyData[] = $row;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $monthlyData[] = [
                        'month' => $i,
                        'order_count' => 0,
                        'revenue' => 0
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $monthlyData,
                'year' => $year
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getTopPerformers()
    {
        header('Content-Type: application/json');

        try {
            $db = $this->connect();

            // Top farmers by revenue
            $topFarmersQuery = "
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(oi.price * oi.quantity) as total_revenue,
                    COUNT(DISTINCT p.id) as total_products
                FROM users u
                JOIN products p ON u.id = p.farmer_id
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('completed', 'delivered')
                GROUP BY u.id
                ORDER BY total_revenue DESC
                LIMIT 10
            ";

            $farmerStmt = $db->prepare($topFarmersQuery);
            $farmerStmt->execute();
            $topFarmers = $farmerStmt->fetchAll(PDO::FETCH_ASSOC);

            // Top buyers by spending
            $topBuyersQuery = "
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    AVG(o.total_amount) as avg_order_value
                FROM users u
                JOIN orders o ON u.id = o.buyer_id
                WHERE o.status IN ('completed', 'delivered')
                GROUP BY u.id
                ORDER BY total_spent DESC
                LIMIT 10
            ";

            $buyerStmt = $db->prepare($topBuyersQuery);
            $buyerStmt->execute();
            $topBuyers = $buyerStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'top_farmers' => $topFarmers,
                'top_buyers' => $topBuyers
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Add these methods to your AdminDashboardController class
    public function getPayments()
    {
        header('Content-Type: application/json');

        try {
            $db = $this->connect();

            // Get filter parameters
            $input = json_decode(file_get_contents('php://input'), true);
            $status = $input['status'] ?? '';
            $dateRange = $input['date_range'] ?? '';
            $search = $input['search'] ?? '';

            // Build the query - MATCHING YOUR ORDERS TABLE STRUCTURE
            $query = "
                SELECT
                    o.id as payment_id,
                    o.id as order_id,
                    o.order_total as amount,
                    o.status as payment_status,
                    CONCAT('TXN-', o.id, '-', DATE_FORMAT(o.created_at, '%Y%m%d')) as transaction_id,
                    o.created_at as payment_date,
                    o.created_at,
                    o.order_total,
                    o.shipping_cost,
                    o.total_weight_kg,
                    u.name as buyer_name,
                    u.email as buyer_email,
                    o.delivery_address,
                    o.delivery_city,
                    o.delivery_phone,
                    o.status as order_status
                FROM orders o
                JOIN users u ON o.buyer_id = u.id
                WHERE 1=1
            ";

            $params = [];

            // Apply status filter (using order status as payment status indicator)
            if (!empty($status)) {
                $query .= " AND o.status = :status";
                $params[':status'] = $status;
            }

            // Apply date range filter
            if (!empty($dateRange)) {
                switch ($dateRange) {
                    case 'today':
                        $query .= " AND DATE(o.created_at) = CURDATE()";
                        break;
                    case 'week':
                        $query .= " AND YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
                        break;
                    case 'month':
                        $query .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
                        break;
                    case 'quarter':
                        $query .= " AND QUARTER(o.created_at) = QUARTER(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
                        break;
                    case 'year':
                        $query .= " AND YEAR(o.created_at) = YEAR(CURDATE())";
                        break;
                }
            }

            // Apply search filter
            if (!empty($search)) {
                $query .= " AND (o.id LIKE :search OR u.name LIKE :search OR CONCAT('TXN-', o.id) LIKE :search OR o.delivery_city LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            $query .= " ORDER BY o.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get payment statistics from orders table
            $statsQuery = "
                SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN status = 'delivered' OR status = 'completed' THEN order_total ELSE 0 END) as total_completed_amount,
                    SUM(CASE WHEN status = 'delivered' OR status = 'completed' THEN shipping_cost ELSE 0 END) as total_completed_shipping,
                    SUM(CASE WHEN status = 'pending' THEN order_total ELSE 0 END) as total_pending_amount,
                    SUM(CASE WHEN status = 'cancelled' THEN order_total ELSE 0 END) as total_cancelled_amount,
                    SUM(CASE WHEN status = 'shipped' THEN order_total ELSE 0 END) as total_shipped_amount,
                    COUNT(CASE WHEN status = 'delivered' OR status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                    COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_count,
                    SUM(order_total) as total_revenue,
                    AVG(order_total) as avg_payment_amount,
                    SUM(shipping_cost) as total_shipping_revenue,
                    SUM(CASE WHEN status NOT IN ('pending_payment', 'cancelled') THEN shipping_cost ELSE 0 END) as paid_shipping_revenue,
                    AVG(total_weight_kg) as avg_weight
                FROM orders
            ";

            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            $methodDistribution = [];

            // Get monthly payment trends from orders
            $trendsQuery = "
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as payment_count,
                    SUM(order_total) as total_amount,
                    AVG(order_total) as avg_amount
                FROM orders
                WHERE status IN ('delivered', 'completed')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ";

            $trendsStmt = $db->prepare($trendsQuery);
            $trendsStmt->execute();
            $trends = $trendsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate platform commission (5% commission on paid shipping cost, excluding pending_payment and cancelled)
            $totalCommission = ($stats['paid_shipping_revenue'] ?? 0) * 0.05;

            echo json_encode([
                'success' => true,
                'data' => $payments,
                'stats' => [
                    'total_transactions' => (int) ($stats['total_transactions'] ?? 0),
                    'total_revenue' => round($stats['total_revenue'] ?? 0, 2),
                    'total_completed_amount' => round($stats['total_completed_amount'] ?? 0, 2),
                    'total_pending_amount' => round($stats['total_pending_amount'] ?? 0, 2),
                    'total_cancelled_amount' => round($stats['total_cancelled_amount'] ?? 0, 2),
                    'total_shipped_amount' => round($stats['total_shipped_amount'] ?? 0, 2),
                    'completed_count' => (int) ($stats['completed_count'] ?? 0),
                    'pending_count' => (int) ($stats['pending_count'] ?? 0),
                    'cancelled_count' => (int) ($stats['cancelled_count'] ?? 0),
                    'shipped_count' => (int) ($stats['shipped_count'] ?? 0),
                    'avg_payment_amount' => round($stats['avg_payment_amount'] ?? 0, 2),
                    'platform_commission' => round($totalCommission, 2),
                    'total_shipping_revenue' => round($stats['total_shipping_revenue'] ?? 0, 2),
                    'avg_weight' => round($stats['avg_weight'] ?? 0, 2)
                ],
                'method_distribution' => $methodDistribution,
                'trends' => $trends
            ]);
        } catch (Exception $e) {
            error_log("Error in getPayments: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getPaymentDetails($paymentId = null)
    {
        header('Content-Type: application/json');

        try {
            if (!$paymentId) {
                $input = json_decode(file_get_contents('php://input'), true);
                $paymentId = $input['payment_id'] ?? null;
            }

            if (!$paymentId) {
                echo json_encode(['success' => false, 'message' => 'Payment ID required']);
                exit;
            }

            $db = $this->connect();

            // Get payment details from orders table
            $query = "
                SELECT 
                    o.id as payment_id,
                    o.id as order_id,
                    o.order_total as amount,
                    o.shipping_cost,
                    o.total_weight_kg,
                    o.status as payment_status,
                    o.created_at as payment_date,
                    CONCAT('TXN-', o.id, '-', DATE_FORMAT(o.created_at, '%Y%m%d')) as transaction_id,
                    u.name as buyer_name,
                    u.email as buyer_email,
                    bp.phone as buyer_phone,
                    o.delivery_address,
                    o.delivery_city,
                    o.delivery_district_id,
                    o.delivery_town_id,
                    o.delivery_phone,
                    o.status as order_status,
                    NULL as refund_reason,
                    NULL as refund_date
                FROM orders o
                JOIN users u ON o.buyer_id = u.id
                LEFT JOIN buyer_profiles bp ON bp.user_id = u.id
                WHERE o.id = ?
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'payment' => $payment
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function updatePaymentStatus()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $paymentId = $input['payment_id'] ?? null;
            $status = $input['status'] ?? null;

            if (!$paymentId || !$status) {
                echo json_encode(['success' => false, 'message' => 'Payment ID and status required']);
                exit;
            }

            // Valid order statuses based on your table
            $validStatuses = ['pending', 'shipped', 'delivered', 'cancelled'];

            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status. Must be: pending, shipped, delivered, cancelled']);
                exit;
            }

            $db = $this->connect();

            // Update status in orders table
            $query = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :order_id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([':status' => $status, ':order_id' => $paymentId]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function refundPayment()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $paymentId = $input['payment_id'] ?? null;
            $reason = $input['reason'] ?? '';

            if (!$paymentId) {
                echo json_encode(['success' => false, 'message' => 'Payment ID required']);
                exit;
            }

            $db = $this->connect();

            // Update order status to cancelled (as a form of refund)
            $query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = :order_id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([':order_id' => $paymentId]);

            if ($result) {
                // You might want to log the refund reason in a separate table
                echo json_encode(['success' => true, 'message' => 'Payment refunded successfully (order cancelled)']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to refund payment']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    // Add these methods to your AdminDashboardController class

    public function getDisputes()
    {
        header('Content-Type: application/json');

        try {
            $db = $this->connect();

            // Get filter parameters
            $input = json_decode(file_get_contents('php://input'), true);
            $status = $input['status'] ?? '';
            $type = $input['type'] ?? '';
            $priority = $input['priority'] ?? '';
            $search = $input['search'] ?? '';

            // Build the query
            $query = "
                SELECT 
                    o.id as dispute_id,
                    o.id as order_id,
                    o.buyer_id as complainant_id,
                    NULL as respondent_id,
                    'cancelled_order' as type,
                    'Order cancelled - payment revision needed' as reason,
                    'open' as status,
                    'medium' as priority,
                    NULL as resolution_notes,
                    o.created_at,
                    o.updated_at,
                    NULL as resolved_at,
                    CONCAT('ORD-', o.id) as order_number,
                    o.total_amount as order_amount,
                    b.name as complainant_name,
                    b.email as complainant_email,
                    b.role as complainant_role,
                    'System' as respondent_name,
                    '' as respondent_email,
                    'admin' as respondent_role
                FROM orders o
                JOIN users b ON o.buyer_id = b.id
                WHERE o.status = 'cancelled'
            ";

            $params = [];

            // Apply status filter (for cancelled orders, status is always 'open' for disputes)
            if (!empty($status) && $status !== 'open') {
                // If filtering for other statuses, no results since all are 'open'
                $query .= " AND 1=0";
            }

            // Apply type filter
            if (!empty($type) && $type !== 'cancelled_order') {
                $query .= " AND 1=0"; // No other types
            }

            // Apply priority filter
            if (!empty($priority) && $priority !== 'medium') {
                $query .= " AND 1=0"; // All are medium
            }

            // Apply search filter
            if (!empty($search)) {
                $query .= " AND (CONCAT('ORD-', o.id) LIKE :search OR b.name LIKE :search OR 'System' LIKE :search OR 'Order cancelled - payment revision needed' LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            $query .= " ORDER BY o.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get dispute statistics
            $statsQuery = "
                SELECT 
                    COUNT(*) as total_disputes,
                    COUNT(*) as open_disputes,
                    0 as in_progress_disputes,
                    0 as resolved_disputes,
                    0 as closed_disputes,
                    0 as high_priority,
                    COUNT(*) as medium_priority,
                    0 as low_priority,
                    NULL as avg_resolution_hours,
                    0 as order_issues,
                    COUNT(*) as payment_issues,
                    0 as delivery_issues,
                    0 as quality_issues
                FROM orders
                WHERE status = 'cancelled'
            ";

            $statsStmt = $db->prepare($statsQuery);
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // Get dispute type distribution
            $typeQuery = "
                SELECT 
                    'cancelled_order' as type,
                    COUNT(*) as count,
                    COUNT(*) as open_count,
                    0 as resolved_count
                FROM orders
                WHERE status = 'cancelled'
            ";

            $typeStmt = $db->prepare($typeQuery);
            $typeStmt->execute();
            $typeDistribution = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $disputes,
                'stats' => [
                    'total_disputes' => (int) ($stats['total_disputes'] ?? 0),
                    'open_disputes' => (int) ($stats['open_disputes'] ?? 0),
                    'in_progress_disputes' => (int) ($stats['in_progress_disputes'] ?? 0),
                    'resolved_disputes' => (int) ($stats['resolved_disputes'] ?? 0),
                    'closed_disputes' => (int) ($stats['closed_disputes'] ?? 0),
                    'high_priority' => (int) ($stats['high_priority'] ?? 0),
                    'medium_priority' => (int) ($stats['medium_priority'] ?? 0),
                    'low_priority' => (int) ($stats['low_priority'] ?? 0),
                    'avg_resolution_hours' => round($stats['avg_resolution_hours'] ?? 0, 1),
                    'order_issues' => (int) ($stats['order_issues'] ?? 0),
                    'payment_issues' => (int) ($stats['payment_issues'] ?? 0),
                    'delivery_issues' => (int) ($stats['delivery_issues'] ?? 0),
                    'quality_issues' => (int) ($stats['quality_issues'] ?? 0)
                ],
                'type_distribution' => $typeDistribution
            ]);
        } catch (Exception $e) {
            error_log("Error in getDisputes: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getDisputeDetails($disputeId = null)
    {
        header('Content-Type: application/json');

        try {
            if (!$disputeId) {
                $input = json_decode(file_get_contents('php://input'), true);
                $disputeId = $input['dispute_id'] ?? null;
            }

            if (!$disputeId) {
                echo json_encode(['success' => false, 'message' => 'Dispute ID required']);
                exit;
            }

            $db = $this->connect();

            $query = "
                SELECT 
                    o.id as id,
                    o.id as order_id,
                    'cancelled_order' as type,
                    'Order cancelled - payment revision needed' as reason,
                    'open' as status,
                    'medium' as priority,
                    NULL as resolution_notes,
                    o.created_at,
                    o.updated_at,
                    NULL as resolved_at,
                    CONCAT('ORD-', o.id) as order_number,
                    o.total_amount as order_amount,
                    o.status as order_status,
                    b.name as complainant_name,
                    b.email as complainant_email,
                    b.phone as complainant_phone,
                    b.role as complainant_role,
                    'System' as respondent_name,
                    '' as respondent_email,
                    '' as respondent_phone,
                    'admin' as respondent_role
                FROM orders o
                JOIN users b ON o.buyer_id = b.id
                WHERE o.id = ? AND o.status = 'cancelled'
            ";

            $stmt = $db->prepare($query);
            $stmt->execute([$disputeId]);
            $dispute = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dispute) {
                echo json_encode(['success' => false, 'message' => 'Dispute not found']);
                exit;
            }

            // For cancelled orders, no dispute messages yet
            $messages = [];

            echo json_encode([
                'success' => true,
                'dispute' => $dispute,
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function updateDisputeStatus()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $disputeId = $input['dispute_id'] ?? null;
            $status = $input['status'] ?? null;
            $resolutionNotes = $input['resolution_notes'] ?? null;

            if (!$disputeId || !$status) {
                echo json_encode(['success' => false, 'message' => 'Dispute ID and status required']);
                exit;
            }

            $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }

            $db = $this->connect();

            // For cancelled orders as disputes, if resolving, perform refund
            if ($status === 'resolved') {
                // Call refund payment
                $refundInput = ['payment_id' => $disputeId, 'reason' => $resolutionNotes ?? 'Dispute resolved'];
                // Simulate the refund process
                $query = "UPDATE orders SET status = 'refunded', updated_at = NOW() WHERE id = :order_id";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([':order_id' => $disputeId]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Dispute resolved and payment refunded successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to refund payment']);
                }
            } else {
                // For other statuses, just acknowledge (since no table to update)
                echo json_encode(['success' => true, 'message' => 'Dispute status updated successfully']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function addDisputeMessage()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $disputeId = $input['dispute_id'] ?? null;
            $message = $input['message'] ?? null;
            $senderId = $input['sender_id'] ?? ($_SESSION['USER']->id ?? null);

            if (!$disputeId || !$message || !$senderId) {
                echo json_encode(['success' => false, 'message' => 'Dispute ID, message, and sender ID required']);
                exit;
            }

            $db = $this->connect();

            // For cancelled orders, messages are not stored yet
            echo json_encode(['success' => true, 'message' => 'Message noted (not stored)']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function resolveDispute()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $disputeId = $input['dispute_id'] ?? null;
            $resolution = $input['resolution'] ?? null;

            if (!$disputeId) {
                echo json_encode(['success' => false, 'message' => 'Dispute ID required']);
                exit;
            }

            $db = $this->connect();

            // For cancelled orders, resolving means refunding
            $query = "UPDATE orders SET status = 'refunded', updated_at = NOW() WHERE id = :id AND status = 'cancelled'";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':id' => $disputeId
            ]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Dispute resolved and payment refunded successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to resolve dispute']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function sendNotification()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        if (!requireRole('admin', ['json' => true, 'message' => 'Admin access required'])) {
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $recipient = strtolower(trim((string) ($_POST['recipient'] ?? '')));
        $type = strtolower(trim((string) ($_POST['type'] ?? 'system')));
        $selectedUserIds = $_POST['user_ids'] ?? [];

        if ($title === '' || $message === '' || $recipient === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Title, message and recipient are required']);
            exit;
        }

        $allowedTypes = ['system', 'maintenance', 'promotion', 'alert'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'system';
        }

        if ($type !== 'system') {
            $title = strtoupper(substr($type, 0, 1)) . substr($type, 1) . ': ' . $title;
        }

        $userIds = $this->resolveNotificationRecipientUserIds($recipient, $selectedUserIds);
        if (empty($userIds)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No matching recipients found']);
            exit;
        }

        $notifications = new NotificationsModel();
        $recipientKey = preg_replace('/[^a-z_]+/i', '', strtolower((string) $recipient));
        if ($recipientKey === '') {
            $recipientKey = 'unknown';
        }
        $eventKey = 'admin_' . $recipientKey . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));
        $result = $notifications->broadcast($userIds, [
            'event_key' => $eventKey,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => null,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Notification sent',
            'sent' => (int) ($result['sent'] ?? 0),
            'failed' => (int) ($result['failed'] ?? 0),
        ]);
        exit;
    }

    public function getNotificationStats()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        if (!requireRole('admin', ['json' => true, 'message' => 'Admin access required'])) {
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $notifications = new NotificationsModel();
            $stats = $notifications->get_row(
                'SELECT COUNT(*) AS total_sent, SUM(is_read) AS read_count FROM notifications',
                []
            );

            $total = (int) ($stats->total_sent ?? 0);
            $read = (int) ($stats->read_count ?? 0);
            $openRate = $total > 0 ? (int) round(($read / $total) * 100) : 0;

            echo json_encode([
                'success' => true,
                'total_sent' => $total,
                'delivered' => $total,
                'open_rate' => $openRate,
                'click_rate' => 0,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getNotificationSummary()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        if (!$this->requireAdminJson()) {
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $notifications = new NotificationsModel();

            $sql = "
                SELECT
                    n.event_key,
                    MIN(n.title) AS title,
                    MIN(n.message) AS message,
                    MIN(n.type) AS type,
                    MIN(n.created_at) AS sent_at,
                    COUNT(*) AS delivered_count,
                    CASE
                        WHEN n.event_key LIKE 'admin\\_%\\_%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(n.event_key, '_', 2), '_', -1)
                        ELSE 'unknown'
                    END AS recipient_type
                FROM notifications n
                WHERE n.event_key LIKE 'admin\\_%'
                  AND COALESCE(n.event_key, '') NOT LIKE 'mock\\_%'
                  AND COALESCE(n.event_key, '') NOT LIKE 'test\\_%'
                  AND COALESCE(n.event_key, '') NOT LIKE 'debug\\_%'
                  AND LOWER(COALESCE(n.title, '')) NOT LIKE '%localhost%'
                  AND LOWER(COALESCE(n.message, '')) NOT LIKE '%localhost%'
                GROUP BY n.event_key
                ORDER BY sent_at DESC
                LIMIT 200
            ";

            $rows = $notifications->query($sql, []) ?: [];
            $data = [];

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $data[] = [
                        'event_key' => (string) ($row->event_key ?? ''),
                        'title' => (string) ($row->title ?? ''),
                        'message' => (string) ($row->message ?? ''),
                        'type' => (string) ($row->type ?? 'system'),
                        'sent_at' => (string) ($row->sent_at ?? ''),
                        'recipient_type' => (string) ($row->recipient_type ?? 'unknown'),
                        'delivered_count' => (int) ($row->delivered_count ?? 0),
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }

    private function resolveNotificationRecipientUserIds(string $recipient, $selectedUserIds = []): array
    {
        $userModel = new UserModel();

        $recipient = strtolower(trim($recipient));
        $roleMap = [
            'farmers' => 'farmer',
            'buyers' => 'buyer',
            'transporters' => 'transporter',
            'admins' => 'admin',
        ];

        if ($recipient === 'selected') {
            $ids = is_array($selectedUserIds) ? $selectedUserIds : [$selectedUserIds];
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
            if (empty($ids)) {
                return [];
            }

            $placeholders = [];
            $params = [];
            foreach ($ids as $index => $id) {
                $key = 'id_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }

            $rows = $userModel->query(
                "SELECT id FROM users WHERE id IN (" . implode(',', $placeholders) . ")",
                $params
            );

            if (!is_array($rows)) {
                return [];
            }

            return array_values(array_unique(array_map(fn($row) => (int) ($row->id ?? 0), $rows)));
        }

        if ($recipient === 'all') {
            $rows = $userModel->query("SELECT id FROM users", []);
            if (!is_array($rows)) {
                return [];
            }
            return array_values(array_unique(array_map(fn($row) => (int) ($row->id ?? 0), $rows)));
        }

        if (isset($roleMap[$recipient])) {
            $role = $roleMap[$recipient];
            $rows = $userModel->query("SELECT id FROM users WHERE role = :role", ['role' => $role]);
            if (!is_array($rows)) {
                return [];
            }
            return array_values(array_unique(array_map(fn($row) => (int) ($row->id ?? 0), $rows)));
        }

        return [];
    }

    public function getCancelledOrdersDisputes()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        if (!requireRole(['admin', 'superadmin'], ['json' => true, 'message' => 'Admin access required'])) {
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $search = trim((string) ($input['search'] ?? ''));
        $revisionStatus = strtolower(trim((string) ($input['revision_status'] ?? ''))); // revised|unrevised|all

        try {
            $db = $this->connect();

            $where = ["o.status = 'cancelled'"];
            $params = [];

            if ($search !== '') {
                $where[] = "(CAST(o.id AS CHAR) LIKE :search OR b.name LIKE :search OR b.email LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if ($revisionStatus === 'revised') {
                $where[] = "d.id IS NOT NULL";
            } elseif ($revisionStatus === 'unrevised') {
                $where[] = "d.id IS NULL";
            }

            $whereSql = implode(' AND ', $where);

            // LEFT JOIN the latest dispute row per order (disputes is append-only,
            // so MAX(id) identifies the most recent revision).
            $sql = "
                SELECT
                    o.id AS order_id,
                    o.buyer_id,
                    b.name AS buyer_name,
                    b.email AS buyer_email,
                    o.total_amount,
                    o.shipping_cost,
                    o.order_total,
                    o.delivery_city,
                    o.created_at,
                    o.updated_at,
                    d.id AS revision_id,
                    d.revised_total_amount,
                    d.revised_shipping_cost,
                    d.revised_order_total,
                    d.reason AS revision_reason,
                    d.created_at AS revised_at,
                    admin_u.name AS revised_by_name
                FROM orders o
                JOIN users b ON b.id = o.buyer_id
                LEFT JOIN disputes d
                    ON d.id = (SELECT MAX(id) FROM disputes WHERE order_id = o.id)
                LEFT JOIN users admin_u ON admin_u.id = d.admin_id
                WHERE {$whereSql}
                ORDER BY o.updated_at DESC, o.id DESC
                LIMIT 200
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $total = count($rows);
            $revised = 0;
            foreach ($rows as $r) {
                if (!empty($r['revised_at'])) {
                    $revised++;
                }
            }
            $unrevised = $total - $revised;

            $logStmt = $db->query("SELECT COUNT(*) FROM disputes");
            $revisionLogCount = $logStmt ? (int) $logStmt->fetchColumn() : 0;

            echo json_encode([
                'success' => true,
                'data' => $rows,
                'stats' => [
                    'total_cancelled' => $total,
                    'revised' => $revised,
                    'unrevised' => $unrevised,
                    'revision_log_count' => $revisionLogCount,
                ],
            ]);
            exit;
        } catch (Exception $e) {
            error_log("Error in getCancelledOrdersDisputes: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function getCancelledOrderDisputeDetails()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        if (!requireRole(['admin', 'superadmin'], ['json' => true, 'message' => 'Admin access required'])) {
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = (int) ($input['order_id'] ?? 0);

        if ($orderId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            exit;
        }

        try {
            $db = $this->connect();

            $orderStmt = $db->prepare("
                SELECT
                    o.*,
                    b.name AS buyer_name,
                    b.email AS buyer_email
                FROM orders o
                JOIN users b ON b.id = o.buyer_id
                WHERE o.id = ?
                LIMIT 1
            ");
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order || ($order['status'] ?? '') !== 'cancelled') {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cancelled order not found']);
                exit;
            }

            $itemsStmt = $db->prepare("
                SELECT
                    oi.*,
                    u.name AS farmer_name
                FROM order_items oi
                LEFT JOIN users u ON u.id = oi.farmer_id
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC
            ");
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $revisionStmt = $db->prepare("
                SELECT
                    d.*,
                    admin_u.name AS revised_by_name
                FROM disputes d
                LEFT JOIN users admin_u ON admin_u.id = d.admin_id
                WHERE d.order_id = ?
                ORDER BY d.id DESC
                LIMIT 1
            ");
            $revisionStmt->execute([$orderId]);
            $latest = $revisionStmt->fetch(PDO::FETCH_ASSOC);

            $revision = null;
            if ($latest) {
                $revision = [
                    'original_total_amount' => $latest['original_total_amount'],
                    'original_shipping_cost' => $latest['original_shipping_cost'],
                    'original_order_total' => $latest['original_order_total'],
                    'revised_total_amount' => $latest['revised_total_amount'],
                    'revised_shipping_cost' => $latest['revised_shipping_cost'],
                    'revised_order_total' => $latest['revised_order_total'],
                    'reason' => $latest['reason'],
                    'revised_at' => $latest['created_at'],
                    'revised_by_admin_id' => $latest['admin_id'],
                    'revised_by_name' => $latest['revised_by_name'],
                ];
            }

            $payoutAccountsModel = new PayoutAccountsModel();
            $payoutAccount = $payoutAccountsModel->getDefaultAccountByUserId($order['buyer_id']);

            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items,
                'revision' => $revision,
                'payout_account' => $payoutAccount,
            ]);
            exit;
        } catch (Exception $e) {
            error_log("Error in getCancelledOrderDisputeDetails: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function reviseCancelledOrderPayment()
    {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json');

        if (!requireRole('admin', ['json' => true, 'message' => 'Admin access required'])) {
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = (int) ($input['order_id'] ?? 0);
        $revisedTotal = (float) ($input['revised_total_amount'] ?? 0);
        $revisedShipping = (float) ($input['revised_shipping_cost'] ?? 0);
        $reason = trim((string) ($input['reason'] ?? ''));

        if ($orderId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            exit;
        }

        if ($reason === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Reason is required']);
            exit;
        }

        if ($revisedTotal < 0 || $revisedShipping < 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Amounts cannot be negative']);
            exit;
        }

        try {
            $db = $this->connect();

            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order || ($order['status'] ?? '') !== 'cancelled') {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cancelled order not found']);
                exit;
            }

            $originalTotal = (float) ($order['total_amount'] ?? 0);
            $originalShipping = (float) ($order['shipping_cost'] ?? 0);
            $originalOrderTotal = (float) ($order['order_total'] ?? ($originalTotal + $originalShipping));

            $revisedOrderTotal = round($revisedTotal + $revisedShipping, 2);

            $adminId = (int) (authUserId() ?? 0);
            if ($adminId <= 0 && !empty($_SESSION['USER']->id)) {
                $adminId = (int) $_SESSION['USER']->id;
            }

            $db->beginTransaction();

            $insert = $db->prepare("
                INSERT INTO disputes (
                    order_id,
                    original_total_amount,
                    original_shipping_cost,
                    original_order_total,
                    revised_total_amount,
                    revised_shipping_cost,
                    revised_order_total,
                    reason,
                    admin_id
                ) VALUES (
                    :order_id,
                    :original_total,
                    :original_shipping,
                    :original_order_total,
                    :revised_total,
                    :revised_shipping,
                    :revised_order_total,
                    :reason,
                    :admin_id
                )
            ");

            $insert->execute([
                ':order_id' => $orderId,
                ':original_total' => $originalTotal,
                ':original_shipping' => $originalShipping,
                ':original_order_total' => $originalOrderTotal,
                ':revised_total' => $revisedTotal,
                ':revised_shipping' => $revisedShipping,
                ':revised_order_total' => $revisedOrderTotal,
                ':reason' => substr($reason, 0, 500),
                ':admin_id' => $adminId > 0 ? $adminId : null,
            ]);

            $update = $db->prepare("
                UPDATE orders
                SET total_amount = :total_amount,
                    shipping_cost = :shipping_cost,
                    order_total = :order_total,
                    updated_at = NOW()
                WHERE id = :order_id
                LIMIT 1
            ");
            $update->execute([
                ':total_amount' => $revisedTotal,
                ':shipping_cost' => $revisedShipping,
                ':order_total' => $revisedOrderTotal,
                ':order_id' => $orderId,
            ]);

            // Deduct/adjust transporter earnings by updating the linked delivery request(s).
            // In this project, transporter earnings are derived from delivery_requests.shipping_fee.
            $drUpdate = $db->prepare("
                UPDATE delivery_requests
                SET shipping_fee = :shipping_fee,
                    updated_at = NOW()
                WHERE order_id = :order_id
            ");
            $drUpdate->execute([
                ':shipping_fee' => $revisedShipping,
                ':order_id' => $orderId,
            ]);
            $deliveryRequestsUpdated = (int) $drUpdate->rowCount();

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Payment revised and recorded',
                'order_id' => $orderId,
                'revised_order_total' => $revisedOrderTotal,
                'delivery_requests_updated' => $deliveryRequestsUpdated,
            ]);
            exit;
        } catch (Exception $e) {
            try {
                if (!empty($db) && $db instanceof PDO && $db->inTransaction()) {
                    $db->rollBack();
                }
            } catch (Exception $ignored) {
            }

            error_log("Error in reviseCancelledOrderPayment: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}
