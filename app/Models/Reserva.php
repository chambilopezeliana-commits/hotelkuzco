<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reserva extends Model
{
    use SoftDeletes;

    public const ESTADO_CONFIRMADA = 'confirmada';

    public const ESTADO_EN_ESTADIA = 'en_estadia';

    public const ESTADO_FINALIZADA = 'finalizada';

    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::ESTADO_CONFIRMADA,
        self::ESTADO_EN_ESTADIA,
        self::ESTADO_FINALIZADA,
        self::ESTADO_CANCELADA,
    ];

    public const ESTADOS_ACTIVOS = [
        self::ESTADO_CONFIRMADA,
        self::ESTADO_EN_ESTADIA,
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
        return $this->belongsTo(Habitacion::class)->withTrashed();
    }

    public function huesped(): BelongsTo
    {
        return $this->belongsTo(Huesped::class)->withTrashed();
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class);
    }

    public function nochesOcupadas(): HasMany
    {
        return $this->hasMany(ReservaNoche::class);
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
        return ! in_array($this->estado, [self::ESTADO_CANCELADA, self::ESTADO_FINALIZADA]);
    }

    public function enEstadia(): bool
    {
        return $this->estado === self::ESTADO_EN_ESTADIA;
    }
}
