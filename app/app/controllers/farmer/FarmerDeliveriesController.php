<?php

class FarmerDeliveriesController
{
    use Controller;

    protected $farmerModel;

    public function __construct()
    {
        $this->farmerModel = new FarmerModel();
    }

    public function index()
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $farmerId = (int)authUserId();
        $filter = strtolower(trim($_GET['status'] ?? 'running'));

        $deliveries = $this->farmerModel->getFarmerDeliveryRequests($farmerId);

        if ($filter === 'running') {
            $deliveries = array_values(array_filter($deliveries, function ($delivery) {
                return in_array(($delivery->status ?? ''), ['accepted', 'in_transit'], true);
            }));
        } elseif ($filter !== 'all') {
            $deliveries = array_values(array_filter($deliveries, function ($delivery) use ($filter) {
                return ($delivery->status ?? '') === $filter;
            }));
        }

        $summary = $this->farmerModel->getFarmerDeliverySummary($farmerId);

        $data = [
            'pageTitle' => 'Deliveries',
            'activePage' => 'deliveries',
            'pageStyles' => ['deliveries.css'],
            'filter' => $filter,
            'deliverySummary' => $summary,
            'deliveries' => $deliveries,
            'contentView' => '../app/views/farmer/farmerDeliveries.view.php'
        ];

        $this->view('farmer/farmerSidebar', $data);
    }
}
