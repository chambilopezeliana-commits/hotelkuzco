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
                    Nº {{ $habitacion->numero }} · {{ ucfirst($habitacion->tipo) }} ({{ bs($habitacion->precio) }}/noche)
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

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="btn btn-primary">{{ $esEdicion ? 'Actualizar reserva' : 'Crear reserva' }}</button>
    <a href="{{ route('reservas.index') }}" class="btn btn-secondary">Cancelar</a>
</div>

<script>
    const entrada = document.getElementById('fecha_entrada');
    const salida = document.getElementById('fecha_salida');
    const habitacion = document.getElementById('habitacion_id');
    const estimacion = document.getElementById('estimacion');

    function calcular() {
        const precio = parseFloat(habitacion.selectedOptions[0]?.dataset.precio || 0);
        const i = new Date(entrada.value);
        const s = new Date(salida.value);
        const noches = Math.round((s - i) / 86400000);

        if (precio > 0 && noches > 0) {
            const fmt = v => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2 }).format(v);
            estimacion.classList.remove('hidden');
            estimacion.textContent = noches + ' noche(s) × Bs ' + fmt(precio) + ' = Total estimado: Bs ' + fmt(noches * precio);
        } else {
            estimacion.classList.add('hidden');
        }
    }

    [entrada, salida, habitacion].forEach(el => el.addEventListener('change', calcular));
    calcular();
</script>
