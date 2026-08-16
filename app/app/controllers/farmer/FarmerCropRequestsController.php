<?php
class FarmerCropRequestsController
{
    use Controller;

    public function index()
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        // Load CropRequestModel to fetch all crop requests
        $cropRequestModel = new CropRequestModel();

        // Get all crop requests from buyers (not filtered by farmer - farmers see all open requests)
        $requests = $cropRequestModel->findAll();

        // Ensure it's an array
        if (!is_array($requests)) {
            $requests = [];
        }

        $data = [
            'pageTitle' => 'Crop Requests',
            'activePage' => 'crop-requests',
            'pageStyles' => ['crop-requests.css'],
            'contentView' => '../app/views/farmer/cropRequests.view.php',
            'requests' => $requests
        ];

        $this->view('farmer/farmerSidebar', $data);
    }

    public function show($id)
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $cropRequestModel = new CropRequestModel();
        $request = $cropRequestModel->getRequestById($id);

        if (!$request) {
            $_SESSION['error'] = 'Crop request not found';
            return redirect('farmercroprequests');
        }

        $data = [
            'pageTitle' => 'Crop Request Details',
            'activePage' => 'crop-requests',
            'pageStyles' => ['crop-requests.css'],
            'contentView' => '../app/views/farmer/cropRequestDetails.view.php',
            'request' => $request
        ];

        $this->view('farmer/farmerSidebar', $data);
    }

    public function accept($id)
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $cropRequestModel = new CropRequestModel();
        $request = $cropRequestModel->getRequestById($id);

        if (!$request) {
            $_SESSION['error'] = 'Crop request not found';
            return redirect('farmercroprequests');
        }

        // Update status to accepted
        $updated = $cropRequestModel->update($id, ['status' => 'accepted']);

        if ($updated) {
            $_SESSION['success'] = 'Crop request accepted successfully. Continue by creating a matching product listing.';
        } else {
            $_SESSION['error'] = 'Failed to accept crop request';
        }

        return redirect('farmercroprequests/show/' . $id);
    }

    public function reject($id)
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $cropRequestModel = new CropRequestModel();
        $request = $cropRequestModel->getRequestById($id);

        if (!$request) {
            $_SESSION['error'] = 'Crop request not found';
            return redirect('farmercroprequests');
        }

        // Update status to declined
        $updated = $cropRequestModel->update($id, ['status' => 'declined']);

        if ($updated) {
            $_SESSION['success'] = 'Crop request declined';
        } else {
            $_SESSION['error'] = 'Failed to reject crop request';
        }

        return redirect('farmercroprequests');
    }
}
