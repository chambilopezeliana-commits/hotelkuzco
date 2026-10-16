@extends('layouts.app')

@section('titulo', 'Importar huéspedes')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('huespedes.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a huéspedes</a>
    <h1 class="page-title mt-2">Importar huéspedes desde planillas</h1>
    <p class="page-sub">Cargue un archivo CSV para migrar el historial de huéspedes de las planillas actuales.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Cargar archivo</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('huespedes.importar.procesar') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-grid">
                        <div class="md:col-span-2">
                            <label for="archivo" class="label">Archivo CSV *</label>
                            <input type="file" id="archivo" name="archivo" accept=".csv,.txt" class="input file:mr-3 file:rounded-md file:border-0 file:bg-marca-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-marca-700 hover:file:bg-marca-100" required>
                            @error('archivo') <div class="error-text">{{ $message }}</div> @enderror
                            <div class="hint">Tamaño máximo 4 MB. Codificación UTF-8 separado por comas.</div>
                        </div>
                        <div>
                            <label for="accion" class="label">Acción *</label>
                            <select id="accion" name="accion" class="select" required>
                                <option value="importar" {{ old('accion') === 'importar' ? 'selected' : '' }}>Importar (insertar/actualizar)</option>
                                <option value="depurar" {{ old('accion') === 'depurar' ? 'selected' : '' }}>Solo depurar (detectar duplicados)</option>
                            </select>
                            @error('accion') <div class="error-text">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mt-5">
                        <button type="submit" class="btn btn-primary">Procesar archivo</button>
                    </div>
                </form>
            </div>
        </div>

        @if (session('errores') && count(session('errores')) > 0)
            <div class="card mt-6">
                <div class="card-header"><h2 class="card-title text-rose-600">Errores encontrados ({{ count(session('errores')) }})</h2></div>
                <div class="card-body">
                    <ul class="text-sm text-rose-700 space-y-1 list-disc list-inside">
                        @foreach (session('errores') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('depurados') && count(session('depurados')) > 0)
            <div class="card mt-6">
                <div class="card-header"><h2 class="card-title text-amber-600">Duplicados detectados ({{ count(session('depurados')) }})</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Nombre</th><th>Documento</th><th>Correo</th></tr>
                        </thead>
                        <tbody>
                            @foreach (session('depurados') as $fila)
                                <tr>
                                    <td>{{ $fila['id'] }}</td>
                                    <td class="font-medium">{{ $fila['nombre'] }}</td>
                                    <td>{{ $fila['tipo_documento'] }} {{ $fila['documento'] }}</td>
                                    <td>{{ $fila['email'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Formato esperado</h2></div>
            <div class="card-body space-y-3 text-sm text-slate-600">
                <p>La primera fila debe contener los encabezados. Columnas admitidas:</p>
                <ul class="space-y-1">
                    <li><code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">nombre</code> — obligatorio</li>
                    <li><code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">tipo_documento</code> — DNI/CI/PASAPORTE (por defecto DNI)</li>
                    <li><code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">documento</code> — obligatorio</li>
                    <li><code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">email</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">telefono</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">direccion</code> — opcionales</li>
                </ul>
                <pre class="text-xs bg-slate-900 text-slate-100 rounded-lg p-3 overflow-x-auto">nombre,tipo_documento,documento,email,telefono,direccion
Juan Pérez,DNI,12345678,juan@mail.com,70000000,La Paz</pre>
                <p class="text-xs text-slate-500">Los registros con documento ya existente no se duplican: en modo <strong>Importar</strong> se actualizan y en modo <strong>Depurar</strong> solo se listan.</p>
            </div>
        </div>
    </div>
</div>
@endsection
