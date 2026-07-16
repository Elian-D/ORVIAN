@php
    // Buscador global (REQ-07.4) — solo funcional en contexto escuela por ahora,
    // que es de donde sale el índice (metadatos ->defaults('search') en
    // routes/app/*.php, ver GlobalSearchService). En admin el input se queda
    // decorativo, como estaba antes de esta fase.
    $searchItems = \App\Services\Navigation\GlobalSearchService::forUser(auth()->user());
@endphp

<nav
    x-data="{
        query: '',
        open: false,
        items: @js($searchItems),
        get results() {
            if (this.query.trim().length < 1) return [];
            const q = this.query.trim().toLowerCase();
            return this.items
                .filter(i =>
                    i.title.toLowerCase().includes(q) ||
                    (i.description && i.description.toLowerCase().includes(q)) ||
                    i.keywords.some(k => k.toLowerCase().includes(q))
                )
                .slice(0, 8);
        }
    }"
    @keydown.window.alt.k.prevent="items.length && $refs.navbarSearchInput?.focus()"
    class="h-12 bg-white dark:bg-dark-card border-b border-gray-100 dark:border-white/5 flex items-center justify-between px-4 md:px-8 z-30 transition-colors duration-300"
>

    <div class="flex items-center gap-4 w-1/4">

        <button
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 transition">
            <x-heroicon-o-bars-3-bottom-left x-show="sidebarOpen" class="w-6 h-6" />
            <x-heroicon-o-bars-3 x-show="!sidebarOpen" class="w-6 h-6" />
        </button>

        <h1 class="hidden xl:block text-sm font-bold text-gray-800 dark:text-gray-200 tracking-tight">
            {{ $title ?? 'PANEL ADMINISTRATIVO' }}
        </h1>
    </div>

    <div class="flex-1 max-w-2xl hidden md:block relative" @click.away="open = false">
        <x-ui.forms.input
            name="navbar_search"
            placeholder="Buscar en el sistema... (Alt + K)"
            icon-left="heroicon-s-magnifying-glass"
            x-ref="navbarSearchInput"
            x-model="query"
            @focus="open = true"
            @keydown.escape="open = false"
            autocomplete="off"
        />

        <div x-show="open && results.length > 0" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="absolute left-0 right-0 mt-2 bg-white dark:bg-dark-card border border-gray-100 dark:border-white/5 rounded-2xl shadow-xl z-50 overflow-hidden py-1">
            <template x-for="result in results" :key="result.url">
                <a :href="result.url"
                   class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <img :src="'/assets/icons/modules/' + result.icon + '.svg'" class="w-4 h-4 flex-shrink-0" alt="">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="result.title"></p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate" x-text="result.description"></p>
                    </div>
                </a>
            </template>
        </div>

        <div x-show="open && query.trim().length > 0 && results.length === 0" x-cloak
             class="absolute left-0 right-0 mt-2 bg-white dark:bg-dark-card border border-gray-100 dark:border-white/5 rounded-2xl shadow-xl z-50 px-4 py-3 text-sm text-gray-400">
            Sin resultados para "<span x-text="query"></span>"
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 md:gap-4 w-1/4">

        {{-- Buscador móvil --}}
        <button @click="$dispatch('open-modal', 'mobile-search')"
                class="md:hidden p-2.5 rounded-xl bg-gray-100 dark:bg-dark-bg text-gray-500">
            <x-heroicon-s-magnifying-glass class="w-5 h-5" />
        </button>
    </div>

    {{-- Modal de búsqueda móvil — mismo x-data (query/results) del <nav>, Alpine lo hereda --}}
    <x-modal name="mobile-search" maxWidth="lg">
        <div class="p-4 bg-white dark:bg-dark-card">
            <x-ui.forms.input
                name="mobile_search"
                placeholder="Buscar..."
                icon-left="heroicon-s-magnifying-glass"
                x-model="query"
                autocomplete="off"
                autofocus
            />

            <div class="mt-3 flex flex-col divide-y divide-gray-100 dark:divide-white/5">
                <template x-if="query.trim().length > 0 && results.length === 0">
                    <p class="text-xs text-gray-400 px-2 py-3">Sin resultados para "<span x-text="query"></span>"</p>
                </template>
                <template x-if="query.trim().length < 1">
                    <p class="text-xs text-gray-500 px-2 py-3">Escribe para buscar módulos y páginas del sistema.</p>
                </template>
                <template x-for="result in results" :key="result.url">
                    <a :href="result.url" class="flex items-center gap-3 px-2 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <img :src="'/assets/icons/modules/' + result.icon + '.svg'" class="w-4 h-4 flex-shrink-0" alt="">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="result.title"></p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate" x-text="result.description"></p>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </x-modal>
</nav>
