<?php

use App\Http\Controllers\Web\Brand\BrandController;
use App\Http\Controllers\Web\Brand\BrandContentLibraryController;
use App\Http\Controllers\Web\Brand\BrandPostPlanningController;
use App\Http\Controllers\Web\Brand\BrandAiPostLibraryController;
use App\Http\Controllers\Web\Brand\BrandContentSuggestionsController;
use App\Http\Controllers\Web\Brand\BrandDataSourceController;
use App\Http\Controllers\Web\Brand\BrandKnowledgeBaseController;
use App\Http\Controllers\Web\Brand\BrandSettingsController;
use App\Http\Controllers\Web\Brand\SocialAccountController;
use App\Http\Controllers\Web\Content\ContentController;
use App\Http\Controllers\Web\Dashboard\DashboardController;
use App\Http\Controllers\Web\Schedule\ScheduleController;
use App\Http\Controllers\Web\Analytics\AnalyticsController;
use App\Http\Controllers\Web\Reports\ReportController;
use App\Http\Controllers\Web\Team\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['brand.selected'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/brand/dashboard', [DashboardController::class, 'brand'])->name('brand.dashboard');

    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
    Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::prefix('content')->name('content.')->group(function () {
        Route::get('/generate', [ContentController::class, 'generateForm'])->name('generate');
        Route::post('/generate', [ContentController::class, 'generate'])->middleware("plan.limit:posts_per_month");
        Route::get('/library', [ContentController::class, 'library'])->name('library');
        Route::post('/bulk', [ContentController::class, 'bulk'])->name('bulk');
        Route::get('/{contentItem}/edit', [ContentController::class, 'edit'])->name('edit');
        Route::put('/{contentItem}', [ContentController::class, 'update'])->name('update');
        Route::delete('/{contentItem}', [ContentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('schedule')->name('schedule.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::post('/bulk', [ScheduleController::class, 'bulkSchedule'])->name('bulk');
    });

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    Route::get('/ai-generator', [\App\Http\Controllers\Web\AiGeneratorController::class, 'index'])->name('ai-generator.index');

    Route::prefix('brand')->name('brand.')->group(function () {
        Route::post('/switch/{brandId}', [BrandController::class, 'switch'])->name('switch');
        Route::get('/settings', [BrandSettingsController::class, 'edit'])->name('settings');
        Route::put('/settings', [BrandSettingsController::class, 'update'])->name('settings.update');
        Route::delete('/settings', [BrandSettingsController::class, 'destroy'])->name('settings.destroy');
        Route::get('/data-sources', [BrandDataSourceController::class, 'index'])->name('data-sources');
        Route::get('/knowledge-base', [BrandKnowledgeBaseController::class, 'index'])->name('knowledge-base');
        Route::post('/knowledge-base/regenerate', [BrandKnowledgeBaseController::class, 'regenerate'])->name('knowledge-base.regenerate');
        Route::get('/content-suggestions', [BrandContentSuggestionsController::class, 'index'])->name('content-suggestions');
        Route::post('/content-suggestions/generate', [BrandContentSuggestionsController::class, 'generate'])->name('content-suggestions.generate');
        Route::post('/content-suggestions/regenerate-prompt', [BrandContentSuggestionsController::class, 'regeneratePrompt'])->name('content-suggestions.regenerate-prompt');
        Route::get('/content-library', [BrandContentLibraryController::class, 'index'])->name('content-library');
        Route::post('/content-library/store', [BrandContentLibraryController::class, 'store'])->name('content-library.store');
        Route::post('/content-library/manual/update', [BrandContentLibraryController::class, 'updateManual'])->name('content-library.update-manual');
        Route::post('/content-library/manual/delete', [BrandContentLibraryController::class, 'destroyManual'])->name('content-library.destroy-manual');
        Route::post('/content-library/manual', [BrandContentLibraryController::class, 'destroyManual']);
        Route::post('/content-library/manual/carousel-slot/delete', [BrandContentLibraryController::class, 'destroyManualCarouselSlot'])->name('content-library.destroy-carousel-slot');
        Route::post('/content-library/ai/delete', [BrandContentLibraryController::class, 'destroyAi'])->name('content-library.destroy-ai');
        Route::get('/post-planning', [BrandPostPlanningController::class, 'index'])->name('post-planning');
        Route::post('/post-planning/store', [BrandPostPlanningController::class, 'store'])->name('post-planning.store');
        Route::post('/post-planning/manual/update', [BrandPostPlanningController::class, 'updateManual'])->name('post-planning.update-manual');
        Route::post('/post-planning/manual/delete', [BrandPostPlanningController::class, 'destroyManual'])->name('post-planning.destroy-manual');
        Route::post('/post-planning/manual', [BrandPostPlanningController::class, 'destroyManual']);
        Route::post('/post-planning/manual/carousel-slot/delete', [BrandPostPlanningController::class, 'destroyManualCarouselSlot'])->name('post-planning.destroy-carousel-slot');
        Route::post('/post-planning/ai/delete', [BrandPostPlanningController::class, 'destroyAi'])->name('post-planning.destroy-ai');
        Route::post('/post-planning/save', [BrandPostPlanningController::class, 'savePlan'])->name('post-planning.save');
        Route::get('/ai-post-library', [BrandAiPostLibraryController::class, 'index'])->name('ai-post-library');
        Route::get('/ai-post-library/{contentItem}', [BrandAiPostLibraryController::class, 'show'])->name('ai-post-library.show');
        Route::get('/ai-post-library/{contentItem}/edit', [BrandAiPostLibraryController::class, 'edit'])->name('ai-post-library.edit');
        Route::put('/ai-post-library/{contentItem}', [BrandAiPostLibraryController::class, 'update'])->name('ai-post-library.update');
        Route::post('/ai-post-library/{contentItem}/publish', [BrandAiPostLibraryController::class, 'publish'])->name('ai-post-library.publish');
        Route::post('/ai-post-library/{contentItem}/approve', [BrandAiPostLibraryController::class, 'approve'])->name('ai-post-library.approve');
        Route::delete('/ai-post-library/{contentItem}', [BrandAiPostLibraryController::class, 'destroy'])->name('ai-post-library.destroy');
        Route::get('/assets/{asset}', [BrandContentLibraryController::class, 'showAsset'])->name('assets.show');
        Route::get('/social-accounts', [SocialAccountController::class, 'index'])->name('social-accounts');
        Route::post('/social-accounts/connect', [SocialAccountController::class, 'connect'])->name('social-accounts.connect');
        Route::get('/social-accounts/facebook/pages', [SocialAccountController::class, 'selectFacebookPages'])->name('social-accounts.facebook-pages');
        Route::post('/social-accounts/facebook/pages', [SocialAccountController::class, 'storeFacebookPages'])->name('social-accounts.facebook-pages.store');
        Route::get('/social-accounts/instagram/pages', [SocialAccountController::class, 'selectInstagramPage'])->name('social-accounts.instagram-pages');
        Route::post('/social-accounts/instagram/pages', [SocialAccountController::class, 'storeInstagramPage'])->name('social-accounts.instagram-pages.store');
        Route::delete('/social-accounts/{socialAccount}', [SocialAccountController::class, 'destroy'])->name('social-accounts.destroy');
    });
});
