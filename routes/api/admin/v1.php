<?php

use App\Http\Controllers\Api\Admin\V1\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\V1\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\V1\PermissionController as AdminPermissionController;
use App\Http\Controllers\Api\Admin\V1\PlanController as AdminPlanController;
use App\Http\Controllers\Api\Admin\V1\RoleController as AdminRoleController;
use App\Http\Controllers\Api\Admin\V1\SettingController as AdminSettingController;
use App\Http\Controllers\Api\Admin\V1\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('throttle:api-auth')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'api.admin'])->group(function () {
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
    Route::get('/auth/me', [AdminAuthController::class, 'me']);

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('admin.permission:dashboard.view');

    Route::prefix('users')->middleware('admin.permission:users.view')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/{user}', [AdminUserController::class, 'show']);
        Route::put('/{user}', [AdminUserController::class, 'update'])->middleware('admin.permission:users.edit');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->middleware('admin.permission:users.delete');
    });

    Route::prefix('roles')->middleware('admin.permission:roles.view')->group(function () {
        Route::get('/', [AdminRoleController::class, 'index']);
        Route::get('/permissions-list', [AdminRoleController::class, 'permissions']);
        Route::post('/', [AdminRoleController::class, 'store'])->middleware('admin.permission:roles.create');
        Route::get('/{role}', [AdminRoleController::class, 'show']);
        Route::put('/{role}', [AdminRoleController::class, 'update'])->middleware('admin.permission:roles.edit');
        Route::delete('/{role}', [AdminRoleController::class, 'destroy'])->middleware('admin.permission:roles.delete');
    });

    Route::prefix('permissions')->middleware('admin.permission:permissions.view')->group(function () {
        Route::get('/', [AdminPermissionController::class, 'index']);
        Route::post('/', [AdminPermissionController::class, 'store'])->middleware('admin.permission:permissions.create');
        Route::get('/{permission}', [AdminPermissionController::class, 'show']);
        Route::put('/{permission}', [AdminPermissionController::class, 'update'])->middleware('admin.permission:permissions.edit');
        Route::delete('/{permission}', [AdminPermissionController::class, 'destroy'])->middleware('admin.permission:permissions.delete');
    });

    Route::prefix('settings')->middleware('admin.permission:settings.view')->group(function () {
        Route::get('/', [AdminSettingController::class, 'index']);
        Route::put('/', [AdminSettingController::class, 'update'])->middleware('admin.permission:settings.edit');
    });

    Route::prefix('plans')->middleware('admin.permission:plans.view')->group(function () {
        Route::get('/', [AdminPlanController::class, 'index']);
        Route::post('/', [AdminPlanController::class, 'store'])->middleware('admin.permission:plans.create');
        Route::get('/{plan}', [AdminPlanController::class, 'show']);
        Route::put('/{plan}', [AdminPlanController::class, 'update'])->middleware('admin.permission:plans.edit');
        Route::delete('/{plan}', [AdminPlanController::class, 'destroy'])->middleware('admin.permission:plans.delete');
    });
});
