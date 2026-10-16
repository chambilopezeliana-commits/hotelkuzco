<?php

namespace App\Repositories;

use App\Models\Habitacion;
use App\Models\Reserva;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HabitacionRepository
{
    public function listar(?string $buscar = null, int $porPagina = 10): LengthAwarePaginator
    {
        return Habitacion::query()
            ->with('tipoHabitacion')
            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('numero', 'like', "%{$buscar}%")
                        ->orWhereHas('tipoHabitacion', fn ($t) => $t->where('nombre', 'like', "%{$buscar}%"));
                });
            })
            ->orderBy('numero')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function todas(): Collection
    {
        return Habitacion::with('tipoHabitacion')->orderBy('numero')->get();
    }

    public function encontrar(int $id): Habitacion
    {
        return Habitacion::with('tipoHabitacion')->findOrFail($id);
    }

    public function bloquear(int $id): Habitacion
    {
        return Habitacion::with('tipoHabitacion')->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function crear(array $datos): Habitacion
    {
        return Habitacion::create($datos);
    }

    public function actualizar(Habitacion $habitacion, array $datos): Habitacion
    {
        $habitacion->update($datos);

        return $habitacion;
    }

    public function eliminar(Habitacion $habitacion): void
    {
        $habitacion->delete();
    }

    public function tieneReservasActivas(int $habitacionId): bool
    {
        return Reserva::query()
            ->where('habitacion_id', $habitacionId)
            ->whereIn('estado', ['pendiente', 'confirmada', 'hospedado'])
            ->exists();
    }

    /**
     * Habitaciones disponibles para un rango de fechas, filtrando por tipo y
     * capacidad mínima (HU7).
     *
     * @return Collection<int, Habitacion>
     */
    public function disponibles(
        string $entrada,
        string $salida,
        ?int $tipoId = null,
        ?int $capacidadMin = null,
    ): Collection {
        return Habitacion::query()
            ->with('tipoHabitacion')
            ->where('estado', '!=', 'mantenimiento')
            ->when($tipoId, fn (Builder $q) => $q->where('tipo_habitacion_id', $tipoId))
            ->when($capacidadMin, fn (Builder $q) => $q->whereHas(
                'tipoHabitacion',
                fn (Builder $t) => $t->where('capacidad', '>=', $capacidadMin)
            ))
            ->whereDoesntHave('reservas', function (Builder $q) use ($entrada, $salida) {
                $q->where('estado', '!=', 'cancelada')
                    ->where('fecha_entrada', '<', $salida)
                    ->where('fecha_salida', '>', $entrada);
            })
            ->orderBy('numero')
            ->get();
    }
}
