<?php

class TransporterDeliveriesController
{
    use Controller;

    public function index()
    {
        if (!requireRole('transporter')) {
            return;
        }

        $data = [];
        $data['pageTitle'] = 'Deliveries';
        $data['activePage'] = 'deliveries';
        $data['username'] = authUserName();
        $data['pageStyles'] = ['dashboard.css', 'deliveries.css'];
        $data['pageScript'] = 'transporterDeliveries.js';
        $data['contentView'] = '../app/views/transporter/transporterDeliveries.view.php';

        $this->view('transporter/transporterSidebar', $data);
    }
}
