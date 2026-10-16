@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
<div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
    <div>
        <h1 class="page-title">Reportes de cálculo</h1>
        <p class="page-sub">Indicadores de ocupación, ingresos, ADR y RevPAR del período seleccionado.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('reportes.pdf', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Descargar PDF
        </a>
        <a href="{{ route('reportes.imprimir', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}" target="_blank" class="btn btn-secondary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z"/></svg>
            Imprimir reporte
        </a>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('reportes.index') }}" class="flex items-end gap-3 flex-wrap">
            <div>
                <label class="label" for="desde">Desde</label>
                <input type="date" id="desde" name="desde" class="filter-input" value="{{ $desde->toDateString() }}">
            </div>
            <div>
                <label class="label" for="hasta">Hasta</label>
                <input type="date" id="hasta" name="hasta" class="filter-input" value="{{ $hasta->toDateString() }}">
            </div>
            <button class="btn btn-primary" type="submit">Calcular</button>
            <a href="{{ route('reportes.index') }}" class="btn btn-secondary">Mes actual</a>
        </form>
    </div>
</div>

<div class="kpi-grid mb-6">
    <div class="stat-card">
        <span class="stat-label">Ocupación</span>
        <span class="stat-value">{{ number_format($ocupacion, 1) }}%</span>
        <span class="stat-hint">{{ $nochesVendidas }} de {{ $totalHabitaciones * $nochesPeriodo }} noches-habitación</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">ADR</span>
        <span class="stat-value">{{ bs($adr) }}</span>
        <span class="stat-hint">Tarifa promedio por noche vendida</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">RevPAR</span>
        <span class="stat-value">{{ bs($revpar) }}</span>
        <span class="stat-hint">Ingreso por habitación disponible</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Ingresos netos</span>
        <span class="stat-value">{{ bs($ingresosNetos) }}</span>
        <span class="stat-hint">Pagos netos (criterio de caja)</span>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
    <div class="stat-card">
        <span class="stat-label">Pagos brutos</span>
        <span class="stat-value text-emerald-600">{{ bs($pagosBrutos) }}</span>
        <span class="stat-hint">{{ $cantidadPagos }} pago(s)</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Contraasientos</span>
        <span class="stat-value text-rose-600">{{ bs($contraasientos) }}</span>
        <span class="stat-hint">Anulaciones del período</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Noches vendidas</span>
        <span class="stat-value">{{ $nochesVendidas }}</span>
        <span class="stat-hint">Período: {{ $nochesPeriodo }} noches</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Estadía promedio</span>
        <span class="stat-value">{{ number_format($estadiaPromedio, 1) }}</span>
        <span class="stat-hint">noches por reserva</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Tasa de cancelación</span>
        <span class="stat-value">{{ number_format($tasaCancelacion, 1) }}%</span>
        <span class="stat-hint">{{ $canceladas }} de {{ $totalReservasPeriodo }} reservas</span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Por tipo de habitación</h2></div>
        @if ($porTipo->isEmpty())
            <div class="empty-state">Sin datos de tipos.</div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Hab.</th>
                            <th>Noches</th>
                            <th>Ingresos</th>
                            <th>ADR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($porTipo as $fila)
                            <tr>
                                <td class="font-medium">{{ $fila['nombre'] }}</td>
                                <td>{{ $fila['habitaciones'] }}</td>
                                <td>{{ $fila['noches'] }}</td>
                                <td class="font-semibold">{{ bs($fila['ingresos']) }}</td>
                                <td>{{ bs($fila['adr']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Ingresos por método de pago</h2></div>
            @if ($porMetodo->isEmpty())
                <div class="empty-state">Sin pagos en el período.</div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Método</th><th>Pagos</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($porMetodo as $fila)
                                <tr>
                                    <td class="font-medium">{{ $fila['metodo'] }}</td>
                                    <td>{{ $fila['cantidad'] }}</td>
                                    <td class="font-semibold {{ $fila['total'] < 0 ? 'text-rose-600' : '' }}">{{ bs($fila['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Reservas creadas por estado</h2></div>
            @if ($reservasPorEstado->isEmpty())
                <div class="empty-state">Sin reservas creadas en el período.</div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Estado</th><th>Reservas</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($reservasPorEstado as $estadoReserva => $total)
                                <tr>
                                    <td class="font-medium">{{ ucfirst($estadoReserva) }}</td>
                                    <td>{{ $total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h2 class="card-title">Registros por QR y transferencia bancaria</h2>
        <span class="text-xs text-slate-500">Detalle de cada pago recibido por estos medios</span>
    </div>
    @php
        $filaQr = $totalesQrTransferencia->firstWhere('metodo', 'qr');
        $filaTransferencia = $totalesQrTransferencia->firstWhere('metodo', 'transferencia');
        $totalQrTransferencia = $totalesQrTransferencia->sum('total');
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 px-5 py-4 border-b border-slate-100">
        <div class="rounded-lg bg-slate-50 px-4 py-3">
            <div class="stat-label">Total QR</div>
            <div class="text-lg font-bold text-slate-900">{{ bs($filaQr['total'] ?? 0) }}</div>
            <div class="text-xs text-slate-500">{{ $filaQr['cantidad'] ?? 0 }} pago(s)</div>
        </div>
        <div class="rounded-lg bg-slate-50 px-4 py-3">
            <div class="stat-label">Total transferencia bancaria</div>
            <div class="text-lg font-bold text-slate-900">{{ bs($filaTransferencia['total'] ?? 0) }}</div>
            <div class="text-xs text-slate-500">{{ $filaTransferencia['cantidad'] ?? 0 }} pago(s)</div>
        </div>
        <div class="rounded-lg bg-slate-50 px-4 py-3">
            <div class="stat-label">Total QR + transferencia</div>
            <div class="text-lg font-bold text-slate-900">{{ bs($totalQrTransferencia) }}</div>
            <div class="text-xs text-slate-500">{{ $detalleQrTransferencia->count() }} registro(s)</div>
        </div>
    </div>
    @if ($detalleQrTransferencia->isEmpty())
        <div class="empty-state">Sin cobros por QR ni transferencia en el período.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Reserva</th>
                        <th>Huésped</th>
                        <th>Habitación</th>
                        <th>Método</th>
                        <th>Registrado por</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detalleQrTransferencia as $fila)
                        <tr class="{{ $fila['anulado'] ? 'opacity-50' : '' }}">
                            <td>{{ $fila['fecha']->format('d/m/Y') }}</td>
                            <td>#{{ $fila['reserva_id'] }}</td>
                            <td>
                                <div class="font-medium">{{ $fila['huesped'] }}</div>
                                <div class="text-xs text-slate-500">{{ $fila['documento'] }}</div>
                            </td>
                            <td>Nº {{ $fila['habitacion'] }}</td>
                            <td>{{ $fila['metodo_label'] }}</td>
                            <td>{{ $fila['registrado_por'] }}</td>
                            <td class="text-right font-semibold {{ $fila['monto'] < 0 ? 'text-rose-600' : '' }}">{{ bs($fila['monto']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card mb-6">
    <div class="card-header"><h2 class="card-title">Top habitaciones por ingresos</h2></div>
    @if ($topHabitaciones->isEmpty())
        <div class="empty-state">Sin ingresos registrados en el período.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Habitación</th>
                        <th>Pagos</th>
                        <th>Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topHabitaciones as $i => $fila)
                        <tr>
                            <td class="text-slate-400">{{ $i + 1 }}</td>
                            <td class="font-medium">Nº {{ $fila['numero'] }}</td>
                            <td>{{ $fila['pagos'] }}</td>
                            <td class="font-semibold">{{ bs($fila['ingresos']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Detalle diario</h2>
        <span class="text-xs text-slate-500">Ocupación e ingresos día a día</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Noches vendidas</th>
                    <th>Ocupación</th>
                    <th>Ingresos del día</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($serieDiaria as $dia)
                    <tr>
                        <td class="font-medium">{{ $dia['fecha']->format('d/m/Y') }}</td>
                        <td>{{ $dia['noches'] }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-marca-500" style="width: {{ min(100, $dia['ocupacion']) }}%"></div>
                                </div>
                                <span class="text-xs text-slate-600">{{ number_format($dia['ocupacion'], 1) }}%</span>
                            </div>
                        </td>
                        <td class="font-semibold">{{ bs($dia['ingresos']) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-slate-50">
                    <td class="font-bold text-slate-900">Total del período</td>
                    <td class="font-bold text-slate-900">{{ $nochesVendidas }}</td>
                    <td class="font-bold text-slate-900">{{ number_format($ocupacion, 1) }}%</td>
                    <td class="font-bold text-slate-900">{{ bs($ingresosNetos) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
