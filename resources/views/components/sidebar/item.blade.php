@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" 
    {{ $attributes->merge([
        'class' => 'group flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 relative ' . 
        ($active 
            ? 'bg-orvian-orange/10 text-orvian-orange shadow-sm' 
            : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-orvian-blue dark:hover:text-white')
    ]) }}>
    
    @if($active)
        <div class="absolute left-0 w-1 h-5 bg-orvian-orange rounded-r-full"></div>
    @endif

    <x-dynamic-component :component="$icon" 
        class="w-5 h-5 flex-shrink-0 transition-colors duration-200
               {{ $active ? 'text-orvian-orange' : 'text-gray-400 dark:text-gray-500 group-hover:text-orvian-orange' }}" />

    <span x-show="sidebarOpen || hasHover" 
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0 translate-x-2"
          x-transition:enter-end="opacity-100 translate-x-0"
          class="text-sm font-medium whitespace-nowrap overflow-hidden tracking-wide">
        {{ $slot }}
    </span>

    {{-- Tooltip para estado colapsado --}}
    <div x-show="!sidebarOpen && !hasHover" 
         class="absolute left-full ml-4 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50 shadow-xl border border-white/10">
        {{ $slot }}
    </div>
</a>