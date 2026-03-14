{{--
    resources/views/components/app/module-toolbar.blade.php
    --------------------------------------------------------
    Barra secundaria de acciones debajo del navbar.
    Solo se incluye en vistas de módulo — no aparece en el hub.
    Componente anónimo — sin clase PHP.

    SLOTS:
      $actions    — izquierda: botones del módulo (Crear, Exportar, etc.)
      $search     — centro: buscador avanzado o filtro rápido (opcional)
      $secondary  — derecha: acciones secundarias (filtros, columnas, etc.)

    PROPS:
      title       — título/contexto de la vista actual (ej: "Lista de Estudiantes")
      subtitle    — subtítulo o conteo (ej: "247 registros")
      borderless  — bool, elimina el borde inferior (útil sobre tablas con borde propio)

    USO BÁSICO (solo acciones primarias):
        <x-app.module-toolbar title="Estudiantes" subtitle="247 registros">
            <x-slot:actions>
                <x-ui.button variant="primary" size="sm" iconLeft="heroicon-s-plus">
                    Nuevo Estudiante
                </x-ui.button>
            </x-slot:actions>
        </x-app.module-toolbar>

    USO COMPLETO (acciones + buscador + secundarias):
        <x-app.module-toolbar title="Matrículas" subtitle="Año 2025-2026">

            <x-slot:actions>
                <x-ui.button variant="primary" size="sm" iconLeft="heroicon-s-plus">
                    Nueva Matrícula
                </x-ui.button>
                <x-ui.button variant="secondary" type="outline" size="sm" iconLeft="heroicon-s-arrow-up-tray">
                    Importar
                </x-ui.button>
            </x-slot:actions>

            <x-slot:search>
                <x-app.search module="Matrículas" size="md" />
            </x-slot:search>

            <x-slot:secondary>
                <x-ui.button variant="secondary" type="outline" size="sm" icon="heroicon-o-adjustments-horizontal" />
                <x-ui.button variant="secondary" type="outline" size="sm" icon="heroicon-o-squares-2x2" />
                <x-ui.button variant="secondary" type="outline" size="sm" icon="heroicon-s-arrow-down-tray" />
            </x-slot:secondary>

        </x-app.module-toolbar>

    NOTA LIVEWIRE: Este componente vive dentro del $slot del layout,
    es decir dentro de la vista Blade del componente Livewire.
    Es Blade puro — no tiene lógica PHP reactiva propia. Los botones
    dentro de <x-slot:actions> deben tener sus propios wire:click.
--}}

@props([
    'title'      => null,
    'subtitle'   => null,
    'borderless' => false,
])

<div @class([
    'sticky top-[52px] z-40 transition-colors duration-300',
    'bg-white/90 dark:bg-[#0c1220]/90 backdrop-blur-xl',
    'border-b border-slate-200 dark:border-white/5' => !$borderless,
    'border-b border-transparent'                   => $borderless,
])>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-12 flex items-center gap-3">

            {{-- ── Izquierda: título + acciones ── --}}
            <div class="flex items-center gap-2 flex-1 min-w-0">

                {{-- Título y subtítulo (opcionales) --}}
                @if($title)
                    <div class="flex items-baseline gap-2 mr-1 flex-shrink-0">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">
                            {{ $title }}
                        </span>
                        @if($subtitle)
                            <span class="text-[11px] text-slate-400 dark:text-slate-500">
                                {{ $subtitle }}
                            </span>
                        @endif
                    </div>

                    {{-- Divisor entre título y acciones --}}
                    @if(isset($actions) && $actions->isNotEmpty())
                        <div class="h-4 w-px bg-slate-200 dark:bg-white/10 flex-shrink-0"></div>
                    @endif
                @endif

                {{-- Slot de acciones primarias --}}
                @if(isset($actions))
                    <div class="flex items-center gap-1.5 flex-wrap">
                        {{ $actions }}
                    </div>
                @endif

            </div>

            {{-- ── Centro: buscador contextual ── --}}
            @if(isset($search) && $search->isNotEmpty())
                <div class="flex-1 max-w-sm hidden md:block">
                    {{ $search }}
                </div>
            @endif

            {{-- ── Derecha: acciones secundarias ── --}}
            @if(isset($secondary) && $secondary->isNotEmpty())
                <div class="flex items-center gap-1 flex-shrink-0">

                    {{-- Divisor visual --}}
                    @if(isset($actions) && $actions->isNotEmpty())
                        <div class="h-4 w-px bg-slate-200 dark:bg-white/10 mr-1"></div>
                    @endif

                    {{ $secondary }}
                </div>
            @endif

        </div>
    </div>
</div>