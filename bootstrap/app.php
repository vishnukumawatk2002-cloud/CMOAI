<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\CheckPlanLimit;
use App\Http\Middleware\EnsureBrandAccess;
use App\Http\Middleware\EnsureBrandSelected;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'verified', 'subscription.active'])
                ->prefix('app')
                ->name('app.')
                ->group(base_path('routes/web/app.php'));

            Route::middleware(['web'])
                ->group(base_path('routes/web/onboarding.php'));

            Route::middleware(['web'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
            'verified' => EnsureEmailIsVerified::class,
            'brand.selected' => EnsureBrandSelected::class,
            'brand.access' => EnsureBrandAccess::class,
            'plan.limit' => CheckPlanLimit::class,
            'subscription.active' => EnsureSubscriptionActive::class,
            'admin' => AdminAuthenticate::class,
            'admin.permission' => \App\Http\Middleware\CheckAdminPermission::class,
            'api.user' => \App\Http\Middleware\EnsureApiUser::class,
            'api.admin' => \App\Http\Middleware\EnsureApiAdmin::class,
            'api.subscription' => \App\Http\Middleware\EnsureApiSubscriptionActive::class,
            'api.brand' => \App\Http\Middleware\ResolveApiBrand::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'onboarding/payment/payu/success',
            'onboarding/payment/payu/failure',
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ], append: [
            \App\Http\Middleware\SecurityHeaders::class,
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Forbidden.',
                ], 403);
            }
        });

        $exceptions->render(function (\App\Domain\Exceptions\PlanLimitExceededException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [
                        'limit_type' => [$e->limitType],
                        'limit' => [$e->limit],
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (\App\Domain\Exceptions\BrandAccessDeniedException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Brand access denied.',
                ], 403);
            }
        });
    })->create();
