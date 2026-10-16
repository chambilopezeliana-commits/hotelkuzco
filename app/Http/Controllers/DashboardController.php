<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\User;
use App\Repositories\ReservaRepository;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(private readonly ReservaRepository $reservas) {}

    public function index()
    {
        $hoy = Carbon::today();

        $datos = [
            'totalHabitaciones' => Habitacion::count(),
            'disponibles' => Habitacion::where('estado', 'disponible')->count(),
            'ocupadas' => Habitacion::where('estado', 'ocupada')->count(),
            'mantenimiento' => Habitacion::where('estado', 'mantenimiento')->count(),
            'reservasHoy' => Reserva::whereDate('fecha_entrada', $hoy)
                ->whereIn('estado', ['pendiente', 'confirmada', 'hospedado'])
                ->count(),
            'salidasHoy' => Reserva::whereDate('fecha_salida', $hoy)
                ->where('estado', 'hospedado')
                ->count(),
            'huespedesActivos' => Reserva::where('estado', 'hospedado')->count(),
            'reservasActivas' => Reserva::whereIn('estado', ['pendiente', 'confirmada', 'hospedado'])->count(),
            'totalHuespedes' => Huesped::count(),
            'ingresosMes' => (float) Cobro::whereMonth('fecha', $hoy->month)
                ->whereYear('fecha', $hoy->year)
                ->sum('monto'),
            'cobrosHoy' => (float) Cobro::whereDate('fecha', $hoy)->sum('monto'),
        ];

        if (request()->user()->esAdmin()) {
            $datos['usuarios'] = User::count();
            $datos['usuariosActivos'] = User::where('activo', true)->count();
        }

        $proximasReservas = $this->reservas->proximas(8);

        return view('dashboard', compact('datos', 'proximasReservas'));
    }
}
