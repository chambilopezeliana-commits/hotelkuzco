@extends('layouts.app')

@section('titulo', 'Tipos de habitación')

@section('contenido')
<div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
    <div>
        <h1 class="page-title">Tipos de habitación</h1>
        <p class="page-sub">Tarifas y capacidades vigentes del establecimiento</p>
    </div>
    @if (auth()->user()->esAdmin())
        <a href="{{ route('tipos-habitacion.create') }}" class="btn btn-primary">+ Nuevo tipo</a>
    @endif
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('tipos-habitacion.index') }}" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <label class="label" for="buscar">Buscar</label>
                <input type="text" id="buscar" name="buscar" class="filter-input w-full" value="{{ $buscar }}" placeholder="Nombre del tipo...">
            </div>
            <button class="btn btn-primary" type="submit">Buscar</button>
            @if ($buscar)
                <a href="{{ route('tipos-habitacion.index') }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    @if ($tipos->isEmpty())
        <div class="empty-state">No hay tipos de habitación registrados.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Tarifa / noche</th>
                        <th>Capacidad</th>
                        <th>Habitaciones</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                        @if (auth()->user()->esAdmin())
                            <th class="text-right">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tipos as $tipo)
                    <tr>
                        <td class="font-semibold text-slate-900">{{ $tipo->nombre }}</td>
                        <td class="font-semibold">{{ bs($tipo->tarifa) }}</td>
                        <td>{{ $tipo->capacidad }} personas</td>
                        <td>{{ $tipo->habitaciones_count }}</td>
                        <td>
                            <span class="badge {{ $tipo->activo ? 'badge-success' : 'badge-neutral' }}">{{ $tipo->activo ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($tipo->descripcion, 45) }}</td>
                        @if (auth()->user()->esAdmin())
                        <td>
                            <div class="acciones justify-end">
                                <a href="{{ route('tipos-habitacion.edit', $tipo) }}" class="btn btn-sm btn-secondary">Editar</a>
                                <form method="POST" action="{{ route('tipos-habitacion.destroy', $tipo) }}" onsubmit="return confirm('¿Eliminar el tipo {{ $tipo->nombre }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 pb-5">{{ $tipos->links() }}</div>
    @endif
</div>
@endsection
