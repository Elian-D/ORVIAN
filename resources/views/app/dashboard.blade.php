<x-app-layout>
    <div class="w-full max-w-5xl">

        <div class="absolute inset-0 pointer-events-none overflow-hidden dot-pattern opacity-[0.1] text-slate-900 dark:text-white"></div>

        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] rounded-full pointer-events-none"
            style="background: radial-gradient(ellipse, rgba(4,39,95,0.3) 0%, transparent 70%); filter: blur(60px);">
        </div>

        {{-- Encabezado de bienvenida --}}
        <div class="mb-10 md:mb-12">
            <p class="text-xs font-bold uppercase tracking-[0.2em] mb-2"
               :class="darkMode ? 'text-orvian-orange/70' : 'text-orvian-orange'">
                {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
            </p>
            <h1 class="text-3xl md:text-4xl font-black tracking-tight"
                :class="darkMode ? 'text-white' : 'text-orvian-navy'">
                Hola, {{ explode(' ', auth()->user()->name ?? 'Admin')[0] }}.
            </h1>
            <p class="mt-1.5 text-base"
               :class="darkMode ? 'text-slate-500' : 'text-slate-500'">
                ¿Qué vas a gestionar hoy?
            </p>
        </div>

        {{-- Sección: Módulos principales --}}
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-[0.18em]"
                :class="darkMode ? 'text-slate-600' : 'text-slate-400'">
                Módulos
            </h2>
            <div class="h-px flex-1 ml-4" :class="darkMode ? 'bg-white/5' : 'bg-slate-200'"></div>
        </div>

        {{-- Grid con animación en cascada --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">

            <div class="tile-animate" style="animation-delay: 0.04s;">
                <x-ui.app-tile
                    title="Core"
                    subtitle="Sistema"
                    icon="heroicon-o-shield-check"
                    color="bg-orvian-navy"
                    accent="#04275f"
                    url="#" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.08s;">
                <x-ui.app-tile
                    title="Asistencia"
                    subtitle="Control"
                    icon="heroicon-o-qr-code"
                    color="bg-orvian-orange"
                    accent="#f78904"
                    url="#" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.12s;">
                <x-ui.app-tile
                    title="Académico"
                    subtitle="Gestión"
                    icon="heroicon-o-academic-cap"
                    color="bg-blue-600"
                    accent="#2563eb"
                    url="#" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.16s;">
                <x-ui.app-tile
                    title="Notas"
                    subtitle="Calificaciones"
                    icon="heroicon-o-chart-bar"
                    color="bg-emerald-600"
                    accent="#059669"
                    url="#" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.20s;">
                <x-ui.app-tile
                    title="Classroom"
                    subtitle="Virtual"
                    icon="heroicon-o-presentation-chart-line"
                    color="bg-violet-600"
                    accent="#7c3aed"
                    badge="Beta"
                    url="#" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.24s;">
                <x-ui.app-tile
                    title="Horarios"
                    subtitle="Planificación"
                    icon="heroicon-o-calendar-days"
                    color="bg-rose-600"
                    accent="#e11d48"
                    url="#" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.28s;">
                <x-ui.app-tile
                    title="Reportes"
                    subtitle="Analítica"
                    icon="heroicon-o-document-chart-bar"
                    color="bg-teal-600"
                    accent="#0d9488"
                    url="#" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.32s;">
                <x-ui.app-tile
                    title="Configuración"
                    subtitle="Global"
                    icon="heroicon-o-cog-8-tooth"
                    color="bg-slate-600"
                    accent="#475569"
                    url="#" />
            </div>

        </div>

        {{-- Sección: Accesos recientes (placeholder) --}}
        <div class="mt-10 md:mt-12">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-xs font-black uppercase tracking-[0.18em]"
                    :class="darkMode ? 'text-slate-600' : 'text-slate-400'">
                    Accesos recientes
                </h2>
                <div class="h-px flex-1 ml-4" :class="darkMode ? 'bg-white/5' : 'bg-slate-200'"></div>
            </div>

            <div class="rounded-2xl border px-6 py-8 flex items-center justify-center"
                 :class="darkMode
                   ? 'border-white/5 bg-white/2'
                   : 'border-slate-200 bg-white'">
                <p class="text-sm" :class="darkMode ? 'text-slate-600' : 'text-slate-400'">
                    Aquí aparecerán tus últimas secciones visitadas.
                </p>
            </div>
        </div>

    </div>

</x-app-layout>