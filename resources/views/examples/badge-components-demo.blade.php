{{-- 
    resources/views/examples/badge-components-demo.blade.php
    -------------------------------------------------------
    Showcase exhaustivo del componente x-ui.badge.
--}}

<x-app-layout title="Demo — Badges (x-ui.badge)">

<div class="max-w-4xl mx-auto py-12 px-4 space-y-16">

    {{-- ── Encabezado ─────────────────────────────────────────────── --}}
    <div class="space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-widest text-orvian-orange">
            UI Kit · System
        </p>
        <h1 class="text-2xl font-bold text-orvian-navy dark:text-white">
            Componente Badge
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Etiquetas informativas para estados, categorías y conteos rápidos. Ahora con soporte para colores hexadecimales personalizados.
        </p>
    </div>

    {{-- 01 · Variantes de Color (Con Punto) --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">01 · Variantes del Sistema</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="flex flex-wrap gap-4 items-center justify-center bg-slate-50/50 dark:bg-dark-card p-8 rounded-2xl border border-slate-100 dark:border-dark-border">
            <x-ui.badge variant="primary">Primary</x-ui.badge>
            <x-ui.badge variant="success">Success</x-ui.badge>
            <x-ui.badge variant="warning">Warning</x-ui.badge>
            <x-ui.badge variant="error">Error</x-ui.badge>
            <x-ui.badge variant="info">Info</x-ui.badge>
            <x-ui.badge variant="slate">Slate</x-ui.badge>
        </div>
    </div>

    {{-- 02 · Tamaños --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">02 · Escala de Tamaños</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="flex flex-wrap gap-12 items-center justify-center">
            <div class="text-center space-y-3">
                <x-ui.badge size="sm" variant="primary">Tamaño SM</x-ui.badge>
                <p class="text-[10px] text-slate-400 uppercase tracking-tighter">text-[9px] · py-0.5</p>
            </div>
            <div class="text-center space-y-3">
                <x-ui.badge size="md" variant="primary">Tamaño MD</x-ui.badge>
                <p class="text-[10px] text-slate-400 uppercase tracking-tighter">text-xs · py-1.5 (Default)</p>
            </div>
        </div>
    </div>

    {{-- 03 · Sin Punto Indicador --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">03 · Estilo Plano (Sin Punto)</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="flex flex-wrap gap-4 items-center justify-center">
            <x-ui.badge :dot="false" variant="primary">Plan Pro</x-ui.badge>
            <x-ui.badge :dot="false" variant="success">Finalizado</x-ui.badge>
            <x-ui.badge :dot="false" variant="slate">Borrador</x-ui.badge>
            <x-ui.badge :dot="false" variant="info" size="sm">v1.0.4</x-ui.badge>
        </div>
    </div>

    {{-- 04 · Colores Hexadecimales Personalizados --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">04 · Colores Hexadecimales (Nuevos)</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="bg-white dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border p-8 space-y-6">
            <div class="text-sm text-slate-600 dark:text-slate-400">
                <p class="mb-4">El prop <code class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-xs font-mono">hex</code> permite asignar cualquier color hexadecimal manteniendo la estética del sistema (fondo semitransparente + texto sólido).</p>
            </div>

            {{-- Ejemplos predefinidos --}}
            <div class="flex flex-wrap gap-4 items-center justify-center p-6 bg-slate-50/50 dark:bg-slate-900/20 rounded-xl">
                <x-ui.badge hex="#9333EA">Coordinador Regional</x-ui.badge>
                <x-ui.badge hex="#EC4899">Marketing</x-ui.badge>
                <x-ui.badge hex="#F59E0B">Premium</x-ui.badge>
                <x-ui.badge hex="#8B5CF6" :dot="false">Exclusivo</x-ui.badge>
                <x-ui.badge hex="#10B981" size="sm">Disponible</x-ui.badge>
                <x-ui.badge hex="#6366F1" size="sm" :dot="false">Beta</x-ui.badge>
            </div>
        </div>
    </div>

    {{-- 05 · Casos de Uso Reales --}}
    <div class="space-y-8">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">05 · Aplicación en Contexto</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Ejemplo: Estado de Estudiantes --}}
            <div class="p-6 bg-white dark:bg-dark-card rounded-xl border border-slate-100 dark:border-dark-border space-y-4">
                <h4 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">Estado de Inscripción</h4>
                <div class="divide-y divide-slate-50 dark:divide-dark-border">
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Juan Pérez</span>
                        <x-ui.badge variant="success" size="sm">Inscrito</x-ui.badge>
                    </div>
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-slate-400">María García</span>
                        <x-ui.badge variant="warning" size="sm">Pendiente Pago</x-ui.badge>
                    </div>
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Carlos Ruíz</span>
                        <x-ui.badge variant="error" size="sm">Retirado</x-ui.badge>
                    </div>
                </div>
            </div>

            {{-- Ejemplo: Roles Personalizados --}}
            <div class="p-6 bg-white dark:bg-dark-card rounded-xl border border-slate-100 dark:border-dark-border space-y-4">
                <h4 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">Roles Personalizados</h4>
                <div class="divide-y divide-slate-50 dark:divide-dark-border">
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Ana Martínez</span>
                        <x-ui.badge hex="#4F46E5" size="sm">Director/a</x-ui.badge>
                    </div>
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Luis Rodríguez</span>
                        <x-ui.badge hex="#9333EA" size="sm">Coordinador</x-ui.badge>
                    </div>
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Carmen López</span>
                        <x-ui.badge hex="#0EA5E9" size="sm">Docente</x-ui.badge>
                    </div>
                    <div class="py-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Pedro Sánchez</span>
                        <x-ui.badge hex="#EC4899" size="sm" :dot="false">Orientador</x-ui.badge>
                    </div>
                </div>
            </div>

            {{-- Ejemplo: Configuración de Escuela --}}
            <div class="p-6 bg-white dark:bg-dark-card rounded-xl border border-slate-100 dark:border-dark-border space-y-4">
                <h4 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">Sistema SIGERD</h4>
                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Sincronización Global</span>
                            <x-ui.badge variant="info" :dot="false" size="sm">Recomendado</x-ui.badge>
                        </div>
                        <p class="text-xs text-slate-500">Estado actual del enlace con los servidores centrales.</p>
                        <div class="pt-1">
                            <x-ui.badge variant="primary">Conectado</x-ui.badge>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ejemplo: Categorías de Productos --}}
            <div class="p-6 bg-white dark:bg-dark-card rounded-xl border border-slate-100 dark:border-dark-border space-y-4">
                <h4 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">Categorías Dinámicas</h4>
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge hex="#F59E0B" :dot="false" size="sm">Premium</x-ui.badge>
                    <x-ui.badge hex="#8B5CF6" :dot="false" size="sm">Exclusivo</x-ui.badge>
                    <x-ui.badge hex="#10B981" :dot="false" size="sm">Disponible</x-ui.badge>
                    <x-ui.badge hex="#EF4444" :dot="false" size="sm">Agotado</x-ui.badge>
                    <x-ui.badge hex="#6366F1" :dot="false" size="sm">Nuevo</x-ui.badge>
                    <x-ui.badge hex="#14B8A6" :dot="false" size="sm">Descuento</x-ui.badge>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Info --}}
    <div class="pt-8 text-center border-t border-slate-100 dark:border-dark-border">
        <p class="text-[10px] text-slate-400 uppercase tracking-widest">
            ORVIAN Design System · v2026.1
        </p>
    </div>

</div>
</x-app-layout>