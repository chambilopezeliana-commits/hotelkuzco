<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HabitacionController;
use App\Http\Controllers\Api\HuespedController;
use App\Http\Controllers\Api\ReservaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API del Hotel KUZCO (sección 4.2 del documento)
|--------------------------------------------------------------------------
| Contratos principales bajo /api/v1 con autenticación por tokens
| de Laravel Sanctum (Authorization: Bearer <token>).
*/

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/rooms/availability', [HabitacionController::class, 'availability']);

        Route::post('/guests', [HuespedController::class, 'store']);

        Route::post('/reservations', [ReservaController::class, 'store']);
        Route::put('/reservations/{id}', [ReservaController::class, 'update'])->whereNumber('id');
        Route::post('/reservations/{id}/check-in', [ReservaController::class, 'checkIn'])->whereNumber('id');
        Route::post('/reservations/{id}/check-out', [ReservaController::class, 'checkOut'])->whereNumber('id');
        Route::post('/reservations/{id}/payments', [ReservaController::class, 'payment'])->whereNumber('id');

        Route::get('/dashboard', [DashboardController::class, 'index']);
    });
});
