<?php

namespace App\Observers;

use App\Application\Services\Admin\DashboardService;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;

class DashboardCacheObserver
{
    public function created(User|Order|Brand|Subscription $model): void
    {
        DashboardService::clearCache();
    }

    public function updated(User|Order|Brand|Subscription $model): void
    {
        DashboardService::clearCache();
    }

    public function deleted(User|Order|Brand|Subscription $model): void
    {
        DashboardService::clearCache();
    }
}
