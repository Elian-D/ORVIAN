{{--
    resources/views/components/ui/app-tile.blade.php
    ------------------------------------------------
    Tile de módulo estilo Odoo: solo el ícono cuadrado + título debajo.
    Sin card envolvente. La <a> es transparente, el hover va en el ícono.

    PROPS:
      icon       — heroicon (fallback)
      module     — nombre del SVG en public/assets/icons/modules/
      title      — nombre del módulo
      subtitle   — texto secundario bajo el título (opcional)
      color      — clase bg-* para el fondo del contenedor cuando es heroicon
      accent     — color hex para box-shadow del ícono heroicon
      url        — ruta de destino
      badge      — número de notificaciones (opcional)
      comingSoon — deshabilita navegación y aplica opacidad
--}}

@props([
    'icon'       => null,
    'module'     => null,
    'title',
    'subtitle'   => null,
    'color'      => 'bg-orvian-navy',
    'accent'     => null,
    'url'        => '#',
    'badge'      => null,
    'comingSoon' => false,
])

<a
    href="{{ $comingSoon ? '#' : $url }}"
    {{ $attributes->merge(['class' => 'group relative flex flex-col items-center gap-2 focus:outline-none'
        . ($comingSoon ? ' opacity-50 cursor-not-allowed pointer-events-none' : '')
    ]) }}
>
    {{-- Cuadrado del ícono --}}
    <div class="relative flex-shrink-0">
        <div @class([
            'w-[72px] h-[72px] rounded-2xl flex items-center justify-center
             transition-all duration-200
             border border-slate-200/80 dark:border-white/[0.07]
             bg-white dark:bg-white/[0.06]
             shadow-sm',
            'group-hover:shadow-md group-hover:-translate-y-0.5
             group-hover:border-slate-300 dark:group-hover:border-white/15' => !$comingSoon,
        ])>
            @if($module)
                <x-ui.module-icon :name="$module" class="w-10 h-10" />
            @elseif($icon)
                <div @class(['w-10 h-10 rounded-xl flex items-center justify-center', $color])
                     @if($accent) style="box-shadow: 0 4px 12px {{ $accent }}40;" @endif>
                    <x-dynamic-component :component="$icon" class="w-6 h-6 text-white" />
                </div>
            @endif
        </div>

        {{-- Badge de notificaciones --}}
        @if($badge && !$comingSoon)
            <div class="absolute -top-2 -right-2">
                <x-ui.badge variant="primary" size="sm" :dot="false">
                    {{ $badge > 99 ? '99+' : $badge }}
                </x-ui.badge>
            </div>
        @endif

        {{-- Badge "Pronto" --}}
        @if($comingSoon)
            <div class="absolute -top-2 -right-2">
                <x-ui.badge variant="slate" size="sm" :dot="false">Pronto</x-ui.badge>
            </div>
        @endif
    </div>

    {{-- Texto --}}
    <div class="flex flex-col items-center">
        <span class="text-[13px] font-semibold text-center leading-tight
                     text-slate-700 dark:text-slate-200
                     group-hover:text-orvian-navy dark:group-hover:text-white
                     transition-colors duration-150">
            {{ $title }}
        </span>

        @if($subtitle)
            <span class="text-[9px] uppercase tracking-[0.12em] font-medium mt-0.5
                         text-slate-400 dark:text-slate-600">
                {{ $subtitle }}
            </span>
        @endif
    </div>
</a>