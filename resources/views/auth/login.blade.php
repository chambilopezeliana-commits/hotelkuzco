@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <div class="mx-auto h-16 w-16 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] flex items-center justify-center text-kz-espresso font-display text-3xl font-bold shadow-lg shadow-marca-600/30 ring-4 ring-marca-500/20 mb-4">K</div>
        <h1 class="font-display text-2xl font-bold text-kz-espresso tracking-[0.2em]">{{ strtoupper(config('hotel.nombre')) }}</h1>
        <p class="text-[11px] uppercase tracking-[0.3em] text-marca-600 mt-1">{{ config('hotel.lema') }}</p>
        <p class="text-sm text-stone-500 mt-3">Inicie sesión para acceder al sistema</p>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="card-title mb-5">Inicio de sesión</h2>

            @if ($errors->any())
                <div class="alert alert-error">
                    <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="label">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="input" value="{{ old('email') }}" placeholder="usuario@hotel.com" required autofocus>
                </div>

                <div>
                    <label for="password" class="label">Contraseña</label>
                    <input type="password" id="password" name="password" class="input" placeholder="••••••••" required>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-marca-600 focus:ring-marca-500">
                    Recordarme
                </label>

                <button type="submit" class="btn btn-primary btn-lg w-full">Entrar</button>
            </form>
        </div>
    </div>

    <p class="text-center text-xs text-slate-400 mt-6">Sistema de gestión hotelera · Acceso restringido al personal autorizado</p>
</div>
@endsection
