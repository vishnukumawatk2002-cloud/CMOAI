<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Application\Services\Admin\DashboardService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(): JsonResponse
    {
        return $this->success([
            'stats' => $this->dashboard->stats(),
            'charts' => $this->dashboard->monthlyCharts(),
            'activities' => $this->dashboard->recentActivities()->map(fn ($a) => [
                'type' => $a['type'],
                'message' => $a['message'],
                'created_at' => $a['created_at']->toIso8601String(),
            ]),
            'latest_users' => UserResource::collection($this->dashboard->latestUsers()),
        ]);
    }
}
