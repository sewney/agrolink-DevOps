<?php

class VerificationController
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

    public function status(): void
    {
        if (!requireLogin()) {
            return;
        }

        $role = $_SESSION['role'] ?? ($_SESSION['USER']->role ?? '');
        $status = $_SESSION['verification_status'] ?? ($_SESSION['USER']->verification_status ?? null);

        if (!in_array($role, ['farmer', 'transporter'], true)) {
            redirect(authDashboardPath($role));
            return;
        }

        if ($status === 'approved' || $status === 'not_required') {
            redirect(authDashboardPath($role));
            return;
        }

        $data = [
            'verification_status' => $status,
        ];

        if ($status === 'rejected') {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if ($userId > 0) {
                $docModel = new VerificationDocumentModel();
                $data['rejections'] = $docModel->getRejectedDocumentsByUser($userId);
            }
        }

        $this->view('pendingVerification', $data);
    }

    public function resubmit(): void
    {
        if (!requireLogin()) {
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = $_SESSION['role'] ?? ($_SESSION['USER']->role ?? '');
        $status = $_SESSION['verification_status'] ?? ($_SESSION['USER']->verification_status ?? null);

        if ($userId <= 0) {
            redirect('login');
            return;
        }

        if (!in_array($role, ['farmer', 'transporter'], true)) {
            redirect(authDashboardPath($role));
            return;
        }

        if ($status !== 'rejected' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('verification/status');
            return;
        }

        $docModel = new VerificationDocumentModel();

        $data = [
            'role' => $role,
            'verification_status' => $status,
            'rejections' => $docModel->getRejectedDocumentsByUser($userId),
            'errors' => [],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $requiredDocTypes = $role === 'farmer'
                ? ['nic', 'bank_details']
                : ['driving_license', 'vehicle_insurance', 'vehicle_revenue_license'];

            foreach ($requiredDocTypes as $docType) {
                if (empty($_FILES[$docType]) || !isset($_FILES[$docType]['error']) || $_FILES[$docType]['error'] === UPLOAD_ERR_NO_FILE) {
                    $data['errors'][$docType] = 'This document is required.';
                }
            }

            if (empty($data['errors'])) {
                $files = [];
                foreach ($requiredDocTypes as $docType) {
                    $files[$docType] = $_FILES[$docType] ?? null;
                }

                $uploadResult = $this->saveVerificationDocuments($userId, $files);
                if ($uploadResult['success']) {
                    $userModel = new UserModel();
                    $userModel->setVerificationStatus($userId, 'pending');

                    $_SESSION['verification_status'] = 'pending';
                    if (!empty($_SESSION['USER'])) {
                        $_SESSION['USER']->verification_status = 'pending';
                    }

                    redirect('verification/status');
                    return;
                }

                $data['errors']['upload'] = $uploadResult['error'] ?? 'Failed to upload documents. Please try again.';
            }
        }

        $this->view('resubmitVerification', $data);
    }

    /**
     * @param array<string, mixed> $files
     * @return array{success:bool,error:?string}
     */
    private function saveVerificationDocuments(int $userId, array $files): array
    {
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0775, true) && !is_dir($this->uploadDir)) {
                return ['success' => false, 'error' => 'Upload directory could not be created.'];
            }
        }

        if (!is_writable($this->uploadDir)) {
            @chmod($this->uploadDir, 0775);
        }

        if (!is_writable($this->uploadDir)) {
            return ['success' => false, 'error' => 'Upload directory is not writable.'];
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $allowedMimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $docModel = new VerificationDocumentModel();
        $savedAny = false;

        foreach ($files as $docType => $file) {
            if (empty($file) || !is_array($file) || !isset($file['error'])) {
                continue;
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'error' => "Upload failed for {$docType}."];
            }

            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return ['success' => false, 'error' => "Invalid uploaded file for {$docType}."];
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
                return ['success' => false, 'error' => "Unsupported file type for {$docType}."];
            }

            $filename = "doc_{$userId}_{$docType}_" . uniqid('', true) . ".{$resolvedExt}";
            $dest = $this->uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                return ['success' => false, 'error' => "Failed to save {$docType}."];
            }

            $filePath = 'assets/uploads/verification/' . $filename;
            $insertId = $docModel->insert([
                'user_id' => $userId,
                'doc_type' => (string)$docType,
                'file_path' => $filePath,
                'status' => 'pending',
            ]);

            if ($insertId <= 0) {
                @unlink($dest);
                return ['success' => false, 'error' => "Failed to record {$docType} in the database."];
            }

            $savedAny = true;
        }

        if (!$savedAny) {
            return ['success' => false, 'error' => 'No documents were uploaded.'];
        }

        return ['success' => true, 'error' => null];
    }
}

