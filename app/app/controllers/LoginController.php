<?php
class LoginController
{
    use Controller;

    public function index($a = '', $b = '', $c = '')
    {
        $data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (empty($_POST['email']) || empty($_POST['password'])) {
                $data['errors']['email'] = "Please fill in all fields";
            } else {
                $user = new UserModel;
                $row  = $user->first(['email' => $_POST['email']]);

                if ($row && password_verify((string) $_POST['password'], (string) $row->password)) {
                    if (($row->status ?? '') === 'inactive') {
                        $data['errors']['email'] = "Your account has been deactivated. Please contact support@agrolink.lk.";
                        $this->view('login', $data);
                        return;
                    }

                    $_SESSION['USER']                = $row;
                    $_SESSION['user_id']             = $row->id;              // ✅ was $user['id']
                    $_SESSION['role']                = $row->role;            // ✅ was $user['role']
                    $_SESSION['verification_status'] = $row->verification_status; // ✅ was $user['verification_status']

                    $this->checkVerificationStatus();
                    $this->redirectBasedOnRole($row->role);
                    return;
                }

                $data['errors']['email'] = "Wrong email or password";
            }
        }

        $this->view('login', $data);
    }

    private function redirectBasedOnRole(string $role): void
    {
        switch ($role) {
            case 'buyer':
                redirect('buyerDashboard');
                break;
            case 'farmer':
                redirect('farmerDashboard');
                break;
            case 'transporter':
                redirect('transporterDashboard');
                break;
            case 'admin':
            case 'superadmin':
                redirect('adminDashboard');
                break;
            default:
                redirect('home');
                break;
        }
    }
}
