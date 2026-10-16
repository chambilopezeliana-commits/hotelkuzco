@extends('layouts.app')

@section('titulo', 'Nueva habitación')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('habitaciones.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a habitaciones</a>
    <h1 class="page-title mt-2">Nueva habitación</h1>
    <p class="page-sub">Registre el número y asígnele un tipo con su tarifa y capacidad.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('habitaciones.store') }}">
            @csrf
            @include('habitaciones._form')
        </form>
    </div>
</div>
@endsection
