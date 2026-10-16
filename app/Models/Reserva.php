<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserva extends Model
{
    public const ESTADOS = [
        'pendiente',
        'confirmada',
        'hospedado',
        'finalizada',
        'cancelada',
    ];

    protected $fillable = [
        'habitacion_id',
        'huesped_id',
        'fecha_entrada',
        'fecha_salida',
        'num_huespedes',
        'total',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_entrada' => 'date',
        'fecha_salida' => 'date',
        'total' => 'decimal:2',
        'num_huespedes' => 'integer',
    ];

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class);
    }

    public function huesped(): BelongsTo
    {
        return $this->belongsTo(Huesped::class);
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class);
    }

    public function noches(): int
    {
        if (! $this->fecha_entrada || ! $this->fecha_salida) {
            return 0;
        }

        return (int) round(max(0, $this->fecha_entrada->diffInDays($this->fecha_salida)));
    }

    public function activa(): bool
    {
        return ! in_array($this->estado, ['cancelada', 'finalizada']);
    }
}
