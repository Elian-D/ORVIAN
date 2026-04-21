<div class="p-6 space-y-6">

    <x-ui.page-header
        title="Escáner de Asistencia"
        description="Registro automático por código QR o reconocimiento facial.">

        <x-slot name="actions">
            {{-- Wrapper con centrado en mobile --}}
            <div class="flex flex-col sm:flex-row items-center justify-center sm:justify-end gap-3 w-full sm:w-auto">

                {{-- Botón versátil: Abrir Sesión (sin sesión) o Cerrar Sesión (con sesión) --}}
                @if($activeSession)
                    <a href="{{ route('app.attendance.session') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 border border-red-300 dark:border-red-700/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-sm font-bold transition-colors">
                        <x-heroicon-s-stop class="w-4 h-4" />
                        <span>Cerrar Sesión</span>
                    </a>
                    <a href="{{ route('app.attendance.manual') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-300 transition-colors">
                        <x-heroicon-s-queue-list class="w-4 h-4" />
                        <span>Registro Manual</span>
                    </a>
                @else
                    <a href="{{ route('app.attendance.session') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-orvian-orange hover:bg-orvian-orange-hover text-white rounded-xl text-sm font-bold transition-colors shadow-sm shadow-orvian-orange/25">
                        <x-heroicon-s-play class="w-4 h-4" />
                        <span>Abrir Sesión</span>
                    </a>
                @endif
            </div>
        </x-slot>
    </x-ui.page-header>

    {{-- Banner: sin sesión activa --}}
    @if($sessionLoaded && !$activeSession)
        <div class="flex items-center p-5 rounded-[2rem] bg-red-50/50 dark:bg-red-500/10 border border-red-100 dark:border-red-500/20" role="alert">
            <div class="flex-shrink-0 relative">
                <div class="absolute inset-0 rounded-full bg-red-500/20 animate-pulse"></div>
                <x-heroicon-s-exclamation-triangle class="relative w-6 h-6 text-red-500" />
            </div>
            <div class="ml-4">
                <h4 class="text-sm font-bold text-red-900 dark:text-red-400 tracking-tight">Sin sesión activa</h4>
                <p class="text-xs text-red-800/80 dark:text-gray-400 mt-0.5 font-medium">
                    La sesión de asistencia debe ser abierta antes de registrar entradas.
                    <a href="{{ route('app.attendance.session') }}" class="underline font-bold">Ir a Gestión de Sesiones</a>
                </p>
            </div>
        </div>
    @endif

    {{-- Layout 60/40 --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7">
            @include('livewire.app.attendance.partials.scanner-visor')
        </div>
        <div class="lg:col-span-5">
            @include('livewire.app.attendance.partials.scanner-stats')
        </div>
    </div>
</div>
