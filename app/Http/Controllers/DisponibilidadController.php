<?php

namespace App\Http\Controllers;

use App\Repositories\HabitacionRepository;
use App\Repositories\TipoHabitacionRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisponibilidadController extends Controller
{
    public function __construct(
        private readonly HabitacionRepository $habitaciones,
        private readonly TipoHabitacionRepository $tipos,
    ) {}

    public function index(Request $request): View
    {
        $entrada = $request->get('entrada') ?: now()->toDateString();
        $salida = $request->get('salida') ?: now()->addDay()->toDateString();
        $tipo = $request->get('tipo_habitacion_id') ? (int) $request->get('tipo_habitacion_id') : null;
        $capacidad = $request->get('capacidad') ? (int) $request->get('capacidad') : null;

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
