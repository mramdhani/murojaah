<?php

use App\Http\Controllers\Api\AyahController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ReviewLogController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SurahController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Murojaah API Routes
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

// Surahs
Route::get('/surahs', [SurahController::class, 'index']);
Route::get('/surahs/{id}', [SurahController::class, 'show']);

// Ayahs
Route::get('/surahs/{surahId}/ayahs', [AyahController::class, 'index']);
Route::get('/surahs/{surahId}/ayahs/{ayahNumber}', [AyahController::class, 'show']);

// Reviews
Route::post('/reviews', [ReviewController::class, 'store']);

// Progress
Route::get('/progress/surahs/{surahId}', [ProgressController::class, 'show']);

// Review Logs
Route::get('/review-logs', [ReviewLogController::class, 'index']);

// Sessions
Route::post('/sessions', [SessionController::class, 'store']);
Route::patch('/sessions/{id}', [SessionController::class, 'update']);
