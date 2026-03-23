<x-app-layout>
<div class="w-full max-w-4xl">

    {{-- Dot pattern --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden dot-pattern opacity-[0.08] text-slate-900 dark:text-white"></div>

    {{-- ══════ ENCABEZADO ══════ --}}
    @php
        $hour = now()->hour;

        // Saludo según hora
        $greeting = match(true) {
            $hour >= 5  && $hour < 12 => 'Buenos días',
            $hour >= 12 && $hour < 19 => 'Buenas tardes',
            default                   => 'Buenas noches',
        };

        // Pregunta/subtítulo según hora
        $subtitle = match(true) {
            $hour >= 5  && $hour < 9  => '¿Listo para comenzar el día?',
            $hour >= 9  && $hour < 12 => '¿Qué vas a gestionar hoy?',
            $hour >= 12 && $hour < 14 => 'Un buen momento para revisar el avance.',
            $hour >= 14 && $hour < 17 => '¿En qué trabajamos esta tarde?',
            $hour >= 17 && $hour < 19 => 'Últimas horas del día — ¿algo pendiente?',
            $hour >= 19 && $hour < 22 => 'Terminando la jornada, ¿todo en orden?',
            default                   => 'Trabajando tarde. Aquí estamos.',
        };

        $firstName = explode(' ', auth()->user()->name ?? 'Usuario')[0];
    @endphp

    <div class="relative z-10 mb-12">
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] mb-3 text-orvian-orange/60">
            {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM') }}
        </p>

        <h1 class="text-3xl md:text-4xl font-black tracking-tight text-orvian-navy dark:text-white leading-tight">
            {{ $greeting }}, {{ $firstName }}.
        </h1>

        <p class="mt-2 text-[15px] text-slate-400 dark:text-slate-500">
            {{ $subtitle }}
        </p>
    </div>

    {{-- ══════ MÓDULOS ══════ --}}
         <div class="grid grid-cols-4 sm:grid-cols-5 lg:grid-cols-6 gap-x-4 gap-y-6"">

            <div class="tile-animate" style="animation-delay: 0.04s;">
                <x-ui.app-tile
                    module="administracion"
                    title="Administración"
                    subtitle="Sistema"
                    url="{{ route('app.users.index') }}" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.08s;">
                <x-ui.app-tile
                    module="asistencia"
                    title="Asistencia"
                    subtitle="Control"
                    comingSoon="true"
                     />
            </div>

            <div class="tile-animate" style="animation-delay: 0.08s;">
                <x-ui.app-tile
                    module="conversaciones"
                    title="Conversaciones"
                    subtitle="Chat"
                    comingSoon="true" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.12s;">
                <x-ui.app-tile
                    module="academico"
                    title="Académico"
                    subtitle="Gestión"
                    comingSoon="true" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.16s;">
                <x-ui.app-tile
                    module="notas"
                    title="Notas"
                    subtitle="Calificaciones"
                    comingSoon="true" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.20s;">
                <x-ui.app-tile
                    module="classroom"
                    title="Classroom"
                    subtitle="Virtual"
                    comingSoon="true" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.24s;">
                <x-ui.app-tile
                    module="horarios"
                    title="Horarios"
                    subtitle="Planificación"
                    comingSoon="true" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.28s;">
                <x-ui.app-tile
                    module="reportes"
                    title="Reportes"
                    subtitle="Analítica"
                    comingSoon="true" />
            </div>

            <div class="tile-animate" style="animation-delay: 0.32s;">
                <x-ui.app-tile
                    module="web"
                    title="Web"
                    subtitle="Página"
                    comingSoon="true" />
            </div>

        </div>

    {{-- ══════ ACCESOS RECIENTES ══════ --}}
    <div class="relative z-10 mt-14">
        <div class="mb-4 flex items-center gap-3">
            <h2 class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400 dark:text-slate-600 flex-shrink-0">
                Accesos recientes
            </h2>
            <div class="h-px flex-1 bg-slate-200 dark:bg-white/5"></div>
        </div>

        <div class="rounded-2xl border px-6 py-8 flex flex-col items-center justify-center gap-2
                    border-slate-200/60 dark:border-white/[0.05]
                    bg-white/60 dark:bg-white/[0.02]">
            <x-heroicon-o-clock class="w-5 h-5 text-slate-300 dark:text-slate-700" />
            <p class="text-sm text-slate-400 dark:text-slate-600">
                Aquí aparecerán tus últimas secciones visitadas.
            </p>
        </div>
    </div>

</div>
</x-app-layout>