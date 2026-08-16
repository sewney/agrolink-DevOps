<?php
class FarmerDashboardController
{
    use Controller;

    public function index()
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $farmerId = (int)authUserId();

        $farmerModel = new FarmerModel();
        $productsModel = new ProductsModel();
        $cropRequestModel = new CropRequestModel();

        $farmerProducts = $productsModel->getByFarmer($farmerId);
        $farmerOrders = $farmerModel->getFarmerOrders($farmerId);
        $deliverySummary = $farmerModel->getFarmerDeliverySummary($farmerId);
        $cropRequests = $cropRequestModel->findAll();
        if (!is_array($cropRequests)) {
            $cropRequests = [];
        }

        $activeProducts = 0;
        foreach ($farmerProducts as $product) {
            if ((float)($product->quantity ?? 0) > 0) {
                $activeProducts++;
            }
        }

        $activeOrders = 0;
        foreach ($farmerOrders as $order) {
            $status = strtolower((string)($order->status ?? ''));
            if (!in_array($status, ['delivered', 'cancelled', 'pending_payment'], true)) {
                $activeOrders++;
            }
        }

        $monthlyEarnings = (float)$farmerModel->getMonthlyEarnings($farmerId);
        $totalEarnings = (float)$farmerModel->getTotalEarnings($farmerId);
        $topProducts = $farmerModel->getMonthlyEarningsByProduct($farmerId, 3);
        $runningDeliveries = (int)($deliverySummary->accepted_deliveries ?? 0) + (int)($deliverySummary->in_transit_deliveries ?? 0);

        $newCropRequests = 0;
        foreach ($cropRequests as $request) {
            $status = strtolower((string)($request->status ?? 'active'));
            if ($status === '' || $status === 'active') {
                $newCropRequests++;
            }
        }

        $data = [
            'pageTitle' => 'Dashboard',
            'activePage' => 'dashboard',
            'pageStyles' => ['dashboard.css'],
            'contentView' => '../app/views/farmer/farmerDashboard.view.php',
            'pageScript' => 'farmerDashboard.js',
            'activeProducts' => $activeProducts,
            'activeOrders' => $activeOrders,
            'monthlyEarnings' => $monthlyEarnings,
            'totalEarnings' => $totalEarnings,
            'runningDeliveries' => $runningDeliveries,
            'newCropRequests' => $newCropRequests,
            'recentOrders' => array_slice($farmerOrders, 0, 4),
            'topProducts' => $topProducts,
            'deliverySummary' => $deliverySummary,
        ];

        $this->view('farmer/farmerSidebar', $data);
    }
}
