@php
    $esEdicion = isset($huesped);
@endphp

<div class="form-grid">
    <div class="md:col-span-2">
        <label for="nombre" class="label">Nombre completo *</label>
        <input type="text" id="nombre" name="nombre" class="input" value="{{ old('nombre', $huesped->nombre ?? '') }}" required>
        @error('nombre') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="tipo_documento" class="label">Tipo de documento *</label>
        <select id="tipo_documento" name="tipo_documento" class="select" required>
            @foreach (\App\Models\Huesped::TIPOS_DOCUMENTO as $tipo)
                <option value="{{ $tipo }}" {{ old('tipo_documento', $huesped->tipo_documento ?? 'DNI') === $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
            @endforeach
        </select>
        @error('tipo_documento') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="documento" class="label">Número de documento *</label>
        <input type="text" id="documento" name="documento" class="input" value="{{ old('documento', $huesped->documento ?? '') }}" maxlength="30" placeholder="Ej.: 1234567, 1234567-1A" required>
        @error('documento') <div class="error-text">{{ $message }}</div> @enderror
        <div class="hint">Admite letras y números (p. ej. CI boliviana con complemento). La combinación tipo + número debe ser única.</div>
    </div>

    <div>
        <label for="email" class="label">Correo electrónico</label>
        <input type="email" id="email" name="email" class="input" value="{{ old('email', $huesped->email ?? '') }}">
        @error('email') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="telefono" class="label">Teléfono</label>
        <input type="text" id="telefono" name="telefono" class="input" value="{{ old('telefono', $huesped->telefono ?? '') }}">
        @error('telefono') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="direccion" class="label">Dirección</label>
        <input type="text" id="direccion" name="direccion" class="input" value="{{ old('direccion', $huesped->direccion ?? '') }}">
        @error('direccion') <div class="error-text">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="btn btn-primary">{{ $esEdicion ? 'Actualizar huésped' : 'Guardar huésped' }}</button>
    <a href="{{ route('huespedes.index') }}" class="btn btn-secondary">Cancelar</a>
</div>
