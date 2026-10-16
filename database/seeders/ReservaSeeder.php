<?php

namespace Database\Seeders;

use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReservaSeeder extends Seeder
{
    /**
     * Crea un conjunto de reservas de ejemplo que cubre todos los estados
     * (pendiente, confirmada, hospedado, finalizada y cancelada), incluyendo
     * check-ins activos con su cobro asociado.
     */
    public function run(): void
    {
        $base = Carbon::now()->startOfDay();
        $recepcionista = User::where('role', User::ROLE_RECEPCIONISTA)->first()
            ?? User::query()->firstOrFail();

        // [numero habitación, documento huésped, entrada, salida, n° huéspedes, estado, cobro]
        // cobro: null = sin cobro, número = monto abonado, 'total' = pago completo.
        $ejemplos = [
            ['101', '10234567', -10, -7, 1, 'finalizada', 'total'],
            ['101', '20345678', -5, 2, 1, 'hospedado', 200],
            ['102', '40567890', 0, 3, 1, 'confirmada', 150],
            ['103', '50678901', 5, 8, 1, 'pendiente', null],
            ['104', '60789012', -20, -18, 1, 'cancelada', null],
            ['201', '10234567', -8, -5, 2, 'finalizada', 'total'],
            ['201', '30456789', 3, 6, 2, 'confirmada', 250],
            ['202', '20345678', -3, 1, 2, 'hospedado', 300],
            ['203', '40567890', 10, 14, 2, 'confirmada', null],
            ['204', '50678901', -1, 2, 2, 'hospedado', null],
            ['301', '20345678', 1, 4, 3, 'confirmada', null],
            ['302', '40567890', -4, -1, 3, 'finalizada', 'total'],
            ['401', '60789012', -15, -12, 4, 'finalizada', 'total'],
            ['402', '10234567', 7, 11, 3, 'confirmada', 350],
            ['501', '30456789', -6, -2, 2, 'finalizada', 'total'],
            ['501', '20345678', 20, 23, 2, 'pendiente', null],
            ['502', '40567890', -2, 3, 3, 'hospedado', 1000],
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

            $reserva = Reserva::firstOrCreate(
                ['habitacion_id' => $habitacion->id, 'fecha_entrada' => $fechaEntrada->toDateString()],
                [
                    'huesped_id' => $huesped->id,
                    'fecha_salida' => $fechaSalida->toDateString(),
                    'num_huespedes' => $personas,
                    'total' => $total,
                    'estado' => $estado,
                ],
            );

            if ($estado === 'hospedado') {
                $habitacion->update(['estado' => 'ocupada']);
            }

            if ($pago === null) {
                continue;
            }

            $monto = $pago === 'total' ? $total : (float) $pago;

            Cobro::create([
                'reserva_id' => $reserva->id,
                'user_id' => $recepcionista->id,
                'monto' => $monto,
                'metodo' => ['efectivo', 'tarjeta', 'transferencia'][$reserva->id % 3],
                'concepto' => $pago === 'total' ? 'Pago total de la estadía' : 'Anticipo de estadía',
                'tipo' => Cobro::TIPO_PAGO,
                'fecha' => $fechaEntrada->toDateString(),
                'clave_idempotencia' => (string) Str::uuid(),
            ]);
        }
    }
}
