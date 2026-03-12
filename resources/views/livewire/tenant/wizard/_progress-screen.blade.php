    <div
        x-show="$wire.isProcessing"
        x-data="progressScreen"
        style="display:none;"
        class="fixed inset-0 z-[200] flex items-center justify-center"
    >
        {{-- CORRECCIÓN: Se cambió /97 a /95 que sí es una opacidad estándar de Tailwind --}}
        <div class="absolute inset-0 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl"></div>
        
        <div class="absolute top-0 left-0 w-80 h-80 bg-orvian-orange/10 dark:bg-orvian-orange/10 rounded-full blur-3xl pointer-events-none -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-500/10 dark:bg-emerald-500/10 rounded-full blur-3xl pointer-events-none translate-x-1/2 translate-y-1/2"></div>

        <div class="relative z-10 w-full max-w-md px-8 text-center">

            <div class="relative inline-flex items-center justify-center mb-8">
                <div class="absolute w-20 h-20 rounded-full border-4 border-orvian-orange/20 border-t-orvian-orange animate-spin"></div>
                <div class="absolute w-14 h-14 rounded-full border-2 border-orvian-orange/10 border-b-orvian-orange/60"
                     style="animation: spin 1.5s linear infinite reverse;"></div>
                <div class="w-10 h-10 rounded-xl bg-orvian-orange/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orvian-orange animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                    </svg>
                </div>
            </div>

            <h2 class="text-2xl font-black text-orvian-navy dark:text-white mb-1">Configurando tu escuela</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-8">Por favor, no cierres esta ventana mientras preparamos tu entorno.</p>

            <div class="mb-6 min-h-[3rem] flex items-center justify-center">
                {{-- CORRECCIÓN: Se cambió /8 a /10 en el dark:border-white --}}
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 transition-all duration-500 px-4 py-2 rounded-xl bg-slate-100 dark:bg-white/[0.05] border border-slate-200 dark:border-white/10"
                   x-text="currentMessage"></p>
            </div>

            <div class="relative">
                <div class="h-2.5 w-full rounded-full bg-slate-200 dark:bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-orvian-orange via-amber-400 to-orvian-orange transition-all duration-1000 ease-linear"
                         :style="`width: ${progress}%; box-shadow: 0 0 16px rgba(247, 137, 4, 0.6);`"></div>
                </div>
                <div class="absolute top-0 h-2.5 w-8 bg-gradient-to-r from-transparent via-white/40 to-transparent rounded-full transition-all duration-1000 ease-linear"
                     :style="`left: ${Math.max(0, progress - 6)}%; opacity: ${progress < 100 ? 1 : 0};`"></div>
            </div>

            <div class="mt-3 flex items-center justify-between text-xs">
                <span class="font-mono font-bold text-orvian-orange" x-text="`${Math.round(progress)}%`"></span>
                <span class="text-slate-400 dark:text-slate-500" x-text="timeLabel"></span>
            </div>

            <div class="mt-8 grid grid-cols-4 gap-2">
                @foreach([
                    ['icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18', 'label' => 'Estructura'],
                    ['icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'label' => 'Roles'],
                    ['icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5', 'label' => 'Año Escolar'],
                    ['icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'label' => 'Accesos'],
                ] as $pgIdx => $pgStep)
                    <div
                        class="flex flex-col items-center gap-1.5 px-2 py-2.5 rounded-xl transition-all duration-500"
                        {{-- CORRECCIÓN: Se cambió /6 a /5 en el dark:border-white --}}
                        :class="progress >= {{ ($pgIdx + 1) * 25 }} ? 'bg-emerald-500/10 border border-emerald-500/20' : 'bg-slate-100 dark:bg-white/[0.03] border border-slate-200 dark:border-white/5'"
                    >
                        <svg class="w-5 h-5 transition-colors duration-500"
                             :class="progress >= {{ ($pgIdx + 1) * 25 }} ? 'text-emerald-500' : 'text-slate-400 dark:text-slate-600'"
                             fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $pgStep['icon'] }}"/>
                        </svg>
                        <span class="text-[9px] font-bold uppercase tracking-wider transition-colors duration-500"
                              :class="progress >= {{ ($pgIdx + 1) * 25 }} ? 'text-emerald-500' : 'text-slate-400 dark:text-slate-600'">
                            {{ $pgStep['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>

        </div>
    </div>