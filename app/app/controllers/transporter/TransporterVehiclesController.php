<?php

class TransporterVehiclesController
{
    use Controller;

    public function index()
    {
        if (!requireRole('transporter')) {
            return;
        }

        $vehicleTypeModel = new VehicleTypeModel();

        $data = [];
        $data['pageTitle'] = 'Vehicles';
        $data['activePage'] = 'vehicles';
        $data['username'] = authUserName();
        $data['pageStyles'] = ['dashboard.css', 'vehicles.css'];
        $data['pageScript'] = 'transporterVehicles.js';
        $data['contentView'] = '../app/views/transporter/transporterVehicles.view.php';
        $data['vehicleTypes'] = $vehicleTypeModel->getActiveTypes();

        $this->view('transporter/transporterSidebar', $data);
    }
}
