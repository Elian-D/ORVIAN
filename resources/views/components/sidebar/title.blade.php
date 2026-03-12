<h3 x-show="sidebarOpen || hasHover"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-2"
    x-transition:enter-end="opacity-100 translate-x-0"
    class="mt-6 mb-2 px-4 text-[10px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-gray-400">
    {{ $slot }}
</h3>
<div x-show="!sidebarOpen && !hasHover" class="h-px bg-white/5 my-4 mx-4"></div>