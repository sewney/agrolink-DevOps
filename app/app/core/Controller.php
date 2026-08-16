<?php
trait Controller
{
    use Database;

    public function view($name, $data = [])
    {

        if (!empty($data))
            extract($data);

        // Try root level first
        $filename = "../app/views/" . $name . ".view.php";
        if (file_exists($filename)) {
            require $filename;
            return;
        }

        // Try components subdirectory
        $componentsView = "../app/views/components/" . basename($name) . ".view.php";
        if (file_exists($componentsView)) {
            require $componentsView;
            return;
        }

        // Try role-based subdirectories (admin, buyer, farmer, transporter)
        $roles = ['admin', 'buyer', 'farmer', 'transporter'];
        foreach ($roles as $role) {
            $roleView = "../app/views/{$role}/" . $name . ".view.php";
            if (file_exists($roleView)) {
                require $roleView;
                return;
            }
        }

        // Fallback to 404
        $filename = "../app/views/404.view.php";
        require $filename;
    }

    protected function checkVerificationStatus(): void
    {
        if (!isset($_SESSION['user_id'])) {
            return; // Not logged in — let the auth guard handle it
        }

        $status = $_SESSION['verification_status'] ?? null;
        $role = $_SESSION['role'] ?? null;

        if (!in_array($role, ['farmer', 'transporter'], true)) {
            return;
        }

        if ($status === 'pending' || $status === 'rejected') {
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

            $this->view('pendingVerification', $data); // Show holding / rejected page
            exit;
        }
    }
}
