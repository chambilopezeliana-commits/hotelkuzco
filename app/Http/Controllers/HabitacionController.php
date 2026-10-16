<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Habitacion;
use App\Repositories\HabitacionRepository;
use App\Repositories\TipoHabitacionRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HabitacionController extends Controller
{
    public function __construct(
        private readonly HabitacionRepository $habitaciones,
        private readonly TipoHabitacionRepository $tipos,
    ) {}

    public function index(Request $request): View
    {
        $habitaciones = $this->habitaciones->listar($request->get('buscar'));

        return view('habitaciones.index', [
            'habitaciones' => $habitaciones,
            'buscar' => $request->get('buscar'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->esAdmin(), 403);

        return view('habitaciones.create', [
            'tipos' => $this->tipos->activos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        $datos = $this->validar($request);

        $habitacion = $this->habitaciones->crear($datos);
        Auditoria::registrar('crear', 'habitacion', $habitacion->id, 'Habitación creada', [], $habitacion->toArray());

        return redirect()->route('habitaciones.index')->with('exito', 'Habitación creada correctamente.');
    }

    public function edit(Request $request, Habitacion $habitacion): View
    {
        abort_unless($request->user()->esAdmin(), 403);

        return view('habitaciones.edit', [
            'habitacion' => $habitacion,
            'tipos' => $this->tipos->activos(),
        ]);
    }

    public function update(Request $request, Habitacion $habitacion): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        $datos = $this->validar($request, $habitacion);
        $antes = $habitacion->toArray();
        $habitacion = $this->habitaciones->actualizar($habitacion, $datos);

        Auditoria::registrar('actualizar', 'habitacion', $habitacion->id, 'Habitación actualizada', $antes, $habitacion->toArray());

        return redirect()->route('habitaciones.index')->with('exito', 'Habitación actualizada correctamente.');
    }

    public function destroy(Request $request, Habitacion $habitacion): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        if ($this->habitaciones->tieneReservasActivas($habitacion->id)) {
            return back()->with('error', 'No se puede eliminar: la habitación tiene reservas activas.');
        }

        $antes = $habitacion->toArray();
        $this->habitaciones->eliminar($habitacion);

        Auditoria::registrar('eliminar', 'habitacion', $habitacion->id, 'Habitación eliminada', $antes, []);

        return redirect()->route('habitaciones.index')->with('exito', 'Habitación eliminada correctamente.');
    }

    public function limpiar(Request $request, Habitacion $habitacion): RedirectResponse
    {
        if (! $habitacion->requiereLimpieza()) {
            return back()->with('error', 'La habitación Nº '.$habitacion->numero.' no está pendiente de limpieza.');
        }

        $antes = $habitacion->toArray();
        $habitacion->update(['estado' => 'disponible']);

        Auditoria::registrar('actualizar', 'habitacion', $habitacion->id, 'Habitación Nº '.$habitacion->numero.' marcada como limpia', $antes, $habitacion->toArray());

        return back()->with('exito', 'Habitación Nº '.$habitacion->numero.' limpia y disponible.');
    }

    private function validar(Request $request, ?Habitacion $habitacion = null): array
    {
        $request->merge(['numero' => trim((string) $request->input('numero', ''))]);

        $reglas = [
            'numero' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/', 'unique:habitaciones,numero'.($habitacion ? ','.$habitacion->id : '')],
            'tipo_habitacion_id' => ['required', 'exists:tipos_habitacion,id'],
            'estado' => ['required', 'in:'.implode(',', Habitacion::ESTADOS)],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];

        $mensajes = [
            'numero.required' => 'El número de habitación es obligatorio.',
            'numero.regex' => 'El número de habitación solo admite dígitos.',
            'numero.unique' => 'Ya existe una habitación con ese número.',
            'tipo_habitacion_id.required' => 'Debe seleccionar el tipo de habitación.',
            'tipo_habitacion_id.exists' => 'El tipo de habitación no existe.',
            'estado.in' => 'Estado no válido.',
        ];

        return $request->validate($reglas, $mensajes);
    }
}
