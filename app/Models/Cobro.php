<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cobro extends Model
{
    public const METODOS = ['efectivo', 'tarjeta', 'transferencia'];

    public const TIPO_PAGO = 'pago';

    public const TIPO_CONTRAASIENTO = 'contraasiento';

    protected $fillable = [
        'reserva_id',
        'cobro_origen_id',
        'user_id',
        'monto',
        'metodo',
        'concepto',
        'tipo',
        'motivo',
        'anulado',
        'fecha',
        'clave_idempotencia',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
        'anulado' => 'boolean',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cobroOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'cobro_origen_id');
    }

    public function contraasientos(): HasMany
    {
        return $this->hasMany(self::class, 'cobro_origen_id');
    }

    public function esContraasiento(): bool
    {
        return $this->tipo === self::TIPO_CONTRAASIENTO;
    }
}
