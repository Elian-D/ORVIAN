<div x-data="toastManager()"
     @notify.window="addToast($event.detail)"
     @notify-redirect.window="saveForRedirect($event.detail)"
     @remove-toast.window="removeToast($event.detail)"
     class="fixed top-4 right-1 z-[100] flex flex-col items-end gap-3 pointer-events-none sm:p-4">

    {{-- 1. Ingesta de Sesiones Clásicas de Laravel --}}
    <div class="hidden">
        @if (session('success'))
            <span x-init="addToast({ type: 'success', title: '¡Éxito!', message: {{ json_encode(session('success')) }} })"></span>
        @endif
        @if (session('error'))
            <span x-init="addToast({ type: 'error', title: 'Error', message: {{ json_encode(session('error')) }}, duration: 8000 })"></span>
        @endif
        @if (session('info'))
            <span x-init="addToast({ type: 'info', title: 'Información', message: {{ json_encode(session('info')) }} })"></span>
        @endif
        @if (session('warning'))
            <span x-init="addToast({ type: 'warning', title: 'Advertencia', message: {{ json_encode(session('warning')) }}, duration: 8000 })"></span>
        @endif
        @if ($errors->any())
            @php
                $errorCount = $errors->count();
                $firstError = $errors->first();
                $title = $errorCount > 1 ? "Error de validación (+" . ($errorCount - 1) . " más)" : "Error de validación";
            @endphp
            <span x-init="addToast({ type: 'error', title: {{ json_encode($title) }}, message: {{ json_encode($firstError) }}, duration: 8000 })"></span>
        @endif
    </div>

    {{-- 2. Renderizado Reactivo de Toasts (Alpine) --}}
    <template x-for="toast in toasts" :key="toast.id">
        <div x-data="toastItem(toast)"
             x-show="show"
             x-transition:enter="transform transition ease-out duration-500"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transform transition ease-in duration-400"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-full opacity-0"
             @mouseenter="pause()"
             @mouseleave="resume()"
             class="relative w-full max-w-sm overflow-hidden rounded-lg border-l-4 shadow-xl transition-all pointer-events-auto bg-white dark:bg-gray-900"
             :class="config.bgClass">

            <div class="p-4 flex items-center gap-3">
                {{-- Iconos Dinámicos --}}
                <div class="flex-shrink-0" :class="config.iconClass">
                    <div x-show="toast.type === 'success'"><x-heroicon-s-check-circle class="w-6 h-6" /></div>
                    <div x-show="toast.type === 'error'"><x-heroicon-s-x-circle class="w-6 h-6" /></div>
                    <div x-show="toast.type === 'warning'"><x-heroicon-s-exclamation-triangle class="w-6 h-6" /></div>
                    <div x-show="toast.type === 'info' || !toast.type"><x-heroicon-s-information-circle class="w-6 h-6" /></div>
                </div>

                {{-- Contenido --}}
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-none" x-text="toast.title"></h3>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 font-medium" x-text="toast.message"></p>
                </div>

                {{-- Cerrar --}}
                <button @click="close()" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <x-heroicon-s-x-mark class="w-5 h-5" />
                </button>
            </div>

            {{-- Barra de Progreso --}}
            <div class="absolute bottom-0 left-0 w-full h-1 bg-black/5 dark:bg-white/5">
                <div class="h-full transition-all ease-linear"
                     :class="config.progressClass"
                     :style="`width: ${percent}%`"></div>
            </div>
        </div>
    </template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastManager', () => ({
            toasts: [],
            init() {
                // Recuperar Toast pendiente si venimos de una redirección JS
                const pending = sessionStorage.getItem('orvian_pending_toast');
                if (pending) {
                    setTimeout(() => {
                        this.addToast(JSON.parse(pending));
                    }, 300); // Pequeño delay para que la animación se aprecie al cargar la página
                    sessionStorage.removeItem('orvian_pending_toast');
                }
            },
            addToast(toast) {
                toast.id = Date.now() + Math.random();
                toast.duration = toast.duration || 5000;
                this.toasts.push(toast);
            },
            saveForRedirect(toast) {
                // Guarda en sesión del navegador en lugar de mostrarlo al instante
                sessionStorage.setItem('orvian_pending_toast', JSON.stringify(toast));
            },
            removeToast(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.toasts.splice(index, 1);
                }
            }
        }));

        Alpine.data('toastItem', (toast) => ({
            show: false,
            remaining: toast.duration,
            interval: null,
            paused: false,
            percent: 100,
            config: {},

            init() {
                this.setConfig();
                setTimeout(() => { this.show = true; }, 50);
                this.startTimer();
            },
            setConfig() {
                const configs = {
                    success: { bgClass: 'border-emerald-500 bg-emerald-50', iconClass: 'text-emerald-500', progressClass: 'bg-emerald-500' },
                    error: { bgClass: 'border-red-500 bg-red-50', iconClass: 'text-red-500', progressClass: 'bg-red-500' },
                    warning: { bgClass: 'border-amber-500 bg-amber-50', iconClass: 'text-amber-500', progressClass: 'bg-amber-500' },
                    info: { bgClass: 'border-blue-500 bg-blue-50', iconClass: 'text-blue-500', progressClass: 'bg-blue-500' }
                };
                this.config = configs[toast.type] || configs.info;
            },
            startTimer() {
                this.paused = false;
                let lastTick = Date.now();
                this.interval = setInterval(() => {
                    if (!this.paused) {
                        const now = Date.now();
                        const delta = now - lastTick;
                        lastTick = now;
                        this.remaining -= delta;
                        this.percent = Math.max(0, (this.remaining / toast.duration) * 100);
                        if (this.remaining <= 0) this.close();
                    } else {
                        lastTick = Date.now(); // Prevenir saltos de tiempo al regresar de otra pestaña
                    }
                }, 10);
            },
            pause() { this.paused = true; },
            resume() { this.paused = false; },
            close() {
                this.show = false;
                clearInterval(this.interval);
                setTimeout(() => {
                    this.$dispatch('remove-toast', toast.id);
                }, 500); // Esperar que termine la transición de salida
            }
        }));
    });
</script>