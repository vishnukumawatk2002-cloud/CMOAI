<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\BrandSettingsController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\SocialAccountController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('throttle:api-auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{plan}', [PlanController::class, 'show']);

Route::middleware(['auth:sanctum', 'api.user'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    Route::get('/subscription', [SubscriptionController::class, 'show']);
    Route::post('/subscription', [SubscriptionController::class, 'store']);

    Route::get('/brands', [BrandController::class, 'index']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::get('/brands/{brand}', [BrandController::class, 'show']);
    Route::put('/brands/{brand}', [BrandController::class, 'update']);
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
    Route::post('/brands/{brand}/switch', [BrandController::class, 'switch']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::middleware('api.subscription')->group(function () {
        Route::middleware('api.brand')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);

            Route::get('/brand/settings', [BrandSettingsController::class, 'show']);
            Route::put('/brand/settings', [BrandSettingsController::class, 'update']);

            Route::get('/content', [ContentController::class, 'index']);
            Route::post('/content/generate', [ContentController::class, 'generate'])
                ->middleware(['plan.limit:posts_per_month', 'throttle:content-generate']);
            Route::get('/content/{contentItem}', [ContentController::class, 'show']);
            Route::put('/content/{contentItem}', [ContentController::class, 'update']);
            Route::delete('/content/{contentItem}', [ContentController::class, 'destroy']);

            Route::get('/schedule', [ScheduleController::class, 'index']);
            Route::post('/schedule', [ScheduleController::class, 'store']);
            Route::delete('/schedule/{scheduledPost}', [ScheduleController::class, 'destroy']);

            Route::get('/social-accounts', [SocialAccountController::class, 'index']);
            Route::get('/social-accounts/{socialAccount}', [SocialAccountController::class, 'show']);
            Route::delete('/social-accounts/{socialAccount}', [SocialAccountController::class, 'destroy']);
        });
    });
});
