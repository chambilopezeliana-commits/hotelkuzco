<?php

namespace App\Repositories;

use App\Models\Cobro;

class CobroRepository
{
    public function totalCobrado(int $reservaId): float
    {
        return (float) Cobro::where('reserva_id', $reservaId)->sum('monto');
    }

    public function porReserva(int $reservaId)
    {
        return Cobro::with(['usuario', 'cobroOrigen'])
            ->where('reserva_id', $reservaId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    public function porClaveIdempotencia(string $clave, ?int $reservaId = null): ?Cobro
    {
        return Cobro::query()
            ->where('clave_idempotencia', $clave)
            ->when($reservaId, fn ($query) => $query->where('reserva_id', $reservaId))
            ->first();
    }

    public function crear(array $datos): Cobro
    {
        return Cobro::create($datos);
    }
}
