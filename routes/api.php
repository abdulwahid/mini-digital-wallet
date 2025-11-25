<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes (if any)
// Route::post('/register', ...);
// Route::post('/login', ...);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // User info endpoint
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Transaction routes with rate limiting
    // GET: 120 requests per minute per user (reading is less resource-intensive)
    // POST: 60 requests per minute per user (writing is more resource-intensive)
    Route::middleware('throttle:transactions.view')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index']);
    });

    Route::middleware('throttle:transactions.create')->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store']);
    });
});

