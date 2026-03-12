{{-- resources/views/livewire/auth/register-user.blade.php --}}
<div class="w-full">

    <div class="rounded-3xl border shadow-2xl overflow-hidden backdrop-blur-xl
                bg-white/80 dark:bg-slate-900/60
                border-slate-200 dark:border-white/8">

        {{-- Header --}}
        <div class="px-8 pt-8 pb-6 border-b border-slate-200 dark:border-white/5">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-2xl bg-orvian-orange/10 border border-orvian-orange/20 flex items-center justify-center flex-shrink-0">
                    <x-heroicon-o-academic-cap class="w-5 h-5 text-orvian-orange" />
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-orvian-orange">ORVIAN · Registro</p>
                    <h1 class="text-lg font-black text-slate-800 dark:text-white leading-tight">
                        Crea tu cuenta institucional
                    </h1>
                </div>
            </div>
        </div>

        {{-- Formulario --}}
        <div class="px-8 py-7 space-y-5">

            <x-ui.forms.input
                label="Nombre Completo"
                name="name"
                wire:model="name"
                placeholder="Ej. María Fernández"
                icon-left="heroicon-o-user"
                :error="$errors->first('name')"
                required
                autofocus
            />

            <x-ui.forms.input
                label="Correo Electrónico"
                name="email"
                type="email"
                wire:model="email"
                placeholder="director@miescuela.edu.do"
                icon-left="heroicon-o-envelope"
                :error="$errors->first('email')"
                required
            />

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

            {{-- Aviso TTL --}}
            <div class="flex items-start gap-2.5 p-3.5 rounded-xl bg-amber-500/5 border border-amber-500/15">
                <x-heroicon-o-clock class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" />
                <p class="text-xs text-amber-400/80 leading-relaxed">
                    Tendrás <strong class="text-amber-400">24 horas</strong> para completar la configuración de tu centro educativo. Pasado ese tiempo, la cuenta será eliminada automáticamente.
                </p>
            </div>

        </div>

        {{-- Footer --}}
        <div class="px-8 pb-8 space-y-4">
            <x-ui.button
                variant="primary"
                :fullWidth="true"
                :hoverEffect="true"
                iconRight="heroicon-s-arrow-right"
                wire:click="register"
                wire:loading.attr="disabled"
                wire:target="register"
            >
                Crear Cuenta y Continuar
            </x-ui.button>

            <p class="text-center text-xs text-slate-400">
                ¿Ya tienes una cuenta?
                <a href="{{ route('login') }}" class="text-orvian-orange font-bold hover:underline">
                    Inicia Sesión
                </a>
            </p>
        </div>

    </div>
</div>