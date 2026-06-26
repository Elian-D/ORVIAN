<div class="max-w-5xl mx-auto space-y-6 p-4 md:p-6 flex flex-col gap-4">
    {{-- Header --}}
    <div class="space-y-1">
        <h1 class="text-2xl font-bold text-orvian-navy dark:text-white">
            Configuración Institucional
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Gestiona la identidad, ubicación y gobernanza de tu centro educativo.
        </p>
    </div>

    <form wire:submit="save" class="">
        <div class="bg-white dark:bg-dark-card rounded-xl border border-slate-200 dark:border-dark-border overflow-hidden shadow-sm">
            
            {{-- SECCIÓN UNIFICADA: Identidad e Información General --}}
            @include('livewire.app.settings.school-partials._identity-info')

            {{-- SECCIÓN: Estructura Educativa --}}
            @include('livewire.app.settings.school-partials._educational-structure')

            {{-- SECCIÓN: Ubicación Física --}}
            @include('livewire.app.settings.school-partials._physical-location')
            
        </div>

        {{-- SECCIÓN: Zona de Peligro, FUNCIONES CRÍTICAS --}}
        @include('livewire.app.settings.school-partials._danger-zone')

        {{-- Botón Guardar (Sticky Footer Compacto) --}}
        <div class="sticky bottom-6 mt-10 px-4 sm:px-6 lg:px-8 z-30">
            <div class="max-w-5xl mx-auto">
                <div class="bg-white/80 dark:bg-dark-card/80 backdrop-blur-md border border-slate-200 dark:border-dark-border rounded-2xl shadow-lg shadow-slate-200/50 dark:shadow-none px-4 py-3 flex items-center justify-between gap-4">
                    
                    {{-- Loading Indicator Sutil --}}
                    <div class="flex items-center">
                        <div wire:loading wire:target="save" class="flex items-center gap-2">
                            <div class="w-4 h-4 border-2 border-orvian-orange border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 hidden sm:inline">
                                Sincronizando
                            </span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-2">
                        <x-ui.button
                            variant="secondary"
                            type="outline"
                            size="sm"
                            href="{{ route('app.dashboard') }}"
                            class="!py-1.5 opacity-70 hover:opacity-100 transition-opacity"
                        >
                            Cancelar
                        </x-ui.button>

                        {{-- 1. Botón Disparador: Ahora solo abre el modal --}}
                        <x-ui.button
                            variant="primary"
                            size="sm"
                            iconLeft="heroicon-s-check"
                            x-on:click="$dispatch('open-modal', 'confirm-school-update')"
                            wire:loading.attr="disabled"
                            class="!py-1.5 shadow-sm shadow-orvian-orange/20"
                        >
                            Guardar Cambios
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </form>


    {{-- ── Modal: Crear nueva terminal ──────────────────────────────── --}}
    <x-modal name="showCreateDeviceModal" maxWidth="md">
        @if (!$generatedToken)
            {{-- Paso 1: Ingresar nombre --}}
            <div class="p-6 space-y-5">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Registrar nueva terminal</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Asigna un nombre descriptivo a este dispositivo kiosko.
                        Podrás identificarlo en la lista para revocarlo individualmente si es necesario.
                    </p>
                </div>
                <x-ui.forms.input
                    wire:model="newDeviceName"
                    label="Nombre del dispositivo"
                    placeholder="Ej: Portería Principal, Entrada Norte..."
                    :error="$errors->first('newDeviceName')" />
                <div class="flex justify-end gap-3">
                    <x-ui.button x-on:click="$dispatch('close-modal', 'showCreateDeviceModal')" type="ghost" size="sm">Cancelar</x-ui.button>
                    <x-ui.button wire:click="createDeviceToken" type="solid" variant="primary" size="sm">Generar token</x-ui.button>
                </div>
            </div>
        @else
            {{-- Paso 2: Mostrar el token (única vez) --}}
            <div class="p-6 space-y-5">
                <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-amber-800 dark:text-amber-300 font-medium">
                        Este token solo se muestra ahora. Una vez cierres este modal,
                        no habrá forma de recuperarlo — deberás generar uno nuevo.
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Token de acceso</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-xs bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 font-mono text-gray-800 dark:text-gray-200 break-all select-all">
                            {{ $generatedToken }}
                        </code>
                        <button
                            x-data
                            @click="navigator.clipboard.writeText('{{ $generatedToken }}'); $dispatch('notify', { type: 'success', message: 'Token copiado.' })"
                            class="flex-shrink-0 p-2.5 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">
                            <x-heroicon-s-clipboard class="w-4 h-4 text-gray-500" />
                        </button>
                    </div>
                </div>
                <div class="flex justify-start">
                    <x-ui.button
                        x-on:click="$dispatch('close-modal', 'showCreateDeviceModal'); setTimeout(() => $wire.set('generatedToken', null), 300)"
                        type="solid"
                        variant="primary"
                        size="sm">
                        Entendido, cerrar
                    </x-ui.button>
                </div>
            </div>
        @endif
    </x-modal>

    {{-- ── Modal: Confirmar revocación ─────────────────────────────── --}}
    <x-modal name="showRevokeModal" maxWidth="md">
        <div class="p-6 space-y-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                    <x-heroicon-s-trash class="w-5 h-5 text-red-500" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        ¿Revocar este dispositivo?
                    </h3>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Esta acción revocará el token inmediatamente y no se puede deshacer.
                    </p>

                    <p class="mt-3 text-sm text-amber-700 dark:text-amber-400">
                        <strong>Importante:</strong> Si cambiaste el PIN recientemente,
                        <strong>espera al menos 30 segundos antes de continuar.</strong>
                        Si revocas el dispositivo antes de ese tiempo, el kiosko seguirá
                        usando el PIN anterior porque aún no se habrá sincronizado.
                    </p>
                </div>
            </div>
            <div>
                <x-ui.forms.input
                    wire:model="revokeConfirmName"
                    label="Escribe el nombre del dispositivo para confirmar"
                    placeholder="{{ $deviceToRevokeName }}"
                    :error="$errors->first('revokeConfirmName')" />
            </div>
            <div class="flex justify-end gap-3">
                <x-ui.button x-on:click="$dispatch('close-modal', 'showRevokeModal')" type="ghost" size="sm">Cancelar</x-ui.button>
                <x-ui.button wire:click="revokeDevice" type="solid" variant="error" size="sm" iconLeft="heroicon-s-trash">
                    Sí, revocar acceso
                </x-ui.button>
            </div>
        </div>
    </x-modal>

    {{-- 2. El Componente Modal --}}
    <x-modal name="confirm-school-update" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                ¿Estás seguro de actualizar la información institucional?
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Estos cambios afectarán los reportes oficiales y la identidad del centro ante el sistema. 
                Asegúrate de que los datos como el código SIGERD y la ubicación sean correctos.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button 
                    variant="secondary" 
                    x-on:click="$dispatch('close')"
                >
                    Cancelar
                </x-ui.button>

                <x-ui.button
                    variant="primary"
                    wire:click="save"
                    x-on:click="$dispatch('close')"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">Confirmar Actualización</span>
                    <span wire:loading wire:target="save">Procesando...</span>
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>