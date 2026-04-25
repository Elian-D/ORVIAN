<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ORVIAN') }}</title>

    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <x-ui.theme-init />

    <style>
        /* ── Grid blueprint ──
           Light: más visible con mayor opacidad naranja
           Dark:  tenue, casi imperceptible */
        .orvian-grid {
            background-image:
                linear-gradient(to right,  rgba(247,137,4,0.10) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(247,137,4,0.10) 1px, transparent 1px);
            background-size: 100px 100px;
        }
        .dark .orvian-grid {
            background-image:
                linear-gradient(to right,  rgba(247,137,4,0.035) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(247,137,4,0.035) 1px, transparent 1px);
        }

        /* ── Puntos ──
           Light: notables
           Dark:  sutiles */
        .orvian-dots {
            background-image: radial-gradient(circle at 2px 2px, rgba(247,137,4,0.18) 1.5px, transparent 0);
            background-size: 40px 40px;
        }
        .dark .orvian-dots {
            background-image: radial-gradient(circle at 2px 2px, rgba(247,137,4,0.055) 1.5px, transparent 0);
        }

        /* ── Parpadeo status dot ── */
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.2; }
        }
        .status-blink { animation: blink 2.8s ease-in-out infinite; }

        /* ── Animación de entrada ── */
        @keyframes card-rise {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-rise { animation: card-rise 0.5s cubic-bezier(0.16,1,0.3,1) both; }

        /* ── Fade entre frases ── */
        @keyframes phrase-in {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .phrase-in { animation: phrase-in 0.6s cubic-bezier(0.16,1,0.3,1) both; }

        /* ── Anillos orbitales ── */
        .orbit {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        /* ── Separador luminoso entre paneles ──
           Light: más brillante y visible
           Dark:  igual pero sobre fondo oscuro */
        .panel-divider {
            position: absolute;
            top: 0; right: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(
                to bottom,
                transparent 0%,
                rgba(247,137,4,0.25) 20%,
                rgba(247,137,4,0.75) 50%,
                rgba(247,137,4,0.25) 80%,
                transparent 100%
            );
            box-shadow:
                0 0 14px 2px rgba(247,137,4,0.35),
                0 0 40px 6px rgba(247,137,4,0.12);
        }

        /* ── Cruz decorativa (esquinas) ── */
        .corner-cross {
            position: absolute;
            width: 20px; height: 20px;
            pointer-events: none;
        }
        .corner-cross::before,
        .corner-cross::after {
            content: '';
            position: absolute;
            background: rgba(247,137,4,0.35);
        }
        .dark .corner-cross::before,
        .dark .corner-cross::after {
            background: rgba(247,137,4,0.18);
        }
        .corner-cross::before { width: 1px; height: 100%; left: 50%; top: 0; }
        .corner-cross::after  { height: 1px; width: 100%; top: 50%; left: 0; }

        /* ── Línea diagonal decorativa ── */
        .diag-line {
            position: absolute;
            width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(247,137,4,0.20), transparent);
            transform-origin: top center;
            pointer-events: none;
        }
        .dark .diag-line {
            background: linear-gradient(to bottom, transparent, rgba(247,137,4,0.08), transparent);
        }
    </style>
</head>

@php
    $frases = [
        'Registro de asistencia en segundos, no en minutos.',
        'Del aula al informe: gestión académica sin fricciones.',
        'Construido para las escuelas dominicanas, desde adentro.',
        'Un sistema que entiende cómo funciona una escuela de verdad.',
        'Biometría, QR o manual — el centro decide cómo registrar.',
        'Calificaciones validadas, boletines generados. Sin papel.',
        'Multi-institución. Un solo sistema. Datos separados.',
        'Privacidad primero. Los datos del centro son del centro.',
        'Diseñado para directores, maestros y coordinadores.',
        'De la matrícula al egreso: un solo hilo conductor.',
    ];
    $frase = $frases[array_rand($frases)];
@endphp

<body class="min-h-screen flex bg-[#f5f3f0] dark:bg-dark-bg antialiased transition-colors duration-500">

    {{-- Orbs de ambiente --}}
    <div aria-hidden="true" class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute rounded-full"
             style="width:700px;height:700px;background:#03224e;opacity:0.06;filter:blur(120px);top:-200px;left:-200px;"></div>
        <div class="absolute rounded-full"
             style="width:500px;height:500px;background:#f78904;opacity:0.07;filter:blur(130px);bottom:-100px;right:4%;"></div>
        {{-- Orb oscuro solo visible en dark --}}
        <div class="absolute rounded-full dark:opacity-40 opacity-0 transition-opacity duration-500"
             style="width:700px;height:700px;background:#03224e;filter:blur(120px);top:-200px;left:-200px;"></div>
    </div>

    <x-ui.toasts />

    {{-- ══════════════════════════════════════════
         PANEL IZQUIERDO — Branding
    ══════════════════════════════════════════ --}}
    <aside class="hidden lg:flex lg:w-[55%] xl:w-[58%] flex-col justify-center
                  relative overflow-hidden min-h-screen p-16 xl:p-24
                  bg-white dark:bg-dark-bg transition-colors duration-500">

        {{-- Texturas: grid + dots (intensidad controlada por CSS arriba) --}}
        <div aria-hidden="true" class="orvian-grid absolute inset-0 pointer-events-none"></div>
        <div aria-hidden="true" class="orvian-dots absolute inset-0 pointer-events-none"></div>

        {{-- Zona naranja suave en esquina superior izquierda — más visible en light --}}
        <div aria-hidden="true" class="absolute inset-0 pointer-events-none"
             style="background: radial-gradient(ellipse 60% 40% at 0% 0%, rgba(247,137,4,0.06) 0%, transparent 70%);"></div>
        <div aria-hidden="true" class="absolute inset-0 pointer-events-none dark:block hidden"
             style="background: radial-gradient(ellipse at 15% 55%, rgba(4,39,95,0.3) 0%, transparent 65%);"></div>

        {{-- Figura isométrica decorativa — más visible en light --}}
        <div aria-hidden="true" class="absolute inset-0 flex items-center justify-center pointer-events-none"
             style="opacity: 0.12;">
            <div class="dark:opacity-100" style="opacity:0.7;width:820px;height:620px;
                        border-left:1px solid rgba(247,137,4,0.6);
                        border-top:1px solid rgba(247,137,4,0.6);
                        transform:rotate(30deg) skewX(-10deg);"></div>
            <div class="dark:opacity-100" style="position:absolute;opacity:0.7;width:420px;height:310px;
                        border:1px solid rgba(247,137,4,0.7);
                        transform:rotate(30deg) skewX(-10deg) translate(85px,-42px);"></div>
        </div>

        {{-- Anillo orbital principal --}}
        <div aria-hidden="true" class="orbit"
             style="width:640px;height:640px;
                    border:1px solid rgba(247,137,4,0.12);
                    top:50%;left:50%;transform:translate(-50%,-50%);"></div>
        {{-- Anillo secundario más pequeño --}}
        <div aria-hidden="true" class="orbit"
             style="width:380px;height:380px;
                    border:1px solid rgba(247,137,4,0.08);
                    top:50%;left:50%;transform:translate(-50%,-50%);"></div>

        {{-- Cruces decorativas en esquinas internas --}}
        <div aria-hidden="true" class="corner-cross" style="top:48px; left:48px;"></div>
        <div aria-hidden="true" class="corner-cross" style="bottom:48px; right:48px;"></div>
        <div aria-hidden="true" class="corner-cross" style="top:48px; right:60px;"></div>

        {{-- Líneas diagonales decorativas --}}
        <div aria-hidden="true" class="diag-line"
             style="height:180px;top:60px;right:120px;transform:rotate(45deg);"></div>
        <div aria-hidden="true" class="diag-line"
             style="height:120px;bottom:80px;left:80px;transform:rotate(-30deg);"></div>

        {{-- Separador luminoso derecho --}}
        <div aria-hidden="true" class="panel-divider"></div>

        {{-- ── Contenido principal ── --}}
        <div class="relative z-10 max-w-xl">

            {{-- Wordmark ORVIAN --}}
            <div class="card-rise" style="animation-delay:0.05s;">
                <h1 class="font-etna text-[5.5rem] xl:text-[6.8rem] tracking-tighter leading-none select-none"
                    style="color:#f78904; font-weight:900;">
                    ORVIAN
                </h1>
            </div>

            {{-- Badge versión + frase aleatoria --}}
            <div class="mt-6 space-y-6 card-rise" style="animation-delay:0.12s;">

                {{-- Badge versión --}}
                <div class="flex items-center gap-3">
                    <span class="font-mono text-[11px] tracking-widest uppercase px-2.5 py-1 rounded
                                 text-gray-500 dark:text-[#79747e]
                                 border border-gray-300 dark:border-[#cac4cf]/15
                                 bg-white/60 dark:bg-transparent">
                        v{{ $appVersion ?? '0.4.1' }}
                    </span>
                    <div class="h-px flex-1 bg-gray-200 dark:bg-[#cac4cf]/10"></div>
                </div>

                {{-- Frase aleatoria --}}
                <p class="phrase-in font-light text-lg leading-relaxed max-w-sm
                           text-gray-600 dark:text-[#e2e2e5]/75">
                    {{ $frase }}
                </p>
            </div>
        </div>

        {{-- Decoración inferior: coordenadas ficticias estilo blueprint --}}
        <div class="absolute bottom-10 left-16 xl:left-24 z-10 pointer-events-none">
            <p class="font-mono text-[9px] tracking-widest uppercase
                      text-gray-300 dark:text-white/10">
                18°28'N 69°54'O — RD // EDU.NODE.01
            </p>
        </div>
    </aside>

    {{-- ══════════════════════════════════════════
         PANEL DERECHO — Formulario (slot)
    ══════════════════════════════════════════ --}}
    {{-- ─── PANEL DERECHO — Formulario ─── --}}
        <div class="flex-1 flex flex-col relative bg-white dark:bg-dark-bg">

            {{-- Logo visible solo en mobile --}}
            <div class="lg:hidden flex justify-center pt-10 pb-0">
                <span class="font-etna font-black text-5xl tracking-tighter leading-none select-none"
                      style="color:#f78904;">ORVIAN</span>
            </div>

            {{-- Formulario: centrado verticalmente --}}
            <div class="flex-1 flex items-center justify-center px-8 sm:px-16 py-12">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>

        {{-- Badge de seguridad --}}
        <div class="absolute bottom-8 right-8 sm:right-12 text-right hidden sm:block pointer-events-none">
            <div class="flex items-center justify-end gap-1.5 mb-0.5
                        text-gray-300 dark:text-white/15">
                <x-heroicon-s-shield-check class="w-3 h-3 flex-shrink-0" />
                <span class="font-mono text-[8.5px] uppercase tracking-widest">
                    Creado con seguridad y privacidad en mente
                </span>
            </div>
            <p class="font-mono text-[7.5px] uppercase tracking-tighter text-gray-200 dark:text-white/10">
                Tus datos están seguros con nosotros
            </p>
        </div>
    </div>

    @livewireScripts
</body>
</html>