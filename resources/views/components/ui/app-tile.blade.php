@props([
    'icon'     => 'heroicon-o-squares-plus',
    'title',
    'subtitle',
    'color'    => 'bg-orvian-navy',
    'accent'   => null,
    'url'      => '#',
    'badge'    => null,
])

<a href="{{ $url }}"
   {{ $attributes->merge(['class' => 'group relative flex flex-col rounded-2xl border overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-orvian-orange/50 bg-white border-slate-200/80 hover:border-slate-300 dark:bg-[#0f1828] dark:border-white/8 dark:hover:border-white/15']) }}>

    {{-- Barra de color superior --}}
    <div class="h-1.5 w-full {{ $color }} opacity-90 group-hover:opacity-100 transition-opacity duration-300"></div>

    {{-- Cuerpo --}}
    <div class="flex flex-col items-center gap-3 px-4 py-5 md:py-6 flex-1">

        {{-- Ícono --}}
        <div class="relative">
            <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110 {{ $color }}"
                 @if($accent) style="box-shadow: 0 8px 24px {{ $accent }}40;" @endif>
                <x-dynamic-component :component="$icon" class="w-7 h-7 md:w-8 md:h-8 text-white" />
            </div>

            @if($badge)
                <span class="absolute -top-1.5 -right-1.5 text-[9px] font-black uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-orvian-orange text-white shadow-sm">
                    {{ $badge }}
                </span>
            @endif
        </div>

        {{-- Texto --}}
        <div class="text-center w-full">
            <p class="text-[15px] font-bold leading-tight truncate transition-colors duration-200 text-slate-800 group-hover:text-orvian-navy dark:text-slate-100 dark:group-hover:text-white">
                {{ $title }}
            </p>
            <p class="text-[10px] mt-1 uppercase tracking-widest font-semibold truncate text-slate-400 dark:text-slate-600">
                {{ $subtitle }}
            </p>
        </div>
    </div>

    {{-- Flecha en hover --}}
    <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-all duration-200 translate-x-1 group-hover:translate-x-0">
        <x-heroicon-s-arrow-right class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
    </div>

</a>