<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoHabitacion extends Model
{
    protected $table = 'tipos_habitacion';

    protected $fillable = [
        'nombre',
        'tarifa',
        'capacidad',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'tarifa' => 'decimal:2',
        'capacidad' => 'integer',
        'activo' => 'boolean',
    ];

    public function habitaciones(): HasMany
    {
        return $this->hasMany(Habitacion::class);
    }

    /** @param Builder<TipoHabitacion> $query */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
