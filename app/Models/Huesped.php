<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Huesped extends Model
{
    protected $table = 'huespedes';

    public const TIPOS_DOCUMENTO = ['DNI', 'CI', 'PASAPORTE', 'CE', 'NIT'];

    protected $fillable = [
        'nombre',
        'tipo_documento',
        'documento',
        'email',
        'telefono',
        'direccion',
    ];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function getDocumentoCompletoAttribute(): string
    {
        return trim(($this->tipo_documento ?? '').' '.$this->documento);
    }
}
