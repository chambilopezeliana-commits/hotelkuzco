<?php

namespace App\Repositories;

use App\Models\Reserva;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReservaRepository
{
    public function listar(?string $estado = null, ?string $buscar = null, ?string $desde = null, ?string $hasta = null, int $porPagina = 10): LengthAwarePaginator
    {
        return Reserva::query()
            ->with(['habitacion.tipoHabitacion', 'huesped'])
            ->when($estado, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->whereHas('huesped', function ($h) use ($buscar) {
                        $h->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('documento', 'like', "%{$buscar}%");
                    });
                });
            })
            ->when($desde, fn ($query, $desde) => $query->where('fecha_salida', '>=', $desde))
            ->when($hasta, fn ($query, $hasta) => $query->where('fecha_entrada', '<=', $hasta))
            ->orderByDesc('fecha_entrada')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function encontrar(int $id): Reserva
    {
        return Reserva::with(['habitacion.tipoHabitacion', 'huesped'])->findOrFail($id);
    }

    public function solapadas(int $habitacionId, string $entrada, string $salida, ?int $ignorarId = null)
    {
        return Reserva::query()
            ->where('habitacion_id', $habitacionId)
            ->whereNotIn('estado', [Reserva::ESTADO_CANCELADA, Reserva::ESTADO_FINALIZADA])
            ->when($ignorarId, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->where('fecha_entrada', '<', $salida)
            ->where('fecha_salida', '>', $entrada);
    }

    public function crear(array $datos): Reserva
    {
        return Reserva::create($datos);
    }

    public function guardar(Reserva $reserva, array $datos): Reserva
    {
        $reserva->update($datos);

        return $reserva;
    }

    public function eliminar(Reserva $reserva): void
    {
        $reserva->delete();
    }

    public function proximas(int $limite = 5)
    {
        return Reserva::with(['habitacion.tipoHabitacion', 'huesped'])
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->whereDate('fecha_salida', '>=', now()->toDateString())
            ->orderBy('fecha_entrada')
            ->limit($limite)
            ->get();
    }
}
