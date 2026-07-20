<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(base_path('routes/api/v1.php'));

Route::prefix('admin/v1')->group(base_path('routes/api/admin/v1.php'));
