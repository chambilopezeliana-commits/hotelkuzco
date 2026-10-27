<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaNoche extends Model
{
    protected $table = 'reservas_noches';

    protected $fillable = [
        'reserva_id',
        'habitacion_id',
        'noche',
    ];

    protected $casts = [
        'noche' => 'date',
    ];

    /**
     * La noche se persiste como fecha pura (Y-m-d) para que el índice único
     * (habitación, noche) compare valores consistentes, sin truncar los
     * timestamps created_at/updated_at del modelo.
     */
    protected function noche(): Attribute
    {
        return Attribute::make(
            set: fn ($valor) => Carbon::parse($valor)->toDateString(),
        );
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class);
    }
}
