@props(['href', 'icon' => null, 'active' => false])

<a href="{{ $href }}" 
   @if($active) x-init="$dispatch('dropdown-activate')" @endif
   class="flex items-center gap-3 px-3 py-1.5 rounded-lg text-sm transition-all duration-200 group
          {{ $active 
            ? 'text-orvian-orange font-medium bg-orvian-orange/5' 
            : 'text-gray-500 dark:text-gray-400 hover:text-orvian-blue dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5' 
          }}">
    
    @if($icon)
        <x-dynamic-component :component="$icon" 
            class="w-4 h-4 flex-shrink-0 transition-colors duration-200 
                   {{ $active ? 'text-orvian-orange' : 'text-gray-400 dark:text-gray-500 group-hover:text-orvian-blue dark:group-hover:text-gray-300' }}" />
    @else
        <div class="w-1.5 h-1.5 rounded-full transition-all duration-200 
                    {{ $active ? 'bg-orvian-orange shadow-sm' : 'bg-gray-300 dark:bg-gray-600 group-hover:bg-orvian-blue dark:group-hover:bg-gray-400' }}">
        </div>
    @endif

    <span x-show="sidebarOpen || hasHover" class="tracking-wide">
        {{ $slot }}
    </span>
</a>