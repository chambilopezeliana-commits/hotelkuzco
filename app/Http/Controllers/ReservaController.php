<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Repositories\HabitacionRepository;
use App\Repositories\HuespedRepository;
use App\Services\CobroService;
use App\Services\OperacionInvalida;
use App\Services\ReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $servicioReservas,
        private readonly CobroService $servicioCobros,
        private readonly HabitacionRepository $habitaciones,
        private readonly HuespedRepository $huespedes,
    ) {}

    public function index(Request $request): View
    {
        return view('reservas.index', [
            'reservas' => $this->servicioReservas->listar(
                $request->get('estado'),
                $request->get('buscar'),
                $request->get('desde'),
                $request->get('hasta')
            ),
            'estado' => $request->get('estado'),
            'buscar' => $request->get('buscar'),
            'desde' => $request->get('desde'),
            'hasta' => $request->get('hasta'),
        ]);
    }

    public function create(): View
    {
        return view('reservas.create', [
            'habitaciones' => $this->habitaciones->todas(),
            'huespedes' => $this->huespedes->todos(),
        ]);
    }

    public function walkInForm(): View
    {
        return view('reservas.walkin', [
            'habitaciones' => $this->habitaciones->todas(),
            'huespedes' => $this->huespedes->todos(),
        ]);
    }

    public function walkInStore(Request $request): RedirectResponse
    {
        $datos = $this->validarWalkIn($request);

        try {
            $reserva = $this->servicioReservas->walkIn($datos);
        } catch (OperacionInvalida $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reservas.index')->with('exito', 'Check-in directo realizado correctamente.');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        try {
            $this->servicioReservas->crear($datos);
        } catch (OperacionInvalida $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reservas.index')->with('exito', 'Reserva creada correctamente.');
    }

    public function edit(Reserva $reserva): View
    {
        return view('reservas.edit', [
            'reserva' => $reserva->load(['habitacion', 'huesped']),
            'habitaciones' => $this->habitaciones->todas(),
            'huespedes' => $this->huespedes->todos(),
        ]);
    }

    public function update(Request $request, Reserva $reserva): RedirectResponse
    {
        $datos = $this->validar($request, $reserva);

        try {
            $this->servicioReservas->actualizar($reserva, $datos);
        } catch (OperacionInvalida $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reservas.index')->with('exito', 'Reserva actualizada correctamente.');
    }

    public function destroy(Reserva $reserva): RedirectResponse
    {
        try {
            $this->servicioReservas->eliminar($reserva);
        } catch (OperacionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('reservas.index')->with('exito', 'Reserva eliminada correctamente.');
    }

    public function cancelar(Reserva $reserva): RedirectResponse
    {
        try {
            $this->servicioReservas->cancelar($reserva);
        } catch (OperacionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', 'Reserva cancelada. La habitación fue liberada.');
    }

    public function checkIn(Reserva $reserva): RedirectResponse
    {
        try {
            $this->servicioReservas->checkIn($reserva);
        } catch (OperacionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', 'Check-in realizado. Huésped hospedado.');
    }

    public function checkOut(Reserva $reserva): RedirectResponse
    {
        try {
            $saldo = $this->servicioCobros->saldoPendiente($reserva);
            $this->servicioReservas->checkOut($reserva, $saldo);
        } catch (OperacionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', 'Check-out finalizado. Estadía cerrada.');
    }

    private function validar(Request $request, ?Reserva $reserva = null): array
    {
        $estadosEditables = ['pendiente', 'confirmada', 'cancelada'];

        if ($reserva && in_array($reserva->estado, ['hospedado', 'finalizada'])) {
            $estadosEditables[] = $reserva->estado;
        }

        $datos = $request->validate([
            'habitacion_id' => ['required', 'exists:habitaciones,id'],
            'huesped_id' => ['required', 'exists:huespedes,id'],
            'fecha_entrada' => ['required', 'date'],
            'fecha_salida' => ['required', 'date', 'after:fecha_entrada'],
            'num_huespedes' => ['required', 'integer', 'min:1', 'max:20'],
            'estado' => ['required', 'in:'.implode(',', $estadosEditables)],
            'notas' => ['nullable', 'string', 'max:1000'],
        ], [
            'habitacion_id.required' => 'Debe seleccionar una habitación.',
            'habitacion_id.exists' => 'La habitación seleccionada no existe.',
            'huesped_id.required' => 'Debe seleccionar el huésped.',
            'huesped_id.exists' => 'El huésped seleccionado no existe.',
            'fecha_entrada.required' => 'La fecha de entrada es obligatoria.',
            'fecha_salida.required' => 'La fecha de salida es obligatoria.',
            'fecha_salida.after' => 'La fecha de salida debe ser posterior a la de entrada.',
            'estado.in' => 'Estado no válido.',
        ]);

        return $datos;
    }

    private function validarWalkIn(Request $request): array
    {
        $datos = $request->validate([
            'habitacion_id' => ['required', 'exists:habitaciones,id'],
            'huesped_id' => ['required', 'exists:huespedes,id'],
            'fecha_salida' => ['required', 'date', 'after:today'],
            'num_huespedes' => ['required', 'integer', 'min:1', 'max:20'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ], [
            'fecha_salida.after' => 'La fecha de salida debe ser posterior a hoy.',
        ]);

        return $datos;
    }
}
