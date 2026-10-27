<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\Reserva;
use App\Services\CobroService;
use App\Services\OperacionInvalida;
use App\Services\ReservaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $servicioReservas,
        private readonly CobroService $servicioCobros,
    ) {}

    /**
     * POST /api/v1/reservations — HU10: crea una reserva validando que no se solape
     * y registrando el anticipo mínimo exigido por la regla de negocio.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'guest_id' => ['required', 'integer', Rule::exists('huespedes', 'id')->whereNull('deleted_at')],
            'room_id' => ['required', 'integer', Rule::exists('habitaciones', 'id')->whereNull('deleted_at')],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'num_guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'deposit_amount' => ['required', 'numeric', 'min:0.01'],
            'deposit_method' => ['required', Rule::in(Cobro::METODOS)],
            'deposit_date' => ['nullable', 'date'],
            'deposit_reference' => ['nullable', 'string', 'max:80'],
        ]);

        $usuario = $request->user();

        $reserva = DB::transaction(function () use ($datos, $usuario) {
            $reserva = $this->servicioReservas->crear([
                'habitacion_id' => (int) $datos['room_id'],
                'huesped_id' => (int) $datos['guest_id'],
                'fecha_entrada' => $datos['check_in'],
                'fecha_salida' => $datos['check_out'],
                'num_huespedes' => (int) ($datos['num_guests'] ?? 1),
                'estado' => Reserva::ESTADO_CONFIRMADA,
                'notas' => $datos['notes'] ?? null,
            ]);

            $monto = round((float) $datos['deposit_amount'], 2);
            $minimo = $this->servicioReservas->anticipoMinimo((float) $reserva->total);

            if ($monto < $minimo - 0.009) {
                throw new OperacionInvalida(
                    'El anticipo mínimo para asegurar la reserva es '.bs($minimo)
                    .' ('.config('hotel.anticipo_porcentaje').'% de '.bs($reserva->total).').'
                );
            }

            $this->servicioCobros->registrar($reserva, [
                'monto' => $monto,
                'metodo' => $datos['deposit_method'],
                'concepto' => 'Anticipo de reserva',
                'fecha' => $datos['deposit_date'] ?? now()->toDateString(),
                'clave_idempotencia' => $datos['deposit_reference'] ?? null,
                'tipo' => Cobro::TIPO_PAGO,
            ], $usuario);

            return $reserva;
        });

        return response()->json($this->payload($reserva->fresh()), 201);
    }

    /**
     * PUT /api/v1/reservations/{id} — HU11: modifica fechas u habitación
     * con la misma validación de solapes.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $reserva = Reserva::findOrFail($id);

        $datos = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('habitaciones', 'id')->whereNull('deleted_at')],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'num_guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $reserva = $this->servicioReservas->actualizar($reserva, [
            'habitacion_id' => (int) $datos['room_id'],
            'huesped_id' => $reserva->huesped_id,
            'fecha_entrada' => $datos['check_in'],
            'fecha_salida' => $datos['check_out'],
            'num_huespedes' => (int) ($datos['num_guests'] ?? $reserva->num_huespedes),
            'estado' => $reserva->estado,
            'notas' => $datos['notes'] ?? $reserva->notas,
        ]);

        return response()->json($this->payload($reserva->fresh()), 200);
    }

    /**
     * POST /api/v1/reservations/{id}/check-in — HU14.
     */
    public function checkIn(int $id): JsonResponse
    {
        $reserva = $this->servicioReservas->encontrar($id);

        $reserva = $this->servicioReservas->checkIn($reserva);

        return response()->json($this->payload($reserva->fresh()), 200);
    }

    /**
     * POST /api/v1/reservations/{id}/payments — HU17: registra un pago
     * sin exceder el saldo ni duplicarlo ante reintentos (reference).
     */
    public function payment(Request $request, int $id): JsonResponse
    {
        $reserva = $this->servicioReservas->encontrar($id);

        $datos = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:80'],
            'method' => ['nullable', Rule::in(Cobro::METODOS)],
            'concept' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date'],
        ]);

        $referencia = $datos['reference'] ?? null;

        if ($referencia && $this->servicioCobros->porReserva($reserva->id)->contains('clave_idempotencia', $referencia)) {
            return response()->json([
                'message' => 'Pago duplicado: ya existe un cobro con esa referencia.',
            ], 409);
        }

        $cobro = $this->servicioCobros->registrar($reserva, [
            'monto' => round((float) $datos['amount'], 2),
            'metodo' => $datos['method'] ?? 'efectivo',
            'concepto' => $datos['concept'] ?? 'Estadía',
            'fecha' => $datos['date'] ?? now()->toDateString(),
            'clave_idempotencia' => $referencia,
            'tipo' => Cobro::TIPO_PAGO,
        ], $request->user());

        if (! $cobro->wasRecentlyCreated) {
            return response()->json([
                'message' => 'Pago duplicado: ya existe un cobro con esa referencia.',
            ], 409);
        }

        return response()->json([
            'id' => $cobro->id,
            'reservation_id' => $reserva->id,
            'amount' => (float) $cobro->monto,
            'method' => $cobro->metodo,
            'concept' => $cobro->concepto,
            'paid_at' => $cobro->fecha->toDateString(),
            'reference' => $cobro->clave_idempotencia,
            'status' => 'activo',
            'balance' => $this->servicioCobros->saldoPendiente($reserva->fresh()),
        ], 201);
    }

    /**
     * POST /api/v1/reservations/{id}/check-out — HU15: solo con saldo cero,
     * salvo excepción autorizada por el administrador (exception_reason).
     */
    public function checkOut(Request $request, int $id): JsonResponse
    {
        $reserva = $this->servicioReservas->encontrar($id);

        $datos = $request->validate([
            'exception_reason' => ['nullable', 'string', 'max:200'],
        ]);

        $reserva = $this->servicioReservas->checkOut(
            $reserva,
            $datos['exception_reason'] ?? null,
            $request->user()
        );

        return response()->json($this->payload($reserva->fresh()), 200);
    }

    /**
     * Contrato de respuesta de reserva (sección 4.2 del documento).
     */
    private function payload(Reserva $reserva): array
    {
        $reserva->load(['habitacion.tipoHabitacion', 'huesped']);

        return [
            'id' => $reserva->id,
            'guest_id' => $reserva->huesped_id,
            'guest_name' => $reserva->huesped->nombre ?? null,
            'room_id' => $reserva->habitacion_id,
            'room_number' => $reserva->habitacion->numero ?? null,
            'check_in' => $reserva->fecha_entrada?->toDateString(),
            'check_out' => $reserva->fecha_salida?->toDateString(),
            'nights' => $reserva->noches(),
            'rate' => (float) ($reserva->habitacion->precio ?? 0),
            'total' => (float) $reserva->total,
            'paid' => round($this->servicioCobros->totalCobrado($reserva->id), 2),
            'balance' => round($this->servicioCobros->saldoPendiente($reserva), 2),
            'status' => $reserva->estado,
        ];
    }
}
