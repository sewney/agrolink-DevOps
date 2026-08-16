<?php

class FarmerEarningsController
{
    use Controller;

    protected $farmerModel;
    protected $orderModel;

    public function __construct()
    {
        $this->farmerModel = new FarmerModel();
        $this->orderModel = new OrderModel();
    }

    /**
     * Display farmer earnings page
     */
    public function index()
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $summary = $this->getEarningsSummary((int)authUserId(), 100);

        $data = [
            'pageTitle' => 'Earnings',
            'activePage' => 'earnings',
            'pageStyles' => ['earnings.css'],
            'totalEarnings' => $summary['totalEarnings'],
            'monthlyEarnings' => $summary['monthlyEarnings'],
            'earningsByProduct' => $summary['earningsByProduct'],
            'recentEarnings' => $summary['recentEarnings'],
            'earningsStats' => $summary['earningsStats'],
            'weeklyOrders' => $summary['weeklyOrders'],
            'monthlyChangePercent' => $summary['monthlyChangePercent'],
            'dailyChart' => $summary['dailyChart'],
            'monthlyChart' => $summary['monthlyChart'],
            'yearlyChart' => $summary['yearlyChart'],
            'contentView' => '../app/views/farmer/farmerEarnings.view.php',
            'pageScript' => 'farmerEarnings.js'
        ];

        $this->view('farmer/farmerSidebar', $data);
    }

    /**
     * Printable report page (use browser "Save as PDF").
     */
    public function report()
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $summary = $this->getEarningsSummary((int)authUserId(), 50);
        $farmerName = authUserName() ?? 'Farmer';
        $generatedAt = date('Y-m-d H:i:s');
        $autoPrint = isset($_GET['print']) ? '1' : '0';

        include '../app/views/farmer/farmerEarningsReport.view.php';
        exit;
    }

    /**
     * Get earnings data for a specific period (AJAX)
     */
    public function getEarningsByPeriod()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!hasRole('farmer')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $userId = authUserId();
        $period = $_GET['period'] ?? 'month'; // month, year, all

        switch ($period) {
            case 'week':
                $earnings = $this->farmerModel->getWeeklyEarnings($userId);
                break;
            case 'year':
                $earnings = $this->farmerModel->getYearlyEarnings($userId);
                break;
            case 'all':
                $earnings = $this->farmerModel->getTotalEarnings($userId);
                break;
            default:
                $earnings = $this->farmerModel->getMonthlyEarnings($userId);
        }

        echo json_encode([
            'success' => true,
            'earnings' => $earnings,
            'period' => $period
        ]);
        exit;
    }

    private function getEarningsSummary(int $userId, int $recentLimit = 10): array
    {
        $monthlyChart = $this->buildLast12MonthChart($userId);
        $dailyChart = $this->buildLastNDaysChart($userId, 7);
        $yearlyChart = $this->buildLastNYearsChart($userId, 5);
        $currentMonthEarnings = (float)($monthlyChart[count($monthlyChart) - 1]['earnings'] ?? 0);
        $previousMonthEarnings = (float)($monthlyChart[count($monthlyChart) - 2]['earnings'] ?? 0);
        $monthlyChange = 0.0;
        if ($previousMonthEarnings > 0) {
            $monthlyChange = (($currentMonthEarnings - $previousMonthEarnings) / $previousMonthEarnings) * 100;
        }

        return [
            'totalEarnings' => (float)$this->farmerModel->getTotalEarnings($userId),
            'monthlyEarnings' => (float)$this->farmerModel->getMonthlyEarnings($userId),
            'earningsByProduct' => $this->farmerModel->getEarningsByProduct($userId),
            'recentEarnings' => $this->farmerModel->getRecentEarnings($userId, $recentLimit),
            'earningsStats' => $this->farmerModel->getEarningsStats($userId),
            'weeklyOrders' => $this->farmerModel->getWeeklyOrdersCount($userId),
            'monthlyChart' => $monthlyChart,
            'dailyChart' => $dailyChart,
            'yearlyChart' => $yearlyChart,
            'monthlyChangePercent' => $monthlyChange,
        ];
    }

    private function buildLast12MonthChart(int $userId): array
    {
        $raw = $this->farmerModel->getMonthlyEarningsChart($userId);
        $map = [];
        foreach ($raw as $row) {
            $map[(string)$row->month] = (float)$row->earnings;
        }

        $result = [];
        $cursor = new DateTime('first day of this month');
        $cursor->modify('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $key = $cursor->format('Y-m');
            $result[] = [
                'month' => $key,
                'label' => $cursor->format('M'),
                'earnings' => $map[$key] ?? 0.0,
            ];
            $cursor->modify('+1 month');
        }

        return $result;
    }

    private function buildLastNDaysChart(int $userId, int $days): array
    {
        $days = max(1, $days);
        $raw = $this->farmerModel->getDailyEarningsChart($userId, $days);
        $map = [];
        foreach ($raw as $row) {
            $map[(string)$row->day_key] = (float)$row->earnings;
        }

        $result = [];
        $cursor = new DateTime('today');
        $cursor->modify('-' . ($days - 1) . ' days');
        for ($i = 0; $i < $days; $i++) {
            $key = $cursor->format('Y-m-d');
            $result[] = [
                'key' => $key,
                'label' => $cursor->format('D'),
                'fullLabel' => $cursor->format('M d'),
                'earnings' => $map[$key] ?? 0.0,
            ];
            $cursor->modify('+1 day');
        }

        return $result;
    }

    private function buildLastNYearsChart(int $userId, int $years): array
    {
        $years = max(1, $years);
        $raw = $this->farmerModel->getYearlyEarningsChart($userId, $years);
        $map = [];
        foreach ($raw as $row) {
            $map[(string)$row->year_key] = (float)$row->earnings;
        }

        $result = [];
        $currentYear = (int)date('Y');
        $startYear = $currentYear - ($years - 1);
        for ($year = $startYear; $year <= $currentYear; $year++) {
            $result[] = [
                'key' => (string)$year,
                'label' => (string)$year,
                'fullLabel' => (string)$year,
                'earnings' => $map[(string)$year] ?? 0.0,
            ];
        }

        return $result;
    }
}
