<nav class="h-12 bg-white dark:bg-dark-card border-b border-gray-100 dark:border-white/5 flex items-center justify-between px-4 md:px-8 z-30 transition-colors duration-300">
    
    <div class="flex items-center gap-4 w-1/4">
        <button @click="sidebarOpen = !sidebarOpen" 
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 transition">
            <x-heroicon-o-bars-3-bottom-left x-show="sidebarOpen" class="w-6 h-6" />
            <x-heroicon-o-bars-3 x-show="!sidebarOpen" class="w-6 h-6" />
        </button>

        <h1 class="hidden xl:block text-sm font-bold text-gray-800 dark:text-gray-200 tracking-tight">
            {{ $title ?? 'PANEL ADMINISTRATIVO' }}
        </h1>
    </div>

    <div class="flex-1 max-w-2xl hidden md:block">
        <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <x-heroicon-s-magnifying-glass class="w-4 h-4 text-gray-400 group-focus-within:text-orvian-orange transition-colors" />
            </div>
            <input type="text" 
                   placeholder="Buscar en el sistema... (Alt + K)" 
                   class="w-full bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-white/10 text-sm rounded-2xl py-2.5 pl-11 pr-4 focus:ring-2 focus:ring-orvian-orange/20 focus:border-orvian-orange/50 transition-all duration-300 placeholder:text-gray-400 dark:text-gray-200 shadow-sm">
            <div class="absolute inset-y-0 right-3 flex items-center">
                <kbd class="hidden lg:inline-block px-1.5 py-0.5 text-[10px] font-semibold text-gray-500 bg-white dark:bg-dark-card border border-gray-200 dark:border-white/10 rounded-md">ALT K</kbd>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 md:gap-4 w-1/4" x-data="{}">
        
        {{-- Buscador en Móvil (Trigger del Modal) --}}
        <button @click="$dispatch('open-modal', 'mobile-search')" 
                class="md:hidden p-2.5 rounded-xl bg-gray-100 dark:bg-dark-bg text-gray-500">
            <x-heroicon-s-magnifying-glass class="w-5 h-5" />
        </button>

        <button @click="darkMode = !darkMode" 
                class="p-2.5 rounded-xl bg-gray-100 dark:bg-dark-bg text-gray-500 dark:text-gray-400 hover:text-orvian-orange transition-all duration-300 group">
            <template x-if="darkMode">
                <x-heroicon-s-sun class="w-5 h-5 text-orvian-orange animate-toggle" />
            </template>
            <template x-if="!darkMode">
                <x-heroicon-s-moon class="w-5 h-5 group-hover:text-orvian-blue" />
            </template>
        </button>

        <button class="p-2.5 rounded-xl bg-gray-100 dark:bg-dark-bg text-gray-500 dark:text-gray-400 hover:text-orvian-orange transition relative">
            <x-heroicon-s-bell class="w-5 h-5" />
            <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-orvian-orange rounded-full border-2 border-white dark:border-dark-bg"></span>
        </button>
    </div>
</nav>

{{-- Modal de Búsqueda para Móvil --}}
<x-modal name="mobile-search" maxWidth="lg">
    <div class="p-4 bg-white dark:bg-dark-card">
        <div class="relative">
            <x-heroicon-s-magnifying-glass class="absolute left-3 top-3 w-5 h-5 text-gray-400" />
            <input type="text" autofocus placeholder="Buscar..." 
                   class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-dark-bg border-none focus:ring-2 focus:ring-orvian-orange rounded-xl text-gray-200">
        </div>
        <div class="mt-4 text-xs text-gray-500 px-2">
            Resultados recientes aparecerán aquí...
        </div>
    </div>
</x-modal>