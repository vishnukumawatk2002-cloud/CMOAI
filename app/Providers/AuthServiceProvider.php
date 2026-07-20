<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Brand::class => \App\Policies\BrandPolicy::class,
        \App\Models\ContentItem::class => \App\Policies\ContentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
