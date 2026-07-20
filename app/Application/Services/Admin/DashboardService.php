<?php

namespace App\Application\Services\Admin;

use App\Models\Brand;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const CACHE_TTL = 300;

    public function stats(): array
    {
        return Cache::remember('admin.dashboard.stats', self::CACHE_TTL, function () {
            $orderStats = Order::query()
                ->where('status', 'paid')
                ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(amount), 0) as total_revenue')
                ->first();

            return [
                'total_users' => User::query()->count(),
                'total_orders' => (int) $orderStats->total_orders,
                'total_revenue' => (float) $orderStats->total_revenue,
            ];
        });
    }

    public function monthlyCharts(int $months = 12): array
    {
        return Cache::remember("admin.dashboard.charts.{$months}", self::CACHE_TTL, function () use ($months) {
            $labels = [];
            $monthKeys = [];

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $labels[] = $date->format('M Y');
                $monthKeys[] = $date->format('Y-m');
            }

            $start = now()->subMonths($months - 1)->startOfMonth();

            $usersByMonth = User::query()
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
                ->where('created_at', '>=', $start)
                ->groupBy('month')
                ->pluck('total', 'month');

            $ordersByMonth = Order::query()
                ->selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month, COUNT(*) as total, COALESCE(SUM(amount), 0) as revenue')
                ->where('status', 'paid')
                ->where('paid_at', '>=', $start)
                ->groupBy('month')
                ->get()
                ->keyBy('month');

            $userCounts = [];
            $orderCounts = [];
            $revenueTotals = [];

            foreach ($monthKeys as $key) {
                $userCounts[] = (int) ($usersByMonth[$key] ?? 0);
                $orderCounts[] = (int) ($ordersByMonth[$key]->total ?? 0);
                $revenueTotals[] = (float) ($ordersByMonth[$key]->revenue ?? 0);
            }

            return [
                'labels' => $labels,
                'users' => $userCounts,
                'orders' => $orderCounts,
                'revenue' => $revenueTotals,
            ];
        });
    }

    public function recentActivities(int $limit = 8): Collection
    {
        return Cache::remember("admin.dashboard.activities.{$limit}", 60, function () use ($limit) {
            $activities = collect();

            User::query()->latest()->take($limit)->get(['id', 'first_name', 'last_name', 'created_at'])
                ->each(fn (User $user) => $activities->push([
                    'type' => 'user_registered',
                    'message' => "{$user->full_name} registered",
                    'icon' => 'user',
                    'color' => 'primary',
                    'created_at' => $user->created_at,
                ]));

            Order::query()->with('user:id,first_name,last_name')->latest()->take($limit)
                ->get(['id', 'user_id', 'amount', 'created_at'])
                ->each(fn (Order $order) => $activities->push([
                    'type' => 'order_placed',
                    'message' => "Order #{$order->id} — ₹".number_format($order->amount, 0)." by {$order->user?->full_name}",
                    'icon' => 'cart',
                    'color' => 'success',
                    'created_at' => $order->created_at,
                ]));

            Subscription::query()->with(['user:id,first_name,last_name', 'plan:id,name'])->latest()->take($limit)
                ->get(['id', 'user_id', 'plan_id', 'created_at'])
                ->each(fn (Subscription $sub) => $activities->push([
                    'type' => 'subscription',
                    'message' => "{$sub->user?->full_name} subscribed to {$sub->plan?->name}",
                    'icon' => 'credit-card',
                    'color' => 'info',
                    'created_at' => $sub->created_at,
                ]));

            Brand::query()->with('user:id,first_name,last_name')->latest()->take($limit)
                ->get(['id', 'name', 'user_id', 'created_at'])
                ->each(fn (Brand $brand) => $activities->push([
                    'type' => 'brand_created',
                    'message' => "Brand \"{$brand->name}\" created by {$brand->user?->full_name}",
                    'icon' => 'building',
                    'color' => 'warning',
                    'created_at' => $brand->created_at,
                ]));

            return $activities->sortByDesc('created_at')->take($limit)->values();
        });
    }

    public function latestUsers(int $limit = 5): Collection
    {
        return User::query()
            ->latest()
            ->take($limit)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at']);
    }

    public static function clearCache(): void
    {
        Cache::forget('admin.dashboard.stats');
        Cache::forget('admin.dashboard.charts.12');
        Cache::forget('admin.dashboard.activities.8');
    }
}
