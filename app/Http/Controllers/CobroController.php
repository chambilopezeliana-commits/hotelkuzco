<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Reserva;
use App\Services\CobroService;
use App\Services\OperacionInvalida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CobroController extends Controller
{
    public function __construct(private readonly CobroService $servicioCobros) {}

    public function index(Reserva $reserva): View
    {
        return view('reservas.cobros', [
            'reserva' => $reserva->load(['habitacion', 'huesped']),
            'cobros' => $this->servicioCobros->porReserva($reserva->id),
            'cobrado' => $this->servicioCobros->totalCobrado($reserva->id),
            'saldo' => $this->servicioCobros->saldoPendiente($reserva),
        ]);
    }

    public function store(Request $request, Reserva $reserva): RedirectResponse
    {
        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo' => ['required', Rule::in(Cobro::METODOS)],
            'concepto' => ['required', 'string', 'max:100'],
            'fecha' => ['required', 'date'],
            'clave_idempotencia' => ['nullable', 'string', 'max:80'],
        ], [
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'metodo.in' => 'Método de pago no válido.',
            'fecha.required' => 'La fecha es obligatoria.',
        ]);

        try {
            $this->servicioCobros->registrar($reserva, $datos, $request->user());
        } catch (OperacionInvalida $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('cobros.index', $reserva)->with('exito', 'Cobro registrado correctamente.');
    }

    public function anular(Request $request, int $cobroId): RedirectResponse
    {
        abort_unless($request->user()->esAdmin(), 403);

        $cobro = Cobro::with('reserva')->findOrFail($cobroId);
        $reserva = $cobro->reserva;

        $request->validate([
            'motivo' => ['required', 'string', 'max:200'],
        ], [
            'motivo.required' => 'El motivo de la anulación es obligatorio.',
        ]);

        try {
            $this->servicioCobros->anular($cobro, $request->get('motivo'), $request->user());
        } catch (OperacionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('cobros.index', $reserva)->with('exito', 'Cobro anulado correctamente mediante contraasiento.');
    }

    public function comprobante(Reserva $reserva): View
    {
        return view('reservas.comprobante', [
            'reserva' => $reserva->load(['habitacion.tipoHabitacion', 'huesped', 'cobros.usuario']),
            'cobros' => $this->servicioCobros->porReserva($reserva->id),
            'cobrado' => $this->servicioCobros->totalCobrado($reserva->id),
            'saldo' => $this->servicioCobros->saldoPendiente($reserva),
        ]);
    }
}
