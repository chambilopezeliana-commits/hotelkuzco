<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UsuarioRepository
{
    public function listar(?string $buscar = null, int $porPagina = 10): LengthAwarePaginator
    {
        return User::query()
            ->when($buscar, fn ($query, $buscar) => $query->where('name', 'like', "%{$buscar}%"))
            ->orderBy('name')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function encontrar(int $id): User
    {
        return User::findOrFail($id);
    }

    public function crear(array $datos): User
    {
        return User::create($datos);
    }

    public function actualizar(User $usuario, array $datos): User
    {
        $usuario->update($datos);

        return $usuario;
    }

    public function eliminar(User $usuario): void
    {
        $usuario->delete();
    }
}
