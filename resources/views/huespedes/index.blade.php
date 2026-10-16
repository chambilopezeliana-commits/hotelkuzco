@extends('layouts.app')

@section('titulo', 'Huéspedes')

@section('contenido')
<div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
    <div>
        <h1 class="page-title">Huéspedes</h1>
        <p class="page-sub">Directorio único de huéspedes con documento validado</p>
    </div>
    <div class="flex items-center gap-2">
        @if (auth()->user()->esAdmin())
            <a href="{{ route('huespedes.importar') }}" class="btn btn-secondary">Importar planillas</a>
        @endif
        <a href="{{ route('huespedes.create') }}" class="btn btn-primary">+ Nuevo huésped</a>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('huespedes.index') }}" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-[220px]">
                <label class="label" for="buscar">Buscar</label>
                <input type="text" id="buscar" name="buscar" class="filter-input w-full" value="{{ $buscar }}" placeholder="Nombre o documento...">
            </div>
            <button class="btn btn-primary" type="submit">Buscar</button>
            @if ($buscar)
                <a href="{{ route('huespedes.index') }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    @if ($huespedes->isEmpty())
        <div class="empty-state">
            <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            No se encontraron huéspedes.
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($huespedes as $huesped)
                    <tr>
                        <td class="font-semibold text-slate-900">{{ $huesped->nombre }}</td>
                        <td><span class="badge badge-neutral">{{ $huesped->documento_completo }}</span></td>
                        <td>{{ $huesped->email ?? '—' }}</td>
                        <td>{{ $huesped->telefono ?? '—' }}</td>
                        <td class="text-slate-500">{{ $huesped->direccion ?? '—' }}</td>
                        <td>
                            <div class="acciones justify-end">
                                <a href="{{ route('huespedes.edit', $huesped) }}" class="btn btn-sm btn-secondary">Editar</a>
                                <form method="POST" action="{{ route('huespedes.destroy', $huesped) }}" onsubmit="return confirm('¿Eliminar al huésped {{ $huesped->nombre }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 pb-5">{{ $huespedes->links() }}</div>
    @endif
</div>
@endsection
