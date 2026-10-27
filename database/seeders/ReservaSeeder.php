<?php

namespace Database\Seeders;

use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservaSeeder extends Seeder
{
    /**
     * Crea un conjunto de reservas de ejemplo que cubre todos los estados
     * (confirmada, en estadía, finalizada y cancelada), incluyendo
     * check-ins activos, sus cobros y sus noches ocupadas.
     */
    public function run(): void
    {
        $base = Carbon::now()->startOfDay();
        $recepcionista = User::where('role', User::ROLE_RECEPCIONISTA)->first()
            ?? User::query()->firstOrFail();

        // [número habitación, documento huésped, entrada, salida, n° huéspedes, estado, pago]
        // pago: null = sin cobro, 'anticipo' = anticipo mínimo (config hotel.anticipo_porcentaje),
        // 'total' = pago completo de la estadía. Las reservas 'en_estadia'/'finalizada'
        // conviene que estén pagadas porque el check-out exige cuenta saldada.
        $ejemplos = [
            ['101', '10234567', -10, -7, 1, 'finalizada', 'total'],
            ['101', '20345678', -5, 2, 1, 'en_estadia', 'total'],
            ['102', '40567890', 0, 3, 1, 'confirmada', 'anticipo'],
            ['103', '50678901', 5, 8, 1, 'confirmada', 'anticipo'],
            ['104', '60789012', -20, -18, 1, 'cancelada', null],
            ['201', '10234567', -8, -5, 2, 'finalizada', 'total'],
            ['201', '30456789', 3, 6, 2, 'confirmada', 'total'],
            ['202', '20345678', -3, 1, 2, 'en_estadia', 'total'],
            ['203', '40567890', 10, 14, 2, 'confirmada', 'anticipo'],
            ['204', '50678901', -1, 2, 2, 'en_estadia', 'total'],
            ['301', '20345678', 1, 4, 3, 'confirmada', 'anticipo'],
            ['302', '40567890', -4, -1, 3, 'finalizada', 'total'],
            ['401', '60789012', -15, -12, 4, 'finalizada', 'total'],
            ['402', '10234567', 7, 11, 3, 'confirmada', 'anticipo'],
            ['501', '30456789', -6, -2, 2, 'finalizada', 'total'],
            ['501', '20345678', 20, 23, 2, 'confirmada', 'anticipo'],
            ['502', '40567890', -2, 3, 3, 'en_estadia', 'total'],
        ];

        foreach ($ejemplos as [$numero, $documento, $entrada, $salida, $personas, $estado, $pago]) {
            $habitacion = Habitacion::where('numero', $numero)->first();

            if (! $habitacion) {
                continue;
            }

            $huesped = Huesped::where('documento', $documento)->first() ?? Huesped::query()->first();

            if (! $huesped) {
                continue;
            }

            $fechaEntrada = $base->copy()->addDays($entrada);
            $fechaSalida = $base->copy()->addDays($salida);
            $noches = max(1, (int) $fechaEntrada->diffInDays($fechaSalida));
            $tarifa = (float) ($habitacion->tipoHabitacion->tarifa ?? 0);
            $total = round($noches * $tarifa, 2);

            $reserva = Reserva::query()
                ->where('habitacion_id', $habitacion->id)
                ->whereDate('fecha_entrada', $fechaEntrada->toDateString())
                ->first();

            if (! $reserva) {
                $reserva = Reserva::create([
                    'habitacion_id' => $habitacion->id,
                    'huesped_id' => $huesped->id,
                    'fecha_entrada' => $fechaEntrada->toDateString(),
                    'fecha_salida' => $fechaSalida->toDateString(),
                    'num_huespedes' => $personas,
                    'total' => $total,
                    'estado' => $estado,
                ]);
            }

            // Noches ocupadas: solo las reservas activas ocupan fechas.
            if ($reserva->activa()) {
                for ($noche = $reserva->fecha_entrada->copy(); $noche->lt($reserva->fecha_salida); $noche->addDay()) {
                    DB::table('reservas_noches')->insertOrIgnore([
                        'reserva_id' => $reserva->id,
                        'habitacion_id' => $reserva->habitacion_id,
                        'noche' => $noche->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($reserva->wasRecentlyCreated && $reserva->estado === Reserva::ESTADO_EN_ESTADIA) {
                $habitacion->update(['estado' => 'ocupada']);
            }

            if ($pago === null || ! $reserva->activa() || $reserva->cobros()->exists()) {
                continue;
            }

            $monto = $pago === 'total'
                ? (float) $reserva->total
                : anticipo_minimo((float) $reserva->total);

            Cobro::create([
                'reserva_id' => $reserva->id,
                'user_id' => $recepcionista->id,
                'monto' => $monto,
                'metodo' => ['efectivo', 'qr', 'transferencia'][$reserva->id % 3],
                'concepto' => $pago === 'total' ? 'Pago total de la estadía' : 'Anticipo de reserva',
                'tipo' => Cobro::TIPO_PAGO,
                'fecha' => $reserva->fecha_entrada->toDateString(),
                'clave_idempotencia' => (string) Str::uuid(),
            ]);
        }
    }
}
