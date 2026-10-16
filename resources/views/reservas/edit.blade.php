@extends('layouts.app')

@section('titulo', 'Editar reserva')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('reservas.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a reservas</a>
    <h1 class="page-title mt-2">Editar reserva #{{ $reserva->id }}</h1>
    <p class="page-sub">Modifique fechas o habitación; se valida el solape con otras reservas activas.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('reservas.update', $reserva) }}">
            @csrf
            @method('PUT')
            @include('reservas._form')
        </form>
    </div>
</div>
@endsection
