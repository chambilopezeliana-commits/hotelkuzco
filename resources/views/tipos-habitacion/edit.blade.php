@extends('layouts.app')

@section('titulo', 'Editar tipo de habitación')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('tipos-habitacion.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a tipos</a>
    <h1 class="page-title mt-2">Editar tipo: {{ $tipo->nombre }}</h1>
    <p class="page-sub">Los cambios de tarifa se aplican a las nuevas reservas.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('tipos-habitacion.update', $tipo) }}">
            @csrf
            @method('PUT')
            @include('tipos-habitacion._form')
        </form>
    </div>
</div>
@endsection
