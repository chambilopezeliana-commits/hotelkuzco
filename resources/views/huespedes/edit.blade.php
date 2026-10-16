@extends('layouts.app')

@section('titulo', 'Editar huésped')

@section('contenido')
<div class="mb-6">
    <a href="{{ route('huespedes.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Volver a huéspedes</a>
    <h1 class="page-title mt-2">Editar huésped</h1>
    <p class="page-sub">{{ $huesped->nombre }} · {{ $huesped->documento_completo }}</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('huespedes.update', $huesped) }}">
            @csrf
            @method('PUT')
            @include('huespedes._form')
        </form>
    </div>
</div>
@endsection
