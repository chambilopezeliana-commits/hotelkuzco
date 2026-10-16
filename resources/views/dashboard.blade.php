@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
<div class="mb-6">
    <h1 class="page-title">Panel de control</h1>
    <p class="page-sub">Resumen operativo del hotel en tiempo real</p>
</div>

<div class="kpi-grid mb-8">
    <div class="stat-card">
        <span class="stat-label">Habitaciones libres</span>
        <span class="stat-value">{{ $datos['disponibles'] }}<span class="text-slate-300 text-lg font-semibold">/{{ $datos['totalHabitaciones'] }}</span></span>
        <span class="stat-hint">{{ $datos['mantenimiento'] }} en mantenimiento</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Ocupadas</span>
        <span class="stat-value">{{ $datos['ocupadas'] }}</span>
        <span class="stat-hint">{{ $datos['huespedesActivos'] }} huéspedes hospedados</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Reservas activas</span>
        <span class="stat-value">{{ $datos['reservasActivas'] }}</span>
        <span class="stat-hint">{{ $datos['reservasHoy'] }} entradas hoy · {{ $datos['salidasHoy'] }} salidas</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Cobros de hoy</span>
        <span class="stat-value">{{ bs($datos['cobrosHoy']) }}</span>
        <span class="stat-hint">Caja del día</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Ingresos del mes</span>
        <span class="stat-value">{{ bs($datos['ingresosMes']) }}</span>
        <span class="stat-hint">Pagos netos del mes en curso</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Huéspedes registrados</span>
        <span class="stat-value">{{ $datos['totalHuespedes'] }}</span>
        <span class="stat-hint">Fichas en el directorio</span>
    </div>
    @if (auth()->user()->esAdmin())
    <div class="stat-card">
        <span class="stat-label">Usuarios del sistema</span>
        <span class="stat-value">{{ $datos['usuarios'] ?? 0 }}</span>
        <span class="stat-hint">{{ $datos['usuariosActivos'] ?? 0 }} activos</span>
    </div>
    <a href="{{ route('reportes.index') }}" class="stat-card hover:border-marca-300 hover:shadow-md transition group">
        <span class="stat-label text-marca-600">Reportes de cálculo</span>
        <span class="stat-value text-marca-600 group-hover:text-marca-700">ADR · RevPAR</span>
        <span class="stat-hint">Ocupación, ingresos y desgloses →</span>
    </a>
    @endif
</div>

<div class="barra-superior mb-4 flex items-center justify-between gap-3 flex-wrap">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Próximas reservas</h2>
        <p class="text-sm text-slate-500">Entradas y salidas más cercanas</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('reservas.walkin') }}" class="btn btn-success">Check-in directo</a>
        <a href="{{ route('reservas.create') }}" class="btn btn-primary">+ Nueva reserva</a>
    </div>
</div>

<div class="card">
    @if ($proximasReservas->isEmpty())
        <div class="empty-state">
            <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            No hay reservas próximas.
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Habitación</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($proximasReservas as $reserva)
                    <tr>
                        <td>
                            <div class="font-semibold text-slate-900">{{ $reserva->huesped->nombre ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $reserva->huesped->documento ?? '' }}</div>
                        </td>
                        <td>
                            <span class="font-medium">Nº {{ $reserva->habitacion->numero }}</span>
                            <span class="text-xs text-slate-500">{{ $reserva->habitacion->tipo ?? '—' }}</span>
                        </td>
                        <td>{{ $reserva->fecha_entrada->format('d/m/Y') }}</td>
                        <td>{{ $reserva->fecha_salida->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $clases = ['pendiente' => 'badge-warning', 'confirmada' => 'badge-success', 'hospedado' => 'badge-info', 'finalizada' => 'badge-neutral', 'cancelada' => 'badge-danger'];
                            @endphp
                            <span class="badge {{ $clases[$reserva->estado] ?? 'badge-neutral' }}">{{ ucfirst($reserva->estado) }}</span>
                        </td>
                        <td class="font-semibold">{{ bs($reserva->total) }}</td>
                        <td class="text-right">
                            <a href="{{ route('cobros.index', $reserva) }}" class="btn btn-sm btn-secondary">Cobros</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
