{{--
    resources/views/examples/module-icons-demo.blade.php
    ----------------------------------------------------
    Showcase del sistema de iconos de módulos (x-ui.module-icon y x-ui.app-tile).
    Acceder vía: Route::view('/demo/module-icons', 'examples.module-icons-demo');
--}}

<x-app-layout title="Demo — Iconos de Módulos">

<div class="max-w-4xl mx-auto py-12 px-4 space-y-16">

    {{-- ── Encabezado ─────────────────────────────────────────────── --}}
    <div class="space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-widest text-orvian-orange">
            UI Kit · Módulos
        </p>
        <h1 class="text-2xl font-bold text-orvian-navy dark:text-white">
            Sistema de Iconos de Módulos
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Iconos SVG propios de ORVIAN para identificación de módulos.
            Componentes: <code class="text-orvian-orange">x-ui.module-icon</code>
            y <code class="text-orvian-orange">x-ui.app-tile</code>.
        </p>
    </div>

    {{-- 01 · Catálogo de iconos disponibles --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">01 · Catálogo — x-ui.module-icon</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500">
            El componente renderiza solo el <code>&lt;img&gt;</code> — sin fondo ni contenedor.
            El tamaño se controla con clases Tailwind <code>w-*</code> y <code>h-*</code>.
        </p>

        <div class="grid grid-cols-3 sm:grid-cols-5 lg:grid-cols-9 gap-6 bg-slate-50 dark:bg-dark-card p-8 rounded-2xl border border-slate-100 dark:border-dark-border">
            @foreach([
                'administracion' => 'Administración',
                'asistencia'     => 'Asistencia',
                'conversaciones' => 'Conversaciones',
                'academico'      => 'Académico',
                'notas'          => 'Notas',
                'classroom'      => 'Classroom',
                'horarios'       => 'Horarios',
                'reportes'       => 'Reportes',
                'web'            => 'Web',
            ] as $name => $label)
                <div class="flex flex-col items-center gap-2">
                    <x-ui.module-icon :name="$name" class="w-12 h-12" />
                    <span class="text-[9px] font-semibold uppercase tracking-wider text-slate-400 text-center leading-tight">
                        {{ $label }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 02 · Escala de tamaños --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">02 · Escala de tamaños</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <div class="flex flex-wrap items-end justify-center gap-10 bg-slate-50 dark:bg-dark-card p-8 rounded-2xl border border-slate-100 dark:border-dark-border">
            @foreach([
                'w-5 h-5'   => 'w-5 · Navbar',
                'w-8 h-8'   => 'w-8 · Compacto',
                'w-10 h-10' => 'w-10 · App-tile',
                'w-14 h-14' => 'w-14 · Mediano',
                'w-20 h-20' => 'w-20 · Hero',
            ] as $size => $label)
                <div class="flex flex-col items-center gap-3">
                    <x-ui.module-icon name="administracion" :class="$size" />
                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 text-center">
                        {{ $label }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 03 · Icono en distintos contenedores --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">03 · Icono en distintos contenedores</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500">
            El icono es neutro — el contenedor define el contexto visual.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-8 p-8 bg-slate-50 dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border">

            {{-- Sin contenedor (navbar inline) --}}
            <div class="flex flex-col items-center gap-2">
                <x-ui.module-icon name="academico" class="w-6 h-6" />
                <span class="text-[9px] uppercase tracking-wider text-slate-400">Sin contenedor</span>
            </div>

            {{-- Contenedor blanco con borde sutil (app-tile default) --}}
            <div class="flex flex-col items-center gap-2">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center
                            bg-white dark:bg-white/[0.06]
                            border border-slate-200/80 dark:border-white/[0.07] shadow-sm">
                    <x-ui.module-icon name="academico" class="w-9 h-9" />
                </div>
                <span class="text-[9px] uppercase tracking-wider text-slate-400">App-tile</span>
            </div>

            {{-- Contenedor con fondo naranja tenue --}}
            <div class="flex flex-col items-center gap-2">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center
                            bg-orvian-orange/10 border border-orvian-orange/20">
                    <x-ui.module-icon name="academico" class="w-9 h-9" />
                </div>
                <span class="text-[9px] uppercase tracking-wider text-slate-400">Highlight</span>
            </div>

            {{-- Contenedor oscuro (sidebar) --}}
            <div class="flex flex-col items-center gap-2">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center
                            bg-orvian-navy/90 border border-orvian-navy/5">
                    <x-ui.module-icon name="academico" class="w-9 h-9" />
                </div>
                <span class="text-[9px] uppercase tracking-wider text-slate-400">Dark</span>
            </div>

        </div>
    </div>

    {{-- 04 · x-ui.app-tile — estados --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">04 · x-ui.app-tile — estados</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 p-8 bg-slate-50 dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border">

            {{-- Activo con URL --}}
            <div class="flex flex-col items-center gap-1.5">
                <x-ui.app-tile
                    module="administracion"
                    title="Administración"
                    subtitle="Sistema"
                    url="#" />
                <span class="text-[9px] text-slate-400 uppercase tracking-wider">Activo</span>
            </div>

            {{-- Coming soon --}}
            <div class="flex flex-col items-center gap-1.5">
                <x-ui.app-tile
                    module="asistencia"
                    title="Asistencia"
                    subtitle="Control"
                    comingSoon="true" />
                <span class="text-[9px] text-slate-400 uppercase tracking-wider">Coming Soon</span>
            </div>

            {{-- Con badge --}}
            <div class="flex flex-col items-center gap-1.5">
                <x-ui.app-tile
                    module="conversaciones"
                    title="Conversaciones"
                    subtitle="Chat"
                    :badge="7"
                    url="#" />
                <span class="text-[9px] text-slate-400 uppercase tracking-wider">Con Badge</span>
            </div>

            {{-- Badge alto --}}
            <div class="flex flex-col items-center gap-1.5">
                <x-ui.app-tile
                    module="notas"
                    title="Notas"
                    subtitle="Calificaciones"
                    :badge="150"
                    url="#" />
                <span class="text-[9px] text-slate-400 uppercase tracking-wider">Badge 99+</span>
            </div>

        </div>
    </div>

    {{-- 05 · Grid completo estilo dashboard --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">05 · Grid completo — vista real del dashboard</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500">
            Así se ve el grid de módulos en el dashboard. Un módulo activo, el resto con <code>comingSoon</code>.
        </p>

        <div class="p-8 bg-slate-50 dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border">
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                <x-ui.app-tile module="administracion" title="Administración" subtitle="Sistema" url="#" />
                <x-ui.app-tile module="asistencia"     title="Asistencia"     subtitle="Control"       comingSoon="true" />
                <x-ui.app-tile module="conversaciones" title="Conversaciones" subtitle="Chat"           comingSoon="true" />
                <x-ui.app-tile module="academico"      title="Académico"      subtitle="Gestión"        comingSoon="true" />
                <x-ui.app-tile module="notas"          title="Notas"          subtitle="Calificaciones" comingSoon="true" />
                <x-ui.app-tile module="classroom"      title="Classroom"      subtitle="Virtual"        comingSoon="true" />
                <x-ui.app-tile module="horarios"       title="Horarios"       subtitle="Planificación"  comingSoon="true" />
                <x-ui.app-tile module="reportes"       title="Reportes"       subtitle="Analítica"      comingSoon="true" />
                <x-ui.app-tile module="web"            title="Web"            subtitle="Página"         comingSoon="true" />
            </div>
        </div>
    </div>

    {{-- 06 · Fallback con heroicon --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">06 · Fallback con Heroicon</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500">
            Cuando el SVG del módulo aún no existe, se puede usar la prop <code>icon</code>
            con un heroicon como placeholder temporal. No deben coexistir ambas props —
            <code>module</code> tiene prioridad si se pasan las dos.
        </p>

        <div class="flex flex-wrap gap-6 items-end p-8 bg-slate-50 dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border">
            <x-ui.app-tile
                icon="heroicon-o-shield-check"
                color="bg-orvian-navy"
                accent="#002855"
                title="Permisos"
                subtitle="Roles" />

            <x-ui.app-tile
                icon="heroicon-o-chart-pie"
                color="bg-emerald-600"
                accent="#059669"
                title="Analytics"
                subtitle="Datos" />

            <x-ui.app-tile
                icon="heroicon-o-calendar-days"
                color="bg-rose-600"
                accent="#e11d48"
                title="Calendario"
                subtitle="Eventos"
                comingSoon="true" />
        </div>
    </div>

    {{-- Footer --}}
    <div class="pt-8 text-center border-t border-slate-100 dark:border-dark-border">
        <p class="text-[10px] text-slate-400 uppercase tracking-widest">
            ORVIAN Design System · v2026.1
        </p>
    </div>

</div>

</x-app-layout>