<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\SettingsController;

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
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        // Ruta de configuración
        Route::put('/settings', [SettingsController::class, 'update']);
        // Rutas de registros
        Route::get('/registers', [RegisterController::class, 'index']);
        Route::get('/registers/{id}', [RegisterController::class, 'show']);
        Route::put('/registers/{register}', [RegisterController::class, 'update']);
        Route::delete('/registers/{id}', [RegisterController::class, 'destroy']);

    });

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
        Route::get('/me', [UserController::class, 'me']);
    });
});
