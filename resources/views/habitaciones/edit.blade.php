@extends('layouts.app')

@section('titulo', 'Editar habitación')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('habitaciones.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a habitaciones</a>
    <h1 class="page-title mt-2">Editar habitación Nº {{ $habitacion->numero }}</h1>
    <p class="page-sub">Actualice el tipo, estado o descripción de la habitación.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('habitaciones.update', $habitacion) }}">
            @csrf
            @method('PUT')
            @include('habitaciones._form')
        </form>
    </div>
</div>
@endsection
