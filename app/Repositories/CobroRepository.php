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

    public function porClaveIdempotencia(string $clave): ?Cobro
    {
        return Cobro::where('clave_idempotencia', $clave)->first();
    }

    public function crear(array $datos): Cobro
    {
        return Cobro::create($datos);
    }

    public function eliminar(Cobro $cobro): void
    {
        $cobro->delete();
    }

    public function contar(): int
    {
        return Cobro::count();
    }
}
