@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex items-center justify-between">

        {{-- Contador --}}
        <div class="hidden sm:block">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Mostrando
                @if ($paginator->firstItem())
                    <span class="font-bold text-orvian-navy dark:text-white">{{ $paginator->firstItem() }}</span>
                    –
                    <span class="font-bold text-orvian-navy dark:text-white">{{ $paginator->lastItem() }}</span>
                @endif
                de
                <span class="font-bold text-orvian-navy dark:text-white">{{ $paginator->total() }}</span>
                resultados
            </p>
        </div>

        {{-- Controles --}}
        <div class="flex items-center gap-1">

            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <span class="w-10 h-10 flex items-center justify-center rounded-xl
                             text-slate-300 dark:text-slate-600 cursor-not-allowed">
                    <x-heroicon-s-chevron-left class="w-5 h-5" />
                </span>
            @else
                <button
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                    class="w-10 h-10 flex items-center justify-center rounded-xl transition-colors
                           text-orvian-navy dark:text-slate-300
                           hover:bg-slate-100 dark:hover:bg-white/5
                           disabled:opacity-50 disabled:cursor-wait">
                    <x-heroicon-s-chevron-left class="w-5 h-5" />
                </button>
            @endif

            {{-- Números --}}
            <div class="flex items-center gap-1 mx-1">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="w-8 h-10 flex items-center justify-center
                                     text-slate-400 dark:text-slate-600 text-sm">
                            &hellip;
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="w-10 h-10 flex items-center justify-center font-bold text-sm
                                             rounded-xl text-white
                                             bg-orvian-orange shadow-md shadow-orvian-orange/30">
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    wire:loading.attr="disabled"
                                    class="w-10 h-10 flex items-center justify-center font-bold text-sm
                                           rounded-xl transition-colors
                                           text-slate-500 dark:text-slate-400
                                           hover:text-orvian-navy dark:hover:text-white
                                           hover:bg-slate-100 dark:hover:bg-white/5
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
                    dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                    class="w-10 h-10 flex items-center justify-center rounded-xl transition-colors
                           text-orvian-navy dark:text-slate-300
                           hover:bg-slate-100 dark:hover:bg-white/5
                           disabled:opacity-50 disabled:cursor-wait">
                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                </button>
            @else
                <span class="w-10 h-10 flex items-center justify-center rounded-xl
                             text-slate-300 dark:text-slate-600 cursor-not-allowed">
                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                </span>
            @endif

        </div>
    </nav>
@endif