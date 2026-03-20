@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         x-data="{
             goToPage: {{ $paginator->currentPage() }},
             jump() {
                 const page = parseInt(this.goToPage);
                 const total = {{ $paginator->lastPage() }};
                 if (page >= 1 && page <= total) {
                     $wire.gotoPage(page, '{{ $paginator->getPageName() }}');
                 }
             }
         }"
         class="flex flex-wrap items-center justify-between gap-4
                bg-slate-50 dark:bg-white/[0.02] px-5 py-3.5 rounded-xl">

        {{-- Izquierda: Anterior + números + Siguiente --}}
        <div class="flex items-center gap-2">

            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <span class="flex items-center gap-1 px-3 py-2 rounded-xl
                             text-slate-300 dark:text-slate-600 cursor-not-allowed text-sm font-bold uppercase tracking-wide">
                    <x-heroicon-s-chevron-left class="w-4 h-4" />
                    Anterior
                </span>
            @else
                <button
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-1 px-3 py-2 rounded-xl transition-colors text-sm font-bold uppercase tracking-wide
                           text-orvian-navy dark:text-slate-300
                           hover:bg-slate-200 dark:hover:bg-white/5
                           disabled:opacity-50 disabled:cursor-wait">
                    <x-heroicon-s-chevron-left class="w-4 h-4" />
                    Anterior
                </button>
            @endif

            {{-- Números --}}
            <div class="flex items-center gap-1 mx-2">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="w-10 h-10 flex items-center justify-center text-sm
                                     text-slate-400 dark:text-slate-600">
                            &hellip;
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="w-10 h-10 flex items-center justify-center font-bold text-sm
                                             rounded-xl text-white
                                             bg-orvian-orange shadow-lg shadow-orvian-orange/25">
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    wire:loading.attr="disabled"
                                    class="w-10 h-10 flex items-center justify-center font-bold text-sm
                                           rounded-xl transition-all
                                           text-slate-500 dark:text-slate-400
                                           hover:text-orvian-navy dark:hover:text-white
                                           hover:bg-slate-200 dark:hover:bg-white/5
                                           disabled:opacity-50 disabled:cursor-wait">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Siguiente --}}
            @if ($paginator->hasMorePages())
                <button
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-1 px-3 py-2 rounded-xl transition-colors text-sm font-bold uppercase tracking-wide
                           text-orvian-navy dark:text-slate-300
                           hover:bg-slate-200 dark:hover:bg-white/5
                           disabled:opacity-50 disabled:cursor-wait">
                    Siguiente
                    <x-heroicon-s-chevron-right class="w-4 h-4" />
                </button>
            @else
                <span class="flex items-center gap-1 px-3 py-2 rounded-xl
                             text-slate-300 dark:text-slate-600 cursor-not-allowed text-sm font-bold uppercase tracking-wide">
                    Siguiente
                    <x-heroicon-s-chevron-right class="w-4 h-4" />
                </span>
            @endif

        </div>

        {{-- Derecha: Ir a página --}}
        <div class="flex items-center gap-6">

            <div class="hidden sm:block h-8 w-px bg-slate-200 dark:bg-dark-border"></div>

            <div class="flex items-center gap-3">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                    Ir a página
                </span>
                <div class="flex items-center gap-2">
                    <input
                        type="number"
                        x-model="goToPage"
                        min="1"
                        max="{{ $paginator->lastPage() }}"
                        @keydown.enter="jump()"
                        class="w-14 text-center text-sm font-bold rounded-xl py-2
                               bg-white dark:bg-dark-bg
                               ring-1 ring-slate-200 dark:ring-dark-border
                               text-orvian-navy dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-orvian-orange/40
                               transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" />
                    <button
                        @click="jump()"
                        class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest
                               bg-orvian-navy dark:bg-orvian-blue text-white
                               hover:opacity-90 active:scale-[0.98] transition-all">
                        IR
                    </button>
                </div>
            </div>

            <span class="text-xs text-slate-400 dark:text-slate-500 hidden lg:block">
                Página
                <span class="font-bold text-slate-600 dark:text-slate-300">{{ $paginator->currentPage() }}</span>
                de
                <span class="font-bold text-slate-600 dark:text-slate-300">{{ $paginator->lastPage() }}</span>
            </span>

        </div>
    </nav>
@endif