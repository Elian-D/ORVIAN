{{-- SECCIÓN: Zona de Peligro, FUNCIONES CRÍTICAS --}}
<div x-data="{ open: false }" class="mt-10">
    {{-- Cabecera colapsable de la zona de peligro --}}
    <button
        @click="open = !open"
        type="button"
        class="w-full flex items-center justify-between p-4 rounded-2xl border border-red-200 dark:border-red-800/40 bg-red-50/50 dark:bg-red-900/10 text-left transition-colors hover:bg-red-100/50 dark:hover:bg-red-900/20">
        <div class="flex items-center gap-3">
            <x-heroicon-s-shield-exclamation class="w-5 h-5 text-red-500 flex-shrink-0" />
            <div>
                <p class="text-sm font-bold text-red-700 dark:text-red-400">Zona de Peligro — Configuración Crítica</p>
                <p class="text-xs text-red-600/70 dark:text-red-500/70">
                    Operaciones estructurales y de seguridad que afectan el funcionamiento global del centro.
                </p>
            </div>
        </div>
        <x-heroicon-s-chevron-down class="w-4 h-4 text-red-400 transition-transform" ::class="open && 'rotate-180'" />
    </button>

    <div x-show="open" x-collapse class="mt-4 space-y-6">

        {{-- ── Año Escolar Activo ──────────────── --}}
        <div class="rounded-2xl border border-red-200 dark:border-red-800/30 p-6 bg-red-50/30 dark:bg-red-900/5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                <div class="space-y-4">
                    <x-ui.forms.input 
                        label="Año Escolar Activo" 
                        name="current_academic_year"
                        wire:model="current_academic_year"
                        iconLeft="heroicon-o-calendar"
                        readonly
                        hint="Para cambiar el ciclo, debe iniciar el proceso de 'Cierre de Año' en el módulo académico."
                        class="bg-white dark:bg-slate-900 cursor-not-allowed"
                    />
                </div>
                <div class="p-4 rounded-xl border border-red-100 dark:border-red-500/20 bg-white dark:bg-slate-900 flex gap-3">
                    <x-heroicon-s-information-circle class="w-5 h-5 text-red-500 shrink-0" />
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-tight">Nota de Seguridad</p>
                        <p class="text-[11px] leading-relaxed text-red-600/80 dark:text-red-400/80">
                            El año escolar es un parámetro estructural. Su modificación está restringida para prevenir inconsistencias en actas, calificaciones y registros de asistencia.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Lista de Dispositivos Activos ────────────────────── --}}
        <div class="rounded-2xl border border-gray-200 dark:border-dark-border overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-dark-border">
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Terminales registradas</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Cada terminal tiene un token de acceso único e independiente.
                        Revocar un token desconecta únicamente ese dispositivo.
                    </p>
                </div>
                <x-ui.button
                    x-on:click="$dispatch('open-modal', 'showCreateDeviceModal')"
                    type="solid"
                    variant="primary"
                    size="sm"
                    iconLeft="heroicon-s-plus">
                    Nueva terminal
                </x-ui.button>
            </div>

            @php
                $kioskTokens = Auth::user()->school->tokens()
                    ->where('abilities', json_encode(['kiosk']))
                    ->latest()
                    ->get();
            @endphp

            @forelse ($kioskTokens as $token)
                <div class="flex items-center justify-between px-5 py-3.5 border-b last:border-0 border-gray-100 dark:border-dark-border/50">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-s-computer-desktop class="w-4 h-4 text-gray-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $token->name }}</p>
                            <p class="text-xs text-gray-400">
                                Creado {{ $token->created_at->diffForHumans() }}
                                · Último uso {{ $token->last_used_at?->diffForHumans() ?? 'nunca' }}
                            </p>
                        </div>
                    </div>
                    <x-ui.button
                        x-on:click="$dispatch('open-modal', 'showRevokeModal'); $wire.confirmRevokeDevice({{ $token->id }}, '{{ $token->name }}')"
                        type="outline"
                        variant="error"
                        size="sm"
                        iconLeft="heroicon-s-trash">
                        Revocar
                    </x-ui.button>
                </div>
            @empty
                <div class="px-5 py-8 text-center">
                    <x-heroicon-o-computer-desktop class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                    <p class="text-sm text-gray-400">No hay terminales registradas.</p>
                </div>
            @endforelse
        </div>

        {{-- ── PIN de Técnico ────────────────────────────────────── --}}
        <div class="rounded-2xl border border-gray-200 dark:border-dark-border p-5 space-y-4">
            <div>
                <p class="text-sm font-bold text-gray-900 dark:text-white">PIN de acceso técnico</p>

                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Código numérico de 4 a 6 dígitos que el técnico debe ingresar en el kiosko
                    para acceder al formulario de configuración. Si no hay PIN configurado,
                    el formulario de configuración es accesible sin restricciones.
                </p>

                <p class="text-xs text-white mt-2">
                    Importante: Si vas a revocar el token después de cambiar este PIN,
                    espera al menos <strong>30 segundos</strong> antes de hacerlo para que
                    el kiosko sincronice el nuevo PIN.
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                {{-- Nuevo PIN (Construcción Inline con Alpine Toggle) --}}
                <div x-data="{ show: false }" class="flex flex-col group">
                    <label class="text-[11px] font-bold uppercase tracking-wider mb-2 text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                        Nuevo PIN
                    </label>
                    <div class="relative flex items-center">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                            <x-heroicon-o-lock-closed class="w-5 h-5" />
                        </span>
                        <input
                            :type="show ? 'text' : 'password'"
                            wire:model="kioskPin"
                            inputmode="numeric"
                            maxlength="6"
                            placeholder="••••"
                            class="w-full border-0 border-b {{ $errors->has('kioskPin') ? 'border-state-error' : 'border-slate-200 dark:border-dark-border' }} bg-transparent rounded-none pl-7 pr-7 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors"
                        />
                        <button type="button" @click="show = !show" class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-orvian-orange transition-colors">
                            <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                            <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" />
                        </button>
                    </div>
                    @error('kioskPin')
                        <span class="text-xs text-state-error mt-1.5">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Confirmar PIN (Construcción Inline con Alpine Toggle) --}}
                <div x-data="{ show: false }" class="flex flex-col group">
                    <label class="text-[11px] font-bold uppercase tracking-wider mb-2 text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                        Confirmar PIN
                    </label>
                    <div class="relative flex items-center">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                            <x-heroicon-o-lock-closed class="w-5 h-5" />
                        </span>
                        <input
                            :type="show ? 'text' : 'password'"
                            wire:model="kioskPinConfirm"
                            inputmode="numeric"
                            maxlength="6"
                            placeholder="••••"
                            class="w-full border-0 border-b {{ $errors->has('kioskPinConfirm') ? 'border-state-error' : 'border-slate-200 dark:border-dark-border' }} bg-transparent rounded-none pl-7 pr-7 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors"
                        />
                        <button type="button" @click="show = !show" class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-orvian-orange transition-colors">
                            <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                            <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" />
                        </button>
                    </div>
                    @error('kioskPinConfirm')
                        <span class="text-xs text-state-error mt-1.5">{{ $message }}</span>
                    @enderror
                </div>
                
            </div>
            <div class="mt-4">
                <x-ui.button
                    wire:click="saveKioskPin"
                    type="outline"
                    variant="primary"
                    size="sm">
                    Guardar PIN
                </x-ui.button>
            </div>
        </div>

    </div>
</div>