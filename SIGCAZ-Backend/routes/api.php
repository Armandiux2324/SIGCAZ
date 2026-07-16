<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\QrScanController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ReportController;

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
        Route::get('/users/search', [UserController::class, 'searchByEmail']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        // Ruta de configuración
        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);

    });

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
        Route::get('/me', [UserController::class, 'me']);
        // Rutas de registros
        Route::get('/registers', [RegisterController::class, 'index']);
        Route::get('/registers/search-filter', [RegisterController::class, 'searchByFilter']);
        Route::get('/registers/{id}', [RegisterController::class, 'show']);
        Route::put('/registers/{register}', [RegisterController::class, 'update']);
        Route::delete('/registers/{id}', [RegisterController::class, 'destroy']);
        // Rutas de escaneos
        Route::post('/scans', [QrScanController::class, 'scan']);
        Route::get('/scans', [QrScanController::class, 'index']);
        // Rutas de estadísticas
        Route::get('/stats/summary', [StatsController::class, 'summary']);
        // Reportes descargables
        Route::get('/reports/participants', [ReportController::class, 'participants']);
        Route::get('/reports/gender', [ReportController::class, 'byGender']);
        Route::get('/reports/shirt-size', [ReportController::class, 'byShirtSize']);
        Route::get('/reports/state', [ReportController::class, 'byState']);
        Route::get('/reports/municipality', [ReportController::class, 'byMunicipality']);
        Route::get('/reports/group', [ReportController::class, 'byGroup']);
        Route::get('/reports/accommodation', [ReportController::class, 'byAccommodation']);
        Route::get('/reports/participation-count', [ReportController::class, 'byParticipationCount']);
        Route::get('/reports/attendance', [ReportController::class, 'attendance']);
    });
});
