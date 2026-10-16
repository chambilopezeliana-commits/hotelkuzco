@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
<div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
    <div>
        <h1 class="page-title">Usuarios del sistema</h1>
        <p class="page-sub">Cuentas, roles y estado de acceso</p>
    </div>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">+ Nuevo usuario</a>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('usuarios.index') }}" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <label class="label" for="buscar">Buscar</label>
                <input type="text" id="buscar" name="buscar" class="filter-input w-full" value="{{ $buscar }}" placeholder="Nombre o correo...">
            </div>
            <button class="btn btn-primary" type="submit">Buscar</button>
            @if ($buscar)
                <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $usuario)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 text-sm font-bold shrink-0">
                                {{ strtoupper(substr($usuario->name, 0, 1)) }}
                            </div>
                            <span class="font-semibold text-slate-900">{{ $usuario->name }}</span>
                        </div>
                    </td>
                    <td>{{ $usuario->email }}</td>
                    <td>
                        <span class="badge {{ $usuario->esAdmin() ? 'badge-purple' : 'badge-info' }}">{{ ucfirst($usuario->role) }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $usuario->estaActivo() ? 'badge-success' : 'badge-danger' }}">{{ $usuario->estaActivo() ? 'Activo' : 'Desactivado' }}</span>
                    </td>
                    <td>
                        <div class="acciones justify-end">
                            <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-sm btn-secondary">Editar</a>
                            @if ($usuario->id !== auth()->id())
                                <form method="POST" action="{{ route('usuarios.toggle', $usuario) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm {{ $usuario->estaActivo() ? 'btn-secondary' : 'btn-success' }}" type="submit">
                                        {{ $usuario->estaActivo() ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Eliminar al usuario {{ $usuario->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 pb-5">{{ $usuarios->links() }}</div>
</div>
@endsection
