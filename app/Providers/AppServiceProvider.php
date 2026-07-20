<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Observers\DashboardCacheObserver;
use App\Socialite\FacebookBusinessProvider;
use App\Socialite\SnapchatProvider;
use App\Socialite\YouTubeProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerObservers();
        $this->registerSocialiteProviders();
    }

    protected function registerSocialiteProviders(): void
    {
        Socialite::extend('facebook', function ($app) {
            $config = $app['config']['services.facebook'];

            return Socialite::buildProvider(FacebookBusinessProvider::class, $config);
        });

        Socialite::extend('youtube', function ($app) {
            $config = $app['config']['services.youtube'];

            return Socialite::buildProvider(YouTubeProvider::class, $config);
        });

        Socialite::extend('snapchat', function ($app) {
            $config = $app['config']['services.snapchat'];

            return Socialite::buildProvider(SnapchatProvider::class, $config);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('content-generate', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    }

    protected function registerObservers(): void
    {
        $observer = DashboardCacheObserver::class;

        User::observe($observer);
        Order::observe($observer);
        Brand::observe($observer);
        Subscription::observe($observer);
    }
}
