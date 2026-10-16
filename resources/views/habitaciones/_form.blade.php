@php($esEdicion = isset($habitacion))

<div class="form-grid">
    <div>
        <label for="numero" class="label">Número *</label>
        <input type="text" id="numero" name="numero" class="input" value="{{ old('numero', $habitacion->numero ?? '') }}" placeholder="Ej: 101" required>
        @error('numero') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="tipo_habitacion_id" class="label">Tipo de habitación *</label>
        <select id="tipo_habitacion_id" name="tipo_habitacion_id" class="select" required>
            <option value="">Seleccione un tipo...</option>
            @foreach ($tipos as $tipo)
                <option value="{{ $tipo->id }}" {{ old('tipo_habitacion_id', $habitacion->tipo_habitacion_id ?? '') == $tipo->id ? 'selected' : '' }}>
                    {{ $tipo->nombre }} · {{ bs($tipo->tarifa) }}/noche · {{ $tipo->capacidad }} pers.
                </option>
            @endforeach
        </select>
        @error('tipo_habitacion_id') <div class="error-text">{{ $message }}</div> @enderror
        <div class="hint">La tarifa y la capacidad provienen del tipo seleccionado.</div>
    </div>

    <div>
        <label for="estado" class="label">Estado *</label>
        <select id="estado" name="estado" class="select" required>
            @foreach (\App\Models\Habitacion::ESTADOS as $valor)
                <option value="{{ $valor }}" {{ old('estado', $habitacion->estado ?? 'disponible') === $valor ? 'selected' : '' }}>{{ ucfirst($valor) }}</option>
            @endforeach
        </select>
        @error('estado') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="descripcion" class="label">Descripción</label>
        <input type="text" id="descripcion" name="descripcion" class="input" value="{{ old('descripcion', $habitacion->descripcion ?? '') }}" placeholder="Opcional">
        @error('descripcion') <div class="error-text">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="btn btn-primary">{{ $esEdicion ? 'Actualizar habitación' : 'Guardar habitación' }}</button>
    <a href="{{ route('habitaciones.index') }}" class="btn btn-secondary">Cancelar</a>
</div>
