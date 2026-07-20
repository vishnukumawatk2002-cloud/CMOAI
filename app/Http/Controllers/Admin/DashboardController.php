<?php

namespace App\Http\Controllers\Admin;

use App\Application\Services\Admin\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => $this->dashboard->stats(),
            'charts' => $this->dashboard->monthlyCharts(),
            'activities' => $this->dashboard->recentActivities(),
            'latestUsers' => $this->dashboard->latestUsers(),
        ]);
    }
}
