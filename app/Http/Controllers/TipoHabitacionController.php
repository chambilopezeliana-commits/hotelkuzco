<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\TipoHabitacion;
use App\Repositories\TipoHabitacionRepository;
use App\Rules\SoloLetras;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TipoHabitacionController extends Controller
{
    public function __construct(private readonly TipoHabitacionRepository $tipos) {}

    public function index(Request $request): View
    {
        return view('tipos-habitacion.index', [
            'tipos' => $this->tipos->listar($request->get('buscar')),
            'buscar' => $request->get('buscar'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->esAdmin(), 403);

        return view('tipos-habitacion.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        $datos = $this->validar($request);
        $tipo = $this->tipos->crear($datos);

        Auditoria::registrar('crear', 'tipo_habitacion', $tipo->id, 'Tipo de habitación creado', [], $tipo->toArray());

        return redirect()->route('tipos-habitacion.index')->with('exito', 'Tipo de habitación creado correctamente.');
    }

    public function edit(Request $request, TipoHabitacion $tipo_habitacion): View
    {
        abort_unless($request->user()->esAdmin(), 403);

        return view('tipos-habitacion.edit', ['tipo' => $tipo_habitacion]);
    }

    public function update(Request $request, TipoHabitacion $tipo_habitacion): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        $datos = $this->validar($request, $tipo_habitacion);
        $antes = $tipo_habitacion->toArray();
        $tipo_habitacion = $this->tipos->actualizar($tipo_habitacion, $datos);

        Auditoria::registrar('actualizar', 'tipo_habitacion', $tipo_habitacion->id, 'Tipo de habitación actualizado', $antes, $tipo_habitacion->toArray());

        return redirect()->route('tipos-habitacion.index')->with('exito', 'Tipo de habitación actualizado correctamente.');
    }

    public function destroy(Request $request, TipoHabitacion $tipo_habitacion): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        // Validación previa: no eliminar tipos con relaciones activas (habitaciones asociadas).
        if ($this->tipos->tieneHabitaciones($tipo_habitacion->id)) {
            return back()->with('error', 'No se puede eliminar: hay habitaciones asociadas a este tipo.');
        }

        $antes = $tipo_habitacion->toArray();

        // Baja lógica: se desocupa el nombre único para permitir recrear el tipo.
        $tipo_habitacion->update(['nombre' => 'ELIMINADO #'.$tipo_habitacion->id]);

        $this->tipos->eliminar($tipo_habitacion);

        Auditoria::registrar('eliminar', 'tipo_habitacion', $tipo_habitacion->id, 'Tipo de habitación eliminado (baja lógica)', $antes, []);

        return redirect()->route('tipos-habitacion.index')->with('exito', 'Tipo de habitación eliminado correctamente.');
    }

    private function validar(Request $request, ?TipoHabitacion $tipo = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', new SoloLetras, Rule::unique('tipos_habitacion', 'nombre')
                ->whereNull('deleted_at')
                ->ignore($tipo?->id)],
            'tarifa' => ['required', 'numeric', 'min:0'],
            'capacidad' => ['required', 'integer', 'min:1', 'max:20'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un tipo con ese nombre.',
        ]);
    }
}
