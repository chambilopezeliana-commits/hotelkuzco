<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $contacto['nombre'] }} · {{ $contacto['lema'] }}</title>
    <meta name="description" content="{{ $contacto['descripcion'] }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --kz-espresso: #2a1a0f;
            --kz-brown: #4a3220;
            --kz-wood: #8a6a48;
            --kz-gold: #c89b3c;
            --kz-gold-light: #e6c877;
            --kz-cream: #f6efe3;
        }
        body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Cinzel', serif; }
        .kz-gold-text {
            background: linear-gradient(180deg, var(--kz-gold-light), var(--kz-gold));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .kz-andes {
            background-image:
                repeating-linear-gradient(45deg, rgba(200,155,60,.18) 0 8px, transparent 8px 16px),
                repeating-linear-gradient(-45deg, rgba(200,155,60,.18) 0 8px, transparent 8px 16px);
        }
        .kz-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--kz-gold), transparent);
        }
    </style>
</head>
<body class="bg-[#f6efe3] text-[#2a1a0f] antialiased">

    <!-- Barra superior -->
    <div class="hidden md:block bg-[#2a1a0f] text-[#e6c877] text-xs tracking-wide">
        <div class="max-w-6xl mx-auto px-6 h-10 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <span class="inline-flex items-center gap-2">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                    {{ $contacto['telefono'] }}
                </span>
                <span class="inline-flex items-center gap-2">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    {{ $contacto['email'] }}
                </span>
            </div>
            <span class="inline-flex items-center gap-2">{{ $contacto['horario'] }}</span>
        </div>
    </div>

    <!-- Encabezado -->
    <header class="sticky top-0 z-40 bg-[#2a1a0f]/95 backdrop-blur border-b border-[#c89b3c]/30">
        <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="{{ route('catalogo') }}" class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] flex items-center justify-center text-[#2a1a0f] font-display font-bold text-2xl ring-2 ring-[#c89b3c]/40 shadow-lg shadow-black/30">K</div>
                <div>
                    <div class="font-display font-bold text-xl tracking-[0.2em] text-[#f6efe3] leading-none">{{ strtoupper($contacto['nombre']) }}</div>
                    <div class="text-[10px] tracking-[0.35em] text-[#c89b3c] uppercase mt-1">{{ $contacto['lema'] }}</div>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-[#e6c877]/80">
                <a href="#inicio" class="hover:text-[#e6c877] transition">Inicio</a>
                <a href="#habitaciones" class="hover:text-[#e6c877] transition">Habitaciones</a>
                <a href="#instalaciones" class="hover:text-[#e6c877] transition">Instalaciones</a>
                <a href="#galeria" class="hover:text-[#e6c877] transition">Galería</a>
                <a href="#servicios" class="hover:text-[#e6c877] transition">Servicios</a>
                <a href="#contacto" class="hover:text-[#e6c877] transition">Contacto</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-2 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] px-5 py-2.5 text-sm font-semibold text-[#2a1a0f] hover:brightness-110 transition shadow-lg shadow-black/30">
                    Reservar
                </a>
                <a href="{{ route('login') }}" class="rounded-full border border-[#c89b3c]/50 px-5 py-2.5 text-sm font-semibold text-[#e6c877] hover:bg-[#c89b3c]/10 transition">Iniciar sesión</a>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section id="inicio" class="relative overflow-hidden">
        <img src="{{ $hero }}" alt="{{ $contacto['nombre'] }}" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-[#2a1a0f] via-[#2a1a0f]/80 to-[#2a1a0f]/50"></div>
        <div class="relative max-w-6xl mx-auto px-6 py-24 md:py-36 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] flex items-center justify-center text-[#2a1a0f] font-display text-3xl font-bold ring-4 ring-[#c89b3c]/20 mb-6">K</div>
            <div class="text-xs tracking-[0.5em] text-[#c89b3c] uppercase">Bienvenido a</div>
            <h1 class="mt-3 font-display text-5xl md:text-7xl font-bold tracking-[0.15em] kz-gold-text">{{ strtoupper($contacto['nombre']) }}</h1>
            <div class="kz-divider max-w-xs mx-auto my-6"></div>
            <p class="font-display text-lg md:text-2xl tracking-[0.3em] text-[#f6efe3] uppercase">{{ $contacto['lema'] }}</p>
            <p class="mt-6 max-w-2xl mx-auto text-[#f6efe3]/80">{{ $contacto['descripcion'] }}</p>

            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] px-8 py-3.5 font-semibold text-[#2a1a0f] hover:brightness-110 transition shadow-xl shadow-black/40">
                    Reservar por WhatsApp
                </a>
                <a href="#habitaciones" class="inline-flex items-center gap-2 rounded-full border border-[#e6c877]/50 px-8 py-3.5 font-semibold text-[#f6efe3] hover:bg-[#e6c877]/10 transition">Ver habitaciones</a>
            </div>

            <div class="mt-14 grid grid-cols-3 gap-6 max-w-xl mx-auto">
                <div>
                    <div class="font-display text-4xl font-bold kz-gold-text">{{ $totalHabitaciones }}</div>
                    <div class="text-xs tracking-widest text-[#e6c877]/70 uppercase">Habitaciones</div>
                </div>
                <div>
                    <div class="font-display text-4xl font-bold kz-gold-text">{{ $tipos->count() }}</div>
                    <div class="text-xs tracking-widest text-[#e6c877]/70 uppercase">Categorías</div>
                </div>
                <div>
                    <div class="font-display text-4xl font-bold kz-gold-text">24/7</div>
                    <div class="text-xs tracking-widest text-[#e6c877]/70 uppercase">Atención</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Franja andina -->
    <div class="h-3 kz-andes bg-[#4a3220]"></div>

    <!-- Habitaciones -->
    <section id="habitaciones" class="max-w-6xl mx-auto px-6 py-16 md:py-24">
        <div class="text-center max-w-2xl mx-auto">
            <span class="text-xs font-semibold uppercase tracking-[0.4em] text-[#8a6a48]">Cámaras imperiales</span>
            <h2 class="mt-3 font-display text-3xl md:text-4xl font-bold text-[#2a1a0f]">Elige tu aposento</h2>
            <div class="kz-divider max-w-xs mx-auto my-5"></div>
            <p class="text-[#4a3220]/80">Tarifas por noche, capacidad y disponibilidad actualizada. Reserva directamente por WhatsApp.</p>
        </div>

        @if ($tipos->isEmpty())
            <div class="mt-12 text-center text-[#8a6a48]">Pronto publicaremos nuestras habitaciones.</div>
        @else
            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($tipos as $tipo)
                    @php $mensaje = rawurlencode('Hola, quiero reservar una habitación '.$tipo['nombre'].' en '.$contacto['nombre'].'.'); @endphp
                    <article class="group bg-white rounded-2xl overflow-hidden border border-[#c89b3c]/25 shadow-sm hover:shadow-xl hover:border-[#c89b3c]/60 transition flex flex-col">
                        <div class="relative overflow-hidden">
                            <img src="{{ $tipo['imagen'] }}" alt="Habitación {{ $tipo['nombre'] }}" class="w-full h-56 object-cover group-hover:scale-105 transition duration-500">
                            <span class="absolute top-3 left-3 rounded-full px-3 py-1 text-xs font-semibold {{ $tipo['disponibles'] > 0 ? 'bg-emerald-600/90 text-white' : 'bg-red-700/90 text-white' }}">
                                {{ $tipo['disponibles'] > 0 ? $tipo['disponibles'].' disponibles' : 'No disponible' }}
                            </span>
                            <span class="absolute top-3 right-3 rounded-full bg-[#2a1a0f]/80 text-[#e6c877] px-3 py-1 text-xs font-semibold">
                                {{ $tipo['capacidad'] }} {{ $tipo['capacidad'] == 1 ? 'persona' : 'personas' }}
                            </span>
                        </div>

                        <div class="p-6 flex flex-col flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-display text-xl font-bold text-[#2a1a0f]">{{ $tipo['nombre'] }}</h3>
                                <div class="text-right shrink-0">
                                    <div class="font-display text-2xl font-bold text-[#c89b3c]">{{ bs($tipo['tarifa']) }}</div>
                                    <div class="text-[11px] text-[#8a6a48]">por noche</div>
                                </div>
                            </div>

                            <p class="mt-3 text-sm text-[#4a3220]/80 flex-1">{{ $tipo['descripcion'] }}</p>

                            <div class="mt-4 flex flex-wrap gap-1.5">
                                @foreach (['Wi-Fi', 'TV cable', 'Baño privado'] as $servicio)
                                    <span class="rounded-full bg-[#f6efe3] border border-[#c89b3c]/30 px-3 py-0.5 text-xs font-medium text-[#4a3220]">{{ $servicio }}</span>
                                @endforeach
                                @if ($tipo['capacidad'] > 2)
                                    <span class="rounded-full bg-[#f6efe3] border border-[#c89b3c]/30 px-3 py-0.5 text-xs font-medium text-[#4a3220]">Familiar</span>
                                @endif
                            </div>

                            <div class="mt-4 pt-4 border-t border-[#c89b3c]/20 text-xs text-[#8a6a48]">
                                N.º de habitaciones:
                                <span class="font-semibold text-[#4a3220]">{{ $tipo['numeros'] ? implode(' · ', $tipo['numeros']) : '—' }}</span>
                            </div>

                            <a href="{{ $whatsappUrl }}?text={{ $mensaje }}" target="_blank" rel="noopener"
                               class="mt-5 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] px-5 py-3 font-semibold text-[#2a1a0f] hover:brightness-110 transition">
                                Reservar por WhatsApp
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Instalaciones -->
    <section id="instalaciones" class="bg-[#2a1a0f] py-16 md:py-24">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-xs font-semibold uppercase tracking-[0.4em] text-[#c89b3c]">Instalaciones del palacio</span>
                <h2 class="mt-3 font-display text-3xl md:text-4xl font-bold text-[#f6efe3]">Recepción, descanso, comedor y piscina</h2>
                <div class="kz-divider max-w-xs mx-auto my-5"></div>
                <p class="text-[#f6efe3]/70">Espacios pensados para que tu estadía sea digna de un emperador.</p>
            </div>

            <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($instalaciones as $instalacion)
                    <article class="group relative rounded-2xl overflow-hidden border border-[#c89b3c]/25 hover:border-[#c89b3c]/70 transition">
                        <img src="{{ $instalacion['imagen'] }}" alt="{{ $instalacion['nombre'] }}" class="h-72 w-full object-cover group-hover:scale-105 transition duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#2a1a0f] via-[#2a1a0f]/40 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            <h3 class="font-display text-lg font-bold text-[#e6c877]">{{ $instalacion['nombre'] }}</h3>
                            <p class="mt-1 text-sm text-[#f6efe3]/75">{{ $instalacion['descripcion'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Galería -->
    <section id="galeria" class="max-w-6xl mx-auto px-6 py-16 md:py-24">
        <div class="text-center">
            <span class="text-xs font-semibold uppercase tracking-[0.4em] text-[#8a6a48]">Galería</span>
            <h2 class="mt-3 font-display text-3xl md:text-4xl font-bold text-[#2a1a0f]">El palacio en imágenes</h2>
            <div class="kz-divider max-w-xs mx-auto my-5"></div>
        </div>
        <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($galeria as $i => $foto)
                <img src="{{ $foto }}" alt="Galería {{ $i + 1 }}"
                     class="w-full object-cover rounded-xl border border-[#c89b3c]/25 hover:border-[#c89b3c]/60 transition {{ $i === 0 ? 'col-span-2 row-span-2 h-84 md:h-full' : 'h-40 md:h-56' }}">
            @endforeach
        </div>
    </section>

    <!-- Servicios -->
    <section id="servicios" class="max-w-6xl mx-auto px-6 py-16 md:py-24">
        <div class="text-center max-w-2xl mx-auto">
            <span class="text-xs font-semibold uppercase tracking-[0.4em] text-[#8a6a48]">Servicios del palacio</span>
            <h2 class="mt-3 font-display text-3xl md:text-4xl font-bold text-[#2a1a0f]">Todo para tu comodidad</h2>
            <div class="kz-divider max-w-xs mx-auto my-5"></div>
        </div>
        <div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-6">
            @php
                $servicios = [
                    ['Wi-Fi imperial', 'Conexión gratuita de alta velocidad'],
                    ['Banquete matutino', 'Desayuno buffet incluido'],
                    ['Estacionamiento', 'Plaza privada sin costo'],
                    ['Guardia 24 horas', 'Recepción siempre disponible'],
                ];
            @endphp
            @foreach ($servicios as [$titulo, $texto])
                <div class="bg-white rounded-2xl border border-[#c89b3c]/25 p-6 text-center h-full hover:border-[#c89b3c]/60 transition">
                    <div class="mx-auto h-12 w-12 rounded-full bg-[#f6efe3] border border-[#c89b3c]/40 text-[#c89b3c] flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="mt-4 font-display font-bold text-[#2a1a0f]">{{ $titulo }}</h3>
                    <p class="mt-1 text-sm text-[#4a3220]/75">{{ $texto }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="bg-[#4a3220] py-16 md:py-24">
        <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <div>
                <span class="text-xs font-semibold uppercase tracking-[0.4em] text-[#c89b3c]">Reservas y contacto</span>
                <h2 class="mt-3 font-display text-3xl md:text-4xl font-bold text-[#f6efe3]">¿Listo para tu estadía real?</h2>
                <div class="kz-divider max-w-xs my-5"></div>
                <p class="text-[#f6efe3]/75">Escríbenos por cualquiera de nuestros canales y con gusto te ayudamos a reservar el aposento ideal.</p>

                <div class="mt-8 space-y-4">
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-2xl bg-[#2a1a0f]/50 border border-[#c89b3c]/25 p-4 hover:border-[#c89b3c]/60 transition">
                        <div class="h-11 w-11 rounded-full bg-emerald-600/90 text-white flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 004.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs text-[#c89b3c]">WhatsApp</div>
                            <div class="font-semibold text-[#f6efe3]">{{ $contacto['telefono'] }}</div>
                        </div>
                    </a>

                    <a href="mailto:{{ $contacto['email'] }}" class="flex items-center gap-4 rounded-2xl bg-[#2a1a0f]/50 border border-[#c89b3c]/25 p-4 hover:border-[#c89b3c]/60 transition">
                        <div class="h-11 w-11 rounded-full bg-[#c89b3c] text-[#2a1a0f] flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        </div>
                        <div>
                            <div class="text-xs text-[#c89b3c]">Correo electrónico</div>
                            <div class="font-semibold text-[#f6efe3]">{{ $contacto['email'] }}</div>
                        </div>
                    </a>

                    <div class="flex items-center gap-4 rounded-2xl bg-[#2a1a0f]/50 border border-[#c89b3c]/25 p-4">
                        <div class="h-11 w-11 rounded-full bg-[#8a6a48] text-[#f6efe3] flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs text-[#c89b3c]">Dirección</div>
                            <div class="font-semibold text-[#f6efe3]">{{ $contacto['direccion'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <div class="text-sm font-semibold text-[#e6c877] mb-3">Síguenos en redes</div>
                    <div class="flex items-center gap-3">
                        <a href="{{ $contacto['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook" class="h-11 w-11 rounded-full bg-[#2a1a0f]/60 border border-[#c89b3c]/25 text-[#e6c877] hover:bg-[#c89b3c] hover:text-[#2a1a0f] transition flex items-center justify-center">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94z"/></svg>
                        </a>
                        <a href="{{ $contacto['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram" class="h-11 w-11 rounded-full bg-[#2a1a0f]/60 border border-[#c89b3c]/25 text-[#e6c877] hover:bg-[#c89b3c] hover:text-[#2a1a0f] transition flex items-center justify-center">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.42.56.22.96.48 1.38.9.42.42.68.82.9 1.38.17.42.37 1.06.42 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.42 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.17-1.06.37-2.23.42-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.42-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.17-.42-.37-1.06-.42-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.42-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.17 1.06-.37 2.23-.42C8.42 2.17 8.8 2.16 12 2.16zm0 3.68a6.16 6.16 0 100 12.32 6.16 6.16 0 000-12.32zm0 10.16a4 4 0 110-8 4 4 0 010 8zm7.85-10.4a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z"/></svg>
                        </a>
                        <a href="{{ $contacto['tiktok'] }}" target="_blank" rel="noopener" aria-label="TikTok" class="h-11 w-11 rounded-full bg-[#2a1a0f]/60 border border-[#c89b3c]/25 text-[#e6c877] hover:bg-[#c89b3c] hover:text-[#2a1a0f] transition flex items-center justify-center">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 3c.4 2.1 1.7 3.5 3.9 3.7v2.6c-1.3.1-2.6-.2-3.9-.9v6.2c0 3.4-2.6 5.9-5.8 5.9-3.1 0-5.7-2.4-5.7-5.6 0-3.4 3-6 6.6-5.5v2.7c-.4-.1-.8-.2-1.3-.2-1.6 0-2.8 1.2-2.8 2.9 0 1.7 1.3 2.9 2.9 2.9 1.6 0 2.9-1.2 2.9-3V3h3.2z"/></svg>
                        </a>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="WhatsApp" class="h-11 w-11 rounded-full bg-[#2a1a0f]/60 border border-[#c89b3c]/25 text-[#e6c877] hover:bg-[#c89b3c] hover:text-[#2a1a0f] transition flex items-center justify-center">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 004.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl overflow-hidden border border-[#c89b3c]/30 bg-[#2a1a0f]/50">
                <div class="bg-gradient-to-br from-[#c89b3c] to-[#8a6a48] p-6">
                    <div class="text-xs uppercase tracking-widest text-[#2a1a0f]/70">Reserva rápida</div>
                    <div class="font-display text-2xl font-bold text-[#2a1a0f] mt-1">Contáctanos ahora</div>
                </div>
                <div class="p-6 space-y-4 text-sm">
                    <div class="flex justify-between border-b border-[#c89b3c]/20 pb-3">
                        <span class="text-[#c89b3c]">Teléfono</span>
                        <span class="font-semibold text-[#f6efe3]">{{ $contacto['telefono'] }}</span>
                    </div>
                    <div class="flex justify-between border-b border-[#c89b3c]/20 pb-3">
                        <span class="text-[#c89b3c]">Correo</span>
                        <span class="font-semibold text-[#f6efe3]">{{ $contacto['email'] }}</span>
                    </div>
                    <div class="flex justify-between border-b border-[#c89b3c]/20 pb-3">
                        <span class="text-[#c89b3c]">Dirección</span>
                        <span class="font-semibold text-[#f6efe3] text-right">{{ $contacto['direccion'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#c89b3c]">Atención</span>
                        <span class="font-semibold text-[#f6efe3]">{{ $contacto['horario'] }}</span>
                    </div>

                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-5 py-3 font-semibold text-white hover:bg-emerald-700 transition w-full mt-2">
                        Reservar por WhatsApp
                    </a>
                    <a href="mailto:{{ $contacto['email'] }}" class="flex items-center justify-center gap-2 rounded-full border border-[#c89b3c]/50 px-5 py-3 font-semibold text-[#e6c877] hover:bg-[#c89b3c]/10 transition w-full">Escribir un correo</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Pie -->
    <footer class="bg-[#2a1a0f] border-t border-[#c89b3c]/30">
        <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-[#e6c877]/60">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-[#e6c877] to-[#c89b3c] flex items-center justify-center text-[#2a1a0f] font-display font-bold">K</div>
                <span class="font-display tracking-widest text-[#f6efe3]">{{ strtoupper($contacto['nombre']) }}</span>
                <span class="text-[#c89b3c]">· {{ $contacto['lema'] }}</span>
            </div>
            <p>&copy; {{ now()->year }} {{ $contacto['nombre'] }}. Todos los derechos reservados.</p>
            <a href="{{ route('login') }}" class="hover:text-[#e6c877] transition">Acceso al sistema</a>
        </div>
    </footer>

</body>
</html>
