<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\HabitacionController;
use App\Http\Controllers\HuespedController;
use App\Http\Controllers\HuespedImportController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\TipoHabitacionController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogoController::class, 'index'])->name('catalogo');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/panel', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])->name('disponibilidad.index');

    Route::resource('huespedes', HuespedController::class)->except('show')->parameters(['huespedes' => 'huesped']);

    Route::get('/reservas/walk-in', [ReservaController::class, 'walkInForm'])->name('reservas.walkin');
    Route::post('/reservas/walk-in', [ReservaController::class, 'walkInStore'])->name('reservas.walkin.store');

    Route::resource('reservas', ReservaController::class)->except('show')->parameters(['reservas' => 'reserva']);
    Route::post('/reservas/{reserva}/check-in', [ReservaController::class, 'checkIn'])->name('reservas.checkin');
    Route::post('/reservas/{reserva}/check-out', [ReservaController::class, 'checkOut'])->name('reservas.checkout');
    Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');
    Route::get('/reservas/{reserva}/comprobante', [CobroController::class, 'comprobante'])->name('cobros.comprobante');

    Route::get('/reservas/{reserva}/cobros', [CobroController::class, 'index'])->name('cobros.index');
    Route::post('/reservas/{reserva}/cobros', [CobroController::class, 'store'])->name('cobros.store');
    Route::delete('/cobros/{cobro}', [CobroController::class, 'destroy'])->name('cobros.destroy');
    Route::post('/cobros/{cobro}/anular', [CobroController::class, 'anular'])->name('cobros.anular');

    Route::resource('habitaciones', HabitacionController::class)->except('show')->parameters(['habitaciones' => 'habitacion']);
    Route::post('/habitaciones/{habitacion}/limpiar', [HabitacionController::class, 'limpiar'])->name('habitaciones.limpiar');

    Route::resource('tipos-habitacion', TipoHabitacionController::class)->except('show')->parameters(['tipos-habitacion' => 'tipo_habitacion']);

    Route::middleware('role:administrador')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except('show');
        Route::patch('/usuarios/{usuario}/toggle', [UsuarioController::class, 'toggleEstado'])->name('usuarios.toggle');

        Route::get('/huespedes/importar/csv', [HuespedImportController::class, 'formulario'])->name('huespedes.importar');
        Route::post('/huespedes/importar/csv', [HuespedImportController::class, 'importar'])->name('huespedes.importar.procesar');

        Route::get('/auditorias', [AuditoriaController::class, 'index'])->name('auditorias.index');

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/imprimir', [ReporteController::class, 'imprimir'])->name('reportes.imprimir');
        Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');
    });
});
