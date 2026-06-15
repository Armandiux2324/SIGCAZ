<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\UserController;

Route::prefix('v1')->group(function () {
    // Rutas de autenticación y usuarios
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/users', [UserController::class, 'store']);

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        // Route::get('/me', [UserController::class, 'me']);
        // Route::post('/logout', [UserController::class, 'logout']);
        // Route::get('/users', [UserController::class, 'index']);
    });
});
