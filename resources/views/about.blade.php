{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- about.blade.php                                                      --}}
{{-- Página Institucional "Sobre Nosotros" — ORVIAN                       --}}
{{-- Rama: feature/landing-about                                          --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<x-layouts::public>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- CSS LOCAL: Blueprint pattern + tipografía huge            --}}
{{-- Scope local con prefijo "about-" para no contaminar       --}}
{{-- otros layouts del sistema.                                --}}
{{-- ══════════════════════════════════════════════════════════ --}}
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
    .about-text-huge {
        font-size: clamp(3rem, 9vw, 7.5rem);
        line-height: 0.92;
        letter-spacing: -0.03em;
    }
    .about-timeline-line {
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 1px;
        background: linear-gradient(to bottom, transparent, rgba(148,163,184,0.3) 10%, rgba(148,163,184,0.3) 90%, transparent);
        transform: translateX(-50%);
    }
</style>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- §4.1 HERO EDITORIAL                                        --}}
{{-- Blueprint pattern + composición asimétrica                --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<section class="relative pt-36 pb-32 px-4 sm:px-6 about-blueprint overflow-hidden
                bg-white dark:bg-dark-bg">

    {{-- Número decorativo de fondo --}}
    <div class="absolute -top-10 -left-8 text-[18rem] font-black select-none pointer-events-none
                text-orvian-navy/[0.03] dark:text-white/[0.02] font-etna leading-none"
         aria-hidden="true">01</div>

    <div class="relative max-w-6xl mx-auto">

        {{-- Composición de dos columnas: título + párrafo --}}
        <div class="flex flex-col lg:flex-row gap-12 lg:gap-16 items-start">

            {{-- Columna izquierda: eyebrow + headline enorme --}}
            <div class="lg:w-3/5">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-orvian-orange mb-8">
                    Nuestra Misión &amp; Esencia
                </p>

                <h1 class="about-text-huge font-black text-orvian-navy dark:text-white">
                    Forjando el Futuro<br>
                    <span class="text-orvian-orange">Educativo.</span>
                </h1>
            </div>

            {{-- Columna derecha: párrafo con acento lateral --}}
            <div class="lg:w-2/5 lg:pt-36">
                <div class="border-l-4 border-orvian-navy dark:border-orvian-orange pl-7 py-2">
                    <p class="text-slate-600 dark:text-slate-300 text-lg leading-relaxed">
                        En ORVIAN, no solo implementamos software; diseñamos ecosistemas de precisión.
                        Nuestra misión es dotar a las instituciones dominicanas de herramientas que
                        trasciendan la administración para convertirse en motores de excelencia académica
                        y transparencia operativa.
                    </p>
                </div>
            </div>
        </div>

        {{-- ── Composición asimétrica con imagen + caja flotante ── --}}
        <div class="mt-20 grid grid-cols-12 gap-6 items-end">

            {{-- Imagen panorámica del equipo --}}
            <div class="col-span-12 lg:col-span-8">
                <img
                    src="{{ asset('img/team/team-banner.png') }}"
                    alt="Equipo ORVIAN trabajando"
                    class="w-full aspect-[21/9] object-cover rounded-[2rem] shadow-2xl
                           ring-1 ring-slate-200/50 dark:ring-white/10"
                    {{-- Placeholder de color si la imagen no existe todavía --}}
                    onerror="this.style.background='linear-gradient(135deg,#0f1729,#1e3a5f)';this.removeAttribute('src')"
                />
            </div>

            {{-- Caja flotante "Ingeniería de Impacto" (orvian-navy) --}}
            <div class="hidden lg:flex lg:col-span-4 flex-col
                        bg-orvian-navy dark:bg-slate-800
                        p-10 rounded-[2rem] text-white shadow-2xl
                        -mb-12 relative z-10
                        ring-1 ring-white/10">

                <div class="w-12 h-12 rounded-xl bg-orvian-orange/20 flex items-center justify-center mb-6">
                    <x-heroicon-o-cpu-chip class="w-7 h-7 text-orvian-orange" />
                </div>

                <h3 class="text-2xl font-black text-white mb-3 leading-tight">
                    Ingeniería de Impacto
                </h3>
                <p class="text-slate-300 leading-relaxed text-sm">
                    Cada línea de código está pensada para el contexto único de la República Dominicana,
                    asegurando que la tecnología sea un puente, no una barrera.
                </p>
            </div>
        </div>

    </div>
</section>


{{-- ══════════════════════════════════════════════════════════ --}}
{{-- §4.2 ESTRUCTURA DE VALOR (Timeline vertical)              --}}
{{-- Misión → Visión → Valores en flujo alternado              --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<section class="py-32 px-4 sm:px-6 relative overflow-hidden
                bg-slate-50 dark:bg-white/[0.02]">

    <div class="max-w-4xl mx-auto">

        {{-- Encabezado de sección --}}
        <div class="text-center mb-24">
            <h2 class="text-3xl sm:text-4xl font-black text-orvian-navy dark:text-white
                       italic uppercase tracking-widest mb-4">
                Estructura de Valor
            </h2>
            <div class="w-20 h-1 bg-orvian-orange mx-auto rounded-full"></div>
        </div>

        {{-- Timeline container --}}
        <div class="relative">

            {{-- Línea vertical central (solo desktop) --}}
            <div class="about-timeline-line hidden md:block" aria-hidden="true"></div>

            {{-- ── Item 1: MISIÓN ── --}}
            <div class="relative mb-28 group">
                <div class="md:flex items-center justify-between">

                    {{-- Número decorativo izquierda (solo desktop) --}}
                    <div class="md:w-[45%] text-right hidden md:block pr-12">
                        <span class="text-5xl font-black text-orvian-navy/10 dark:text-white/10
                                     group-hover:text-orvian-navy/20 dark:group-hover:text-white/20
                                     transition-colors duration-300 select-none font-etna">01</span>
                    </div>

                    {{-- Punto de la línea del tiempo --}}
                    <div class="absolute left-1/2 -translate-x-1/2 w-4 h-4 rounded-full
                                bg-orvian-navy dark:bg-orvian-orange
                                border-4 border-slate-50 dark:border-[#080e1a]
                                z-10 hidden md:block shadow-sm"
                         aria-hidden="true"></div>

                    {{-- Card de contenido --}}
                    <div class="md:w-[45%] bg-white dark:bg-white/[0.04]
                                p-8 rounded-3xl
                                border border-slate-200 dark:border-white/[0.07]
                                shadow-sm hover:shadow-xl hover:-translate-y-1
                                transition-all duration-300">

                        <div class="flex items-center gap-4 mb-5">
                            <div class="w-10 h-10 rounded-full bg-orvian-orange flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-academic-cap class="w-5 h-5 text-white" />
                            </div>
                            <h3 class="text-xl font-black text-orvian-navy dark:text-white uppercase tracking-tighter">
                                Misión
                            </h3>
                        </div>

                        <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                            Modernizar los procesos académicos y administrativos de los centros educativos
                            públicos dominicanos mediante tecnología accesible, segura y construida
                            para su realidad.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Item 2: VISIÓN ── --}}
            <div class="relative mb-28 group">
                <div class="md:flex items-center justify-between flex-row-reverse">

                    {{-- Número decorativo derecha (solo desktop) --}}
                    <div class="md:w-[45%] text-left hidden md:block pl-12">
                        <span class="text-5xl font-black text-orvian-navy/10 dark:text-white/10
                                     group-hover:text-orvian-navy/20 dark:group-hover:text-white/20
                                     transition-colors duration-300 select-none font-etna">02</span>
                    </div>

                    {{-- Punto --}}
                    <div class="absolute left-1/2 -translate-x-1/2 w-4 h-4 rounded-full
                                bg-orvian-navy dark:bg-orvian-orange
                                border-4 border-slate-50 dark:border-[#080e1a]
                                z-10 hidden md:block shadow-sm"
                         aria-hidden="true"></div>

                    {{-- Card --}}
                    <div class="md:w-[45%] bg-white dark:bg-white/[0.04]
                                p-8 rounded-3xl
                                border border-slate-200 dark:border-white/[0.07]
                                shadow-sm hover:shadow-xl hover:-translate-y-1
                                transition-all duration-300">

                        <div class="flex items-center gap-4 mb-5">
                            <div class="w-10 h-10 rounded-full bg-orvian-navy dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-eye class="w-5 h-5 text-white" />
                            </div>
                            <h3 class="text-xl font-black text-orvian-navy dark:text-white uppercase tracking-tighter">
                                Visión
                            </h3>
                        </div>

                        <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                            Ser la plataforma de referencia en gestión educativa de República Dominicana,
                            reconocida por reducir la carga administrativa de los docentes y mejorar
                            la trazabilidad del aprendizaje.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Item 3: VALORES ── --}}
            <div class="relative group">
                <div class="md:flex items-center justify-between">

                    {{-- Número --}}
                    <div class="md:w-[45%] text-right hidden md:block pr-12">
                        <span class="text-5xl font-black text-orvian-navy/10 dark:text-white/10
                                     group-hover:text-orvian-navy/20 dark:group-hover:text-white/20
                                     transition-colors duration-300 select-none font-etna">03</span>
                    </div>

                    {{-- Punto --}}
                    <div class="absolute left-1/2 -translate-x-1/2 w-4 h-4 rounded-full
                                bg-orvian-orange
                                border-4 border-slate-50 dark:border-[#080e1a]
                                z-10 hidden md:block shadow-sm"
                         aria-hidden="true"></div>

                    {{-- Card --}}
                    <div class="md:w-[45%] bg-white dark:bg-white/[0.04]
                                p-8 rounded-3xl
                                border border-slate-200 dark:border-white/[0.07]
                                shadow-sm hover:shadow-xl hover:-translate-y-1
                                transition-all duration-300">

                        <div class="flex items-center gap-4 mb-5">
                            <div class="w-10 h-10 rounded-full bg-orvian-orange/20 border border-orvian-orange/30 flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-shield-check class="w-5 h-5 text-orvian-orange" />
                            </div>
                            <h3 class="text-xl font-black text-orvian-navy dark:text-white uppercase tracking-tighter">
                                Valores
                            </h3>
                        </div>

                        {{-- Badges de valores --}}
                        <div class="flex flex-wrap gap-2">
                            @foreach(['Innovación', 'Integridad', 'Compromiso', 'Excelencia', 'Colaboración'] as $valor)
                                <span class="px-3.5 py-1.5 rounded-full text-xs font-bold
                                             bg-slate-100 dark:bg-white/[0.06]
                                             text-slate-600 dark:text-slate-400
                                             border border-slate-200 dark:border-white/[0.08]">
                                    {{ $valor }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>


{{-- ══════════════════════════════════════════════════════════ --}}
{{-- §4.3 EQUIPO: "Arquitectos de la Revolución Digital"       --}}
{{-- Líder destacado (4/5) + grid circular del resto           --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<section class="py-32 px-4 sm:px-6 overflow-hidden
                bg-white dark:bg-dark-bg">

    <div class="max-w-6xl mx-auto">

        {{-- Encabezado de sección --}}
        <div class="flex flex-col lg:flex-row items-start lg:items-end justify-between mb-20 gap-8">
            <div class="max-w-xl">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-orvian-orange mb-4">
                    Capital Humano
                </p>
                <h2 class="text-4xl sm:text-5xl font-black text-orvian-navy dark:text-white leading-tight">
                    Arquitectos de la<br>
                    Revolución Digital.
                </h2>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-lg max-w-sm italic leading-relaxed">
                "Mentes apasionadas trabajando juntas para redefinir el estándar de la educación dominicana."
            </p>
        </div>

        {{-- Grid principal: líder (5 cols) + equipo (7 cols) --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 items-start">

            {{-- ── LÍDER: Elian David ── --}}
            <div class="md:col-span-5 group relative">
                {{-- Foto 4/5 con hover overlay --}}
                <div class="aspect-[4/5] overflow-hidden rounded-[2.5rem] bg-slate-100 dark:bg-slate-800
                            transition-transform duration-500 group-hover:scale-[0.98] relative">

                    <img
                        src="{{ asset('img/team/elian-david.png') }}"
                        alt="Elian David — Fundador y Director General de ORVIAN"
                        class="w-full h-full object-cover"
                        onerror="this.onerror=null;this.src='{{ asset('img/team/placeholder.svg') }}'"
                    />

                    {{-- Overlay con cita al hacer hover --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-orvian-navy/80 via-transparent to-transparent
                                opacity-0 group-hover:opacity-100 transition-opacity duration-500
                                flex flex-col justify-end p-10">
                        <p class="text-white italic text-lg leading-relaxed">
                            "Transformando el futuro de la educación."
                        </p>
                    </div>
                </div>

                {{-- Nombre y cargo del líder --}}
                <div class="mt-6">
                    <h3 class="text-2xl sm:text-3xl font-black text-orvian-navy dark:text-white leading-tight">
                        Elian David
                    </h3>
                    <p class="text-orvian-orange font-bold text-sm tracking-widest uppercase mt-1">
                        Fundador / Director General
                    </p>
                </div>
            </div>

            {{-- ── RESTO DEL EQUIPO (grid 2x2 + centrado) ── --}}
            <div class="md:col-span-7">
                @php
                    $equipo = [
                        [
                            'nombre'    => 'Kimberly Marte',
                            'cargo'     => 'Gerente Administrativa',
                            'slug'      => 'kimberly-marte',
                            'pt'        => 'md:pt-12',   // offset visual como en el diseño
                        ],
                        [
                            'nombre'    => 'Meredyth Ferreira',
                            'cargo'     => 'Coordinadora de Relaciones Institucionales',
                            'slug'      => 'meredyth-ferreira',
                            'pt'        => '',
                        ],
                        [
                            'nombre'    => 'Jhostin Morales',
                            'cargo'     => 'Líder de Tecnología e Infraestructura',
                            'slug'      => 'jhostin-morales',
                            'pt'        => '',
                        ],
                        [
                            'nombre'    => 'Justin Francisco',
                            'cargo'     => 'Desarrollador de Software',
                            'slug'      => 'justin-francisco',
                            'pt'        => 'md:pt-12',
                        ],
                    ];
                @endphp

                {{-- Grid 2×2 para los primeros 4 miembros --}}
                <div class="grid grid-cols-2 gap-6 sm:gap-8 mb-8">
                    @foreach($equipo as $miembro)
                        <div class="group {{ $miembro['pt'] }}">

                            {{-- Foto circular --}}
                            <div class="aspect-square overflow-hidden rounded-full mb-5
                                        border-2 border-slate-200 dark:border-white/[0.08] p-1.5
                                        group-hover:border-orvian-orange transition-colors duration-300">
                                <img
                                    src="{{ asset('img/team/' . $miembro['slug'] . '.jpg') }}"
                                    alt="{{ $miembro['nombre'] }} — {{ $miembro['cargo'] }}"
                                    class="w-full h-full object-cover rounded-full"
                                    onerror="this.onerror=null;this.src='{{ asset('img/team/placeholder.svg') }}'"
                                />
                            </div>

                            <h4 class="font-black text-base sm:text-lg text-orvian-navy dark:text-white leading-tight">
                                {{ $miembro['nombre'] }}
                            </h4>
                            <p class="text-slate-400 dark:text-slate-500 font-semibold text-xs tracking-wider uppercase mt-1">
                                {{ $miembro['cargo'] }}
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Jeremía Meléndez: centrado debajo del grid --}}
                <div class="flex flex-col items-center group mt-4">
                    <div class="w-36 h-36 sm:w-44 sm:h-44 overflow-hidden rounded-full mb-5
                                border-2 border-slate-200 dark:border-white/[0.08] p-1.5
                                group-hover:border-orvian-orange transition-colors duration-300">
                        <img
                            src="{{ asset('img/team/jeremias-melendez.jpg') }}"
                            alt="Jeremías Meléndez — Diseño y Marketing"
                            class="w-full h-full object-cover rounded-full"
                            onerror="this.onerror=null;this.src='{{ asset('img/team/placeholder.svg') }}'"
                        />
                    </div>
                    <h4 class="font-black text-base sm:text-lg text-orvian-navy dark:text-white text-center leading-tight">
                        Jeremías Meléndez
                    </h4>
                    <p class="text-slate-400 dark:text-slate-500 font-semibold text-xs tracking-wider uppercase mt-1 text-center">
                        Diseño y Marketing
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>


{{-- ══════════════════════════════════════════════════════════ --}}
{{-- §4.4 VALORES (grid de cards con Heroicons)                --}}
{{-- Fondo alternado respecto al equipo                        --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<section class="py-24 px-4 sm:px-6 bg-slate-50 dark:bg-white/[0.02]">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-14">
            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-orvian-orange/70 mb-3">
                Lo que nos guía
            </p>
            <h2 class="text-3xl font-black text-orvian-navy dark:text-white">
                Nuestros Valores
            </h2>
        </div>

        @php
            $valores = [
                [
                    'icon'  => 'heroicon-o-heart',
                    'title' => 'Compromiso con la educación pública',
                    'desc'  => 'Construimos ORVIAN pensando primero en las escuelas que más lo necesitan.',
                ],
                [
                    'icon'  => 'heroicon-o-shield-check',
                    'title' => 'Privacidad por diseño',
                    'desc'  => 'Los datos biométricos nunca salen del sistema. La confianza de los centros es nuestra responsabilidad.',
                ],
                [
                    'icon'  => 'heroicon-o-light-bulb',
                    'title' => 'Tecnología accesible',
                    'desc'  => 'Un sistema de grado empresarial no debería requerir un presupuesto empresarial.',
                ],
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($valores as $valor)
                <div class="flex flex-col gap-4 p-7 rounded-2xl
                            bg-white dark:bg-white/[0.03]
                            border border-slate-200 dark:border-white/[0.06]
                            hover:shadow-lg hover:-translate-y-0.5
                            transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-orvian-orange/10 flex items-center justify-center flex-shrink-0">
                        <x-dynamic-component :component="$valor['icon']" class="w-6 h-6 text-orvian-orange" />
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base leading-tight">
                        {{ $valor['title'] }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        {{ $valor['desc'] }}
                    </p>
                </div>
            @endforeach
        </div>

    </div>
</section>


{{-- ══════════════════════════════════════════════════════════ --}}
{{-- §4.5 CTA FINAL                                            --}}
{{-- Gradiente institucional orvian-navy + botón WhatsApp      --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<section class="py-20 px-4 sm:px-6 relative overflow-hidden bg-white dark:bg-dark-bg">

    {{-- Adorno Blueprint decorativo esquina inferior derecha --}}
    <div class="absolute right-0 bottom-0 w-72 h-72 opacity-[0.06] pointer-events-none" aria-hidden="true">
        <svg viewBox="0 0 100 100" class="w-full h-full text-orvian-navy dark:text-white">
            <rect x="10" y="10" width="80" height="80" fill="none" stroke="currentColor" stroke-width="0.5"/>
            <line x1="10" y1="10" x2="90" y2="90" stroke="currentColor" stroke-width="0.5"/>
            <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="0.5"/>
        </svg>
    </div>

    <div class="max-w-5xl mx-auto">
        {{-- Card con gradiente institucional --}}
        <div class="relative bg-orvian-navy dark:bg-slate-800 rounded-[3rem] px-8 py-20 sm:px-16
                    text-center text-white overflow-hidden shadow-2xl">

            {{-- Acento diagonal decorativo --}}
            <div class="absolute top-0 right-0 w-[40%] h-full bg-white/[0.04]
                        skew-x-12 translate-x-20 pointer-events-none" aria-hidden="true"></div>

            {{-- Acento de color naranja sutil en la parte superior --}}
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-1 bg-orvian-orange rounded-b-full" aria-hidden="true"></div>

            <div class="relative z-10 max-w-2xl mx-auto">

                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white italic mb-6 leading-tight">
                    ¿Listo para sistematizar el éxito de tu institución?
                </h2>

                <p class="text-slate-300 text-lg mb-10 max-w-xl mx-auto font-light leading-relaxed">
                    Únete a la nueva era de la gestión educativa en la República Dominicana.
                </p>

                <x-ui.button
                    href="https://wa.me/18296257463?text=Solicito+una+auditor%C3%ADa+del+sistema"
                    target="_blank"
                    rel="noopener noreferrer"
                    title="Solicitar una auditoría de ORVIAN por WhatsApp"
                    variant="primary"
                    size="lg"
                    :hoverEffect="true"
                    class="px-12 py-5 rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-orvian-orange/30"
                >
                    Reservar Auditoría
                </x-ui.button>
            </div>
        </div>
    </div>
</section>

</x-layouts::public>