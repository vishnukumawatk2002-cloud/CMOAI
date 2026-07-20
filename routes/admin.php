<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('admin')->group(function () {
    Route::redirect('/', '/admin/dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('admin.permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('users')->name('users.')->middleware('admin.permission:users.view')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}/edit', [UserController::class, 'edit'])
            ->middleware('admin.permission:users.edit')
            ->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])
            ->middleware('admin.permission:users.edit')
            ->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])
            ->middleware('admin.permission:users.delete')
            ->name('destroy');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
    });

    Route::prefix('brands')->name('brands.')->middleware('admin.permission:brands.view')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->name('index');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])
            ->middleware('admin.permission:brands.delete')
            ->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->middleware('admin.permission:roles.view')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])
            ->middleware('admin.permission:roles.create')
            ->name('create');
        Route::post('/', [RoleController::class, 'store'])
            ->middleware('admin.permission:roles.create')
            ->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])
            ->middleware('admin.permission:roles.edit')
            ->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])
            ->middleware('admin.permission:roles.edit')
            ->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])
            ->middleware('admin.permission:roles.delete')
            ->name('destroy');
    });

    Route::prefix('permissions')->name('permissions.')->middleware('admin.permission:permissions.view')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/create', [PermissionController::class, 'create'])
            ->middleware('admin.permission:permissions.create')
            ->name('create');
        Route::post('/', [PermissionController::class, 'store'])
            ->middleware('admin.permission:permissions.create')
            ->name('store');
        Route::get('/{permission}/edit', [PermissionController::class, 'edit'])
            ->middleware('admin.permission:permissions.edit')
            ->name('edit');
        Route::put('/{permission}', [PermissionController::class, 'update'])
            ->middleware('admin.permission:permissions.edit')
            ->name('update');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])
            ->middleware('admin.permission:permissions.delete')
            ->name('destroy');
    });

    Route::prefix('settings')->name('settings.')->middleware('admin.permission:settings.view')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/', [SettingController::class, 'update'])
            ->middleware('admin.permission:settings.edit')
            ->name('update');
    });

    Route::prefix('plans')->name('plans.')->middleware('admin.permission:plans.view')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])
            ->middleware('admin.permission:plans.create')
            ->name('create');
        Route::post('/', [PlanController::class, 'store'])
            ->middleware('admin.permission:plans.create')
            ->name('store');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])
            ->middleware('admin.permission:plans.edit')
            ->name('edit');
        Route::put('/{plan}', [PlanController::class, 'update'])
            ->middleware('admin.permission:plans.edit')
            ->name('update');
        Route::patch('/{plan}/toggle-active', [PlanController::class, 'toggleActive'])
            ->middleware('admin.permission:plans.edit')
            ->name('toggle-active');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])
            ->middleware('admin.permission:plans.delete')
            ->name('destroy');
    });
});
