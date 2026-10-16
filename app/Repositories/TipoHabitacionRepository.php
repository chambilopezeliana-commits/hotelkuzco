<?php

namespace App\Repositories;

use App\Models\TipoHabitacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TipoHabitacionRepository
{
    public function listar(?string $buscar = null, int $porPagina = 10): LengthAwarePaginator
    {
        return TipoHabitacion::query()
            ->withCount('habitaciones')
            ->when($buscar, fn ($query, $buscar) => $query->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function activos(): Collection
    {
        return TipoHabitacion::activos()->orderBy('nombre')->get();
    }

    public function todos(): Collection
    {
        return TipoHabitacion::orderBy('nombre')->get();
    }

    public function encontrar(int $id): TipoHabitacion
    {
        return TipoHabitacion::findOrFail($id);
    }

    public function crear(array $datos): TipoHabitacion
    {
        return TipoHabitacion::create($datos);
    }

    public function actualizar(TipoHabitacion $tipo, array $datos): TipoHabitacion
    {
        $tipo->update($datos);

        return $tipo;
    }

    public function eliminar(TipoHabitacion $tipo): void
    {
        $tipo->delete();
    }

    public function tieneHabitaciones(int $tipoId): bool
    {
        return TipoHabitacion::whereKey($tipoId)->whereHas('habitaciones')->exists();
    }
}
