<?php

use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\AssetHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/api/sites/{site}/assets', [WelcomeController::class, 'siteAssets'])->name('site.assets');

// Asset History PDF Routes
Route::get('/asset-history/{assetHistory}/pdf', [AssetHistoryController::class, 'downloadPDF'])->name('asset-history.pdf');
Route::get('/asset/{asset}/report', [AssetHistoryController::class, 'generateAssetReport'])->name('asset.report');
Route::get('/asset/{asset}/shift-report', [AssetHistoryController::class, 'generateShiftReport'])->name('asset.shift-report');
Route::get('/asset/{asset}/health-report', [AssetHistoryController::class, 'generateHealthReport'])->name('asset.health-report');
