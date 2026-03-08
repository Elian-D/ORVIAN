<div x-data="{ 
        show: true, 
        remaining: {{ $duration }}, 
        interval: null,
        paused: false,
        percent: 100,
        
        init() { this.startTimer(); },
        startTimer() {
            this.paused = false;
            let lastTick = Date.now();
            this.interval = setInterval(() => {
                if (!this.paused) {
                    const now = Date.now();
                    const delta = now - lastTick;
                    lastTick = now;
                    this.remaining -= delta;
                    this.percent = Math.max(0, (this.remaining / {{ $duration }}) * 100);
                    if (this.remaining <= 0) this.close();
                } else { lastTick = Date.now(); }
            }, 10);
        },
        pause() { this.paused = true; },
        resume() {
            this.paused = false;
        },
        close() {
            this.show = false;
            setTimeout(() => { clearInterval(this.interval); }, 500);
        }
    }" 
    x-show="show"
    x-cloak
    {{-- Animación: Entrada desde la derecha, Salida hacia la derecha --}}
    x-transition:enter="transform transition ease-out duration-500"
    x-transition:enter-start="translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transform transition ease-in duration-400"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-full opacity-0"
    
    @mouseenter="pause()" 
    @mouseleave="resume()"
    class="relative w-full max-w-sm overflow-hidden rounded-lg border-l-4 {{ $bgClass }} shadow-xl transition-all"
>
    <div class="p-4 flex items-center gap-3 bg-white dark:bg-gray-900">
        {{-- Icono --}}
        <div class="flex-shrink-0 {{ $iconClass }}">
            <x-dynamic-component :component="$icon" class="w-6 h-6" />
        </div>

        {{-- Contenido --}}
        <div class="flex-1">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-none">
                {{ $title }}
            </h3>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 font-medium">
                {{ $message }}
            </p>
        </div>

        {{-- Botón Cerrar --}}
        <button @click="close()" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
            <x-heroicon-s-x-mark class="w-5 h-5" />
        </button>
    </div>

    {{-- Barra de carga --}}
    <div class="absolute bottom-0 left-0 w-full h-1 bg-black/5 dark:bg-white/5">
        <div class="h-full {{ $progressClass }} transition-all ease-linear" 
             :style="`width: ${percent}%`"
        ></div>
    </div>
</div>