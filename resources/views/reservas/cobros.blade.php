@extends('layouts.app')

@section('titulo', 'Cobros de reserva')

@section('contenido')
<div class="mb-6 flex items-start justify-between gap-3 flex-wrap">
    <div>
        <a href="{{ route('reservas.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a reservas</a>
        <h1 class="page-title mt-2">Estado de cuenta — Reserva #{{ $reserva->id }}</h1>
        <p class="page-sub">Total, pagos y saldo de la estadía</p>
    </div>
    <a href="{{ route('cobros.comprobante', $reserva) }}" target="_blank" class="btn btn-secondary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z"/></svg>
        Imprimir comprobante
    </a>
</div>

<div class="card mb-6">
    <div class="card-body grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <div class="stat-label">Cliente</div>
            <div class="font-semibold text-slate-900 mt-1">{{ $reserva->huesped?->nombre ?? '—' }}</div>
            <div class="text-xs text-slate-500">{{ $reserva->huesped?->documento ?? '' }}</div>
        </div>
        <div>
            <div class="stat-label">Habitación</div>
            <div class="font-semibold text-slate-900 mt-1">Nº {{ $reserva->habitacion?->numero ?? 'N/D' }}</div>
            <div class="text-xs text-slate-500">{{ $reserva->habitacion?->tipo ? ucfirst($reserva->habitacion?->tipo) : '—' }}</div>
        </div>
        <div>
            <div class="stat-label">Estadía</div>
            <div class="font-semibold text-slate-900 mt-1">{{ $reserva->fecha_entrada->format('d/m/Y') }} → {{ $reserva->fecha_salida->format('d/m/Y') }}</div>
            <div class="text-xs text-slate-500">{{ $reserva->noches() }} noches · {{ ucfirst($reserva->estado) }}</div>
        </div>
        <div>
            <div class="stat-label">Total reserva</div>
            <div class="font-semibold text-slate-900 mt-1">{{ bs($reserva->total) }}</div>
        </div>
    </div>
    <div class="border-t border-slate-100 px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-lg bg-slate-50 px-4 py-3">
            <div class="stat-label">Cobrado</div>
            <div class="text-lg font-bold saldo-pagado">{{ bs($cobrado) }}</div>
        </div>
        <div class="rounded-lg bg-slate-50 px-4 py-3">
            <div class="stat-label">Saldo pendiente</div>
            <div class="text-lg font-bold {{ $saldo > 0 ? 'saldo-pendiente' : 'saldo-pagado' }}">{{ bs($saldo) }}</div>
        </div>
        <div class="rounded-lg bg-slate-50 px-4 py-3">
            <div class="stat-label">Estado</div>
            <div class="text-lg font-bold {{ $saldo <= 0 ? 'saldo-pagado' : 'text-slate-700' }}">{{ $saldo <= 0 ? 'Saldada' : 'Con saldo' }}</div>
        </div>
    </div>
</div>

@if (! in_array($reserva->estado, ['finalizada', 'cancelada']))
<div class="card mb-6">
    <div class="card-header">
        <h2 class="card-title">Registrar cobro</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('cobros.store', $reserva) }}">
            @csrf
            <input type="hidden" name="clave_idempotencia" value="{{ old('clave_idempotencia', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="form-grid">
                <div>
                    <label for="monto" class="label">Monto (Bs) *</label>
                    <input type="number" id="monto" name="monto" class="input" step="0.01" min="0.01" max="{{ max($saldo, 0) }}" value="{{ old('monto', $saldo > 0 ? $saldo : '') }}" required>
                    @error('monto') <div class="error-text">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="metodo" class="label">Método *</label>
                    <select id="metodo" name="metodo" class="select" required>
                        @foreach (\App\Models\Cobro::METODOS as $metodo)
                            <option value="{{ $metodo }}" {{ old('metodo', 'efectivo') === $metodo ? 'selected' : '' }}>{{ \App\Models\Cobro::etiqueta($metodo) }}</option>
                        @endforeach
                    </select>
                    @error('metodo') <div class="error-text">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="concepto" class="label">Concepto *</label>
                    <input type="text" id="concepto" name="concepto" class="input" value="{{ old('concepto', 'Estadía') }}" required>
                    @error('concepto') <div class="error-text">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="fecha" class="label">Fecha *</label>
                    <input type="date" id="fecha" name="fecha" class="input" value="{{ old('fecha', now()->format('Y-m-d')) }}" required>
                    @error('fecha') <div class="error-text">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mt-5">
                <button type="submit" class="btn btn-primary">Registrar cobro</button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Historial de cobros</h2>
        <span class="text-xs text-slate-400">Los reintentos con la misma clave no duplican el cobro.</span>
    </div>
    @if ($cobros->isEmpty())
        <div class="empty-state">Sin cobros registrados.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Método</th>
                        <th>Monto</th>
                        <th>Registrado por</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cobros as $cobro)
                    <tr class="{{ $cobro->anulado ? 'opacity-50' : '' }}">
                        <td>{{ $cobro->fecha->format('d/m/Y') }}</td>
                        <td>
                            <div class="font-medium">{{ $cobro->concepto }}</div>
                            <div class="flex items-center gap-2 mt-1">
                                @if ($cobro->esContraasiento())
                                    <span class="badge badge-purple">Contraasiento</span>
                                @endif
                                @if ($cobro->anulado)
                                    <span class="badge badge-danger">Anulado</span>
                                @endif
                                @if ($cobro->motivo)
                                    <span class="text-xs text-slate-500 italic">{{ $cobro->motivo }}</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ \App\Models\Cobro::etiqueta($cobro->metodo) }}</td>
                        <td class="font-bold {{ $cobro->monto < 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ bs($cobro->monto) }}</td>
                        <td>{{ $cobro->usuario->name ?? '—' }}</td>
                        <td>
                            <div class="acciones justify-end">
                                @if (auth()->user()->esAdmin() && ! $cobro->anulado && ! $cobro->esContraasiento() && $reserva->estado !== 'finalizada')
                                    <form method="POST" action="{{ route('cobros.anular', $cobro->id) }}" onsubmit="const m = prompt('Motivo de la anulación (obligatorio):'); if (!m) { return false; } this.motivo.value = m; return true;">
                                        @csrf
                                        <input type="hidden" name="motivo" value="">
                                        <button class="btn btn-sm btn-secondary" type="submit">Anular</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
