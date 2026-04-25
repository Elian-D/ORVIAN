<x-app-layout>
<div class="w-full max-w-4xl mx-auto px-4">

    {{-- Dot pattern --}}
    <div class="fixed inset-0 pointer-events-none dot-pattern opacity-[0.08] text-slate-900 dark:text-white"></div>

    @php
        $hour = now()->hour;
        $greeting = match(true) {
            $hour >= 5  && $hour < 12 => 'Buenos días',
            $hour >= 12 && $hour < 19 => 'Buenas tardes',
            default                   => 'Buenas noches',
        };

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

        // Obtenemos los slugs de las features activas en el plan del tenant
        $activeModules = auth()->user()->school->plan->features->pluck('slug')->toArray() ?? [];
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
    <div class="grid grid-cols-4 sm:grid-cols-5 lg:grid-cols-6 gap-x-4 gap-y-8">

        {{-- Administración - Global (Sin restricción de plan por ser core) --}}
        <div class="tile-animate" style="animation-delay: 0.05s;">
            <x-ui.app-tile
                module="administracion"
                title="Administración"
                subtitle="Sistema"
                url="{{ route('app.school.settings') }}" 
                :active="true" />
        </div>

        {{-- Asistencia - Basado en attendance_qr --}}
        <div class="tile-animate" style="animation-delay: 0.10s;">
            <x-ui.app-tile
                module="asistencia"
                title="Asistencia"
                subtitle="Control"
                url="{{ route('app.attendance.dashboard') }}"
                :active="in_array('attendance_qr', $activeModules)" />
        </div>

        {{-- Conversaciones - Global / Chat --}}
        <div class="tile-animate" style="animation-delay: 0.15s;">
            <x-ui.app-tile
                module="conversaciones"
                title="Conversaciones"
                subtitle="Chat"
                :active="true"
                comingSoon="true" />
        </div>

        {{-- Académico - Basado en academic_grades --}}
        <div class="tile-animate" style="animation-delay: 0.20s;">
            <x-ui.app-tile
                module="academico"
                title="Académico"
                subtitle="Gestión"
                :active="in_array('academic_grades', $activeModules)"
                comingSoon="true" />
        </div>

        {{-- Notas - Dependiente de academic_grades (Mismo permiso) --}}
        <div class="tile-animate" style="animation-delay: 0.25s;">
            <x-ui.app-tile
                module="notas"
                title="Notas"
                subtitle="Calificaciones"
                :active="in_array('academic_grades', $activeModules)"
                comingSoon="true" />
        </div>

        {{-- Classroom - Basado en classroom_internal --}}
        {{-- <div class="tile-animate" style="animation-delay: 0.30s;">
            <x-ui.app-tile
                module="classroom"
                title="Classroom"
                subtitle="Virtual"
                :active="in_array('classroom_internal', $activeModules)"
                comingSoon="true" />
        </div> --}}

        {{-- Horarios - Global --}}
        {{-- <div class="tile-animate" style="animation-delay: 0.35s;">
            <x-ui.app-tile
                module="horarios"
                title="Horarios"
                subtitle="Planificación"
                :active="true"
                comingSoon="true" />
        </div> --}}

        {{-- Reportes - Basado en reports_advanced --}}
        <div class="tile-animate" style="animation-delay: 0.40s;">
            <x-ui.app-tile
                module="reportes"
                title="Reportes"
                subtitle="Analítica"
                :active="in_array('reports_advanced', $activeModules)"
                comingSoon="true" />
        </div>

        {{-- Web - Global --}}
        {{-- <div class="tile-animate" style="animation-delay: 0.45s;">
            <x-ui.app-tile
                module="web"
                title="Web"
                subtitle="Página"
                :active="true"
                comingSoon="true" />
        </div> --}}

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