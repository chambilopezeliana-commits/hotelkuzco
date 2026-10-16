@extends('layouts.app')

@section('titulo', 'Nuevo tipo de habitación')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('tipos-habitacion.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a tipos</a>
    <h1 class="page-title mt-2">Nuevo tipo de habitación</h1>
    <p class="page-sub">Defina la tarifa por noche y la capacidad máxima del tipo.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('tipos-habitacion.store') }}">
            @csrf
            @include('tipos-habitacion._form')
        </form>
    </div>
</div>
@endsection
