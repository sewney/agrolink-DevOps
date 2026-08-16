<?php
class RegisterController
{
    use Controller;
    use Database;

    private string $uploadDir;

    public function __construct()
    {
        $publicPath = realpath(__DIR__ . '/../../public');
        if ($publicPath === false) {
            $publicPath = dirname(__DIR__, 2) . '/public';
        }

        $this->uploadDir = rtrim($publicPath, '/\\')
            . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'verification'
            . DIRECTORY_SEPARATOR;
    }

    public function index($a = '', $b = '', $c = '')
    {
        $user = new UserModel;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($user->validate($_POST)) {
                $userId = $user->insert($_POST);
                $role   = $_POST['role'] ?? '';

                if ($role === 'buyer') {
                    (new BuyerModel())->createProfile($userId, []);
                    // Buyers need no verification
                    $user->setVerificationStatus($userId, 'not_required');
                } elseif ($role === 'farmer') {
                    (new FarmerModel())->createProfile($userId, []);
                    $uploaded = $this->handleUploads($userId, [
                        'nic'          => $_FILES['nic']          ?? null,
                        'bank_details' => $_FILES['bank_details'] ?? null,
                    ]);
                    if ($uploaded === 0) {
                        error_log('RegisterController: no verification docs saved for farmer user_id=' . (int)$userId);
                    }
                    $user->setVerificationStatus($userId, 'pending');
                } elseif ($role === 'transporter') {
                    (new TransporterModel())->createProfile($userId, []);
                    $uploaded = $this->handleUploads($userId, [
                        'driving_license'        => $_FILES['driving_license']        ?? null,
                        'vehicle_insurance'      => $_FILES['vehicle_insurance']      ?? null,
                        'vehicle_revenue_license' => $_FILES['vehicle_revenue_license'] ?? null,
                    ]);
                    if ($uploaded === 0) {
                        error_log('RegisterController: no verification docs saved for transporter user_id=' . (int)$userId);
                    }
                    $user->setVerificationStatus($userId, 'pending');
                }

                redirect('login?registered=1');
            }
        }

        $data['errors'] = $user->errors;
        $this->view('register', $data);
    }

    private function handleUploads(int $userId, array $files): int
    {
        error_log("=== Starting handleUploads for user_id: $userId ===");

        // Check table structure first
        $docModel = new VerificationDocumentModel();
        $tableCheck = $docModel->checkTableStructure();
        error_log("Table check: " . json_encode($tableCheck));

        if (!$tableCheck['table_exists']) {
            error_log("ERROR: verification_documents table does not exist!");
            return 0;
        }

        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0775, true) && !is_dir($this->uploadDir)) {
                error_log('RegisterController: failed to create upload directory: ' . $this->uploadDir);
                return 0;
            }
        }

        if (!is_writable($this->uploadDir)) {
            @chmod($this->uploadDir, 0775);
        }

        if (!is_writable($this->uploadDir)) {
            error_log('RegisterController: upload directory not writable: ' . $this->uploadDir);
            return 0;
        }

        $savedCount = 0;
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $allowedMimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        foreach ($files as $docType => $file) {
            error_log("Processing document type: $docType");

            if (empty($file) || !isset($file['error'])) {
                error_log("  - No file or missing error field");
                continue;
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                error_log(
                    '  - Upload error: code=' . (int)$file['error']
                        . ', msg=' . $this->getUploadErrorMessage((int)$file['error'])
                );
                continue;
            }

            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                error_log('  - Invalid uploaded file');
                continue;
            }

            $clientExt = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
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
                error_log(
                    '  - Blocked file type: mime=' . ($detectedMime !== '' ? $detectedMime : 'unknown')
                        . ', ext=' . $clientExt
                );
                continue;
            }

            $filename = "doc_{$userId}_{$docType}_" . uniqid('', true) . ".{$resolvedExt}";
            $dest = $this->uploadDir . $filename;

            error_log("  - Saving to: $dest");

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $filePath = 'assets/uploads/verification/' . $filename;
                error_log("  - File moved successfully. Path: $filePath");

                // Use the debug version of insert
                $insertResult = $docModel->insertWithDebug([
                    'user_id'   => $userId,
                    'doc_type'  => $docType,
                    'file_path' => $filePath,
                    'status'    => 'pending',
                ]);

                error_log("  - Insert result: " . json_encode($insertResult));

                if ($insertResult['success']) {
                    $savedCount++;
                    error_log("  ✓ Document saved successfully with ID: " . $insertResult['id']);
                } else {
                    @unlink($dest);
                    error_log("  ✗ Failed to save to database: " . $insertResult['error']);
                }
            } else {
                error_log('  ✗ Failed to move uploaded file');
            }
        }

        error_log("=== handleUploads complete. Saved $savedCount documents ===");
        return $savedCount;
    }

    private function getUploadErrorMessage(int $code): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File uploaded partially',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp upload directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        ];

        return $errors[$code] ?? 'Unknown upload error';
    }
}
