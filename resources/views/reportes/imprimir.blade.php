<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de cálculo {{ $desde->format('d/m/Y') }} - {{ $hasta->format('d/m/Y') }} · {{ config('hotel.nombre') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-kz-cream">
<div class="max-w-4xl mx-auto p-6">
    <div class="no-print flex items-center justify-between mb-4">
        <a href="{{ route('reportes.index', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a reportes</a>
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
    </div>

    <div class="card p-8">
        <div class="flex items-start justify-between border-b border-slate-200 pb-5">
            <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] flex items-center justify-center text-kz-espresso font-display font-bold text-xl">K</div>
                <div>
                    <div class="font-display text-lg font-bold text-kz-espresso tracking-wide">{{ strtoupper(config('hotel.nombre')) }}</div>
                    <div class="text-[10px] uppercase tracking-[0.2em] text-marca-600">{{ config('hotel.lema') }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Reporte de cálculo</div>
                <div class="text-sm font-semibold text-slate-900">{{ $desde->format('d/m/Y') }} → {{ $hasta->format('d/m/Y') }}</div>
                <div class="text-xs text-slate-500">Generado: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-6 border-b border-slate-200">
            <div>
                <div class="stat-label">Ocupación</div>
                <div class="text-xl font-bold text-slate-900">{{ number_format($ocupacion, 1) }}%</div>
                <div class="text-xs text-slate-500">{{ $nochesVendidas }}/{{ $totalHabitaciones * $nochesPeriodo }} noches-hab.</div>
            </div>
            <div>
                <div class="stat-label">ADR</div>
                <div class="text-xl font-bold text-slate-900">{{ bs($adr) }}</div>
                <div class="text-xs text-slate-500">Tarifa promedio</div>
            </div>
            <div>
                <div class="stat-label">RevPAR</div>
                <div class="text-xl font-bold text-slate-900">{{ bs($revpar) }}</div>
                <div class="text-xs text-slate-500">Por habitación disp.</div>
            </div>
            <div>
                <div class="stat-label">Ingresos netos</div>
                <div class="text-xl font-bold text-slate-900">{{ bs($ingresosNetos) }}</div>
                <div class="text-xs text-slate-500">Criterio de caja</div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-6 border-b border-slate-200 text-sm">
            <div><span class="text-slate-500">Pagos brutos:</span> <span class="font-semibold">{{ bs($pagosBrutos) }}</span> ({{ $cantidadPagos }})</div>
            <div><span class="text-slate-500">Contraasientos:</span> <span class="font-semibold text-rose-600">{{ bs($contraasientos) }}</span></div>
            <div><span class="text-slate-500">Noches vendidas:</span> <span class="font-semibold">{{ $nochesVendidas }}</span> de {{ $nochesPeriodo }}</div>
            <div><span class="text-slate-500">Estadía promedio:</span> <span class="font-semibold">{{ number_format($estadiaPromedio, 1) }}</span> noches</div>
            <div><span class="text-slate-500">Tasa de cancelación:</span> <span class="font-semibold">{{ number_format($tasaCancelacion, 1) }}%</span> ({{ $canceladas }}/{{ $totalReservasPeriodo }})</div>
        </div>

        <div class="py-6">
            <div class="stat-label mb-3">Desglose por tipo de habitación</div>
            <table class="table">
                <thead>
                    <tr><th>Tipo</th><th>Habitaciones</th><th>Noches</th><th class="text-right">Ingresos</th><th class="text-right">ADR</th></tr>
                </thead>
                <tbody>
                    @foreach ($porTipo as $fila)
                        <tr>
                            <td>{{ $fila['nombre'] }}</td>
                            <td>{{ $fila['habitaciones'] }}</td>
                            <td>{{ $fila['noches'] }}</td>
                            <td class="text-right font-semibold">{{ bs($fila['ingresos']) }}</td>
                            <td class="text-right">{{ bs($fila['adr']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="py-6 border-t border-slate-200">
            <div class="stat-label mb-3">Ingresos por método de pago</div>
            <table class="table">
                <thead>
                    <tr><th>Método</th><th>Pagos</th><th class="text-right">Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($porMetodo as $fila)
                        <tr>
                            <td>{{ $fila['metodo'] }}</td>
                            <td>{{ $fila['cantidad'] }}</td>
                            <td class="text-right font-semibold {{ $fila['total'] < 0 ? 'text-rose-600' : '' }}">{{ bs($fila['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="py-6 border-t border-slate-200">
            <div class="stat-label mb-3">Top habitaciones por ingresos</div>
            @if ($topHabitaciones->isEmpty())
                <p class="text-sm text-slate-500">Sin ingresos registrados en el período.</p>
            @else
                <table class="table">
                    <thead>
                        <tr><th>#</th><th>Habitación</th><th>Pagos</th><th class="text-right">Ingresos</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($topHabitaciones as $i => $fila)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>Nº {{ $fila['numero'] }}</td>
                                <td>{{ $fila['pagos'] }}</td>
                                <td class="text-right font-semibold">{{ bs($fila['ingresos']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="py-6 border-t border-slate-200">
            <div class="stat-label mb-3">Detalle diario</div>
            <table class="table">
                <thead>
                    <tr><th>Fecha</th><th>Noches vendidas</th><th>Ocupación</th><th class="text-right">Ingresos del día</th></tr>
                </thead>
                <tbody>
                    @foreach ($serieDiaria as $dia)
                        <tr>
                            <td>{{ $dia['fecha']->format('d/m/Y') }}</td>
                            <td>{{ $dia['noches'] }}</td>
                            <td>{{ number_format($dia['ocupacion'], 1) }}%</td>
                            <td class="text-right font-semibold">{{ bs($dia['ingresos']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-slate-50">
                        <td class="font-bold">Total del período</td>
                        <td class="font-bold">{{ $nochesVendidas }}</td>
                        <td class="font-bold">{{ number_format($ocupacion, 1) }}%</td>
                        <td class="text-right font-bold">{{ bs($ingresosNetos) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-slate-400 mt-6 border-t border-slate-100 pt-4">
            Documento de uso interno gerencial. ADR = ingresos netos / noches vendidas. RevPAR = ingresos netos / (habitaciones × noches del período).
        </p>
    </div>
</div>
</body>
</html>
