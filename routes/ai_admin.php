<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AIAdminController;

// Append this file in routes/web.php by adding at the bottom:
// require __DIR__ . '/ai_admin.php';

Route::middleware(['auth', 'admin'])->prefix('admin/ai')->name('admin.ai.')->group(function () {
    Route::get('/services/search',   [AIAdminController::class, 'searchServices'])->name('services.search');
    Route::get('/services/lookup',   [AIAdminController::class, 'lookupBySupplierServiceId'])->name('services.lookup');
    Route::get('/services',          [AIAdminController::class, 'servicesIndex'])->name('services.index');
    Route::post('/services/bulk',    [AIAdminController::class, 'bulkAction'])->name('services.bulk');

    Route::post('/quality/score-all',        [AIAdminController::class, 'scoreAllServices'])->name('quality.score-all');
    Route::post('/services/{service}/score', [AIAdminController::class, 'scoreService'])->name('services.score');
    Route::get('/quality/low',               [AIAdminController::class, 'lowQualityServices'])->name('quality.low');

    Route::get('/duplicates',          [AIAdminController::class, 'duplicates'])->name('duplicates.index');
    Route::post('/duplicates/resolve', [AIAdminController::class, 'resolveDuplicates'])->name('duplicates.resolve');

    Route::get('/pricing',          [AIAdminController::class, 'pricingIndex'])->name('pricing.index');
    Route::post('/pricing/global',  [AIAdminController::class, 'updateGlobalMargin'])->name('pricing.global');

    Route::get('/suppliers/health',         [AIAdminController::class, 'supplierHealth'])->name('suppliers.health');
    Route::post('/suppliers/health-check',  [AIAdminController::class, 'runHealthCheck'])->name('suppliers.health-check');

    Route::post('/services/{service}/analyze',        [AIAdminController::class, 'analyzeService'])->name('services.analyze');
    Route::post('/services/{service}/generate-title', [AIAdminController::class, 'generateTitle'])->name('services.generate-title');
    Route::post('/services/{service}/apply-title',    [AIAdminController::class, 'applyTitle'])->name('services.apply-title');

    Route::post('/users/{user}/wallet',         [AIAdminController::class, 'walletAction'])->name('wallet.action');
    Route::get('/users/{user}/wallet/history',  [AIAdminController::class, 'walletHistory'])->name('wallet.history');
});
