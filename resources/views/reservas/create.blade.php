@extends('layouts.app')

@section('titulo', 'Nueva reserva')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('reservas.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a reservas</a>
    <h1 class="page-title mt-2">Nueva reserva</h1>
    <p class="page-sub">El sistema rechazará automáticamente cualquier solape de fechas.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('reservas.store') }}">
            @csrf
            @include('reservas._form')
        </form>
    </div>
</div>
@endsection
