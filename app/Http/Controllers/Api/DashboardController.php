<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard — HU21: métricas operativas. El recepcionista
     * recibe la vista operativa limitada (sin cifras financieras).
     */
    public function index(Request $request): JsonResponse
    {
        $hoy = Carbon::today();
        $esAdmin = $request->user()->esAdmin();

        $datos = [
            'date' => $hoy->toDateString(),
            'rooms' => [
                'total' => Habitacion::count(),
                'available' => Habitacion::where('estado', 'disponible')->count(),
                'occupied' => Habitacion::where('estado', 'ocupada')->count(),
                'maintenance' => Habitacion::where('estado', 'mantenimiento')->count(),
                'cleaning' => Habitacion::where('estado', 'limpieza')->count(),
            ],
            'reservations' => [
                'active' => Reserva::whereIn('estado', Reserva::ESTADOS_ACTIVOS)->count(),
                'arrivals_today' => Reserva::whereDate('fecha_entrada', $hoy)
                    ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
                    ->count(),
                'departures_today' => Reserva::whereDate('fecha_salida', $hoy)
                    ->where('estado', Reserva::ESTADO_EN_ESTADIA)
                    ->count(),
                'in_stay' => Reserva::where('estado', Reserva::ESTADO_EN_ESTADIA)->count(),
            ],
            'guests' => [
                'total' => Huesped::count(),
            ],
        ];

        if ($esAdmin) {
            $datos['payments'] = [
                'today' => round((float) Cobro::whereDate('fecha', $hoy)->sum('monto'), 2),
                // Criterio de caja: pagos netos del mes (incluye contraasientos).
                'month_net' => round((float) Cobro::whereMonth('fecha', $hoy->month)
                    ->whereYear('fecha', $hoy->year)
                    ->sum('monto'), 2),
            ];
            $datos['users'] = [
                'total' => User::count(),
                'active' => User::where('activo', true)->count(),
            ];
        }

        return response()->json($datos, 200);
    }
}
