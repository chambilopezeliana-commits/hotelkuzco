<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Habitacion extends Model
{
    use SoftDeletes;

    public const ESTADOS = ['disponible', 'ocupada', 'mantenimiento', 'limpieza'];

    public const ESTADOS_NO_DISPONIBLES = ['mantenimiento', 'limpieza'];

    public function requiereLimpieza(): bool
    {
        return $this->estado === 'limpieza';
    }

    protected $table = 'habitaciones';

    protected $fillable = [
        'numero',
        'tipo_habitacion_id',
        'estado',
        'descripcion',
    ];

    public function tipoHabitacion(): BelongsTo
    {
        return $this->belongsTo(TipoHabitacion::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function nochesOcupadas(): HasMany
    {
        return $this->hasMany(ReservaNoche::class);
    }

    public function getTipoAttribute(): ?string
    {
        return $this->tipoHabitacion?->nombre;
    }

    public function getPrecioAttribute(): ?string
    {
        return $this->tipoHabitacion?->tarifa;
    }

    public function getCapacidadAttribute(): ?int
    {
        return $this->tipoHabitacion?->capacidad;
    }
}
