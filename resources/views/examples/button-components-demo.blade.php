<x-app-layout title="Demo — Botones (x-ui.button)">

<div class="max-w-4xl mx-auto py-12 px-4 space-y-16">

    {{-- Encabezado --}}
    <div class="space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-widest text-orvian-orange">UI Kit · System</p>
        <h1 class="text-2xl font-bold text-orvian-navy dark:text-white">Componente Button</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Polimórfico: renderiza <code class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">&lt;button&gt;</code> o
            <code class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">&lt;a&gt;</code> según contexto.
            Soporta colores hexadecimales con contraste automático y estado de carga nativo.
        </p>
    </div>

    @php $sep = 'flex items-center gap-4 mb-6'; $line = 'h-px flex-1 bg-slate-100 dark:bg-dark-border'; $label = 'text-[11px] font-bold uppercase tracking-wider text-slate-400 whitespace-nowrap'; @endphp

    {{-- 01 · Variantes Sólidas --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">01 · Variantes Sólidas</span><div class="{{ $line }}"></div></div>
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
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">02 · Estilo Outline</span><div class="{{ $line }}"></div></div>
        <div class="flex flex-wrap gap-4 items-center justify-center">
            <x-ui.button variant="primary"   type="outline">Primary</x-ui.button>
            <x-ui.button variant="secondary" type="outline">Secondary</x-ui.button>
            <x-ui.button variant="success"   type="outline">Success</x-ui.button>
            <x-ui.button variant="warning"   type="outline">Warning</x-ui.button>
            <x-ui.button variant="error"     type="outline">Error</x-ui.button>
            <x-ui.button variant="info"      type="outline">Info</x-ui.button>
        </div>
    </div>

    {{-- 03 · Variante Ghost (nuevo) --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">03 · Estilo Ghost (Toolbar)</span><div class="{{ $line }}"></div></div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Sin borde ni fondo base — ideal para barras de herramientas y acciones secundarias discretas.</p>
        <div class="flex flex-wrap gap-4 items-center justify-center p-6 rounded-xl bg-slate-50 dark:bg-dark-card border border-slate-100 dark:border-dark-border">
            <x-ui.button variant="primary"   type="ghost" iconLeft="heroicon-o-plus">Agregar</x-ui.button>
            <x-ui.button variant="secondary" type="ghost" iconLeft="heroicon-o-pencil-square">Editar</x-ui.button>
            <x-ui.button variant="error"     type="ghost" iconLeft="heroicon-o-trash" size="sm">Eliminar</x-ui.button>
            <x-ui.button variant="info"      type="ghost" icon="heroicon-o-eye" size="sm" aria-label="Ver" />
            <x-ui.button variant="secondary" type="ghost" icon="heroicon-o-ellipsis-vertical" aria-label="Más opciones" />
        </div>
    </div>

    {{-- 04 · Tamaños --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">04 · Escala de Tamaños</span><div class="{{ $line }}"></div></div>
        <div class="flex flex-wrap gap-6 items-end justify-center">
            @foreach(['sm' => 'SM', 'md' => 'MD (Default)', 'lg' => 'LG', 'xl' => 'XL'] as $s => $l)
                <div class="text-center space-y-2">
                    <x-ui.button size="{{ $s }}">{{ $l }}</x-ui.button>
                    <p class="text-[10px] text-slate-400 uppercase">{{ $l }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 05 · Iconos y Modo Solo Icono --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">05 · Iconos y Modo Solo Icono</span><div class="{{ $line }}"></div></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-4">Con texto</p>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button iconLeft="heroicon-s-plus">Nuevo</x-ui.button>
                    <x-ui.button variant="secondary" iconRight="heroicon-s-arrow-right">Continuar</x-ui.button>
                    <x-ui.button variant="error" type="outline" iconLeft="heroicon-s-trash" size="sm">Eliminar</x-ui.button>
                </div>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-4">Solo icono (cuadrado automático)</p>
                <div class="flex flex-wrap gap-3 items-center">
                    <x-ui.button icon="heroicon-s-pencil" size="sm" aria-label="Editar" />
                    <x-ui.button variant="secondary" icon="heroicon-s-cog-6-tooth" aria-label="Configurar" />
                    <x-ui.button variant="primary" type="outline" icon="heroicon-s-magnifying-glass" size="lg" aria-label="Buscar" />
                    <x-ui.button variant="error" icon="heroicon-s-x-mark" size="xl" aria-label="Cerrar" />
                    <x-ui.button variant="secondary" type="ghost" icon="heroicon-o-ellipsis-vertical" aria-label="Más opciones" />
                </div>
            </div>
        </div>
    </div>

    {{-- 06 · Colores Hexadecimales con Contraste Automático (nuevo) --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">06 · Hex + Contraste Automático</span><div class="{{ $line }}"></div></div>
        <div class="space-y-6">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                El prop <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">hex</code> aplica el color via
                <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">style</code> inline.
                En modo <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">solid</code>,
                el texto cambia automáticamente entre blanco y oscuro según luminancia (YIQ).
            </p>

            <div>
                <p class="text-xs font-semibold text-slate-500 mb-3">Solid — contraste automático (prueba con colores claros y oscuros)</p>
                <div class="flex flex-wrap gap-3 items-center">
                    <x-ui.button hex="#7C3AED">Director/a</x-ui.button>
                    <x-ui.button hex="#0EA5E9">Docente</x-ui.button>
                    <x-ui.button hex="#F59E0B">Coordinador</x-ui.button>
                    <x-ui.button hex="#F0FDF4" iconRight="heroicon-s-check">{{-- claro → texto oscuro --}}Aprobado</x-ui.button>
                    <x-ui.button hex="#0F172A">Admin</x-ui.button>
                    <x-ui.button hex="#EC4899" icon="heroicon-s-star" size="sm" aria-label="Favorito" />
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold text-slate-500 mb-3">Outline hex</p>
                <div class="flex flex-wrap gap-3 items-center">
                    <x-ui.button hex="#7C3AED" type="outline">Director/a</x-ui.button>
                    <x-ui.button hex="#0EA5E9" type="outline">Docente</x-ui.button>
                    <x-ui.button hex="#EC4899" type="outline" size="sm">Marketing</x-ui.button>
                    <x-ui.button hex="#10B981" type="outline" iconLeft="heroicon-s-check">Disponible</x-ui.button>
                </div>
            </div>
        </div>
    </div>

    {{-- 07 · Tag Dinámico: botones como enlaces (nuevo) --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">07 · Tag Dinámico (href → &lt;a&gt;)</span><div class="{{ $line }}"></div></div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            Al pasar <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">href</code>
            el componente renderiza un <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">&lt;a&gt;</code>
            con exactamente el mismo aspecto visual que un <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">&lt;button&gt;</code>.
        </p>
        <div class="flex flex-wrap gap-4 items-center">
            <x-ui.button href="#" variant="primary">Ir al Dashboard</x-ui.button>
            <x-ui.button href="#" variant="secondary" type="outline" iconRight="heroicon-s-arrow-right">Ver documentación</x-ui.button>
            <x-ui.button href="#" variant="link">Enlace inline</x-ui.button>
            <x-ui.button href="#" variant="info" icon="heroicon-o-arrow-top-right-on-square" size="sm" aria-label="Abrir enlace externo" />
        </div>
    </div>

    {{-- 08 · wire:loading (nuevo) --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">08 · Estado de Carga (wire:loading)</span><div class="{{ $line }}"></div></div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">wire:loading.class</code> y
            <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-xs">wire:loading.attr="disabled"</code>
            están integrados de forma nativa. El botón se atenúa y bloquea durante cualquier petición Livewire
            que tenga como target la acción del botón.
        </p>

        <div class="p-6 bg-slate-50 dark:bg-dark-card rounded-xl border border-slate-100 dark:border-dark-border space-y-4">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Patrón recomendado en vistas Livewire</p>
            <pre class="bg-slate-900 text-slate-100 rounded-xl p-4 text-[11px] font-mono overflow-x-auto leading-relaxed">{{-- Texto cambia mientras carga, botón se bloquea --}}
&lt;x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save"&gt;
    &lt;span wire:loading.remove wire:target="save"&gt;Guardar cambios&lt;/span&gt;
    &lt;span wire:loading wire:target="save"&gt;Guardando...&lt;/span&gt;
&lt;/x-ui.button&gt;

{{-- Icono exclusivo — aria-label obligatorio --}}
&lt;x-ui.button variant="error" icon="heroicon-s-trash"
    wire:click="delete({{ '$id' }})"
    wire:loading.attr="disabled"
    wire:target="delete({{ '$id' }})"
    aria-label="Eliminar registro" /&gt;</pre>

            <p class="text-xs text-slate-400">
                El componente agrega automáticamente
                <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded text-[11px]">wire:loading.class="opacity-60 pointer-events-none"</code>
                a todos los botones. No es necesario declararlo manualmente.
            </p>
        </div>
    </div>

    {{-- 09 · Estados especiales --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">09 · Estados y Efectos</span><div class="{{ $line }}"></div></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-3">
                <p class="text-xs font-semibold text-slate-500">Micro-interacción + Deshabilitado</p>
                <div class="flex gap-4">
                    <x-ui.button variant="primary" :hoverEffect="true">Con Hover Effect</x-ui.button>
                    <x-ui.button variant="primary" :disabled="true">Deshabilitado</x-ui.button>
                </div>
            </div>
            <div class="space-y-3">
                <p class="text-xs font-semibold text-slate-500">Full Width</p>
                <x-ui.button variant="secondary" :fullWidth="true">Botón Ancho Completo</x-ui.button>
            </div>
        </div>
    </div>

    {{-- 10 · Variante Link --}}
    <div>
        <div class="{{ $sep }}"><div class="{{ $line }}"></div><span class="{{ $label }}">10 · Variante Link</span><div class="{{ $line }}"></div></div>
        <div class="flex flex-wrap gap-12 justify-center items-center">
            <div class="text-center space-y-2">
                <x-ui.button variant="link">Standard Link</x-ui.button>
                <p class="text-[10px] text-slate-400 uppercase">Solid (Plain)</p>
            </div>
            <div class="text-center space-y-2">
                <x-ui.button variant="link" type="outline">Underlined Link</x-ui.button>
                <p class="text-[10px] text-slate-400 uppercase">Outline</p>
            </div>
            <x-ui.button variant="link" iconLeft="heroicon-s-chevron-left" size="sm">Volver al listado</x-ui.button>
        </div>
    </div>

    {{-- Footer --}}
    <div class="pt-8 text-center border-t border-slate-100 dark:border-dark-border">
        <p class="text-[10px] text-slate-400 uppercase tracking-widest">ORVIAN Design System · Button v2</p>
    </div>

</div>
</x-app-layout>