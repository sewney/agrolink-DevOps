<?php

class TransporterEarningsController
{
    use Controller;

    public function index()
    {
        if (!requireRole('transporter')) {
            return;
        }

        $data = [];
        $data['pageTitle'] = 'Earnings';
        $data['activePage'] = 'earnings';
        $data['username'] = authUserName();
        $data['pageStyles'] = ['dashboard.css', 'earnings.css'];
        $data['pageScript'] = 'transporterEarnings.js';
        $data['contentView'] = '../app/views/transporter/transporterEarnings.view.php';

        $this->view('transporter/transporterSidebar', $data);
    }
}
