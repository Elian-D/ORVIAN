@php
    $waMessage = urlencode("Solicito el sistema, cuál es el procedimiento para adquirirlo");
@endphp

<footer class="bg-slate-50 dark:bg-dark-bg/50 border-t border-slate-200 dark:border-white/5">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-8">
            
            {{-- Info Marca --}}
            <div class="md:col-span-4 lg:col-span-5">
                <span class="font-etna text-2xl tracking-tighter text-orvian-navy dark:text-white" style="font-weight:900;">
                    ORVIAN
                </span>
                <p class="mt-4 text-slate-500 dark:text-slate-400 leading-relaxed max-w-sm">
                    Revolucionando la gestión escolar en la República Dominicana con tecnología modular, segura y centrada en el estudiante.
                </p>
                <div class="mt-6 flex items-center gap-4">
                    {{-- Instagram --}}
                    <a href="https://www.instagram.com/orvian__srl/" target="_blank" class="w-10 h-10 rounded-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 flex items-center justify-center text-slate-400 hover:text-orvian-orange transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    {{-- WhatsApp con mensaje --}}
                    <a href="https://wa.me/18296257463?text={{ $waMessage }}" target="_blank" class="w-10 h-10 rounded-full bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 flex items-center justify-center text-slate-400 hover:text-orvian-orange transition-colors">
                        <x-heroicon-s-chat-bubble-left-right class="w-5 h-5" />
                    </a>
                </div>
            </div>

            {{-- Links --}}
            <div class="md:col-span-8 lg:col-span-7 grid grid-cols-2 sm:grid-cols-3 gap-8">
                <div>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-6">Plataforma</h4>
                    <ul class="space-y-4">
                        <li><a href="#modulos" class="text-sm text-slate-500 hover:text-orvian-orange transition-colors">Módulos</a></li>
                        <li><a href="#precios" class="text-sm text-slate-500 hover:text-orvian-orange transition-colors">Precios</a></li>
                        <li><a href="{{ route('about') }}" class="text-sm text-slate-500 hover:text-orvian-orange transition-colors">Sobre nosotros</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-6">Soporte</h4>
                    <ul class="space-y-4">
                        <li><a href="https://wa.me/18296257463?text={{ $waMessage }}" class="text-sm text-slate-500 hover:text-orvian-orange transition-colors">WhatsApp</a></li>
                        <li><a href="#faq" class="text-sm text-slate-500 hover:text-orvian-orange transition-colors">Preguntas frecuentes</a></li>
                    </ul>
                </div>
                
                {{-- Meta Info --}}
                <div class="col-span-2 sm:col-span-1 flex flex-col justify-start">
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-6">Sistema</h4>
                    <div class="flex flex-col items-start gap-4 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400/80 dark:text-slate-500">
                        <x-ui.badge variant="primary" size="sm" :dot="false">V {{ $appVersion }}</x-ui.badge>
                        
                        <div class="flex items-center gap-2">
                            <span>HECHO EN RD</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" viewBox="0 0 32 24" class="rounded-sm opacity-80 grayscale-[0.2] hover:grayscale-0 transition-all">
                                <path fill="#FFF" d="M0 0h32v24H0z"/>
                                <path fill="#002D62" d="M0 0h14v10H0zm18 0h14v10H18zM0 14h14v10H0zm18 0h14v10H18z"/>
                                <path fill="#CE1126" d="M18 0h14v10H18zM0 14h14v10H0z"/>
                                <path fill="#002D62" d="M0 0h14v10H0zM18 14h14v10H18z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom --}}
        <div class="mt-20 pt-8 border-t border-slate-200 dark:border-white/5 flex flex-col sm:flex-row items-center justify-between gap-6">
            <p class="text-[13px] font-medium text-slate-400">
                &copy; {{ date('Y') }} ORVIAN. Todos los derechos reservados.
            </p>
            <p class="text-[11px] font-bold text-slate-300 dark:text-slate-600 uppercase tracking-widest">
                TALL Stack Architecture
            </p>
        </div>
    </div>
</footer>