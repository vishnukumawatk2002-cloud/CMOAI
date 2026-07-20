<?php

use App\Http\Controllers\Web\Marketing\LandingController;
use App\Http\Controllers\Web\Marketing\LegalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/publish-media/{asset}', [\App\Http\Controllers\Web\PublishMediaController::class, 'show'])
    ->middleware('signed')
    ->name('publish-media.show');

Route::controller(LegalController::class)->group(function () {
    Route::get('/privacy-policy', 'privacy')->name('legal.privacy');
    Route::get('/terms', 'terms')->name('legal.terms');
    Route::get('/data-deletion', 'dataDeletion')->name('legal.data-deletion');
});

Route::get('/dashboard', function () {
    return redirect()->route('app.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
