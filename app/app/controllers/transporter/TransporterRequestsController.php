<?php

class TransporterRequestsController
{
    use Controller;

    public function index()
    {
        if (!requireRole('transporter')) {
            return;
        }

        $data = [];
        $data['pageTitle'] = 'Requests';
        $data['activePage'] = 'requests';
        $data['username'] = authUserName();
        $data['pageStyles'] = ['dashboard.css', 'requests.css'];
        $data['pageScript'] = 'transporterRequests.js';
        $data['contentView'] = '../app/views/transporter/transporterRequests.view.php';

        $this->view('transporter/transporterSidebar', $data);
    }
}
