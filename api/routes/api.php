<?php

use App\Http\Controllers\Api\AuthController;
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

Route::post('/auth/guest', [AuthController::class, 'guest']);
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    // Current User profile
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/me', [AuthController::class, 'updatePreferences']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Progress
    Route::get('/progress/surahs/{surahId}', [ProgressController::class, 'show']);
    Route::delete('/progress/surahs/{surahId}', [ProgressController::class, 'destroy']);

    // Review Logs
    Route::get('/review-logs', [ReviewLogController::class, 'index']);

    // Sessions
    Route::post('/sessions', [SessionController::class, 'store']);
    Route::patch('/sessions/{id}', [SessionController::class, 'update']);

    // Surahs and Ayahs with progress context
    Route::get('/mushaf/pages/{pageNumber}', [AyahController::class, 'byPage']);
    Route::get('/juz/{juzNumber}/ayahs', [AyahController::class, 'byJuz']);
    Route::get('/surahs', [SurahController::class, 'index']);
    Route::get('/surahs/{id}', [SurahController::class, 'show']);
    Route::get('/surahs/{surahId}/ayahs', [AyahController::class, 'index']);
    Route::get('/surahs/{surahId}/ayahs/{ayahNumber}', [AyahController::class, 'show']);
});
