@php($esEdicion = isset($reserva))

<div class="form-grid">
    <div>
        <label for="huesped_id" class="label">Huésped responsable *</label>
        <select id="huesped_id" name="huesped_id" class="select" required>
            <option value="">Seleccione un huésped...</option>
            @foreach ($huespedes as $huesped)
                <option value="{{ $huesped->id }}" {{ old('huesped_id', $reserva->huesped_id ?? '') == $huesped->id ? 'selected' : '' }}>
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
                    {{ old('habitacion_id', $reserva->habitacion_id ?? '') == $habitacion->id ? 'selected' : '' }}>
                    Nº {{ $habitacion->numero }} · {{ $habitacion->tipo ? ucfirst($habitacion->tipo) : '—' }} ({{ bs($habitacion->precio) }}/noche)
                    {{ $habitacion->estado === 'mantenimiento' ? '— En mantenimiento' : '' }}
                </option>
            @endforeach
        </select>
        @error('habitacion_id') <div class="error-text">{{ $message }}</div> @enderror
        <div class="hint">Verifique la disponibilidad antes de confirmar.</div>
    </div>

    <div>
        <label for="fecha_entrada" class="label">Fecha de entrada *</label>
        <input type="date" id="fecha_entrada" name="fecha_entrada" class="input" value="{{ old('fecha_entrada', isset($reserva) ? $reserva->fecha_entrada->format('Y-m-d') : '') }}" required>
        @error('fecha_entrada') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="fecha_salida" class="label">Fecha de salida *</label>
        <input type="date" id="fecha_salida" name="fecha_salida" class="input" value="{{ old('fecha_salida', isset($reserva) ? $reserva->fecha_salida->format('Y-m-d') : '') }}" required>
        @error('fecha_salida') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="num_huespedes" class="label">Número de huéspedes *</label>
        <input type="number" id="num_huespedes" name="num_huespedes" class="input" min="1" max="20" value="{{ old('num_huespedes', $reserva->num_huespedes ?? 1) }}" required>
        @error('num_huespedes') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="estado" class="label">Estado *</label>
        <select id="estado" name="estado" class="select" required>
            @foreach (['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'cancelada' => 'Cancelada'] as $valor => $etiqueta)
                <option value="{{ $valor }}" {{ old('estado', $reserva->estado ?? 'pendiente') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('estado') <div class="error-text">{{ $message }}</div> @enderror
        <div class="hint">Check-in y check-out se realizan desde el listado.</div>
    </div>
</div>

<div class="mt-4">
    <label for="notas" class="label">Notas</label>
    <textarea id="notas" name="notas" class="textarea" rows="2">{{ old('notas', $reserva->notas ?? '') }}</textarea>
    @error('notas') <div class="error-text">{{ $message }}</div> @enderror
</div>

<div id="estimacion" class="hidden mt-5 rounded-xl border border-marca-200 bg-marca-50 px-4 py-3 text-sm font-semibold text-marca-800"></div>

@unless ($esEdicion)
    <div class="mt-5 rounded-xl border border-amber-300 bg-amber-50 p-4">
        <h3 class="text-sm font-bold text-slate-800">Anticipo para asegurar la reserva</h3>
        <p class="mt-1 text-xs text-slate-600">
            Se exige un mínimo del {{ (float) config('hotel.anticipo_porcentaje') }}% del total.
            Sin este pago la reserva no queda garantizada.
        </p>
        <div class="form-grid mt-4">
            <div>
                <label for="anticipo_monto" class="label">Anticipo (Bs) *</label>
                <input type="number" step="0.01" min="0.01" id="anticipo_monto" name="anticipo_monto" class="input" value="{{ old('anticipo_monto') }}" required>
                @error('anticipo_monto') <div class="error-text">{{ $message }}</div> @enderror
                <div class="hint" id="anticipo-minimo-hint"></div>
            </div>
            <div>
                <label for="anticipo_metodo" class="label">Método de pago del anticipo *</label>
                <select id="anticipo_metodo" name="anticipo_metodo" class="select" required>
                    @foreach (\App\Models\Cobro::METODOS as $metodo)
                        <option value="{{ $metodo }}" {{ old('anticipo_metodo', 'efectivo') === $metodo ? 'selected' : '' }}>{{ \App\Models\Cobro::etiqueta($metodo) }}</option>
                    @endforeach
                </select>
                @error('anticipo_metodo') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="anticipo_fecha" class="label">Fecha del anticipo *</label>
                <input type="date" id="anticipo_fecha" name="anticipo_fecha" class="input" value="{{ old('anticipo_fecha', now()->format('Y-m-d')) }}" required>
                @error('anticipo_fecha') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
@endunless

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="btn btn-primary">{{ $esEdicion ? 'Actualizar reserva' : 'Crear reserva' }}</button>
    <a href="{{ route('reservas.index') }}" class="btn btn-secondary">Cancelar</a>
</div>

<script>
    const entrada = document.getElementById('fecha_entrada');
    const salida = document.getElementById('fecha_salida');
    const habitacion = document.getElementById('habitacion_id');
    const estimacion = document.getElementById('estimacion');
    const anticipoMinimoHint = document.getElementById('anticipo-minimo-hint');
    const anticipoMonto = document.getElementById('anticipo_monto');
    const porcentajeAnticipo = {{ (float) config('hotel.anticipo_porcentaje') }};

    function calcular() {
        const precio = parseFloat(habitacion.selectedOptions[0]?.dataset.precio || 0);
        const i = new Date(entrada.value);
        const s = new Date(salida.value);
        const noches = Math.round((s - i) / 86400000);

        if (precio > 0 && noches > 0) {
            const fmt = v => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2 }).format(v);
            const total = noches * precio;
            const minimo = total * porcentajeAnticipo / 100;

            estimacion.classList.remove('hidden');
            estimacion.textContent = noches + ' noche(s) × Bs ' + fmt(precio) + ' = Total estimado: Bs ' + fmt(total);

            if (anticipoMinimoHint && anticipoMonto) {
                anticipoMinimoHint.textContent = 'Mínimo para asegurar: Bs ' + fmt(minimo);
                anticipoMonto.min = minimo.toFixed(2);
            }
        } else {
            estimacion.classList.add('hidden');

            if (anticipoMinimoHint) {
                anticipoMinimoHint.textContent = '';
            }
        }
    }

    [entrada, salida, habitacion].forEach(el => el.addEventListener('change', calcular));
    calcular();
</script>
