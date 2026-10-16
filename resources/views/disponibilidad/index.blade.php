@extends('layouts.app')

@section('titulo', 'Disponibilidad')

@section('contenido')
<div class="mb-6">
    <h1 class="page-title">Disponibilidad de habitaciones</h1>
    <p class="page-sub">Consulte habitaciones libres por rango de fechas, tipo y capacidad.</p>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('disponibilidad.index') }}" class="flex items-end gap-3 flex-wrap">
            <div>
                <label class="label" for="entrada">Entrada</label>
                <input type="date" id="entrada" name="entrada" class="filter-input" value="{{ $entrada }}" required>
            </div>
            <div>
                <label class="label" for="salida">Salida</label>
                <input type="date" id="salida" name="salida" class="filter-input" value="{{ $salida }}" required>
            </div>
            <div>
                <label class="label" for="tipo_habitacion_id">Tipo</label>
                <select id="tipo_habitacion_id" name="tipo_habitacion_id" class="filter-input">
                    <option value="">Todos los tipos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->id }}" {{ (string) $tipoId === (string) $tipo->id ? 'selected' : '' }}>
                            {{ $tipo->nombre }} ({{ bs($tipo->tarifa) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="capacidad">Capacidad mínima</label>
                <input type="number" id="capacidad" name="capacidad" class="filter-input w-28" min="1" max="20" value="{{ $capacidad }}" placeholder="Ej: 2">
            </div>
            <button class="btn btn-primary" type="submit">Buscar disponibilidad</button>
        </form>
    </div>
</div>

<div class="mb-4 flex items-center gap-2 text-sm text-slate-600">
    <span class="font-semibold">{{ $disponibles->count() }}</span> habitación(es) disponible(s)
    <span class="text-slate-400">·</span>
    <span>{{ \Carbon\Carbon::parse($entrada)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($salida)->format('d/m/Y') }}</span>
</div>

@if ($disponibles->isEmpty())
    <div class="card"><div class="empty-state">No hay habitaciones disponibles con los criterios seleccionados.</div></div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach ($disponibles as $habitacion)
            <div class="card p-5 hover:border-marca-300 hover:shadow-md transition">
                <div class="flex items-start justify-between">
                    <div class="h-12 w-12 rounded-xl bg-marca-50 text-marca-700 flex items-center justify-center font-bold text-lg">
                        {{ $habitacion->numero }}
                    </div>
                    <span class="badge badge-success">Disponible</span>
                </div>
                <div class="mt-4">
                    <div class="font-semibold text-slate-900">{{ $habitacion->tipoHabitacion->nombre ?? 'Sin tipo' }}</div>
                    <div class="text-xs text-slate-500">{{ $habitacion->tipoHabitacion->capacidad ?? '—' }} personas</div>
                </div>
                <div class="mt-3 flex items-end justify-between">
                    <div>
                        <div class="text-xs text-slate-400">Tarifa / noche</div>
                        <div class="text-lg font-bold text-marca-600">{{ bs($habitacion->precio) }}</div>
                    </div>
                    <a href="{{ route('reservas.create') }}" class="btn btn-sm btn-primary">Reservar</a>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
