<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cobro;
use App\Models\Reserva;
use App\Models\User;
use App\Repositories\CobroRepository;
use Illuminate\Database\QueryException;
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
        if (in_array($reserva->estado, [Reserva::ESTADO_CANCELADA, Reserva::ESTADO_FINALIZADA], true)) {
            throw new OperacionInvalida('No se pueden registrar cobros a una reserva cancelada o finalizada.');
        }

        $monto = round((float) ($datos['monto'] ?? 0), 2);

        if ($monto <= 0) {
            throw new OperacionInvalida('El monto del cobro debe ser mayor a 0.');
        }

        $claveIdem = $datos['clave_idempotencia'] ?? null;

        if ($claveIdem) {
            $existente = $this->cobros->porClaveIdempotencia($claveIdem);

            if ($existente) {
                return $this->resultadoIdempotente($existente, $reserva);
            }
        } else {
            $claveIdem = (string) Str::uuid();
        }

        $datos['clave_idempotencia'] = $claveIdem;

        try {
            $resultado = DB::transaction(function () use ($reserva, $datos, $usuario, $monto, $claveIdem) {
                $reservaBloqueada = Reserva::query()
                    ->whereKey($reserva->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($reservaBloqueada->estado, [Reserva::ESTADO_CANCELADA, Reserva::ESTADO_FINALIZADA], true)) {
                    throw new OperacionInvalida('No se pueden registrar cobros a una reserva cancelada o finalizada.');
                }

                $existente = $this->cobros->porClaveIdempotencia($claveIdem);

                if ($existente) {
                    return ['cobro' => $existente, 'idempotente' => true];
                }

                $cobrado = $this->totalCobrado($reservaBloqueada->id);
                $saldo = round((float) $reservaBloqueada->total - $cobrado, 2);

                if ($monto > $saldo + 0.009) {
                    throw new OperacionInvalida(
                        'El cobro de '.bs($monto)
                        .' excede el saldo pendiente de '.bs($saldo)
                    );
                }

                $datosParaCrear = [
                    'reserva_id' => $reservaBloqueada->id,
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

                return ['cobro' => $this->cobros->crear($datosParaCrear), 'idempotente' => false];
            });
        } catch (QueryException $e) {
            if (! $this->esClaveIdempotenciaDuplicada($e)) {
                throw $e;
            }

            $existente = $this->cobros->porClaveIdempotencia($claveIdem);

            if (! $existente) {
                throw $e;
            }

            return $this->resultadoIdempotente($existente, $reserva);
        }

        if ($resultado['idempotente']) {
            return $this->resultadoIdempotente($resultado['cobro'], $reserva);
        }

        $cobro = $resultado['cobro'];

        Auditoria::registrar('pago', 'cobro', $cobro->id, 'Cobro registrado: '.bs($cobro->monto).' sobre reserva #'.$reserva->id, [], $cobro->only([
            'monto', 'metodo', 'concepto', 'reserva_id',
        ]));

        return $cobro;
    }

    private function resultadoIdempotente(Cobro $cobro, Reserva $reserva): Cobro
    {
        if ((int) $cobro->reserva_id !== (int) $reserva->id) {
            throw new OperacionInvalida('La referencia de pago ya fue utilizada en otra reserva.');
        }

        Auditoria::registrar('pago', 'cobro', $cobro->id, 'Intento de cobro duplicado (idempotente)', [], ['reserva_id' => $reserva->id]);

        return $cobro;
    }

    private function esClaveIdempotenciaDuplicada(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true)
            && str_contains($e->getMessage(), 'clave_idempotencia');
    }

    /**
     * Anula un cobro mediante un contraasiento con motivo (HU18).
     * Solo aplica para pagos no anulados.
     */
    public function anular(Cobro $cobro, string $motivo, User $usuario): Cobro
    {
        return DB::transaction(function () use ($cobro, $motivo, $usuario) {
            $reserva = Reserva::withTrashed()
                ->whereKey($cobro->reserva_id)
                ->lockForUpdate()
                ->firstOrFail();

            $cobroBloqueado = Cobro::query()
                ->whereKey($cobro->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reserva->estado === Reserva::ESTADO_FINALIZADA) {
                throw new OperacionInvalida('No se pueden anular cobros de una reserva finalizada.');
            }

            if ($cobroBloqueado->anulado || $cobroBloqueado->esContraasiento()) {
                throw new OperacionInvalida('El cobro ya se encuentra anulado.');
            }

            $cobroBloqueado->anulado = true;
            $cobroBloqueado->save();

            $contraasiento = $this->cobros->crear([
                'reserva_id' => $cobroBloqueado->reserva_id,
                'cobro_origen_id' => $cobroBloqueado->id,
                'user_id' => $usuario->id,
                'monto' => -abs((float) $cobroBloqueado->monto),
                'metodo' => $cobroBloqueado->metodo,
                'concepto' => 'Anulación: '.$cobroBloqueado->concepto,
                'tipo' => Cobro::TIPO_CONTRAASIENTO,
                'motivo' => $motivo,
                'anulado' => false,
                'fecha' => now()->toDateString(),
                'clave_idempotencia' => null,
            ]);

            Auditoria::registrar('anulacion', 'cobro', $cobroBloqueado->id, 'Cobro anulado: '.$motivo, $cobroBloqueado->only(['monto']), $contraasiento->only(['monto', 'concepto']));

            return $contraasiento;
        });
    }
}
