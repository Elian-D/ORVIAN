<footer {{ $attributes->merge(['class' => 'w-full mt-12 transition-colors duration-300']) }}>
    {{-- Línea divisoria de lado a lado --}}
    <div class="w-full border-t border-slate-200 dark:border-white/5"></div>

    <div class="px-4 md:px-8 py-4 flex flex-col md:flex-row justify-between items-center gap-3">
        {{-- Copyright --}}
        <div class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400/80 dark:text-slate-500">
            © {{ date('Y') }} <span class="text-slate-500 dark:text-slate-400">ORVIAN SYSTEM</span>. TODOS LOS DERECHOS RESERVADOS.
        </div>

        {{-- Meta Info --}}
        <div class="flex items-center gap-4 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400/80 dark:text-slate-500">
            <span class="bg-slate-200/50 dark:bg-white/5 px-2 py-0.5 rounded text-[9px]">V 0.1.0-ALPHA</span>
            
            <span class="w-px h-3 bg-slate-200 dark:bg-white/10"></span>
            
            <div class="flex items-center gap-2">
                <span>HECHO EN RD</span>
                {{-- SVG de la Bandera Dominicana (Consistente en WSL/Linux) --}}
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" viewBox="0 0 32 24" class="rounded-sm opacity-80 grayscale-[0.2] hover:grayscale-0 transition-all">
                    <path fill="#FFF" d="M0 0h32v24H0z"/>
                    <path fill="#002D62" d="M0 0h14v10H0zm18 0h14v10H18zM0 14h14v10H0zm18 0h14v10H18z"/>
                    <path fill="#CE1126" d="M18 0h14v10H18zM0 14h14v10H0z"/>
                    <path fill="#002D62" d="M0 0h14v10H0zM18 14h14v10H18z"/>
                </svg>
            </div>
        </div>
    </div>
</footer>