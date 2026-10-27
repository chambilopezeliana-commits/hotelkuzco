<?php

namespace App\Repositories;

use App\Models\Huesped;
use App\Models\Reserva;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HuespedRepository
{
    public function listar(?string $buscar = null, int $porPagina = 10): LengthAwarePaginator
    {
        return Huesped::query()
            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('documento', 'like', "%{$buscar}%")
                        ->orWhere('tipo_documento', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('nombre')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function todos()
    {
        return Huesped::orderBy('nombre')->get();
    }

    public function porDocumento(string $tipoDocumento, string $documento): ?Huesped
    {
        return Huesped::where('tipo_documento', $tipoDocumento)
            ->where('documento', $documento)
            ->first();
    }

    public function crear(array $datos): Huesped
    {
        return Huesped::create($datos);
    }

    public function actualizar(Huesped $huesped, array $datos): Huesped
    {
        $huesped->update($datos);

        return $huesped;
    }

    public function eliminar(Huesped $huesped): void
    {
        $huesped->delete();
    }

    public function tieneReservas(int $huespedId): bool
    {
        return Reserva::withTrashed()
            ->where('huesped_id', $huespedId)
            ->exists();
    }
}
