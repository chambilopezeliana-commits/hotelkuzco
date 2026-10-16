<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Reserva;
use App\Repositories\HabitacionRepository;
use App\Repositories\HuespedRepository;
use App\Services\CobroService;
use App\Services\OperacionInvalida;
use App\Services\ReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            $this->servicioReservas->walkIn($datos, $request->user());
        } catch (OperacionInvalida $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reservas.index')->with('exito', 'Check-in directo realizado. Pago total registrado.');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        $anticipo = [
            'monto' => round((float) $datos['anticipo_monto'], 2),
            'metodo' => $datos['anticipo_metodo'],
            'concepto' => 'Anticipo de reserva',
            'fecha' => $datos['anticipo_fecha'],
            'tipo' => Cobro::TIPO_PAGO,
        ];
        unset($datos['anticipo_monto'], $datos['anticipo_metodo'], $datos['anticipo_fecha']);

        try {
            DB::transaction(function () use ($datos, $anticipo, $request) {
                $reserva = $this->servicioReservas->crear($datos);

                $minimo = $this->servicioReservas->anticipoMinimo((float) $reserva->total);

                if ($anticipo['monto'] < $minimo - 0.009) {
                    throw new OperacionInvalida(
                        'El anticipo mínimo para asegurar la reserva es '.bs($minimo)
                        .' ('.config('hotel.anticipo_porcentaje').'% de '.bs($reserva->total).').'
                    );
                }

                $this->servicioCobros->registrar($reserva, $anticipo, $request->user());
            });
        } catch (OperacionInvalida $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reservas.index')->with('exito', 'Reserva creada y anticipo registrado.');
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
            $this->servicioReservas->checkOut($reserva);
        } catch (OperacionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', 'Check-out finalizado. La habitación pasó a limpieza.');
    }

    private function validar(Request $request, ?Reserva $reserva = null): array
    {
        $estadosEditables = ['pendiente', 'confirmada', 'cancelada'];

        if ($reserva && in_array($reserva->estado, ['hospedado', 'finalizada'])) {
            $estadosEditables[] = $reserva->estado;
        }

        $reglas = [
            'habitacion_id' => ['required', 'exists:habitaciones,id'],
            'huesped_id' => ['required', 'exists:huespedes,id'],
            'fecha_entrada' => ['required', 'date'],
            'fecha_salida' => ['required', 'date', 'after:fecha_entrada'],
            'num_huespedes' => ['required', 'integer', 'min:1', 'max:20'],
            'estado' => ['required', 'in:'.implode(',', $estadosEditables)],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];

        $mensajes = [
            'habitacion_id.required' => 'Debe seleccionar una habitación.',
            'habitacion_id.exists' => 'La habitación seleccionada no existe.',
            'huesped_id.required' => 'Debe seleccionar el huésped.',
            'huesped_id.exists' => 'El huésped seleccionado no existe.',
            'fecha_entrada.required' => 'La fecha de entrada es obligatoria.',
            'fecha_salida.required' => 'La fecha de salida es obligatoria.',
            'fecha_salida.after' => 'La fecha de salida debe ser posterior a la de entrada.',
            'estado.in' => 'Estado no válido.',
        ];

        if (! $reserva) {
            $reglas['anticipo_monto'] = ['required', 'numeric', 'min:0.01'];
            $reglas['anticipo_metodo'] = ['required', Rule::in(Cobro::METODOS)];
            $reglas['anticipo_fecha'] = ['required', 'date'];

            $mensajes['anticipo_monto.required'] = 'Debe registrar el anticipo para asegurar la reserva.';
            $mensajes['anticipo_monto.min'] = 'El anticipo debe ser mayor a 0.';
            $mensajes['anticipo_metodo.required'] = 'Debe seleccionar el método de pago del anticipo.';
            $mensajes['anticipo_fecha.required'] = 'Debe indicar la fecha del anticipo.';
        }

        return $request->validate($reglas, $mensajes);
    }

    private function validarWalkIn(Request $request): array
    {
        $datos = $request->validate([
            'habitacion_id' => ['required', 'exists:habitaciones,id'],
            'huesped_id' => ['required', 'exists:huespedes,id'],
            'fecha_salida' => ['required', 'date', 'after:today'],
            'num_huespedes' => ['required', 'integer', 'min:1', 'max:20'],
            'metodo_pago' => ['required', Rule::in(Cobro::METODOS)],
            'notas' => ['nullable', 'string', 'max:1000'],
        ], [
            'fecha_salida.after' => 'La fecha de salida debe ser posterior a hoy.',
            'metodo_pago.required' => 'Debe seleccionar el método de pago.',
        ]);

        return $datos;
    }
}
