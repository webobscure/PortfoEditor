<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\FontController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\PortfolioSectionController;
use App\Http\Controllers\Api\PreviewController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\ThemeController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
|
| Session-authenticated (Sanctum SPA). Rate limits are tightened on the
| endpoints where abuse actually costs something: credentials, image
| processing and export builds.
|
*/

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// The gallery must be browsable before signing up.
Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
Route::get('/templates/{template}/styles.css', [TemplateController::class, 'styles'])->name('templates.styles');
Route::get('/templates/{template}/demo', [TemplateController::class, 'demo'])->name('templates.demo');
Route::get('/templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');

Route::get('/theme/options', ThemeController::class)->name('theme.options');
// Named so renderers can build the font base URL in one place.
Route::get('/fonts', [FontController::class, 'index'])->name('fonts.index');
Route::get('/fonts/{file}', [FontController::class, 'show'])
    ->where('file', '[A-Za-z0-9._-]+\.woff2')
    ->name('fonts.show');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('portfolios', PortfolioController::class);

    Route::get('portfolios/{portfolio}/preview', PreviewController::class)->name('portfolios.preview');

    Route::get('portfolios/{portfolio}/sections', [PortfolioSectionController::class, 'index']);
    Route::post('portfolios/{portfolio}/sections', [PortfolioSectionController::class, 'store']);
    Route::post('portfolios/{portfolio}/sections/reorder', [PortfolioSectionController::class, 'reorder']);
    Route::patch('portfolios/{portfolio}/sections/{section}', [PortfolioSectionController::class, 'update']);
    Route::delete('portfolios/{portfolio}/sections/{section}', [PortfolioSectionController::class, 'destroy']);

    Route::get('portfolios/{portfolio}/media', [UploadController::class, 'index']);

    Route::post('/uploads', [UploadController::class, 'store'])->middleware('throttle:60,1');
    Route::delete('/media/{media}', [UploadController::class, 'destroy']);

    Route::post('portfolios/{portfolio}/export', [ExportController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('exports.store');
    Route::get('exports/{export}', [ExportController::class, 'show'])->name('exports.show');
    Route::get('exports/{export}/download', [ExportController::class, 'download'])->name('exports.download');
});
