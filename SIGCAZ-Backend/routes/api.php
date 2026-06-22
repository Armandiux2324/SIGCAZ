<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RegisterController;

Route::prefix('v1')->group(function () {
    // Ruta de autenticación
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    // Rutas de registro de participantes
    Route::post('/registers', [RegisterController::class, 'store']);
    Route::get('/registers/search', [RegisterController::class, 'search']);
    Route::get('/registers/receipt', [RegisterController::class, 'receipt']);

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Rutas de usuarios
        Route::post('/users', [UserController::class, 'store']);
    });

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    });
});
