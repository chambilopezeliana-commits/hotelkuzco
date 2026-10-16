<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\User;
use App\Repositories\HabitacionRepository;
use App\Repositories\ReservaRepository;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    public function __construct(
        private readonly ReservaRepository $reservas,
        private readonly HabitacionRepository $habitaciones,
        private readonly CobroService $cobros,
    ) {}

    public function listar(?string $estado = null, ?string $buscar = null, ?string $desde = null, ?string $hasta = null)
    {
        return $this->reservas->listar($estado, $buscar, $desde, $hasta);
    }

    public function encontrar(int $id): Reserva
    {
        return $this->reservas->encontrar($id);
    }

    /**
     * Anticipo mínimo para garantizar una reserva (porcentaje configurable).
     */
    public function anticipoMinimo(float $total): float
    {
        return anticipo_minimo($total);
    }

    public function crear(array $datos): Reserva
    {
        $this->validarFechas($datos['fecha_entrada'], $datos['fecha_salida']);

        $reserva = DB::transaction(function () use ($datos) {
            $habitacion = $this->habitaciones->bloquear($datos['habitacion_id']);

            $this->validarHabitacionOperativa($habitacion);

            $this->validarCapacidad($habitacion, (int) $datos['num_huespedes']);
            $this->validarSinSolape($habitacion, $datos['fecha_entrada'], $datos['fecha_salida']);

            $datos['total'] = $this->calcularTotal($habitacion->precio, $datos['fecha_entrada'], $datos['fecha_salida']);

            return $this->reservas->crear($datos);
        });

        $this->auditar('crear', $reserva, 'Reserva #'.$reserva->id.' creada');

        return $reserva;
    }

    /**
     * Registro directo (walk-in): crea la reserva con entrada en el día,
     * registra el pago total de la estadía y realiza el check-in (HU14).
     */
    public function walkIn(array $datos, User $usuario): Reserva
    {
        $datos['fecha_entrada'] = now()->toDateString();
        $datos['estado'] = 'confirmada';
        $metodo = $datos['metodo_pago'] ?? 'efectivo';
        unset($datos['metodo_pago']);

        return DB::transaction(function () use ($datos, $usuario, $metodo) {
            $reserva = $this->crear($datos);

            $this->cobros->registrar($reserva, [
                'monto' => (float) $reserva->total,
                'metodo' => $metodo,
                'concepto' => 'Estadía (check-in directo)',
                'fecha' => now()->toDateString(),
                'tipo' => Cobro::TIPO_PAGO,
            ], $usuario);

            return $this->checkIn($reserva);
        });
    }

    public function actualizar(Reserva $reserva, array $datos): Reserva
    {
        if ($reserva->estado === 'hospedado') {
            throw new OperacionInvalida('No se puede editar una reserva con huésped hospedado. Use check-out primero.');
        }

        if ($reserva->estado === 'finalizada') {
            throw new OperacionInvalida('No se puede editar una reserva finalizada.');
        }

        $this->validarFechas($datos['fecha_entrada'], $datos['fecha_salida']);

        $anteriores = $reserva->only(['habitacion_id', 'fecha_entrada', 'fecha_salida', 'estado', 'total']);

        $reserva = DB::transaction(function () use ($reserva, $datos) {
            $habitacion = $this->habitaciones->bloquear($datos['habitacion_id']);

            $this->validarCapacidad($habitacion, (int) $datos['num_huespedes']);
            $this->validarSinSolape(
                $habitacion,
                $datos['fecha_entrada'],
                $datos['fecha_salida'],
                $reserva->id
            );

            $datos['total'] = $this->calcularTotal($habitacion->precio, $datos['fecha_entrada'], $datos['fecha_salida']);

            return $this->reservas->guardar($reserva, $datos);
        });

        Auditoria::registrar('actualizar', 'reserva', $reserva->id, 'Reserva #'.$reserva->id.' actualizada', $anteriores, $reserva->only([
            'habitacion_id', 'fecha_entrada', 'fecha_salida', 'estado', 'total',
        ]));

        return $reserva;
    }

    public function cancelar(Reserva $reserva): Reserva
    {
        if ($reserva->estado === 'hospedado') {
            throw new OperacionInvalida('No se puede cancelar una reserva con huésped hospedado.');
        }

        $reserva = DB::transaction(function () use ($reserva) {
            $habitacion = $this->habitaciones->bloquear($reserva->habitacion_id);
            $reserva = $this->reservas->guardar($reserva, ['estado' => 'cancelada']);
            $this->liberarSiCorresponde($habitacion);

            return $reserva;
        });

        Auditoria::registrar('cancelar', 'reserva', $reserva->id, 'Reserva #'.$reserva->id.' cancelada', ['estado' => 'activa'], ['estado' => 'cancelada']);

        return $reserva;
    }

    public function eliminar(Reserva $reserva): void
    {
        if ($reserva->estado === 'hospedado') {
            throw new OperacionInvalida('No se puede eliminar una reserva con huésped hospedado. Realice el check-out.');
        }

        $datos = $reserva->only(['habitacion_id', 'huesped_id', 'fecha_entrada', 'fecha_salida', 'estado', 'total']);

        DB::transaction(function () use ($reserva) {
            $habitacion = $this->habitaciones->bloquear($reserva->habitacion_id);
            $this->reservas->eliminar($reserva);
            $this->liberarSiCorresponde($habitacion);
        });

        Auditoria::registrar('eliminar', 'reserva', $reserva->id, 'Reserva #'.$reserva->id.' eliminada', $datos, []);
    }

    public function checkIn(Reserva $reserva): Reserva
    {
        if (! in_array($reserva->estado, ['pendiente', 'confirmada'])) {
            throw new OperacionInvalida('Solo reservas pendientes o confirmadas pueden hacer check-in.');
        }

        if (! $reserva->huesped_id) {
            throw new OperacionInvalida('La reserva no tiene huésped asociado.');
        }

        if ($reserva->fecha_entrada->gt(now())) {
            throw new OperacionInvalida('La fecha de entrada aún no llegó.');
        }

        $saldo = $this->cobros->saldoPendiente($reserva);

        if ($saldo > 0.009) {
            throw new OperacionInvalida(
                'No se puede hacer check-in: la estadía tiene un saldo pendiente de '.bs($saldo)
                .'. Registre el pago total antes de entregar la habitación.'
            );
        }

        $reserva = DB::transaction(function () use ($reserva) {
            $habitacion = $this->habitaciones->bloquear($reserva->habitacion_id);

            $this->validarHabitacionOperativa($habitacion);

            $ocupadaPor = $habitacion->reservas()
                ->where('id', '!=', $reserva->id)
                ->where('estado', 'hospedado')
                ->with('huesped')
                ->first();

            if ($ocupadaPor) {
                throw new OperacionInvalida(
                    'No es posible: la habitación '.$habitacion->numero.' ya está ocupada por '
                    .($ocupadaPor->huesped->nombre ?? 'otro huésped').'.'
                );
            }

            $habitacion->update(['estado' => 'ocupada']);

            return $this->reservas->guardar($reserva, ['estado' => 'hospedado']);
        });

        Auditoria::registrar('check-in', 'reserva', $reserva->id, 'Check-in de la reserva #'.$reserva->id, [], ['estado' => 'hospedado']);

        return $reserva;
    }

    public function checkOut(Reserva $reserva): Reserva
    {
        if ($reserva->estado !== 'hospedado') {
            throw new OperacionInvalida('El check-out solo aplica a huéspedes hospedados.');
        }

        $reserva = DB::transaction(function () use ($reserva) {
            $habitacion = $this->habitaciones->bloquear($reserva->habitacion_id);
            $habitacion->update(['estado' => 'limpieza']);

            return $this->reservas->guardar($reserva, ['estado' => 'finalizada']);
        });

        Auditoria::registrar('check-out', 'reserva', $reserva->id, 'Check-out de la reserva #'.$reserva->id.'; habitación '.$reserva->habitacion->numero.' enviada a limpieza', [], ['estado' => 'finalizada']);

        return $reserva;
    }

    private function validarHabitacionOperativa(Habitacion $habitacion): void
    {
        if ($habitacion->estado === 'mantenimiento') {
            throw new OperacionInvalida(
                'No es posible: la habitación '.$habitacion->numero.' está en mantenimiento.'
            );
        }

        if ($habitacion->estado === 'limpieza') {
            throw new OperacionInvalida(
                'No es posible: la habitación '.$habitacion->numero.' está pendiente de limpieza.'
            );
        }
    }

    private function validarCapacidad(Habitacion $habitacion, int $numHuespedes): void
    {
        $capacidad = (int) ($habitacion->capacidad ?? 0);

        if ($capacidad > 0 && $numHuespedes > $capacidad) {
            throw new OperacionInvalida(
                'La habitación '.$habitacion->numero.' admite como máximo '.$capacidad.' huésped(es).'
            );
        }
    }

    private function validarSinSolape(Habitacion $habitacion, string $entrada, string $salida, ?int $ignorarId = null): void
    {
        $conflicto = $this->reservas->solapadas($habitacion->id, $entrada, $salida, $ignorarId)
            ->with('huesped')
            ->orderBy('fecha_entrada')
            ->first();

        if (! $conflicto) {
            return;
        }

        $numero = $habitacion->numero;
        $huesped = $conflicto->huesped->nombre ?? 'otro huésped';
        $del = $conflicto->fecha_entrada->format('d/m/Y');
        $al = $conflicto->fecha_salida->format('d/m/Y');

        if ($conflicto->estado === 'hospedado') {
            throw new OperacionInvalida(
                'No es posible: la habitación '.$numero.' está ocupada por '.$huesped
                .' (hospedaje del '.$del.' al '.$al.').'
            );
        }

        throw new OperacionInvalida(
            'No es posible: la habitación '.$numero.' ya está reservada por '.$huesped
            .' del '.$del.' al '.$al.'.'
        );
    }

    private function validarFechas(string $entrada, string $salida): void
    {
        if (strtotime($salida) <= strtotime($entrada)) {
            throw new OperacionInvalida('La fecha de salida debe ser posterior a la de entrada.');
        }
    }

    private function calcularTotal(string|float|null $precio, string $entrada, string $salida): float
    {
        $noches = max(1, (int) round((strtotime($salida) - strtotime($entrada)) / 86400));

        return round((float) $precio * $noches, 2);
    }

    private function liberarSiCorresponde(Habitacion $habitacion): void
    {
        if ($habitacion->estado !== 'ocupada') {
            return;
        }

        $sigueOcupada = $habitacion->reservas()->where('estado', 'hospedado')->exists();

        if (! $sigueOcupada) {
            $habitacion->update(['estado' => 'disponible']);
        }
    }

    private function auditar(string $accion, Reserva $reserva, string $descripcion): void
    {
        Auditoria::registrar($accion, 'reserva', $reserva->id, $descripcion, [], $reserva->only([
            'habitacion_id', 'huesped_id', 'fecha_entrada', 'fecha_salida', 'estado', 'total',
        ]));
    }
}
