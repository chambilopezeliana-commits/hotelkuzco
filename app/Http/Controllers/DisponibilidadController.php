<?php

namespace App\Http\Controllers;

use App\Repositories\HabitacionRepository;
use App\Repositories\TipoHabitacionRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DisponibilidadController extends Controller
{
    public function __construct(
        private readonly HabitacionRepository $habitaciones,
        private readonly TipoHabitacionRepository $tipos,
    ) {}

    public function index(Request $request): View
    {
        $datos = $request->validate([
            'entrada' => ['nullable', 'date'],
            'salida' => ['nullable', 'date'],
            'tipo_habitacion_id' => ['nullable', 'integer', 'exists:tipos_habitacion,id'],
            'capacidad' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $entrada = $datos['entrada'] ?? now()->toDateString();
        $salida = $datos['salida'] ?? now()->addDay()->toDateString();

        if (strtotime($salida) <= strtotime($entrada)) {
            throw ValidationException::withMessages([
                'salida' => 'La fecha de salida debe ser posterior a la de entrada.',
            ]);
        }

        $tipo = isset($datos['tipo_habitacion_id']) ? (int) $datos['tipo_habitacion_id'] : null;
        $capacidad = isset($datos['capacidad']) ? (int) $datos['capacidad'] : null;

        $disponibles = $this->habitaciones->disponibles($entrada, $salida, $tipo, $capacidad);

        return view('disponibilidad.index', [
            'disponibles' => $disponibles,
            'tipos' => $this->tipos->activos(),
            'entrada' => $entrada,
            'salida' => $salida,
            'tipoId' => $tipo,
            'capacidad' => $capacidad,
        ]);
    }
}
