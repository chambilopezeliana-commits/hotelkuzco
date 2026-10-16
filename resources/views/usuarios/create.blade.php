@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('usuarios.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a usuarios</a>
    <h1 class="page-title mt-2">Nuevo usuario</h1>
    <p class="page-sub">Cree una cuenta y asígnele un rol de acceso al sistema.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('usuarios.store') }}">
            @csrf
            @include('usuarios._form')
        </form>
    </div>
</div>
@endsection
