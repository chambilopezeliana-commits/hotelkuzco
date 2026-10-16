@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('usuarios.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a usuarios</a>
    <h1 class="page-title mt-2">Editar usuario</h1>
    <p class="page-sub">{{ $usuario->name }} · {{ $usuario->email }}</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
            @csrf
            @method('PUT')
            @include('usuarios._form')
        </form>
    </div>
</div>
@endsection
