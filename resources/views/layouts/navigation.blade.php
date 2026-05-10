@php
    $isLanding = request()->routeIs('landing');
    $navLinks = [
        ['id' => 'inicio', 'label' => 'Inicio'],
        ['id' => 'modulos', 'label' => 'Módulos'],
        ['id' => 'por-que', 'label' => '¿Por qué?'],
        ['id' => 'precios', 'label' => 'Precios'],
        ['id' => 'faq', 'label' => 'FAQ'],
    ];
    $waMessage = urlencode("Solicito el sistema o Cómo puedo conseguir el sistema");
@endphp

<nav
    x-data="{ 
        scrolled: false, 
        mobileOpen: false 
    }"
    @scroll.window="scrolled = window.scrollY > 20"
    class="fixed left-0 right-0 z-50 transition-all duration-500 flex justify-center"
    :class="scrolled ? 'top-4' : 'top-0'"
>
    <div 
        :class="scrolled 
            ? 'w-[95%] max-w-6xl bg-white/80 dark:bg-dark-bg/80 shadow-xl backdrop-blur-md border border-slate-200/50 dark:border-white/10 py-3 rounded-full' 
            : 'w-full max-w-7xl bg-transparent py-5 border-transparent'"
        class="px-6 transition-all duration-500"
    >
        <div class="flex items-center justify-between">

            {{-- Wordmark --}}
            <a href="{{ route('landing') }}" class="group flex items-center">
                <span class="font-etna text-2xl tracking-tighter" style="color:#f78904; font-weight:900;">
                    ORVIAN
                </span>
            </a>

            @if($isLanding)
                {{-- Desktop links --}}
                <div class="hidden md:flex items-center bg-slate-100/50 dark:bg-white/5 px-2 py-1.5 rounded-full border border-slate-200/50 dark:border-white/5 backdrop-blur-md">
                    <div class="flex items-center gap-1">
                        @foreach($navLinks as $link)
                            <a href="#{{ $link['id'] }}"
                               class="px-4 py-1.5 text-sm font-bold text-slate-600 dark:text-slate-400 rounded-full hover:bg-white dark:hover:bg-white/10 hover:text-orvian-orange transition-all duration-300"
                            >
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                        
                        {{-- Link a Nosotros (Ruta externa a los anclajes) --}}
                        <a href="{{ route('about') }}"
                           class="px-4 py-1.5 text-sm font-bold text-slate-600 dark:text-slate-400 rounded-full hover:bg-white dark:hover:bg-white/10 hover:text-orvian-orange transition-all duration-300"
                        >
                            Nosotros
                        </a>
                    </div>
                </div>

                {{-- Desktop CTAs --}}
                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <x-ui.button variant="secondary" type="ghost" href="{{ auth()->user()->school_id ? route('app.dashboard') : route('admin.hub') }}" size="sm">
                            Panel
                        </x-ui.button>
                    @else
                        <x-ui.button variant="secondary" type="ghost" href="{{ route('login') }}" size="sm" class="font-bold">
                            Entrar
                        </x-ui.button>
                    @endauth
                    <x-ui.button variant="primary" href="https://wa.me/18296257463?text={{ $waMessage }}" target="_blank" size="sm" class="px-6 shadow-lg shadow-orvian-orange/10">
                        Probar Gratis
                    </x-ui.button>
                </div>
            @else
                {{-- Navbar Simple (Fuera de la landing) --}}
                <div class="flex items-center gap-4">
                    <x-ui.button variant="secondary" type="ghost" href="{{ route('landing') }}" iconLeft="heroicon-o-arrow-left" size="sm">
                        Volver al inicio
                    </x-ui.button>
                </div>
            @endif

            {{-- Mobile Toggle --}}
            @if($isLanding)
            <button @click="mobileOpen = !mobileOpen" 
                    class="md:hidden w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300">
                <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            @endif
        </div>
    </div>

    {{-- Mobile Menu --}}
    @if($isLanding)
    <div x-show="mobileOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak
         class="absolute top-full left-0 right-0 p-4 md:hidden">
        <div class="bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 rounded-[2rem] shadow-2xl p-6 space-y-4">
            <div class="flex flex-col gap-1">
                @foreach($navLinks as $link)
                    <a href="#{{ $link['id'] }}" 
                       @click="mobileOpen = false"
                       class="p-4 rounded-2xl text-slate-600 dark:text-slate-400 font-bold hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                        {{ $link['label'] }}
                    </a>
                @endforeach
                {{-- Link móvil a Nosotros --}}
                <a href="{{ route('about') }}" 
                   class="p-4 rounded-2xl text-orvian-orange font-bold hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                    Sobre Nosotros
                </a>
            </div>
            <div class="pt-4 border-t border-slate-100 dark:border-white/5 grid grid-cols-2 gap-3">
                @auth
                    <x-ui.button variant="secondary" type="ghost" href="{{ auth()->user()->school_id ? route('app.dashboard') : route('admin.hub') }}" size="sm">
                        Panel
                    </x-ui.button>
                @else
                    <x-ui.button variant="secondary" type="ghost" href="{{ route('login') }}" size="sm" class="font-bold">
                        Entrar
                    </x-ui.button>
                @endauth
                <x-ui.button variant="primary" href="https://wa.me/18296257463?text={{ $waMessage }}" class="rounded-2xl py-4 font-bold shadow-lg shadow-orvian-orange/20">
                    WhatsApp
                </x-ui.button>
            </div>
        </div>
    </div>
    @endif
</nav>