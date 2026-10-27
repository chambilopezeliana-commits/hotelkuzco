@php
    $esEdicion = isset($tipo);
@endphp

<div class="form-grid">
    <div>
        <label for="nombre" class="label">Nombre del tipo *</label>
        <input type="text" id="nombre" name="nombre" class="input" value="{{ old('nombre', $tipo->nombre ?? '') }}" placeholder="Ej: Doble" required>
        @error('nombre') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="tarifa" class="label">Tarifa por noche (Bs) *</label>
        <input type="number" id="tarifa" name="tarifa" class="input" step="0.01" min="0" value="{{ old('tarifa', $tipo->tarifa ?? '') }}" required>
        @error('tarifa') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="capacidad" class="label">Capacidad (personas) *</label>
        <input type="number" id="capacidad" name="capacidad" class="input" min="1" max="20" value="{{ old('capacidad', $tipo->capacidad ?? 2) }}" required>
        @error('capacidad') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="activo" class="label">Disponible para reservas</label>
        <label class="flex items-center gap-2 text-sm text-slate-600 h-[42px]">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-marca-600 focus:ring-marca-500" {{ old('activo', $tipo->activo ?? true) ? 'checked' : '' }}>
            Sí, mostrar en disponibilidad
        </label>
        @error('activo') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="descripcion" class="label">Descripción</label>
        <textarea id="descripcion" name="descripcion" class="textarea" rows="2">{{ old('descripcion', $tipo->descripcion ?? '') }}</textarea>
        @error('descripcion') <div class="error-text">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="btn btn-primary">{{ $esEdicion ? 'Actualizar tipo' : 'Guardar tipo' }}</button>
    <a href="{{ route('tipos-habitacion.index') }}" class="btn btn-secondary">Cancelar</a>
</div>
