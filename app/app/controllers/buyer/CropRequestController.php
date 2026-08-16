<?php

class CropRequestController
{
    use Controller;

    protected $cropRequestModel;

    public function __construct()
    {
        $this->cropRequestModel = new CropRequestModel();
    }

    /**
     * Display list of crop requests for the logged-in buyer
     */
    public function index()
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $user_id = authUserId();
        $requests = $this->cropRequestModel->getRequestsByBuyer($user_id);

        // Ensure $requests is an array (Database->query returns 1 when no rows)
        if (!is_array($requests) && !is_object($requests)) {
            $requests = [];
        }

        $data = [
            'requests' => $requests,
            'pageTitle' => 'My Crop Requests',
            'activePage' => 'requests',
            'contentView' => 'buyer/cropRequest.view.php',
            'pageStyles' => 'cropRequest.css',
            'pageScript' => 'cropRequest.js'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    /**
     * Display create crop request form
     */
    public function create()
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formData = [
                'buyer_id' => authUserId(),
                'crop_name' => $_POST['crop_name'] ?? '',
                'quantity' => $_POST['quantity'] ?? '',
                'target_price' => $_POST['target_price'] ?? '',
                'delivery_date' => $_POST['delivery_date'] ?? '',
                'location' => $_POST['location'] ?? '',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($this->cropRequestModel->validate($formData)) {
                if ($this->cropRequestModel->insert($formData)) {
                    $_SESSION['success'] = "Crop request created successfully!";
                    redirect('croprequest');
                }
            } else {
                $_SESSION['errors'] = $this->cropRequestModel->errors;
            }
        }

        $data = [
            'action' => 'create',
            'pageTitle' => 'New Crop Request',
            'activePage' => 'requests',
            'contentView' => 'buyer/cropRequest.view.php',
            'pageStyles' => 'cropRequest.css',
            'pageScript' => 'cropRequest.js'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    /**
     * Display edit crop request form
     */
    public function edit($id = '')
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $request = $this->cropRequestModel->getRequestById($id);

        if (!$request || $request->buyer_id != authUserId()) {
            redirect('croprequest');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formData = [
                'crop_name' => $_POST['crop_name'] ?? '',
                'quantity' => $_POST['quantity'] ?? '',
                'target_price' => $_POST['target_price'] ?? '',
                'delivery_date' => $_POST['delivery_date'] ?? '',
                'location' => $_POST['location'] ?? '',
                'status' => $_POST['status'] ?? $request->status
            ];

            if ($this->cropRequestModel->validate($formData)) {
                if ($this->cropRequestModel->update($id, $formData)) {
                    $_SESSION['success'] = "Crop request updated successfully!";
                    redirect('croprequest');
                }
            } else {
                $_SESSION['errors'] = $this->cropRequestModel->errors;
            }
        }

        $data = [
            'request' => $request,
            'action' => 'edit',
            'pageTitle' => 'Edit Crop Request',
            'activePage' => 'requests',
            'contentView' => 'buyer/cropRequest.view.php',
            'pageStyles' => 'cropRequest.css',
            'pageScript' => 'cropRequest.js'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    /**
     * View single crop request
     */
    public function show($id = '')
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $request = $this->cropRequestModel->getRequestById($id);

        if (!$request || $request->buyer_id != authUserId()) {
            redirect('croprequest');
            return;
        }

        $data = [
            'request' => $request,
            'pageTitle' => 'Crop Request Details',
            'activePage' => 'requests',
            'contentView' => 'buyer/cropRequest.view.php',
            'pageStyles' => 'cropRequest.css',
            'pageScript' => 'cropRequest.js'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    /**
     * Delete crop request
     */
    public function delete($id = '')
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $request = $this->cropRequestModel->getRequestById($id);

        if (!$request || $request->buyer_id != authUserId()) {
            redirect('croprequest');
            return;
        }

        if ($this->cropRequestModel->delete($id)) {
            $_SESSION['success'] = "Crop request deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete crop request";
        }

        redirect('croprequest');
    }

    /**
     * Change request status (for buyers and farmers)
     */
    public function updateStatus($id = '')
    {
        header('Content-Type: application/json');

        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $request = $this->cropRequestModel->getRequestById($id);

        if (!$request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }

        if ($request->buyer_id != authUserId() && authUserRole() !== 'farmer') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'] ?? '';

        if (empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Status is required']);
            exit;
        }

        if ($this->cropRequestModel->update($id, ['status' => $status])) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    }
}
