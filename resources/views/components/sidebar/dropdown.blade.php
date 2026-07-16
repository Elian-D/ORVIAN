@props(['id', 'icon' => null, 'moduleIcon' => null, 'label', 'activeRoutes' => []])

@php
    $isActive = false;
    foreach($isActiveRoutes ?? $activeRoutes as $route) {
        if(request()->routeIs($route)) { $isActive = true; break; }
    }
@endphp

{{--
    Acordeón: el estado "abierto" vive en $openDropdown, compartido por
    todos los dropdowns del sidebar (definido en components/sidebar/layout.blade.php
    a nivel de <nav>) — abrir uno cierra el que estuviera abierto antes.
--}}
<div x-init="if ({{ $isActive ? 'true' : 'false' }}) openDropdown = '{{ $id }}'" class="w-full">

    <button @click="if (sidebarOpen || hasHover) openDropdown = (openDropdown === '{{ $id }}' ? null : '{{ $id }}')"
            class="flex items-center justify-between w-full px-4 py-2.5 rounded-xl transition-all duration-200 group relative
            {{ $isActive
                ? 'bg-orvian-orange/5 text-orvian-orange shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-orvian-blue dark:hover:text-white'
            }}">

        @if($isActive)
            <div class="absolute left-0 w-1 h-5 bg-orvian-orange/50 rounded-r-full"></div>
        @endif

        <div class="flex items-center gap-3">
            @if($moduleIcon)
                <x-ui.module-icon :name="$moduleIcon" class="w-5 h-5 flex-shrink-0" />
            @else
                <x-dynamic-component :component="$icon"
                    class="w-5 h-5 flex-shrink-0 transition-colors duration-200
                           {{ $isActive ? 'text-orvian-orange' : 'text-gray-400 dark:text-gray-500 group-hover:text-orvian-orange' }}" />
            @endif

            <span x-show="sidebarOpen || hasHover" class="text-sm font-medium whitespace-nowrap tracking-wide">
                {{ $label }}
            </span>
        </div>

        <x-heroicon-s-chevron-right x-show="sidebarOpen || hasHover"
            class="w-4 h-4 transition-transform duration-200 {{ $isActive ? 'text-orvian-orange' : 'text-gray-400' }}"
            ::class="{ 'rotate-90': openDropdown === '{{ $id }}' }" />
    </button>

    <div x-show="openDropdown === '{{ $id }}' && (sidebarOpen || hasHover)"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mt-1 ml-4 pl-6 border-l border-gray-200 dark:border-white/10 space-y-1">
        {{ $slot }}
    </div>
</div>
