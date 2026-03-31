{{-- resources/views/components/ui/app-tile.blade.php --}}
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
    'active'     => true,
])

@php
    $isDisabled = $comingSoon || !$active;
    $finalUrl = $isDisabled ? '#' : $url;
@endphp

<a
    href="{{ $finalUrl }}"
    {{ $attributes->merge(['class' => 'group relative flex flex-col items-center gap-2 focus:outline-none'
        . ($isDisabled ? ' cursor-not-allowed' : '')
    ]) }}
>
    <div class="relative flex-shrink-0">
        {{-- Contenedor del ícono --}}
        <div @class([
            'w-[72px] h-[72px] rounded-2xl flex items-center justify-center transition-all duration-300 border shadow-sm',
            'bg-white dark:bg-white/[0.06] border-slate-200/80 dark:border-white/[0.07]' => $active && !$comingSoon,
            'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-white/5' => !$active || $comingSoon,
            'group-hover:shadow-md group-hover:-translate-y-0.5 group-hover:border-slate-300 dark:group-hover:border-white/15' => !$isDisabled,
        ])>
            
            <div @class([
                'transition-all duration-300', 
                'grayscale opacity-40' => !$active,
                'opacity-30' => $comingSoon
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

            {{-- Overlay de Bloqueo por Plan --}}
            @if(!$active && !$comingSoon)
                <div class="absolute inset-0 flex items-center justify-center bg-slate-900/10 dark:bg-black/20 rounded-2xl backdrop-blur-[1px]">
                    <x-heroicon-s-lock-closed class="w-5 h-5 text-slate-500/80 dark:text-slate-400" />
                </div>
            @endif
        </div>

        {{-- Badge de notificaciones --}}
        @if($badge && !$isDisabled)
            <div class="absolute -top-2 -right-2">
                <x-ui.badge variant="primary" size="sm" :dot="false">
                    {{ $badge > 99 ? '99+' : $badge }}
                </x-ui.badge>
            </div>
        @endif

        {{-- Badge de Estado --}}
        @if($comingSoon)
            <div class="absolute -top-2 -right-2">
                <x-ui.badge variant="slate" size="sm" :dot="false" class="text-[8px] px-1.5 font-bold">PRONTO</x-ui.badge>
            </div>
        @elseif(!$active)
            <div class="absolute -top-2 -right-2">
                <x-ui.badge variant="error" size="sm" :dot="false" class="text-[8px] px-1.5 uppercase font-black">PLAN</x-ui.badge>
            </div>
        @endif
    </div>

    {{-- Texto --}}
    <div class="flex flex-col items-center">
        <span @class([
            'text-[13px] font-semibold text-center leading-tight transition-colors duration-150',
            'text-slate-700 dark:text-slate-200 group-hover:text-orvian-navy dark:group-hover:text-white' => !$isDisabled,
            'text-slate-400 dark:text-slate-600' => $isDisabled,
        ])>
            {{ $title }}
        </span>

        @if($subtitle)
            <span class="text-[9px] uppercase tracking-[0.12em] font-medium mt-0.5 text-slate-400 dark:text-slate-600">
                {{ $subtitle }}
            </span>
        @endif
    </div>
</a>