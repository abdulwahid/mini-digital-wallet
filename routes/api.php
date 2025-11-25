<?php

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

    // Transaction routes (to be implemented)
    // Route::get('/transactions', [TransactionController::class, 'index']);
    // Route::post('/transactions', [TransactionController::class, 'store']);
});

