<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    public const ACCIONES = ['crear', 'actualizar', 'desactivar', 'activar', 'eliminar', 'cancelar', 'check-in', 'check-out', 'pago', 'anulacion'];

    public const ENTIDADES = ['usuario', 'tipo_habitacion', 'habitacion', 'huesped', 'reserva', 'cobro'];

    protected $fillable = [
        'user_id',
        'accion',
        'entidad',
        'entidad_id',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Registra un evento en la bitácora de auditoría (HU20).
     *
     * @param  array<string, mixed>  $anteriores
     * @param  array<string, mixed>  $nuevos
     */
    public static function registrar(
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        ?string $descripcion = null,
        array $anteriores = [],
        array $nuevos = [],
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'descripcion' => $descripcion,
            'datos_anteriores' => $anteriores ?: null,
            'datos_nuevos' => $nuevos ?: null,
            'ip' => request()->ip(),
        ]);
    }
}
