{{-- resources/views/livewire/auth/register-install.blade.php --}}
<div x-data class="w-full">

    {{-- ── Stepper ── --}}
    <div class="flex items-center justify-center mb-8 px-4">

        @foreach([1 => 'Bienvenida', 2 => 'Tu Cuenta', 3 => 'Seguridad'] as $n => $label)
            <div class="flex flex-col items-center gap-1.5">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-black text-sm border-2 transition-all duration-300 z-10"
                    :class="{
                        'bg-orvian-orange border-orvian-orange text-white shadow-[0_0_16px_rgba(247,137,4,0.4)]': $wire.step === {{ $n }},
                        'bg-orvian-orange/20 border-orvian-orange text-orvian-orange': $wire.step > {{ $n }},
                        'bg-transparent border-slate-300 dark:border-slate-700 text-slate-400': $wire.step < {{ $n }}
                    }">
                    <template x-if="$wire.step > {{ $n }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <template x-if="$wire.step <= {{ $n }}">
                        <span>{{ $n }}</span>
                    </template>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider transition-colors"
                      :class="$wire.step >= {{ $n }} ? 'text-orvian-orange' : 'text-slate-400 dark:text-slate-600'">
                    {{ $label }}
                </span>
            </div>
            @if($n < 3)
                <div class="flex-1 mx-2 mb-4 h-[2px] rounded-full overflow-hidden bg-slate-200 dark:bg-slate-800">
                    <div class="h-full bg-orvian-orange rounded-full transition-all duration-500 ease-out"
                         :style="$wire.step > {{ $n }} ? 'width:100%' : 'width:0%'"></div>
                </div>
            @endif
        @endforeach

    </div>

    {{-- ── Card ── --}}
    <div class="rounded-3xl border shadow-2xl overflow-hidden backdrop-blur-xl
                bg-white/80 dark:bg-slate-900/60
                border-slate-200 dark:border-white/8">

        {{-- Header --}}
        <div class="px-8 pt-8 pb-6 border-b border-slate-200 dark:border-white/5">
            @php
                $headers = [
                    1 => [
                        'icon'  => 'heroicon-o-bolt',
                        'color' => 'orvian-orange',
                        'title' => 'Bienvenido al Instalador',
                        'sub'   => 'Este asistente configurará el sistema por primera vez',
                    ],
                    2 => [
                        'icon'  => 'heroicon-o-user',
                        'color' => 'blue-500',
                        'title' => 'Datos del Administrador',
                        'sub'   => 'Identifica al propietario maestro del sistema',
                    ],
                    3 => [
                        'icon'  => 'heroicon-o-lock-closed',
                        'color' => 'emerald-500',
                        'title' => 'Credenciales de Acceso',
                        'sub'   => 'Define la contraseña maestra del sistema',
                    ],
                ];
                $h = $headers[$step];
            @endphp
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl bg-{{ $h['color'] }}/10 border border-{{ $h['color'] }}/20 flex items-center justify-center flex-shrink-0">
                    <x-dynamic-component :component="$h['icon']" class="w-5 h-5 text-{{ $h['color'] }}" />
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-wide">
                        {{ $h['title'] }}
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $h['sub'] }}</p>
                </div>
            </div>
        </div>

        {{-- Contenido --}}
        <div class="px-8 py-7">

            {{-- Paso 1: Bienvenida --}}
            <div x-show="$wire.step === 1" x-cloak>
                <div class="grid grid-cols-1 gap-3">
                    @foreach([
                        ['color' => 'orvian-orange', 'icon' => 'heroicon-o-squares-2x2', 'title' => 'Instalación de una sola vez', 'body' => 'Este formulario solo está disponible cuando el sistema no tiene un Owner registrado. Una vez completado, quedará deshabilitado permanentemente.'],
                        ['color' => 'blue-500',       'icon' => 'heroicon-o-shield-check',  'title' => 'Cuenta Owner = acceso total',   'body' => 'Esta cuenta tendrá control absoluto sobre todos los módulos, tenants y configuraciones del sistema. Guarda bien las credenciales.'],
                        ['color' => 'emerald-500',    'icon' => 'heroicon-o-clock',          'title' => 'Solo tomará 2 minutos',         'body' => 'Nombre, email y contraseña. Eso es todo lo que necesitas para inicializar ORVIAN y comenzar a operar.'],
                    ] as $item)
                        <div class="flex items-start gap-3 p-4 rounded-2xl border border-slate-200 dark:border-white/6">
                            <div class="w-8 h-8 rounded-xl bg-{{ $item['color'] }}/15 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <x-dynamic-component :component="$item['icon']" class="w-4 h-4 text-{{ $item['color'] }}" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-white">{{ $item['title'] }}</p>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $item['body'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Paso 2: Datos --}}
            <div x-show="$wire.step === 2" x-cloak>
                <div class="space-y-5">
                    <x-ui.forms.input
                        label="Nombre Completo"
                        name="name"
                        wire:model="name"
                        placeholder="Ej. Elian Bertre"
                        icon-left="heroicon-o-user"
                        :error="$errors->first('name')"
                        required
                        autofocus
                    />
                    <x-ui.forms.input
                        label="Email de Administrador"
                        name="email"
                        type="email"
                        wire:model="email"
                        placeholder="admin@orvian.test"
                        icon-left="heroicon-o-envelope"
                        :error="$errors->first('email')"
                        required
                    />
                    <div class="flex items-start gap-2.5 p-3.5 rounded-xl bg-blue-50 dark:bg-blue-500/5 border border-blue-200 dark:border-blue-500/15">
                        <x-heroicon-o-information-circle class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" />
                        <p class="text-xs text-blue-600 dark:text-blue-300/80 leading-relaxed">
                            Este email será tu identificador permanente para acceder como Owner. No podrá cambiarse una vez completada la instalación.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Paso 3: Contraseña --}}
            <div x-show="$wire.step === 3" x-cloak>
                <div class="space-y-5">

                    {{-- Resumen --}}
                    <div class="flex items-center gap-3 p-4 rounded-2xl bg-orvian-orange/5 border border-orvian-orange/15">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-orvian-orange to-amber-400 flex items-center justify-center text-white text-sm font-black flex-shrink-0"
                             x-text="$wire.name.charAt(0).toUpperCase() || 'O'"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-800 dark:text-white truncate">{{ $name ?: 'Sin nombre' }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $email ?: 'Sin email' }}</p>
                        </div>
                        <x-ui.button variant="secondary" type="ghost" size="sm" wire:click="goPrev">
                            Editar
                        </x-ui.button>
                    </div>

                    {{-- Password fields --}}
                    <div x-data="{ show: false }" class="flex flex-col group">
                        <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                                      text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                            Contraseña <span class="text-state-error ml-0.5">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                <x-heroicon-o-lock-closed class="w-5 h-5" />
                            </span>
                            <input :type="show ? 'text' : 'password'" wire:model="password"
                                   placeholder="Mínimo 8 caracteres"
                                   class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                          rounded-none pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white
                                          placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors" />
                            <button type="button" @click="show = !show"
                                    class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-orvian-orange transition-colors">
                                <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" style="display:none;" />
                            </button>
                        </div>
                        @error('password') <p class="mt-1.5 text-xs text-state-error">{{ $message }}</p> @enderror
                    </div>

                    <div x-data="{ show: false }" class="flex flex-col group">
                        <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                                      text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                            Confirmar Contraseña <span class="text-state-error ml-0.5">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                <x-heroicon-o-lock-closed class="w-5 h-5" />
                            </span>
                            <input :type="show ? 'text' : 'password'" wire:model="password_confirmation"
                                   placeholder="Repite la contraseña"
                                   class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                          rounded-none pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white
                                          placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors" />
                            <button type="button" @click="show = !show"
                                    class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-orvian-orange transition-colors">
                                <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" style="display:none;" />
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="px-8 pb-8 flex items-center"
             :class="$wire.step === 1 ? 'justify-end' : 'justify-between'">

            <x-ui.button x-show="$wire.step > 1" x-cloak
                variant="secondary" type="outline"
                iconLeft="heroicon-s-arrow-left"
                wire:click="goPrev">
                Anterior
            </x-ui.button>

            <x-ui.button x-show="$wire.step < 3" x-cloak
                variant="primary" :hoverEffect="true"
                iconRight="heroicon-s-arrow-right"
                wire:click="goNext">
                Continuar
            </x-ui.button>

            <x-ui.button x-show="$wire.step === 3" x-cloak
                variant="success" :hoverEffect="true"
                iconRight="heroicon-s-rocket-launch"
                wire:click="register">
                Inicializar Plataforma
            </x-ui.button>

        </div>

    </div>
</div>