<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte {{ $desde->format('d/m/Y') }} - {{ $hasta->format('d/m/Y') }}</title>
    <style>
        @page { margin: 26px 30px; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #2a1a0f; font-size: 10px; margin: 0; }

        .header { width: 100%; border-bottom: 2px solid #c89b3c; padding-bottom: 10px; margin-bottom: 14px; }
        .header td { vertical-align: middle; }
        .logo {
            width: 42px; height: 42px; border-radius: 21px; background: #c89b3c;
            color: #2a1a0f; text-align: center; line-height: 42px; font-size: 22px; font-weight: bold;
        }
        .brand { font-size: 17px; font-weight: bold; letter-spacing: 2px; color: #2a1a0f; }
        .brand-sub { font-size: 8px; letter-spacing: 3px; text-transform: uppercase; color: #a87d2c; }
        .doc-title { font-size: 12px; font-weight: bold; color: #2a1a0f; text-align: right; }
        .doc-sub { font-size: 9px; color: #6b4d1a; text-align: right; }

        .kpi { width: 100%; border-collapse: separate; border-spacing: 6px; margin-bottom: 4px; }
        .kpi td { width: 25%; background: #fbf5e6; border: 1px solid #ecd9a6; padding: 8px 10px; }
        .kpi .label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #8a6522; }
        .kpi .value { font-size: 16px; font-weight: bold; color: #2a1a0f; }
        .kpi .hint { font-size: 8px; color: #8a6a48; }

        h3.section {
            font-size: 11px; color: #8a6522; text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 1px solid #ecd9a6; padding-bottom: 3px; margin: 16px 0 6px;
        }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            background: #4a3220; color: #f6efe3; font-size: 8px; text-transform: uppercase;
            letter-spacing: 0.5px; text-align: left; padding: 5px 7px; border: 1px solid #4a3220;
        }
        table.data td { padding: 4px 7px; border: 1px solid #ecd9a6; }
        table.data tr:nth-child(even) td { background: #fbf5e6; }
        .num { text-align: right; }
        .neg { color: #b91c1c; }
        .pos { color: #047857; font-weight: bold; }
        .total td { font-weight: bold; background: #f6ecd3 !important; }

        .two-col { width: 100%; }
        .two-col > tbody > tr > td { width: 50%; vertical-align: top; padding-right: 8px; }
        .two-col > tbody > tr > td:last-child { padding-right: 0; padding-left: 8px; }

        .footer { margin-top: 18px; border-top: 1px solid #ecd9a6; padding-top: 6px; font-size: 8px; color: #8a6a48; }
    </style>
</head>
<body>

    <table class="header">
        <tr>
            <td style="width: 60%;">
                <table>
                    <tr>
                        <td style="width: 50px;"><div class="logo">K</div></td>
                        <td>
                            <div class="brand">{{ strtoupper(config('hotel.nombre')) }}</div>
                            <div class="brand-sub">{{ config('hotel.lema') }}</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%;">
                <div class="doc-title">Reporte de cálculo</div>
                <div class="doc-sub">{{ $desde->format('d/m/Y') }} &rarr; {{ $hasta->format('d/m/Y') }}</div>
                <div class="doc-sub">{{ $nochesPeriodo }} noche(s) · {{ $totalHabitaciones }} habitaciones</div>
            </td>
        </tr>
    </table>

    <table class="kpi">
        <tr>
            <td>
                <div class="label">Ocupación</div>
                <div class="value">{{ number_format($ocupacion, 1) }}%</div>
                <div class="hint">{{ $nochesVendidas }} / {{ $totalHabitaciones * $nochesPeriodo }} noches-hab.</div>
            </td>
            <td>
                <div class="label">ADR</div>
                <div class="value">{{ bs($adr) }}</div>
                <div class="hint">Tarifa media por noche</div>
            </td>
            <td>
                <div class="label">RevPAR</div>
                <div class="value">{{ bs($revpar) }}</div>
                <div class="hint">Ingreso por habitación</div>
            </td>
            <td>
                <div class="label">Ingresos netos</div>
                <div class="value">{{ bs($ingresosNetos) }}</div>
                <div class="hint">Criterio de caja</div>
            </td>
        </tr>
    </table>

    <table class="kpi">
        <tr>
            <td>
                <div class="label">Pagos brutos</div>
                <div class="value pos">{{ bs($pagosBrutos) }}</div>
                <div class="hint">{{ $cantidadPagos }} pago(s)</div>
            </td>
            <td>
                <div class="label">Contraasientos</div>
                <div class="value neg">{{ bs($contraasientos) }}</div>
                <div class="hint">Anulaciones</div>
            </td>
            <td>
                <div class="label">Estadía promedio</div>
                <div class="value">{{ number_format($estadiaPromedio, 1) }}</div>
                <div class="hint">noches por reserva</div>
            </td>
            <td>
                <div class="label">Tasa de cancelación</div>
                <div class="value">{{ number_format($tasaCancelacion, 1) }}%</div>
                <div class="hint">{{ $canceladas }} de {{ $totalReservasPeriodo }} reservas</div>
            </td>
        </tr>
    </table>

    <h3 class="section">Ocupación e ingresos por tipo de habitación</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Tipo</th>
                <th class="num">Habitaciones</th>
                <th class="num">Noches</th>
                <th class="num">Ingresos</th>
                <th class="num">ADR</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($porTipo as $fila)
                <tr>
                    <td>{{ $fila['nombre'] }}</td>
                    <td class="num">{{ $fila['habitaciones'] }}</td>
                    <td class="num">{{ $fila['noches'] }}</td>
                    <td class="num">{{ bs($fila['ingresos']) }}</td>
                    <td class="num">{{ bs($fila['adr']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin datos de tipos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="two-col">
        <tr>
            <td>
                <h3 class="section">Ingresos por método de pago</h3>
                <table class="data">
                    <thead>
                        <tr><th>Método</th><th class="num">Pagos</th><th class="num">Total</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($porMetodo as $fila)
                            <tr>
                                <td>{{ $fila['metodo'] }}</td>
                                <td class="num">{{ $fila['cantidad'] }}</td>
                                <td class="num {{ $fila['total'] < 0 ? 'neg' : '' }}">{{ bs($fila['total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">Sin pagos en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <h3 class="section">Reservas creadas por estado</h3>
                <table class="data">
                    <thead>
                        <tr><th>Estado</th><th class="num">Reservas</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($reservasPorEstado as $estadoReserva => $total)
                            <tr>
                                <td>{{ ucfirst($estadoReserva) }}</td>
                                <td class="num">{{ $total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">Sin reservas creadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <h3 class="section">Top habitaciones por ingresos</h3>
    <table class="data">
        <thead>
            <tr><th>Habitación</th><th class="num">Pagos</th><th class="num">Ingresos</th></tr>
        </thead>
        <tbody>
            @forelse ($topHabitaciones as $fila)
                <tr>
                    <td>Nº {{ $fila['numero'] }}</td>
                    <td class="num">{{ $fila['pagos'] }}</td>
                    <td class="num">{{ bs($fila['ingresos']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin ingresos registrados en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 class="section">Detalle diario</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th>
                <th class="num">Noches vendidas</th>
                <th class="num">Ocupación</th>
                <th class="num">Ingresos del día</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($serieDiaria as $dia)
                <tr>
                    <td>{{ $dia['fecha']->format('d/m/Y') }}</td>
                    <td class="num">{{ $dia['noches'] }}</td>
                    <td class="num">{{ number_format($dia['ocupacion'], 1) }}%</td>
                    <td class="num">{{ bs($dia['ingresos']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>Total del período</td>
                <td class="num">{{ $nochesVendidas }}</td>
                <td class="num">{{ number_format($ocupacion, 1) }}%</td>
                <td class="num">{{ bs($ingresosNetos) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} · {{ config('hotel.nombre') }} — {{ config('hotel.lema') }}
        · Documento no fiscal.
    </div>

</body>
</html>
