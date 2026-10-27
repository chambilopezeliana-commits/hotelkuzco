<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de reserva #{{ $reserva->id }} · {{ config('hotel.nombre') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-kz-cream">
<div class="max-w-3xl mx-auto p-6">
    <div class="no-print flex items-center justify-between mb-4">
        <a href="{{ route('cobros.index', $reserva) }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a cobros</a>
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
    </div>

    <div class="card p-8">
        <div class="flex items-start justify-between border-b border-slate-200 pb-5">
            <div>
                <div class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] flex items-center justify-center text-kz-espresso font-display font-bold text-xl">K</div>
                    <div>
                        <div class="font-display text-lg font-bold text-kz-espresso tracking-wide">{{ strtoupper(config('hotel.nombre')) }}</div>
                        <div class="text-[10px] uppercase tracking-[0.2em] text-marca-600">{{ config('hotel.lema') }}</div>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Comprobante no fiscal</div>
                <div class="text-xl font-bold text-slate-900">Nº {{ str_pad((string) $reserva->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="text-xs text-slate-500">Emitido: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-5 border-b border-slate-200">
            <div>
                <div class="stat-label mb-2">Huésped</div>
                <div class="font-semibold text-slate-900">{{ $reserva->huesped?->nombre ?? '—' }}</div>
                <div class="text-sm text-slate-600">Doc: {{ $reserva->huesped?->tipo_documento ?? '' }} {{ $reserva->huesped?->documento ?? '' }}</div>
                @if ($reserva->huesped?->email)
                    <div class="text-sm text-slate-600">{{ $reserva->huesped?->email }}</div>
                @endif
                @if ($reserva->huesped?->telefono)
                    <div class="text-sm text-slate-600">Tel: {{ $reserva->huesped?->telefono }}</div>
                @endif
            </div>
            <div>
                <div class="stat-label mb-2">Detalle de estadía</div>
                <div class="text-sm text-slate-700">
                    <div><span class="text-slate-500">Habitación:</span> Nº {{ $reserva->habitacion?->numero ?? 'N/D' }} ({{ $reserva->habitacion?->tipoHabitacion?->nombre ?? $reserva->habitacion?->tipo }})</div>
                    <div><span class="text-slate-500">Entrada:</span> {{ $reserva->fecha_entrada->format('d/m/Y') }}</div>
                    <div><span class="text-slate-500">Salida:</span> {{ $reserva->fecha_salida->format('d/m/Y') }}</div>
                    <div><span class="text-slate-500">Noches:</span> {{ $reserva->noches() }}</div>
                    <div><span class="text-slate-500">Huéspedes:</span> {{ $reserva->num_huespedes }}</div>
                </div>
            </div>
        </div>

        <div class="py-5">
            <div class="stat-label mb-3">Pagos registrados</div>
            @if ($cobros->isEmpty())
                <div class="text-sm text-slate-400">Sin pagos registrados.</div>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Método</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cobros as $cobro)
                            <tr>
                                <td>{{ $cobro->fecha->format('d/m/Y') }}</td>
                                <td>{{ $cobro->concepto }}@if ($cobro->esContraasiento()) <span class="badge badge-purple ml-1">Contraasiento</span>@endif</td>
                                <td>{{ \App\Models\Cobro::etiqueta($cobro->metodo) }}</td>
                                <td class="text-right font-semibold {{ $cobro->monto < 0 ? 'text-rose-600' : '' }}">{{ bs($cobro->monto) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="border-t border-slate-200 pt-5 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Total estadía</span>
                <span class="font-semibold">{{ bs($reserva->total) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Total cobrado</span>
                <span class="font-semibold saldo-pagado">{{ bs($cobrado) }}</span>
            </div>
            <div class="flex justify-between text-base border-t border-slate-100 pt-2">
                <span class="font-semibold text-slate-700">Saldo {{ $saldo > 0 ? 'pendiente' : '' }}</span>
                <span class="font-bold {{ $saldo > 0 ? 'saldo-pendiente' : 'saldo-pagado' }}">{{ bs($saldo) }}</span>
            </div>
        </div>

        <p class="text-xs text-slate-400 mt-6 border-t border-slate-100 pt-4">
            Documento de uso interno. No constituye factura ni documento fiscal. Generado por el Sistema de Gestión Hotelera Hotel Kuzco.
        </p>
    </div>
</div>
</body>
</html>
