<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cobro;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\ReservaNoche;
use App\Models\User;
use App\Repositories\HabitacionRepository;
use App\Repositories\ReservaRepository;
use Illuminate\Database\QueryException;
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
            $datos['estado'] = $datos['estado'] ?? Reserva::ESTADO_CONFIRMADA;

            $reserva = $this->reservas->crear($datos);

            if ($reserva->estado !== Reserva::ESTADO_CANCELADA) {
                $this->registrarNoches($reserva);
            }

            return $reserva;
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
        $datos['estado'] = Reserva::ESTADO_CONFIRMADA;
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
        $this->validarFechas($datos['fecha_entrada'], $datos['fecha_salida']);

        $resultado = DB::transaction(function () use ($reserva, $datos) {
            $reservaBloqueada = $this->bloquearReserva($reserva->id);
            $this->validarEditable($reservaBloqueada, $datos);

            $anteriores = $reservaBloqueada->only(['habitacion_id', 'fecha_entrada', 'fecha_salida', 'estado', 'total']);

            $habitacion = $this->habitaciones->bloquear($datos['habitacion_id']);

            $this->validarHabitacionOperativa($habitacion);
            $this->validarCapacidad($habitacion, (int) $datos['num_huespedes']);
            $this->validarSinSolape(
                $habitacion,
                $datos['fecha_entrada'],
                $datos['fecha_salida'],
                $reservaBloqueada->id
            );

            $datos['total'] = $this->calcularTotal($habitacion->precio, $datos['fecha_entrada'], $datos['fecha_salida']);
            $cobrado = $this->cobros->totalCobrado($reservaBloqueada->id);

            if (round((float) $datos['total'], 2) + 0.009 < $cobrado) {
                throw new OperacionInvalida(
                    'No se puede reducir el total de la reserva por debajo de lo cobrado ('.bs($cobrado).'). '
                    .'Anule el cobro excedente primero.'
                );
            }

            $reservaActualizada = $this->reservas->guardar($reservaBloqueada, $datos);

            if ($reservaActualizada->estado === Reserva::ESTADO_CANCELADA) {
                $this->borrarNoches($reservaActualizada);
            } else {
                $this->registrarNoches($reservaActualizada);
            }

            return ['reserva' => $reservaActualizada, 'anteriores' => $anteriores];
        });

        $reserva = $resultado['reserva'];

        Auditoria::registrar('actualizar', 'reserva', $reserva->id, 'Reserva #'.$reserva->id.' actualizada', $resultado['anteriores'], $reserva->only([
            'habitacion_id', 'fecha_entrada', 'fecha_salida', 'estado', 'total',
        ]));

        return $reserva;
    }

    public function cancelar(Reserva $reserva): Reserva
    {
        $reserva = DB::transaction(function () use ($reserva) {
            $reservaBloqueada = $this->bloquearReserva($reserva->id);

            if ($reservaBloqueada->enEstadia()) {
                throw new OperacionInvalida('No se puede cancelar una reserva con huésped en estadía.');
            }

            if ($reservaBloqueada->estado !== Reserva::ESTADO_CONFIRMADA) {
                throw new OperacionInvalida('Solo se pueden cancelar reservas confirmadas.');
            }

            $habitacion = $this->habitaciones->bloquear($reservaBloqueada->habitacion_id);

            $reservaCancelada = $this->reservas->guardar($reservaBloqueada, ['estado' => Reserva::ESTADO_CANCELADA]);

            // Cancelar la reserva libera sus noches ocupadas (HU12).
            $this->borrarNoches($reservaCancelada);

            $this->liberarSiCorresponde($habitacion);

            return $reservaCancelada;
        });

        Auditoria::registrar('cancelar', 'reserva', $reserva->id, 'Reserva #'.$reserva->id.' cancelada', ['estado' => Reserva::ESTADO_CONFIRMADA], ['estado' => Reserva::ESTADO_CANCELADA]);

        return $reserva;
    }

    public function eliminar(Reserva $reserva): void
    {
        $datos = DB::transaction(function () use ($reserva) {
            $reservaBloqueada = $this->bloquearReserva($reserva->id);

            if ($reservaBloqueada->enEstadia()) {
                throw new OperacionInvalida('No se puede eliminar una reserva con huésped en estadía. Realice el check-out.');
            }

            // La verificación se repite con la fila bloqueada para evitar carreras con cobros concurrentes.
            if ($reservaBloqueada->cobros()->exists()) {
                throw new OperacionInvalida(
                    'No se puede eliminar: la reserva #'.$reservaBloqueada->id.' tiene cobros registrados. Anule los cobros antes de eliminarla.'
                );
            }

            $habitacion = $this->habitaciones->bloquear($reservaBloqueada->habitacion_id);
            $datos = $reservaBloqueada->only(['habitacion_id', 'huesped_id', 'fecha_entrada', 'fecha_salida', 'estado', 'total']);

            $this->borrarNoches($reservaBloqueada);
            $this->reservas->eliminar($reservaBloqueada);
            $this->liberarSiCorresponde($habitacion);

            return $datos;
        });

        Auditoria::registrar('eliminar', 'reserva', $reserva->id, 'Reserva #'.$reserva->id.' eliminada', $datos, []);
    }

    /**
     * Check-in (HU14): toda reserva confirmada puede ocupar la habitación.
     * El pago no se exige en este punto; el saldo cero se valida al check-out (HU15).
     */
    public function checkIn(Reserva $reserva): Reserva
    {
        $hoy = now()->startOfDay();

        $reserva = DB::transaction(function () use ($reserva, $hoy) {
            $reservaBloqueada = $this->bloquearReserva($reserva->id);

            if ($reservaBloqueada->estado !== Reserva::ESTADO_CONFIRMADA) {
                throw new OperacionInvalida('Solo reservas confirmadas pueden hacer check-in.');
            }

            if (! $reservaBloqueada->huesped_id) {
                throw new OperacionInvalida('La reserva no tiene huésped asociado.');
            }

            if ($reservaBloqueada->fecha_entrada->copy()->startOfDay()->gt($hoy)) {
                throw new OperacionInvalida('La fecha de entrada aún no llegó.');
            }

            if ($reservaBloqueada->fecha_salida->copy()->startOfDay()->lte($hoy)) {
                throw new OperacionInvalida('La reserva ya venció. Registre una nueva reserva.');
            }

            $habitacion = $this->habitaciones->bloquear($reservaBloqueada->habitacion_id);

            $this->validarHabitacionOperativa($habitacion);

            $ocupadaPor = $habitacion->reservas()
                ->where('id', '!=', $reservaBloqueada->id)
                ->where('estado', Reserva::ESTADO_EN_ESTADIA)
                ->with('huesped')
                ->first();

            if ($ocupadaPor) {
                throw new OperacionInvalida(
                    'No es posible: la habitación '.$habitacion->numero.' ya está ocupada por '
                    .($ocupadaPor->huesped->nombre ?? 'otro huésped').'.'
                );
            }

            $habitacion->update(['estado' => 'ocupada']);

            return $this->reservas->guardar($reservaBloqueada, ['estado' => Reserva::ESTADO_EN_ESTADIA]);
        });

        Auditoria::registrar('check-in', 'reserva', $reserva->id, 'Check-in de la reserva #'.$reserva->id, [], ['estado' => Reserva::ESTADO_EN_ESTADIA]);

        return $reserva;
    }

    /**
     * Check-out (HU15): solo se libera la habitación con la cuenta saldada.
     * Excepción: el administrador puede autorizarlo con saldo pendiente,
     * dejando el motivo registrado en la bitácora de auditoría.
     */
    public function checkOut(Reserva $reserva, ?string $motivoExcepcion = null, ?User $usuario = null): Reserva
    {
        $resultado = DB::transaction(function () use ($reserva, $motivoExcepcion, $usuario) {
            $reservaBloqueada = $this->bloquearReserva($reserva->id);
            $habitacion = $this->habitaciones->bloquear($reservaBloqueada->habitacion_id);

            if ($reservaBloqueada->estado !== Reserva::ESTADO_EN_ESTADIA) {
                throw new OperacionInvalida('El check-out solo aplica a huéspedes en estadía.');
            }

            $saldo = $this->cobros->saldoPendiente($reservaBloqueada);
            $motivo = trim((string) $motivoExcepcion);
            $conExcepcion = $saldo > 0.009;

            if ($conExcepcion) {
                if ($motivo === '') {
                    throw new OperacionInvalida(
                        'No se puede hacer check-out: la cuenta tiene un saldo pendiente de '.bs($saldo)
                        .'. Registre el pago total o autorice la excepción con motivo.'
                    );
                }

                $usuarioExcepcion = $usuario ?? auth()->user();

                if (! $usuarioExcepcion || ! $usuarioExcepcion->esAdmin()) {
                    throw new OperacionInvalida('Solo el administrador puede autorizar el check-out con saldo pendiente.');
                }
            }

            // La reserva finalizada ya no ocupa noches: se liberan también las pasadas.
            $reservaBloqueada->nochesOcupadas()->delete();

            $habitacion->update(['estado' => 'limpieza']);

            $reservaFinalizada = $this->reservas->guardar($reservaBloqueada, ['estado' => Reserva::ESTADO_FINALIZADA]);

            return [
                'reserva' => $reservaFinalizada,
                'habitacion' => $habitacion,
                'saldo' => $saldo,
                'conExcepcion' => $conExcepcion,
                'motivo' => $motivo,
            ];
        });

        $reserva = $resultado['reserva'];

        Auditoria::registrar(
            'check-out',
            'reserva',
            $reserva->id,
            'Check-out de la reserva #'.$reserva->id.'; habitación '.$resultado['habitacion']->numero.' enviada a limpieza'
                .($resultado['conExcepcion'] ? '; excepción de saldo pendiente autorizada: '.$resultado['motivo'] : ''),
            [],
            [
                'estado' => Reserva::ESTADO_FINALIZADA,
                'saldo_pendiente' => round($resultado['saldo'], 2),
                'motivo_excepcion' => $resultado['conExcepcion'] ? $resultado['motivo'] : null,
            ]
        );

        return $reserva;
    }

    private function bloquearReserva(int $id): Reserva
    {
        $reserva = Reserva::query()->whereKey($id)->lockForUpdate()->first();

        if (! $reserva) {
            throw new OperacionInvalida('La reserva ya no existe o fue eliminada.');
        }

        return $reserva;
    }

    private function validarEditable(Reserva $reserva, array $datos): void
    {
        if ($reserva->enEstadia()) {
            throw new OperacionInvalida('No se puede editar una reserva con huésped en estadía. Use check-out primero.');
        }

        if ($reserva->estado === Reserva::ESTADO_FINALIZADA) {
            throw new OperacionInvalida('No se puede editar una reserva finalizada.');
        }

        if ($reserva->estado === Reserva::ESTADO_CANCELADA
            && ($datos['estado'] ?? Reserva::ESTADO_CANCELADA) !== Reserva::ESTADO_CANCELADA) {
            throw new OperacionInvalida('Una reserva cancelada no puede reactivarse. Registre una nueva reserva.');
        }
    }

    private function validarHabitacionOperativa(Habitacion $habitacion): void
    {
        if (! $habitacion->tipoHabitacion || ! $habitacion->tipoHabitacion->activo) {
            throw new OperacionInvalida(
                'No es posible: el tipo de habitación de la habitación '.$habitacion->numero.' está inactivo.'
            );
        }

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

        if ($conflicto->enEstadia()) {
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

    /**
     * Registra una fila en reservas_noches por cada noche [entrada, salida).
     * El índice único (habitación, noche) impide el solapamiento incluso
     * ante solicitudes concurrentes que superen la validación del servicio.
     */
    private function registrarNoches(Reserva $reserva): void
    {
        $this->borrarNoches($reserva);

        try {
            for ($noche = $reserva->fecha_entrada->copy(); $noche->lt($reserva->fecha_salida); $noche->addDay()) {
                ReservaNoche::create([
                    'reserva_id' => $reserva->id,
                    'habitacion_id' => $reserva->habitacion_id,
                    'noche' => $noche->toDateString(),
                ]);
            }
        } catch (QueryException $e) {
            $mensaje = strtolower($e->getMessage());

            if (
                ! in_array((string) $e->getCode(), ['23000', '23505'], true)
                || ! str_contains($mensaje, 'reservas_noches')
            ) {
                throw $e;
            }

            throw new OperacionInvalida(
                'La habitaci�n '.$reserva->habitacion->numero.' ya tiene una reserva que se solapa con el rango de fechas solicitado.'
            );
        }
    }

    private function borrarNoches(Reserva $reserva): void
    {
        $reserva->nochesOcupadas()->delete();
    }

    private function liberarSiCorresponde(Habitacion $habitacion): void
    {
        if ($habitacion->estado !== 'ocupada') {
            return;
        }

        $sigueOcupada = $habitacion->reservas()->where('estado', Reserva::ESTADO_EN_ESTADIA)->exists();

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
