<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'administrador';

    public const ROLE_RECEPCIONISTA = 'recepcionista';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function esRecepcionista(): bool
    {
        return $this->role === self::ROLE_RECEPCIONISTA;
    }

    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }

    /** @param Builder<User> $query */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class);
    }
}
