@extends('layouts.app')

@section('titulo', 'Auditoría')

@section('contenido')
<div class="mb-6">
    <h1 class="page-title">Bitácora de auditoría</h1>
    <p class="page-sub">Registro de quién hizo qué en tarifas, reservas, pagos y usuarios.</p>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('auditorias.index') }}" class="flex items-end gap-3 flex-wrap">
            <div>
                <label class="label" for="entidad">Entidad</label>
                <select id="entidad" name="entidad" class="filter-input">
                    <option value="">Todas</option>
                    @foreach (\App\Models\Auditoria::ENTIDADES as $opcion)
                        <option value="{{ $opcion }}" {{ $entidad === $opcion ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $opcion)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="accion">Acción</label>
                <select id="accion" name="accion" class="filter-input">
                    <option value="">Todas</option>
                    @foreach (\App\Models\Auditoria::ACCIONES as $opcion)
                        <option value="{{ $opcion }}" {{ $accion === $opcion ? 'selected' : '' }}>{{ ucfirst($opcion) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="desde">Desde</label>
                <input type="date" id="desde" name="desde" class="filter-input" value="{{ $desde }}">
            </div>
            <div>
                <label class="label" for="hasta">Hasta</label>
                <input type="date" id="hasta" name="hasta" class="filter-input" value="{{ $hasta }}">
            </div>
            <button class="btn btn-primary" type="submit">Filtrar</button>
            @if ($entidad || $accion || $desde || $hasta)
                <a href="{{ route('auditorias.index') }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    @if ($auditorias->isEmpty())
        <div class="empty-state">No hay registros de auditoría para los filtros seleccionados.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha / hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Entidad</th>
                        <th>Descripción</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $accionBadge = ['crear' => 'badge-success', 'actualizar' => 'badge-info', 'eliminar' => 'badge-danger', 'anulacion' => 'badge-danger', 'desactivar' => 'badge-warning', 'activar' => 'badge-success', 'check-in' => 'badge-info', 'check-out' => 'badge-neutral', 'pago' => 'badge-success', 'cancelar' => 'badge-warning'];
                    @endphp
                    @foreach ($auditorias as $registro)
                    <tr>
                        <td class="whitespace-nowrap text-slate-500">{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                        <td class="font-medium text-slate-900">{{ $registro->usuario->name ?? 'Sistema' }}</td>
                        <td><span class="badge {{ $accionBadge[$registro->accion] ?? 'badge-neutral' }}">{{ ucfirst($registro->accion) }}</span></td>
                        <td class="text-slate-600">{{ ucfirst(str_replace('_', ' ', $registro->entidad)) }}{{ $registro->entidad_id ? ' #'.$registro->entidad_id : '' }}</td>
                        <td>{{ $registro->descripcion ?? '—' }}</td>
                        <td class="text-xs text-slate-400">{{ $registro->ip ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 pb-5">{{ $auditorias->links() }}</div>
    @endif
</div>
@endsection
