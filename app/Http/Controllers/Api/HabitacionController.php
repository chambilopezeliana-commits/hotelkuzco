<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\HabitacionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HabitacionController extends Controller
{
    public function __construct(private readonly HabitacionRepository $habitaciones) {}

    /**
     * GET /api/v1/rooms/availability — HU7: habitaciones disponibles
     * por rango de fechas, tipo y capacidad.
     */
    public function availability(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'type_id' => ['nullable', 'integer', Rule::exists('tipos_habitacion', 'id')->whereNull('deleted_at')->where('activo', true)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $disponibles = $this->habitaciones->disponibles(
            $datos['check_in'],
            $datos['check_out'],
            isset($datos['type_id']) ? (int) $datos['type_id'] : null,
            isset($datos['capacity']) ? (int) $datos['capacity'] : null,
        );

        return response()->json([
            'check_in' => $datos['check_in'],
            'check_out' => $datos['check_out'],
            'nights' => max(1, (int) round((strtotime($datos['check_out']) - strtotime($datos['check_in'])) / 86400)),
            'available' => $disponibles->map(fn ($habitacion) => [
                'id' => $habitacion->id,
                'room_number' => $habitacion->numero,
                'type' => $habitacion->tipo,
                'capacity' => $habitacion->capacidad,
                'price' => (float) $habitacion->precio,
                'status' => $habitacion->estado,
            ])->values(),
        ], 200);
    }
}
