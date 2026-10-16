@extends('layouts.app')

@section('titulo', 'Habitaciones')

@section('contenido')
<div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
    <div>
        <h1 class="page-title">Habitaciones</h1>
        <p class="page-sub">Inventario de habitaciones y su estado operativo</p>
    </div>
    @if (auth()->user()->esAdmin())
        <a href="{{ route('habitaciones.create') }}" class="btn btn-primary">+ Nueva habitación</a>
    @endif
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('habitaciones.index') }}" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <label class="label" for="buscar">Buscar</label>
                <input type="text" id="buscar" name="buscar" class="filter-input w-full" value="{{ $buscar }}" placeholder="Número o tipo...">
            </div>
            <button class="btn btn-primary" type="submit">Buscar</button>
            @if ($buscar)
                <a href="{{ route('habitaciones.index') }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    @if ($habitaciones->isEmpty())
        <div class="empty-state">
            <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h1.5c.621 0 1.125.504 1.125 1.125V21"/></svg>
            No se encontraron habitaciones.
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Capacidad</th>
                        <th>Precio / noche</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                        @if (auth()->user()->esAdmin())
                            <th class="text-right">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php
                        $estadoBadge = ['disponible' => 'badge-success', 'ocupada' => 'badge-danger', 'mantenimiento' => 'badge-warning'];
                    @endphp
                    @foreach ($habitaciones as $habitacion)
                    <tr>
                        <td class="font-semibold text-slate-900">Nº {{ $habitacion->numero }}</td>
                        <td><span class="badge badge-info">{{ $habitacion->tipo ?? '—' }}</span></td>
                        <td>{{ $habitacion->capacidad ?? '—' }} personas</td>
                        <td class="font-semibold">{{ bs($habitacion->precio) }}</td>
                        <td><span class="badge {{ $estadoBadge[$habitacion->estado] ?? 'badge-neutral' }}">{{ ucfirst($habitacion->estado) }}</span></td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($habitacion->descripcion, 40) }}</td>
                        @if (auth()->user()->esAdmin())
                        <td>
                            <div class="acciones justify-end">
                                <a href="{{ route('habitaciones.edit', $habitacion) }}" class="btn btn-sm btn-secondary">Editar</a>
                                <form method="POST" action="{{ route('habitaciones.destroy', $habitacion) }}" onsubmit="return confirm('¿Eliminar la habitación {{ $habitacion->numero }}?')">
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
        <div class="px-5 pb-5">{{ $habitaciones->links() }}</div>
    @endif
</div>
@endsection
