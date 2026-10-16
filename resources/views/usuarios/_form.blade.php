@php($esEdicion = isset($usuario))

<div class="form-grid">
    <div>
        <label for="name" class="label">Nombre *</label>
        <input type="text" id="name" name="name" class="input" value="{{ old('name', $usuario->name ?? '') }}" required>
        @error('name') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="email" class="label">Correo electrónico *</label>
        <input type="email" id="email" name="email" class="input" value="{{ old('email', $usuario->email ?? '') }}" required>
        @error('email') <div class="error-text">{{ $message }}</div> @enderror
    </div>

    <div>
        <label for="role" class="label">Rol *</label>
        <select id="role" name="role" class="select" required>
            <option value="recepcionista" {{ old('role', $usuario->role ?? 'recepcionista') === 'recepcionista' ? 'selected' : '' }}>Recepcionista</option>
            <option value="administrador" {{ old('role', $usuario->role ?? '') === 'administrador' ? 'selected' : '' }}>Administrador</option>
        </select>
        @error('role') <div class="error-text">{{ $message }}</div> @enderror
        <div class="hint">El administrador gestiona tarifas, usuarios y auditoría; el recepcionista opera reservas y cobros.</div>
    </div>

    <div>
        <label for="password" class="label">Contraseña {{ $esEdicion ? '(vacío para mantener)' : '*' }}</label>
        <input type="password" id="password" name="password" class="input" {{ $esEdicion ? '' : 'required' }} minlength="8">
        @error('password') <div class="error-text">{{ $message }}</div> @enderror
        <div class="hint">Mínimo 8 caracteres.</div>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="btn btn-primary">{{ $esEdicion ? 'Actualizar usuario' : 'Crear usuario' }}</button>
    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
</div>
