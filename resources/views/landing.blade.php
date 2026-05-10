<x-layouts::public>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- §3.2 HERO                                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <style>
        .about-blueprint {
            background-image:
                linear-gradient(to right, rgba(var(--orvian-navy-rgb, 15, 23, 42), 0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(var(--orvian-navy-rgb, 15, 23, 42), 0.04) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .dark .about-blueprint {
            background-image:
                linear-gradient(to right, rgba(255,255,255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255, 0.03) 1px, transparent 1px);
        }

    </style>
    <section id="inicio" class="relative overflow-hidden pt-28 pb-20 px-4 sm:pt-36 sm:pb-28 about-blueprint">

        {{-- Gradientes decorativos de fondo --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-48 -right-48 w-[600px] h-[600px] rounded-full bg-orvian-orange/5 blur-3xl"></div>
            <div class="absolute top-1/2 -left-48 w-[500px] h-[500px] rounded-full bg-orvian-navy/5 blur-3xl"></div>
        </div>

        <div class="relative max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-8 items-center">

                {{-- Texto --}}
                <div class="flex flex-col items-center text-center md:items-start md:text-left">
                    <div x-data @click.prevent="document.getElementById('modulos').scrollIntoView({behavior:'smooth'})">
                        <x-ui.badge variant="primary" size="sm" :dot="false">
                            Sistema de Gestión Educativa
                        </x-ui.badge>
                    </div>

                    <h1 class="mt-6 text-4xl sm:text-5xl lg:text-[56px] font-black leading-tight text-slate-900 dark:text-white">
                        Sistema de Gestión Educativa<br>
                        para <span class="text-orvian-orange">Instituciones Dominicanas</span>
                    </h1>

                    <p class="mt-6 text-base sm:text-lg text-slate-500 dark:text-slate-400 leading-relaxed max-w-lg">
                        La plataforma modular diseñada para instituciones públicas dominicanas.
                        Control total, desde la asistencia hasta las calificaciones, en un solo ecosistema.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <x-ui.button
                            variant="primary"
                            :hoverEffect="true"
                            href="{{ route('login') }}"
                            title="Acceder a ORVIAN — Sistema de gestión educativa"
                        >
                            Empieza Ahora
                        </x-ui.button>
                        <div x-data>
                            <x-ui.button
                                variant="secondary"
                                type="ghost"
                                @click.prevent="document.getElementById('modulos').scrollIntoView({behavior:'smooth'})"
                            >
                                Ver Módulos
                            </x-ui.button>
                        </div>
                    </div>
                </div>

                {{-- Mockup visual del dashboard con Perspectiva 3D --}}
                <div class="flex justify-center md:justify-end py-10 sm:py-24 pr-0 sm:pr-8 md:pr-20 perspective-1000">
                    <div class="relative w-full max-w-lg group cursor-default transition-all duration-1000 ease-in-out preserve-3d hover:preserve-3d [transform:rotateX(15deg)rotateY(-20deg)rotateZ(5deg)] hover:[transform:rotateX(0deg)rotateY(0deg)rotateZ(0deg)]">
                        
                        {{-- Glow de fondo dinámico --}}
                        <div class="absolute inset-0 rounded-3xl bg-orvian-orange/20 blur-[100px] scale-90 -z-10 transition-all duration-700 group-hover:scale-110 group-hover:bg-orvian-orange/30"></div>

                        {{-- Tarjeta Flotante 1: Biometría (Capa Superior) --}}
                        <div class="absolute -top-10 -left-6 md:-left-20 z-50 hidden sm:flex items-center gap-3 p-4 bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 transition-all duration-700 delay-75 transform translate-z-20 group-hover:translate-z-32 group-hover:-translate-y-6">
                            <div class="relative w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center text-state-success overflow-hidden">
                                {{-- Efecto de escaneo animado --}}
                                <div class="absolute inset-0 bg-gradient-to-b from-transparent via-green-500/20 to-transparent animate-scan"></div>
                                <svg class="w-5 h-5 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-tighter">Face ID Identificado</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">ID: 402-2342-1 • 0.02ms</p>
                            </div>
                        </div>

                        {{-- Tarjeta Flotante 2: Notificación WhatsApp (Capa Media) --}}
                        <div class="absolute -bottom-10 -right-4 md:-right-16 z-50 hidden sm:flex items-center gap-3 p-4 bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 transition-all duration-700 delay-150 transform translate-z-10 group-hover:translate-z-24 group-hover:translate-y-4">
                            <div class="w-10 h-10 rounded-full bg-orvian-orange/10 flex items-center justify-center text-orvian-orange">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-tighter">Padre Notificado</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">Entrega: <span class="text-state-success font-bold text-[8px]">RECIBIDO</span></p>
                            </div>
                        </div>

                        {{-- Browser principal --}}
                        <div class="relative rounded-3xl overflow-hidden border border-slate-200 dark:border-white/10 shadow-2xl bg-white dark:bg-[#0a0a0b] transition-all duration-700">
                            
                            {{-- Efecto de brillo de cristal que pasa por encima --}}
                            <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-[1500ms] pointer-events-none"></div>

                            {{-- Chrome bar --}}
                            <div class="bg-slate-100/80 dark:bg-slate-800/50 backdrop-blur-sm px-5 py-3 flex items-center gap-4 border-b border-slate-200 dark:border-white/5">
                                <div class="flex gap-2">
                                    <div class="w-3 h-3 rounded-full bg-[#FF5F57]"></div>
                                    <div class="w-3 h-3 rounded-full bg-[#FEBC2E]"></div>
                                    <div class="w-3 h-3 rounded-full bg-[#28C840]"></div>
                                </div>
                                <div class="flex-1 bg-white/50 dark:bg-black/20 rounded-lg px-3 py-1.5 text-[11px] text-slate-400 dark:text-slate-500 flex items-center gap-2 border border-black/5 dark:border-white/5 font-mono">
                                    <svg class="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    orvian.edu.do/app/attendance-v4
                                </div>
                            </div>

                            {{-- App content --}}
                            <div class="flex" style="height: 380px;">

                                {{-- Sidebar Compacto Premium --}}
                                <div class="w-16 bg-[#0a0a0b] flex flex-col items-center pt-6 pb-6 gap-6 flex-shrink-0 border-r border-white/5">
                                    <div class="w-10 h-10 bg-orvian-orange rounded-xl flex items-center justify-center mb-2 shadow-lg shadow-orvian-orange/20 ring-1 ring-white/20">
                                        <span class="font-bold text-lg text-white">O</span>
                                    </div>
                                    @foreach([1,2,3,4] as $i)
                                        <div class="w-8 h-8 rounded-lg {{ $i == 2 ? 'bg-orvian-orange/20 border border-orvian-orange/50' : 'bg-white/5' }} transition-colors hover:bg-white/10"></div>
                                    @endforeach
                                    <div class="mt-auto w-8 h-8 rounded-full bg-gradient-to-tr from-slate-700 to-slate-500 border border-white/10"></div>
                                </div>

                                {{-- Main Content --}}
                                <div class="flex-1 bg-slate-50 dark:bg-[#0f0f10] p-6 overflow-hidden">
                                    {{-- Header Interno --}}
                                    <div class="flex items-end justify-between mb-6">
                                        <div>
                                            <h4 class="text-sm font-black text-slate-900 dark:text-white tracking-tight uppercase">Sentinel Dashboard</h4>
                                            <p class="text-[10px] text-orvian-orange font-bold uppercase tracking-widest">En Vivo • 14 Mayo 2026</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <div class="h-6 w-6 rounded bg-slate-200 dark:bg-white/5"></div>
                                            <div class="h-6 w-16 rounded bg-orvian-orange/10 border border-orvian-orange/20 flex items-center justify-center">
                                                <span class="text-[9px] font-black text-orvian-orange">LIVE</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Stats Grid Mejorado --}}
                                    <div class="grid grid-cols-2 gap-3 mb-6">
                                        <div class="bg-white dark:bg-white/[0.03] rounded-2xl p-4 border border-slate-200 dark:border-white/5 shadow-sm relative overflow-hidden group/card">
                                            <div class="absolute top-0 right-0 p-2 opacity-10">
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-6h2v6zm4 0h-2V7h2v10z"/></svg>
                                            </div>
                                            <p class="text-[9px] text-slate-400 font-bold uppercase mb-1">Ratio Asistencia</p>
                                            <p class="text-2xl font-black text-state-success tracking-tighter">98.4%</p>
                                        </div>
                                        <div class="bg-white dark:bg-white/[0.03] rounded-2xl p-4 border border-slate-200 dark:border-white/5 shadow-sm">
                                            <p class="text-[9px] text-slate-400 font-bold uppercase mb-1">Discrepancias</p>
                                            <p class="text-2xl font-black text-state-error tracking-tighter">02</p>
                                        </div>
                                    </div>

                                    {{-- Lista de Estudiantes con Look Moderno --}}
                                    <div class="space-y-2">
                                        @foreach([['M. García', '08:02 AM', 'success'], ['J. Pérez', '08:05 AM', 'success'], ['A. Torres', 'Pausado', 'warning']] as [$name, $time, $st])
                                        <div class="flex items-center justify-between p-3 bg-white dark:bg-white/[0.02] border border-slate-100 dark:border-white/5 rounded-xl">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-white/10"></div>
                                                <div>
                                                    <p class="text-[11px] font-bold text-slate-700 dark:text-slate-200">{{ $name }}</p>
                                                    <p class="text-[9px] text-slate-400">{{ $time }}</p>
                                                </div>
                                            </div>
                                            <div class="w-2 h-2 rounded-full {{ $st == 'success' ? 'bg-state-success shadow-[0_0_8px_#10b981]' : 'bg-amber-500 shadow-[0_0_8px_#f59e0b]' }}"></div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .perspective-1000 { perspective: 1000px; }
                    .preserve-3d { transform-style: preserve-3d; }
                    .translate-z-10 { transform: translateZ(10px); }
                    .translate-z-20 { transform: translateZ(20px); }
                    .group:hover .group-hover\:translate-z-24 { transform: translateZ(24px) translateY(1rem); }
                    .group:hover .group-hover\:translate-z-32 { transform: translateZ(32px) translateY(-1.5rem); }
                    
                    @keyframes scan {
                        0% { transform: translateY(-100%); }
                        100% { transform: translateY(100%); }
                    }
                    .animate-scan {
                        animation: scan 2s linear infinite;
                    }
                </style>

            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- §3.3 STATS (Minimalistas y Animados)                          --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <section id="stats" class="pt-24 px-4 relative overflow-hidden">
        {{-- Decoración de fondo sutil --}}
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-px bg-gradient-to-r from-transparent via-slate-200 dark:via-white/10 to-transparent"></div>

        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-12 md:gap-8">
                @php
                    $stats = [
                        ['target' => 500,  'suffix' => '+',   'label' => 'Centros Educativos'],
                        ['target' => 120,  'suffix' => 'K+',  'label' => 'Estudiantes Activos'],
                        ['target' => 99,   'suffix' => '.9%', 'label' => 'Uptime Garantizado'],
                        ['target' => 15,   'suffix' => 'min', 'label' => 'Soporte Promedio'],
                    ];
                @endphp

                @foreach($stats as $stat)
                    <div class="flex flex-col items-center text-center group" 
                        x-data="{ 
                            current: 0, 
                            target: {{ $stat['target'] }}, 
                            time: 1500,
                            start() {
                                let start = null;
                                const step = (timestamp) => {
                                    if (!start) start = timestamp;
                                    const progress = Math.min((timestamp - start) / this.time, 1);
                                    this.current = Math.floor(progress * this.target);
                                    if (progress < 1) {
                                        window.requestAnimationFrame(step);
                                    }
                                };
                                window.requestAnimationFrame(step);
                            }
                        }" 
                        x-intersect.once="start()">
                        
                        {{-- Valor Numérico --}}
                        <div class="flex items-baseline justify-center">
                            <span class="text-5xl md:text-6xl font-black tracking-tighter text-slate-900 dark:text-white transition-transform duration-500 group-hover:scale-110" 
                                x-text="current">
                                0
                            </span>
                            <span class="text-2xl md:text-3xl font-bold text-orvian-orange ml-1">
                                {{ $stat['suffix'] }}
                            </span>
                        </div>

                        {{-- Etiqueta --}}
                        <span class="mt-3 text-xs md:text-sm font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                            {{ $stat['label'] }}
                        </span>
                        
                        {{-- Línea decorativa inferior que aparece en hover --}}
                        <div class="mt-4 w-8 h-1 bg-orvian-orange/0 group-hover:w-12 group-hover:bg-orvian-orange/50 transition-all duration-500 rounded-full"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- §3.4 MÓDULOS                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}

    {{--
        ANIMACIÓN:
        - Los tiles y cards usan la clase `.landing-tile-animate` (scope local,
        no interfiere con `.tile-animate` del dashboard).
        - Al cargar la página los elementos están en opacity:0.
        - IntersectionObserver detecta cuándo cada elemento entra al viewport
        y le agrega `.is-visible`, que dispara la animación tile-in.
        - El delay se calcula por índice (i * 60ms) para el efecto cascada.
        - Al cambiar el toggle Alpine resetea los elementos del bloque que
        aparece para que vuelvan a animarse.
    --}}

    <style>
        @keyframes landing-tile-in {
            from { opacity: 0; transform: translateY(16px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1);    }
        }
        .landing-tile-animate {
            opacity: 0;
        }
        .landing-tile-animate.is-visible {
            animation: landing-tile-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <section id="modulos" class="py-24 px-4" x-data="{ withOrvian: true }">
        <div class="max-w-5xl mx-auto">

            {{-- Encabezado --}}
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orvian-orange/70 mb-3">
                Gestión Modular Sin Límites
            </p>
            <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-2">
                Activa solo lo que necesitas
            </h2>
            <p class="text-slate-500 dark:text-slate-400 mb-10 max-w-xl">
                Nuestra arquitectura se adapta al crecimiento de tu institución.
            </p>

            {{-- Toggle Con / Sin ORVIAN --}}
            <div class="flex items-center gap-3 mb-12">
                <button
                    type="button"
                    role="switch"
                    :aria-checked="withOrvian.toString()"
                    @click="
                        withOrvian = !withOrvian;
                        $nextTick(() => initLandingTiles());
                    "
                    :class="withOrvian
                        ? 'bg-orvian-orange shadow-sm shadow-orvian-orange/30'
                        : 'bg-slate-200 dark:bg-slate-700'"
                    class="relative inline-flex h-6 w-11 items-center rounded-full
                        transition-colors duration-200 focus:outline-none
                        focus:ring-2 focus:ring-orvian-orange focus:ring-offset-2
                        dark:focus:ring-offset-[#080e1a]"
                >
                    <span
                        :class="withOrvian ? 'translate-x-6' : 'translate-x-1'"
                        class="inline-block h-4 w-4 transform rounded-full bg-white
                            shadow-sm transition-transform duration-200"
                    ></span>
                </button>

                <span class="text-sm font-semibold transition-colors duration-200"
                    :class="withOrvian
                        ? 'text-orvian-orange'
                        : 'text-slate-400 dark:text-slate-500'">
                    <span x-text="withOrvian ? 'Con ORVIAN' : 'Sin ORVIAN'"></span>
                </span>
            </div>

            {{-- ── Estado: CON ORVIAN ── --}}
            <div x-show="withOrvian"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">

                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-x-4 gap-y-8">

                    {{-- Módulos activos --}}
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="administracion" title="Core"           subtitle="Sistema"   url="#" :active="true" />
                    </div>
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="asistencia"     title="Asistencia"     subtitle="Control"   url="#" :active="true" />
                    </div>
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="academico"      title="Académico"      subtitle="Gestión"   url="#" :active="true" />
                    </div>
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="notas"          title="Calificaciones" subtitle="Notas"     url="#" :active="true" />
                    </div>
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="conversaciones" title="Mensajería"     subtitle="WhatsApp"  url="#" :active="true" />
                    </div>
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="reportes"       title="Reportes"       subtitle="Analítica" url="#" :active="true" />
                    </div>

                    {{-- Módulos próximamente --}}
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="web"       title="Web"       subtitle="Página"        comingSoon="true" />
                    </div>
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="classroom" title="Classroom" subtitle="Virtual"       comingSoon="true" />
                    </div>
                    <div class="landing-tile-animate">
                        <x-ui.app-tile module="horarios"  title="Horarios"  subtitle="Planificación" comingSoon="true" />
                    </div>

                </div>
            </div>

            {{-- ── Estado: SIN ORVIAN ── --}}
            <div x-show="!withOrvian"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                    @php
                        $problemas = [
                            [
                                'icon'  => 'heroicon-o-document-duplicate',
                                'title' => 'Hojas de Excel sin control',
                                'desc'  => 'Listas de asistencia en decenas de archivos sin versión, sin historial, sin responsable claro.',
                            ],
                            [
                                'icon'  => 'heroicon-o-pencil',
                                'title' => 'Registros en papel',
                                'desc'  => 'Calificaciones manuscritas, propensas a errores, pérdidas y prácticamente imposibles de auditar.',
                            ],
                            [
                                'icon'  => 'heroicon-o-clock',
                                'title' => 'Procesos lentos',
                                'desc'  => 'Horas semanales invertidas en tareas administrativas que no agregan ningún valor pedagógico.',
                            ],
                            [
                                'icon'  => 'heroicon-o-exclamation-triangle',
                                'title' => 'Sin trazabilidad',
                                'desc'  => 'Ausencias y tardanzas sin registro sistemático. Ninguna alerta automática a los tutores.',
                            ],
                            [
                                'icon'  => 'heroicon-o-chat-bubble-left-ellipsis',
                                'title' => 'Comunicación dispersa',
                                'desc'  => 'El WhatsApp personal del docente como único canal. Sin historial ni registro institucional.',
                            ],
                            [
                                'icon'  => 'heroicon-o-chart-bar',
                                'title' => 'Decisiones a ciegas',
                                'desc'  => 'El Director no sabe en tiempo real qué ocurre en su plantel. Sin datos, sin reportes.',
                            ],
                        ];
                    @endphp

                    @foreach ($problemas as $problema)
                        <div class="landing-tile-animate flex gap-4 p-5 rounded-2xl
                                    bg-slate-50 dark:bg-white/[0.03]
                                    border border-slate-200 dark:border-white/[0.06]">
                            <div class="flex-shrink-0 mt-0.5">
                                <x-dynamic-component
                                    :component="$problema['icon']"
                                    class="w-5 h-5 text-slate-400 dark:text-slate-600" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 leading-tight mb-1">
                                    {{ $problema['title'] }}
                                </p>
                                <p class="text-sm text-slate-500 dark:text-slate-500 leading-relaxed">
                                    {{ $problema['desc'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- §3.5 ¿POR QUÉ ORVIAN?                                           --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <section id="por-que" class="py-24 px-4 bg-slate-50 dark:bg-white/[0.02]"
        x-data="{ shown: false }"
        x-intersect.once="shown = true">
        
        <div class="max-w-5xl mx-auto" x-show="shown">
            <div class="tile-animate" style="animation-delay: 0.05s;">
                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orvian-orange/70 mb-3">
                    Construido para la realidad dominicana
                </p>
                <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-2">
                    ¿Por qué ORVIAN?
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mb-12 max-w-xl">
                    Diseñamos cada decisión pensando en los centros que más lo necesitan.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- 1 --}}
                <div class="tile-animate rounded-2xl p-6 bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.06]"
                    style="animation-delay: 0.1s;">
                    <div class="w-11 h-11 rounded-xl bg-orvian-orange/10 flex items-center justify-center mb-4">
                        <x-heroicon-o-map-pin class="w-6 h-6 text-orvian-orange" />
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2 leading-tight">
                        Hecho para República Dominicana
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Diseñado para el currículo MINERD, con geografía educativa dominicana integrada desde el núcleo.
                    </p>
                </div>

                {{-- 2 - Sustituto de Multi-tenant --}}
                <div class="tile-animate rounded-2xl p-6 bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.06]"
                    style="animation-delay: 0.15s;">
                    <div class="w-11 h-11 rounded-xl bg-orvian-orange/10 flex items-center justify-center mb-4">
                        <x-heroicon-o-lock-closed class="w-6 h-6 text-orvian-orange" />
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2 leading-tight">
                        Privacidad Blindada
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Cada centro opera en un entorno digital totalmente privado. Sus datos están aislados y nunca se mezclan con los de otras instituciones.
                    </p>
                </div>

                {{-- 3 --}}
                <div class="tile-animate rounded-2xl p-6 bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.06]"
                    style="animation-delay: 0.2s;">
                    <div class="w-11 h-11 rounded-xl bg-orvian-orange/10 flex items-center justify-center mb-4">
                        <x-heroicon-o-device-phone-mobile class="w-6 h-6 text-orvian-orange" />
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2 leading-tight">
                        Sin Dependencia del Celular
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Funciona desde cualquier navegador. No requiere smartphones costosos para alumnos ni docentes para operar.
                    </p>
                </div>

                {{-- 4 --}}
                <div class="tile-animate rounded-2xl p-6 bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.06]"
                    style="animation-delay: 0.25s;">
                    <div class="w-11 h-11 rounded-xl bg-orvian-orange/10 flex items-center justify-center mb-4">
                        <x-heroicon-o-puzzle-piece class="w-6 h-6 text-orvian-orange" />
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2 leading-tight">
                        Modular y Progresivo
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Activa solo los módulos que necesitas hoy. Escala las funcionalidades según el crecimiento de tu institución.
                    </p>
                </div>

                {{-- 5 --}}
                <div class="tile-animate rounded-2xl p-6 bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.06]"
                    style="animation-delay: 0.3s;">
                    <div class="w-11 h-11 rounded-xl bg-orvian-orange/10 flex items-center justify-center mb-4">
                        <x-heroicon-o-face-smile class="w-6 h-6 text-orvian-orange" />
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2 leading-tight">
                        Biometría Opcional
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        El reconocimiento facial nunca es obligatorio. Siempre ofrecemos alternativas físicas o manuales para garantizar la inclusión.
                    </p>
                </div>

                {{-- 6 --}}
                <div class="tile-animate rounded-2xl p-6 bg-white dark:bg-white/[0.03] border border-slate-200 dark:border-white/[0.06]"
                    style="animation-delay: 0.35s;">
                    <div class="w-11 h-11 rounded-xl bg-orvian-orange/10 flex items-center justify-center mb-4">
                        <x-heroicon-o-bolt class="w-6 h-6 text-orvian-orange" />
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2 leading-tight">
                        Información en Tiempo Real
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Dashboards con datos en vivo. La dirección central siempre sabe qué ocurre en el plantel sin esperar reportes manuales.
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- §3.6 PLANES Y PRECIOS                                           --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @php
        $planes = \App\Models\Tenant\Plan::where('is_active', true)
            ->with('features')
            ->orderBy('price', 'asc')
            ->get();
    @endphp

    <section id="precios" class="pt-24 px-4 bg-white dark:bg-dark-bg relative overflow-hidden">
        <div class="max-w-7xl mx-auto relative z-10">
            
            {{-- Títulos --}}
            <div class="text-center mb-16">
                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orvian-orange mb-3">
                    Inversión Inteligente
                </p>
                <h2 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white mb-6">
                    Planes que crecen contigo
                </h2>
                <p class="text-slate-500 dark:text-slate-400 max-w-2xl mx-auto text-lg">
                    Sin costos ocultos. Elige el plan que mejor se adapte al tamaño de tu institución.
                </p>
            </div>

            {{-- Grid Inteligente de Planes --}}
            <div @class([
                'grid gap-8 mb-12 items-center justify-center',
                'grid-cols-1 md:grid-cols-2 lg:grid-cols-2 max-w-4xl mx-auto' => $planes->count() == 2,
                'grid-cols-1 md:grid-cols-2 lg:grid-cols-3' => $planes->count() == 3,
                'grid-cols-1 md:grid-cols-2 lg:grid-cols-4' => $planes->count() >= 4,
                'max-w-sm mx-auto' => $planes->count() == 1,
            ])>
                @foreach($planes as $plan)
                    <x-ui.plan-card :plan="$plan" :showActions="false">
                        <x-ui.button 
                            href="{{ route('register', ['plan' => $plan->slug]) }}"
                            variant="{{ $plan->is_featured ? 'primary' : 'secondary' }}" 
                            :hoverEffect="true"
                            class="w-full rounded-2xl font-black py-4 shadow-lg"
                        >
                            Comenzar ahora
                        </x-ui.button>
                    </x-ui.plan-card>
                @endforeach
            </div>

            {{-- Nueva Sección: Card de Beneficios Estándar (Estilo Glass) --}}
            <div class="max-w-5xl mx-auto">
                <div class="relative group">
                    {{-- Brillo de fondo decorativo --}}
                    <div class="absolute -inset-px bg-gradient-to-r from-orvian-orange/20 to-orvian-blue/20 rounded-[2.5rem] blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
                    
                    <div class="relative bg-slate-50/50 dark:bg-white/[0.02] backdrop-blur-xl border border-slate-200 dark:border-white/[0.08] rounded-[2.5rem] p-8 md:p-10 overflow-hidden">
                        
                        <div class="flex flex-col lg:flex-row items-center gap-10">
                            {{-- Texto Izquierda --}}
                            <div class="lg:w-1/3 text-center lg:text-left">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orvian-orange/10 text-orvian-orange text-[10px] font-black uppercase tracking-widest mb-4">
                                    <x-heroicon-s-star class="w-3 h-3" />
                                    Estándar en ORVIAN
                                </div>
                                <h4 class="text-2xl font-black text-slate-900 dark:text-white mb-3">
                                    Incluido en todos los planes
                                </h4>
                                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Independientemente del plan, tu institución contará con las herramientas base para una gestión moderna y segura.
                                </p>
                            </div>

                            {{-- Grid de Iconos Derecha --}}
                            <div class="lg:w-2/3 grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                                {{-- Feature 1 --}}
                                <div class="flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05] shadow-sm">
                                    <div class="w-10 h-10 rounded-xl bg-orvian-orange/10 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-orvian-orange" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-tight">Centro de Mensajería</p>
                                        <p class="text-[10px] text-slate-400 uppercase font-medium">Comunicación Directa</p>
                                    </div>
                                </div>

                                {{-- Feature 2 --}}
                                <div class="flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05] shadow-sm">
                                    <div class="w-10 h-10 rounded-xl bg-orvian-blue/10 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-shield-check class="w-5 h-5 text-state-info" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-tight">Privacidad Blindada</p>
                                        <p class="text-[10px] text-slate-400 uppercase font-medium">Datos 100% Aislados</p>
                                    </div>
                                </div>

                                {{-- Feature 3 --}}
                                <div class="flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05] shadow-sm">
                                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-arrow-path class="w-5 h-5 text-purple-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-tight">Actualizaciones Web</p>
                                        <p class="text-[10px] text-slate-400 uppercase font-medium">Mejora Continua</p>
                                    </div>
                                </div>

                                {{-- Feature 4 --}}
                                <div class="flex items-center gap-4 p-4 rounded-2xl bg-white dark:bg-white/[0.03] border border-slate-100 dark:border-white/[0.05] shadow-sm">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-key class="w-5 h-5 text-emerald-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 leading-tight">Seguridad SSL</p>
                                        <p class="text-[10px] text-slate-400 uppercase font-medium">Conexión Encriptada</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- §3.7 COMPARATIVA DE EFICIENCIA (ORVIAN VS TRADICIONAL)          --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-24 px-4 bg-white dark:bg-dark-bg">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-4">
                    ¿Por qué evolucionar a ORVIAN?
                </h2>
                <p class="text-slate-500 dark:text-slate-400 max-w-xl mx-auto">
                    Comparamos la gestión administrativa tradicional frente a la agilidad de nuestra plataforma.
                </p>
            </div>

            <div class="relative overflow-hidden rounded-[2.5rem] border border-slate-200 dark:border-white/10 bg-slate-50/50 dark:bg-white/[0.02] backdrop-blur-md">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/50 dark:bg-white/[0.05]">
                                <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Proceso Crítico</th>
                                <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 border-x border-slate-200 dark:border-white/10 text-center">Método Tradicional</th>
                                <th class="p-6 text-[10px] font-black uppercase tracking-[0.2em] text-orvian-orange text-center">Efecto ORVIAN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-white/10">
                            {{-- Fila: Asistencia --}}
                            <tr class="group hover:bg-white dark:hover:bg-white/[0.02] transition-colors">
                                <td class="p-6">
                                    <p class="text-sm font-black text-slate-800 dark:text-white">Control de Asistencia</p>
                                    <p class="text-[11px] text-slate-500 uppercase tracking-tighter">Diario y por Periodo</p>
                                </td>
                                <td class="p-6 border-x border-slate-200 dark:border-white/10">
                                    <div class="flex items-center gap-3 text-slate-400">
                                        <x-heroicon-o-x-circle class="w-5 h-5 text-slate-300" />
                                        <span class="text-xs font-medium italic">Hojas de papel y transcripción manual a fin de mes.</span>
                                    </div>
                                </td>
                                <td class="p-6 bg-orvian-orange/[0.02]">
                                    <div class="flex items-center justify-center gap-3">
                                        <x-ui.badge variant="success" size="sm" class="font-black">TIEMPO REAL</x-ui.badge>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Automatizado con alertas.</span>
                                    </div>
                                </td>
                            </tr>

                            {{-- Fila: Notas --}}
                            <tr class="group hover:bg-white dark:hover:bg-white/[0.02] transition-colors">
                                <td class="p-6">
                                    <p class="text-sm font-black text-slate-800 dark:text-white">Cálculo de Calificaciones</p>
                                    <p class="text-[11px] text-slate-500 uppercase tracking-tighter">Promedios y Literales</p>
                                </td>
                                <td class="p-6 border-x border-slate-200 dark:border-white/10">
                                    <div class="flex items-center gap-3 text-slate-400">
                                        <x-heroicon-o-x-circle class="w-5 h-5 text-slate-300" />
                                        <span class="text-xs font-medium italic">Fórmulas en Excel propensas a errores de digitación.</span>
                                    </div>
                                </td>
                                <td class="p-6 bg-orvian-orange/[0.02]">
                                    <div class="flex items-center justify-center gap-3">
                                        <x-ui.badge variant="success" size="sm" class="font-black">PRECISIÓN</x-ui.badge>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Cálculo instantáneo por ley.</span>
                                    </div>
                                </td>
                            </tr>

                            {{-- Fila: Notificaciones --}}
                            <tr class="group hover:bg-white dark:hover:bg-white/[0.02] transition-colors">
                                <td class="p-6">
                                    <p class="text-sm font-black text-slate-800 dark:text-white">Avisos a Padres</p>
                                    <p class="text-[11px] text-slate-500 uppercase tracking-tighter">Eventos y Urgencias</p>
                                </td>
                                <td class="p-6 border-x border-slate-200 dark:border-white/10">
                                    <div class="flex items-center gap-3 text-slate-400">
                                        <x-heroicon-o-x-circle class="w-5 h-5 text-slate-300" />
                                        <span class="text-xs font-medium italic">Circulares impresas o grupos de WhatsApp caóticos.</span>
                                    </div>
                                </td>
                                <td class="p-6 bg-orvian-orange/[0.02]">
                                    <div class="flex items-center justify-center gap-3">
                                        <x-ui.badge variant="success" size="sm" class="font-black">OMNICANAL</x-ui.badge>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">App, Correo y WhatsApp.</span>
                                    </div>
                                </td>
                            </tr>

                            {{-- Fila: Classroom --}}
                            <tr class="group hover:bg-white dark:hover:bg-white/[0.02] transition-colors border-b-0">
                                <td class="p-6">
                                    <p class="text-sm font-black text-slate-800 dark:text-white">Aula Virtual (Classroom)</p>
                                    <p class="text-[11px] text-slate-500 uppercase tracking-tighter">Recursos y Tareas</p>
                                </td>
                                <td class="p-6 border-x border-slate-200 dark:border-white/10">
                                    <div class="flex items-center gap-3 text-slate-400">
                                        <x-heroicon-o-x-circle class="w-5 h-5 text-slate-300" />
                                        <span class="text-xs font-medium italic">Plataformas externas sin conexión al registro.</span>
                                    </div>
                                </td>
                                <td class="p-6 bg-orvian-orange/[0.02]">
                                    <div class="flex items-center justify-center gap-3">
                                        <x-ui.badge variant="success" size="sm" class="font-black">INTEGRADO</x-ui.badge>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Sincronización total de notas.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <p class="mt-6 text-center text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]">
                ORVIAN reduce el tiempo administrativo en un 65%
            </p>
        </div>
    </section>
    
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- §3.10 PREGUNTAS FRECUENTES (FAQ)                                --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @php
        $faqs = [
            [
                'q' => '¿Es difícil migrar mis datos actuales a ORVIAN?',
                'a' => 'No, nuestro equipo técnico se encarga de la migración inicial. Importamos tus listados de estudiantes, docentes y padres desde Excel o cualquier sistema previo para que puedas empezar a trabajar en menos de 48 horas.'
            ],
            [
                'q' => '¿El sistema funciona sin internet?',
                'a' => 'ORVIAN es una plataforma basada en la nube. Sin embargo, optimizamos la carga de datos para que incluso con conexiones lentas el registro de asistencia y notas sea fluido y eficiente.'
            ],
            [
                'q' => '¿Qué pasa si mi centro educativo crece?',
                'a' => 'Nuestros planes son escalables. Puedes subir de nivel en cualquier momento para aumentar el límite de estudiantes y usuarios de staff sin perder ninguna configuración ni dato histórico.'
            ],
            [
                'q' => '¿Mis datos están seguros y respaldados?',
                'a' => 'Totalmente. Utilizamos encriptación de grado bancario y realizamos copias de seguridad automáticas diarias. Cada institución tiene su base de datos aislada para máxima privacidad.'
            ],
            [
                'q' => '¿Ofrecen capacitación para el personal?',
                'a' => 'Sí. Todos los planes incluyen sesiones de capacitación virtual y acceso ilimitado a nuestro centro de ayuda con tutoriales paso a paso para docentes y administrativos.'
            ],
            [
                'q' => '¿Puedo personalizar los boletines de notas?',
                'a' => '¡Claro! ORVIAN permite ajustar los formatos de reportes y boletines para que se adapten a la identidad visual y requerimientos pedagógicos específicos de tu centro.'
            ],
        ];
    @endphp

    <section id="faq" class="py-24 px-4 bg-slate-50 dark:bg-white/[0.02]">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-4">
                    Preguntas frecuentes
                </h3>
                <p class="text-slate-500 dark:text-slate-400 max-w-xl mx-auto">
                    Todo lo que necesitas saber sobre la transición a ORVIAN.
                </p>
            </div>

            {{-- Grid de FAQ con Alpine.js --}}
            <div x-data="{ active: null }" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($faqs as $index => $faq)
                    <div class="h-fit">
                        <div 
                            @class([
                                'rounded-[2rem] border transition-all duration-300 overflow-hidden',
                                'bg-white dark:bg-dark-card',
                                'border-slate-200 dark:border-white/10' => true,
                            ])
                            :class="active === {{ $index }} ? 'ring-2 ring-orvian-orange/20 border-orvian-orange/50 shadow-xl' : ''"
                        >
                            <button 
                                @click="active !== {{ $index }} ? active = {{ $index }} : active = null"
                                class="w-full flex items-start justify-between p-7 text-left gap-4"
                            >
                                <span class="font-bold text-[15px] text-slate-800 dark:text-slate-200 leading-tight">
                                    {{ $faq['q'] }}
                                </span>
                                <div 
                                    class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center transition-transform duration-500" 
                                    :class="active === {{ $index }} ? 'rotate-180 bg-orvian-orange/10' : ''"
                                >
                                    <x-heroicon-o-chevron-down class="w-3 h-3 text-slate-500" ::class="active === {{ $index }} ? 'text-orvian-orange' : ''" />
                                </div>
                            </button>
                            
                            <div 
                                x-show="active === {{ $index }}" 
                                x-collapse
                                x-cloak
                            >
                                <div class="px-7 pb-7">
                                    <div class="h-px w-full bg-slate-100 dark:bg-white/5 mb-5"></div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed italic">
                                        {{ $faq['a'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- §3.9 FINAL CTA                                                 --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-24 px-4 bg-white dark:bg-dark-bg text-center relative overflow-hidden">
        {{-- Adorno visual --}}
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-px bg-gradient-to-r from-transparent via-slate-200 dark:via-white/10 to-transparent"></div>
        
        <div class="max-w-3xl mx-auto relative z-10">
            <h2 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white mb-8">
                ¿Listo para transformar tu institución?
            </h2>
            <p class="text-slate-500 dark:text-slate-400 text-lg mb-10">
                Únete a los centros que ya están modernizando su gestión con ORVIAN. Hablemos sobre cómo podemos ayudarte.
            </p>
            
                <x-ui.button 
                    href="https://wa.me/18296257463?text=Hola%2C%20me%20interesa%20conocer%20m%C3%A1s%20sobre%20ORVIAN."
                    target="_blank"
                    rel="noopener noreferrer"
                    title="Contactar con el equipo de ORVIAN por WhatsApp"
                    variant="primary" 
                    size="lg" 
                    :hoverEffect="true"
                    class="w-full sm:w-auto px-12 py-5 rounded-2xl font-black shadow-xl shadow-orvian-orange/20"
                >
                    Contactar por WhatsApp
                </x-ui.button>

                
                <x-ui.button 
                    href="#precios"
                    type="outline"
                    variant="secondary" 
                    size="lg" 
                    class="w-full sm:w-auto px-12 py-5 rounded-2xl font-black"
                >
                    Ver Planes
                </x-ui.button>
            </div>

            <p class="mt-8 text-xs font-bold text-slate-400 uppercase tracking-widest">
                Soporte técnico 24/7 incluido en todos los planes
            </p>
        </div>
    </section>

        
    <script>
        (function () {
            var observer;

            function initLandingTiles() {
                // Desconectar el observer anterior si existe
                if (observer) observer.disconnect();

                // Resetear todos los elementos para que vuelvan a animarse
                var els = document.querySelectorAll('#modulos .landing-tile-animate');
                els.forEach(function (el) {
                    el.classList.remove('is-visible');
                    el.style.animationDelay = '';
                });

                observer = new IntersectionObserver(function (entries) {
                    // Solo procesar los que están intersectando en este ciclo
                    var visible = entries.filter(function (e) { return e.isIntersecting; });

                    visible.forEach(function (entry, i) {
                        var el = entry.target;
                        // Delay escalonado: cada elemento del batch recibe un delay acumulativo
                        el.style.animationDelay = (i * 60) + 'ms';
                        el.classList.add('is-visible');
                        // Dejar de observar una vez animado
                        observer.unobserve(el);
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -40px 0px'
                });

                // Observar solo los elementos del bloque actualmente visible
                // (x-show oculta con display:none; filtramos para no observar elementos ocultos)
                els.forEach(function (el) {
                    var xShowParent = el.closest('[x-show]');
                    if (!xShowParent || xShowParent.style.display !== 'none') {
                        observer.observe(el);
                    }
                });
            }

            // Inicializar al cargar el DOM
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initLandingTiles);
            } else {
                initLandingTiles();
            }

            // Exponer globalmente para que Alpine la llame desde el toggle con $nextTick
            window.initLandingTiles = initLandingTiles;
        })();
    </script>


</x-layouts::public>
