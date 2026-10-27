@extends('layouts.app')

@section('titulo', 'Reservas')

@section('contenido')
<div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
    <div>
        <h1 class="page-title">Reservas</h1>
        <p class="page-sub">Gestión de reservas, check-in y check-out</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('reservas.walkin') }}" class="btn btn-success">Check-in directo</a>
        <a href="{{ route('reservas.create') }}" class="btn btn-primary">+ Nueva reserva</a>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('reservas.index') }}" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[180px]">
                <label class="label" for="buscar">Buscar cliente</label>
                <input type="text" id="buscar" name="buscar" class="filter-input w-full" value="{{ $buscar }}" placeholder="Nombre o documento...">
            </div>
            <div>
                <label class="label" for="estado">Estado</label>
                <select id="estado" name="estado" class="filter-input">
                    <option value="">Todos</option>
                    @php
                        $etiquetasEstado = ['confirmada' => 'Confirmada', 'en_estadia' => 'En estadía', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
                    @endphp
                    @foreach (\App\Models\Reserva::ESTADOS as $opcion)
                        <option value="{{ $opcion }}" {{ $estado === $opcion ? 'selected' : '' }}>{{ $etiquetasEstado[$opcion] ?? ucfirst($opcion) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="desde">Desde (entrada)</label>
                <input type="date" id="desde" name="desde" class="filter-input" value="{{ $desde }}">
            </div>
            <div>
                <label class="label" for="hasta">Hasta (salida)</label>
                <input type="date" id="hasta" name="hasta" class="filter-input" value="{{ $hasta }}">
            </div>
            <button class="btn btn-primary" type="submit">Filtrar</button>
            @if ($estado || $buscar || $desde || $hasta)
                <a href="{{ route('reservas.index') }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    @if ($reservas->isEmpty())
        <div class="empty-state">
            <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            No se encontraron reservas.
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Habitación</th>
                        <th>Estadía</th>
                        <th>Noches</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $clases = ['confirmada' => 'badge-success', 'en_estadia' => 'badge-info', 'finalizada' => 'badge-neutral', 'cancelada' => 'badge-danger'];
                    @endphp
                    @foreach ($reservas as $reserva)
                    <tr>
                        <td>
                            <div class="font-semibold text-slate-900">{{ $reserva->huesped?->nombre ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $reserva->huesped?->documento ?? '' }}</div>
                        </td>
                        <td>
                            <span class="font-medium">Nº {{ $reserva->habitacion?->numero ?? 'N/D' }}</span>
                            <div class="text-xs text-slate-500">{{ $reserva->habitacion?->tipo ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $reserva->fecha_entrada->format('d/m/Y') }} → {{ $reserva->fecha_salida->format('d/m/Y') }}</div>
                        </td>
                        <td>{{ $reserva->noches() }}</td>
                        <td class="font-semibold">{{ bs($reserva->total) }}</td>
                        <td><span class="badge {{ $clases[$reserva->estado] ?? 'badge-neutral' }}">{{ $etiquetasEstado[$reserva->estado] ?? ucfirst($reserva->estado) }}</span></td>
                        <td>
                            <div class="acciones justify-end">
                                @if ($reserva->estado === 'confirmada')
                                    <form method="POST" action="{{ route('reservas.checkin', $reserva) }}" onsubmit="return confirm('¿Realizar check-in? El saldo se valida al momento del check-out.')">
                                        @csrf
                                        <button class="btn btn-sm btn-success" type="submit">Check-in</button>
                                    </form>
                                @endif
                                @if ($reserva->estado === 'en_estadia')
                                    <form method="POST" action="{{ route('reservas.checkout', $reserva) }}" onsubmit="return confirm('¿Realizar check-out? La habitación pasará a limpieza y la cuenta debe estar saldada.')">
                                        @csrf
                                        @if (auth()->user()->esAdmin())
                                            <input type="text" name="motivo_excepcion" class="filter-input w-44 text-xs" placeholder="Motivo excepción (si hay saldo)" title="Solo administrador: motivo para check-out con saldo pendiente">
                                        @endif
                                        <button class="btn btn-sm btn-success" type="submit">Check-out</button>
                                    </form>
                                @endif
                                <a href="{{ route('cobros.index', $reserva) }}" class="btn btn-sm btn-primary">Cobros</a>
                                @if ($reserva->estado === 'confirmada')
                                    <a href="{{ route('reservas.edit', $reserva) }}" class="btn btn-sm btn-secondary">Editar</a>
                                    <form method="POST" action="{{ route('reservas.cancelar', $reserva) }}" onsubmit="return confirm('¿Cancelar esta reserva? Se liberará la habitación.')">
                                        @csrf
                                        <button class="btn btn-sm btn-secondary" type="submit">Cancelar</button>
                                    </form>
                                @endif
                                @if ($reserva->estado === 'cancelada')
                                    <a href="{{ route('reservas.edit', $reserva) }}" class="btn btn-sm btn-secondary">Editar</a>
                                @endif
                                @if (in_array($reserva->estado, ['confirmada', 'finalizada', 'cancelada']))
                                    <form method="POST" action="{{ route('reservas.destroy', $reserva) }}" onsubmit="return confirm('¿Eliminar definitivamente esta reserva?')">
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
        <div class="px-5 pb-5">{{ $reservas->links() }}</div>
    @endif
</div>
@endsection
