{{--
    resources/views/examples/button-components-demo.blade.php
    -------------------------------------------------------
    Showcase exhaustivo del componente x-ui.button.
    Acceder vía: Route::view('/demo/buttons', 'examples.button-components-demo');
--}}

<x-app-layout title="Demo — Botones (x-ui.button)">

<div class="max-w-4xl mx-auto py-12 px-4 space-y-16">

    {{-- ── Encabezado ─────────────────────────────────────────────── --}}
    <div class="space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-widest text-orvian-orange">
            UI Kit · System
        </p>
        <h1 class="text-2xl font-bold text-orvian-navy dark:text-white">
            Componente Button
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Lógica centralizada para acciones, variantes de estado y modos de icono.
        </p>
    </div>

    {{-- 01 · Variantes Sólidas (Default) --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">01 · Variantes Sólidas</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="flex flex-wrap gap-4 items-center justify-center">
            <x-ui.button variant="primary">Primary</x-ui.button>
            <x-ui.button variant="secondary">Secondary</x-ui.button>
            <x-ui.button variant="success">Success</x-ui.button>
            <x-ui.button variant="warning">Warning</x-ui.button>
            <x-ui.button variant="error">Error</x-ui.button>
            <x-ui.button variant="info">Info</x-ui.button>
        </div>
    </div>

    {{-- 02 · Variantes Outline --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">02 · Estilo Outline</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="flex flex-wrap gap-4 items-center justify-center">
            <x-ui.button variant="primary" type="outline">Primary</x-ui.button>
            <x-ui.button variant="secondary" type="outline">Secondary</x-ui.button>
            <x-ui.button variant="success" type="outline">Success</x-ui.button>
            <x-ui.button variant="warning" type="outline">Warning</x-ui.button>
            <x-ui.button variant="error" type="outline">Error</x-ui.button>
            <x-ui.button variant="info" type="outline">Info</x-ui.button>
        </div>
    </div>

    {{-- 03 · Tamaños --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">03 · Escala de Tamaños</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="flex flex-wrap gap-6 items-end justify-center">
            <div class="text-center space-y-2">
                <x-ui.button size="sm">Small</x-ui.button>
                <p class="text-[10px] text-slate-400 uppercase">Size SM</p>
            </div>
            <div class="text-center space-y-2">
                <x-ui.button size="md">Medium</x-ui.button>
                <p class="text-[10px] text-slate-400 uppercase">Size MD (Default)</p>
            </div>
            <div class="text-center space-y-2">
                <x-ui.button size="lg">Large</x-ui.button>
                <p class="text-[10px] text-slate-400 uppercase">Size LG</p>
            </div>
            <div class="text-center space-y-2">
                <x-ui.button size="xl">Extra Large</x-ui.button>
                <p class="text-[10px] text-slate-400 uppercase">Size XL</p>
            </div>
        </div>
    </div>

    {{-- 04 · Iconos y Modos --}}
    <div class="space-y-8">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">04 · Iconos y Modos</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            {{-- Con Texto --}}
            <div class="space-y-4">
                <p class="text-xs font-semibold text-slate-500 mb-4">Con Texto (Rectangulares)</p>
                <div class="flex flex-wrap gap-4">
                    <x-ui.button icon-left="heroicon-s-plus">Nuevo</x-ui.button>
                    <x-ui.button variant="secondary" icon-right="heroicon-s-arrow-right">Continuar</x-ui.button>
                    <x-ui.button variant="error" type="outline" icon-left="heroicon-s-trash" size="sm">Eliminar</x-ui.button>
                </div>
            </div>

            {{-- Solo Icono --}}
            <div class="space-y-4">
                <p class="text-xs font-semibold text-slate-500 mb-4">Solo Icono (Automático Cuadrado)</p>
                <div class="flex flex-wrap gap-4 items-center">
                    <x-ui.button icon="heroicon-s-pencil" size="sm" />
                    <x-ui.button variant="secondary" icon="heroicon-s-cog-6-tooth" />
                    <x-ui.button variant="primary" type="outline" icon="heroicon-s-magnifying-glass" size="lg" />
                    <x-ui.button variant="error" icon="heroicon-s-x-mark" size="xl" />
                </div>
            </div>
        </div>
    </div>

    {{-- 05 · Estados Especiales --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">05 · Estados y Efectos</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
            <div class="space-y-4">
                <p class="text-xs font-semibold text-slate-500">Micro-interacción & Deshabilitado</p>
                <div class="flex gap-4">
                    <x-ui.button variant="primary" :hover-effect="true">Con Hover Effect</x-ui.button>
                    <x-ui.button variant="primary" :disabled="true">Deshabilitado</x-ui.button>
                </div>
            </div>

            <div class="space-y-4">
                <p class="text-xs font-semibold text-slate-500">Full Width</p>
                <x-ui.button variant="secondary" :full-width="true">Botón Ancho Completo</x-ui.button>
            </div>
        </div>
    </div>

    {{-- 06 · Variante Link --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">06 · Variante Link</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="flex flex-wrap gap-12 justify-center items-center">
            <div class="text-center space-y-2">
                <x-ui.button variant="link">Standard Link</x-ui.button>
                <p class="text-[10px] text-slate-400 uppercase">Solid (Plain)</p>
            </div>
            <div class="text-center space-y-2">
                <x-ui.button variant="link" type="outline">Underlined Link</x-ui.button>
                <p class="text-[10px] text-slate-400 uppercase">Outline (Underline)</p>
            </div>
            <x-ui.button variant="link" icon-left="heroicon-s-chevron-left" size="sm">Volver al listado</x-ui.button>
        </div>
    </div>

</div>

</x-app-layout>