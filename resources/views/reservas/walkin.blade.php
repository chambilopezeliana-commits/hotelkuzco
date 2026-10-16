@extends('layouts.app')

@section('titulo', 'Check-in directo')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('reservas.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a reservas</a>
    <h1 class="page-title mt-2">Check-in directo (walk-in)</h1>
    <p class="page-sub">Para huéspedes sin reserva previa. Se crea la reserva con entrada hoy y se realiza el check-in de inmediato.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('reservas.walkin.store') }}">
            @csrf
            <div class="form-grid">
                <div>
                    <label for="huesped_id" class="label">Huésped *</label>
                    <select id="huesped_id" name="huesped_id" class="select" required>
                        <option value="">Seleccione un huésped...</option>
                        @foreach ($huespedes as $huesped)
                            <option value="{{ $huesped->id }}" {{ old('huesped_id') == $huesped->id ? 'selected' : '' }}>
                                {{ $huesped->nombre }} ({{ $huesped->documento }})
                            </option>
                        @endforeach
                    </select>
                    @error('huesped_id') <div class="error-text">{{ $message }}</div> @enderror
                    <div class="hint">
                        <a href="{{ route('huespedes.create') }}" target="_blank" class="text-marca-600 font-medium">+ Registrar nuevo huésped</a>
                    </div>
                </div>

                <div>
                    <label for="habitacion_id" class="label">Habitación *</label>
                    <select id="habitacion_id" name="habitacion_id" class="select" required>
                        <option value="">Seleccione una habitación...</option>
                        @foreach ($habitaciones as $habitacion)
                            <option value="{{ $habitacion->id }}"
                                data-precio="{{ $habitacion->precio }}"
                                {{ old('habitacion_id') == $habitacion->id ? 'selected' : '' }}>
                                Nº {{ $habitacion->numero }} · {{ $habitacion->tipo ? ucfirst($habitacion->tipo) : '—' }} ({{ bs($habitacion->precio) }}/noche)
                            </option>
                        @endforeach
                    </select>
                    @error('habitacion_id') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="label">Fecha de entrada</label>
                    <input type="text" class="input bg-slate-50" value="{{ now()->format('d/m/Y') }}" disabled>
                    <div class="hint">La entrada se registra con la fecha de hoy.</div>
                </div>

                <div>
                    <label for="fecha_salida" class="label">Fecha de salida *</label>
                    <input type="date" id="fecha_salida" name="fecha_salida" class="input" min="{{ now()->addDay()->format('Y-m-d') }}" value="{{ old('fecha_salida', now()->addDay()->format('Y-m-d')) }}" required>
                    @error('fecha_salida') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="num_huespedes" class="label">Número de huéspedes *</label>
                    <input type="number" id="num_huespedes" name="num_huespedes" class="input" min="1" max="20" value="{{ old('num_huespedes', 1) }}" required>
                    @error('num_huespedes') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="metodo_pago" class="label">Método de pago *</label>
                    <select id="metodo_pago" name="metodo_pago" class="select" required>
                        @foreach (\App\Models\Cobro::METODOS as $metodo)
                            <option value="{{ $metodo }}" {{ old('metodo_pago', 'efectivo') === $metodo ? 'selected' : '' }}>{{ \App\Models\Cobro::etiqueta($metodo) }}</option>
                        @endforeach
                    </select>
                    @error('metodo_pago') <div class="error-text">{{ $message }}</div> @enderror
                    <div class="hint">Se cobrará el total de la estadía al registrar el check-in.</div>
                </div>
            </div>

            <div id="estimacion" class="hidden mt-5 rounded-xl border border-marca-200 bg-marca-50 px-4 py-3 text-sm font-semibold text-marca-800"></div>

            <div class="mt-4">
                <label for="notas" class="label">Notas</label>
                <textarea id="notas" name="notas" class="textarea" rows="2">{{ old('notas') }}</textarea>
                @error('notas') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="btn btn-success">Registrar check-in</button>
                <a href="{{ route('reservas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    const habitacion = document.getElementById('habitacion_id');
    const salida = document.getElementById('fecha_salida');
    const estimacion = document.getElementById('estimacion');

    function calcular() {
        const precio = parseFloat(habitacion.selectedOptions[0]?.dataset.precio || 0);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const s = new Date(salida.value);
        const noches = Math.round((s - hoy) / 86400000);

        if (precio > 0 && noches > 0) {
            const fmt = v => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2 }).format(v);
            estimacion.classList.remove('hidden');
            estimacion.textContent = noches + ' noche(s) × Bs ' + fmt(precio) + ' = Total a cobrar: Bs ' + fmt(noches * precio);
        } else {
            estimacion.classList.add('hidden');
        }
    }

    [habitacion, salida].forEach(el => el.addEventListener('change', calcular));
    calcular();
</script>
@endsection
