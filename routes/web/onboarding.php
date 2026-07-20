<?php

use App\Http\Controllers\Web\Brand\BrandController;
use App\Http\Controllers\Web\Brand\BrandWizardController;
use App\Http\Controllers\Web\Brand\SocialConnectController;
use App\Http\Controllers\Web\Onboarding\PayUPaymentController;
use App\Http\Controllers\Web\Onboarding\PlanController;
use Illuminate\Support\Facades\Route;

// PayU posts back from another domain — session cookie often missing, so no auth middleware.
Route::prefix('onboarding')->name('onboarding.')->group(function () {
    Route::match(['get', 'post'], '/payment/payu/success', [PayUPaymentController::class, 'success'])->name('payment.payu.success');
    Route::match(['get', 'post'], '/payment/payu/failure', [PayUPaymentController::class, 'failure'])->name('payment.payu.failure');
});

Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/brand/create', [BrandController::class, 'create'])->name('brand.create');
    Route::post('/brand', [BrandController::class, 'store'])->name('brand.store');

    Route::get('/plans', [PlanController::class, 'index'])->name('plan');
    Route::post('/plans/{slug}', [PlanController::class, 'subscribe'])->name('plan.subscribe');

    Route::get('/wizard', [BrandWizardController::class, 'index'])->name('wizard');
    Route::post('/wizard/step/{step}', [BrandWizardController::class, 'saveStep'])->name('wizard.step');
    Route::get('/wizard/assets/{asset}', [BrandWizardController::class, 'showAsset'])->name('wizard.asset');

    Route::get('/social/{platform}/connect', [SocialConnectController::class, 'redirect'])->name('social.connect');
    Route::get('/social/{platform}/callback', [SocialConnectController::class, 'callback'])->name('social.callback');
});
