<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TherapistApiController;
use App\Http\Controllers\TherapistController;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Protected Application Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Main Dashboard & Dispatcher
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Therapist API for live map & real-time nearby ranking
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/therapists/nearby', [TherapistApiController::class, 'getNearby'])->name('therapists.nearby');
        Route::post('/dispatch-logs', [TherapistApiController::class, 'logDispatch'])->name('dispatch.log');
    });

    // Therapist Master Data CRUD
    Route::prefix('therapists')->name('therapists.')->group(function () {
        Route::get('/', [TherapistController::class, 'index'])->name('index');
        Route::post('/', [TherapistController::class, 'store'])->name('store');
        Route::put('/{id}', [TherapistController::class, 'update'])->name('update');
        Route::patch('/{id}/status', [TherapistController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}', [TherapistController::class, 'destroy'])->name('destroy');
    });
});
