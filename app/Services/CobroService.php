<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cobro;
use App\Models\Reserva;
use App\Models\User;
use App\Repositories\CobroRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CobroService
{
    public function __construct(
        private readonly CobroRepository $cobros,
    ) {}

    public function porReserva(int $reservaId)
    {
        return $this->cobros->porReserva($reservaId);
    }

    public function totalCobrado(int $reservaId): float
    {
        return $this->cobros->totalCobrado($reservaId);
    }

    public function saldoPendiente(Reserva $reserva): float
    {
        return round((float) $reserva->total - $this->totalCobrado($reserva->id), 2);
    }

    public function registrar(Reserva $reserva, array $datos, User $usuario): Cobro
    {
        if (in_array($reserva->estado, ['cancelada'])) {
            throw new OperacionInvalida('No se pueden registrar cobros a una reserva cancelada.');
        }

        $monto = round((float) ($datos['monto'] ?? 0), 2);

        if ($monto <= 0) {
            throw new OperacionInvalida('El monto del cobro debe ser mayor a 0.');
        }

        $claveIdem = $datos['clave_idempotencia'] ?? null;

        if ($claveIdem && $this->cobros->porClaveIdempotencia($claveIdem)) {
            Auditoria::registrar('pago', 'cobro', null, 'Intento de cobro duplicado (idempotente)', [], ['reserva_id' => $reserva->id]);

            return $this->cobros->porClaveIdempotencia($claveIdem);
        }

        if ($claveIdem === null) {
            $claveIdem = (string) Str::uuid();
            $datos['clave_idempotencia'] = $claveIdem;
        } else {
            $datos['clave_idempotencia'] = $claveIdem;
        }

        $cobro = DB::transaction(function () use ($reserva, $datos, $usuario, $monto) {
            $cobrado = $this->totalCobrado($reserva->id);
            $saldo = round((float) $reserva->total - $cobrado, 2);

            if ($monto > $saldo + 0.009) {
                throw new OperacionInvalida(
                    'El cobro de '.bs($monto)
                    .' excede el saldo pendiente de '.bs($saldo)
                );
            }

            $datosParaCrear = [
                'reserva_id' => $reserva->id,
                'cobro_origen_id' => $datos['cobro_origen_id'] ?? null,
                'user_id' => $usuario->id,
                'monto' => $monto,
                'metodo' => $datos['metodo'] ?? 'efectivo',
                'concepto' => $datos['concepto'] ?? 'Estadía',
                'tipo' => $datos['tipo'] ?? Cobro::TIPO_PAGO,
                'motivo' => $datos['motivo'] ?? null,
                'anulado' => false,
                'fecha' => $datos['fecha'] ?? now()->toDateString(),
                'clave_idempotencia' => $datos['clave_idempotencia'],
            ];

            return $this->cobros->crear($datosParaCrear);
        });

        Auditoria::registrar('pago', 'cobro', $cobro->id, 'Cobro registrado: '.bs($cobro->monto).' sobre reserva #'.$reserva->id, [], $cobro->only([
            'monto', 'metodo', 'concepto', 'reserva_id',
        ]));

        return $cobro;
    }

    /**
     * Anula un cobro mediante un contraasiento con motivo (HU18).
     * Solo aplica para pagos no anulados.
     */
    public function anular(Cobro $cobro, string $motivo, User $usuario): Cobro
    {
        if ($cobro->reserva->estado === 'finalizada') {
            throw new OperacionInvalida('No se pueden anular cobros de una reserva finalizada.');
        }

        if ($cobro->anulado || $cobro->esContraasiento()) {
            throw new OperacionInvalida('El cobro ya se encuentra anulado.');
        }

        return DB::transaction(function () use ($cobro, $motivo, $usuario) {
            $cobro->anulado = true;
            $cobro->save();

            $contraasiento = $this->cobros->crear([
                'reserva_id' => $cobro->reserva_id,
                'cobro_origen_id' => $cobro->id,
                'user_id' => $usuario->id,
                'monto' => -abs((float) $cobro->monto),
                'metodo' => $cobro->metodo,
                'concepto' => 'Anulación: '.$cobro->concepto,
                'tipo' => Cobro::TIPO_CONTRAASIENTO,
                'motivo' => $motivo,
                'anulado' => false,
                'fecha' => now()->toDateString(),
                'clave_idempotencia' => null,
            ]);

            Auditoria::registrar('anulacion', 'cobro', $cobro->id, 'Cobro anulado: '.$motivo, $cobro->only(['monto']), $contraasiento->only(['monto', 'concepto']));

            return $contraasiento;
        });
    }

    public function eliminar(Cobro $cobro): void
    {
        if ($cobro->reserva->estado === 'finalizada') {
            throw new OperacionInvalida('No se pueden eliminar cobros de una reserva finalizada.');
        }

        $this->cobros->eliminar($cobro);
    }
}
