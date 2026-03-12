                <div x-show="$wire.step === 5" style="display:none;" class="space-y-6">

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach([
                            ['label' => 'Centro',    'value' => $name ?: '—'],
                            ['label' => 'Modalidad', 'value' => $this->modalidadLabel()],
                            ['label' => 'Niveles',   'value' => count($selectedLevels) . ' seleccionado(s)'],
                            ['label' => 'Plan',      'value' => optional($this->selectedPlan)->name ?? '—', 'orange' => true],
                        ] as $item)
                            <div class="px-4 py-3 rounded-xl bg-slate-50 dark:bg-white/[0.03] border border-slate-200 dark:border-white/6">
                                <p class="text-[9px] uppercase font-bold text-slate-400 dark:text-slate-500">{{ $item['label'] }}</p>
                                <p class="text-xs font-bold mt-0.5 truncate {{ ($item['orange'] ?? false) ? 'text-orvian-orange' : 'text-slate-700 dark:text-slate-200' }}">
                                    {{ $item['value'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ui.forms.input label="Nombre Completo" name="principal_name" wire:model="principal_name"
                            placeholder="Ej. María Fernández" icon-left="heroicon-o-user"
                            :error="$errors->first('principal_name')" required />
                        <x-ui.forms.input label="Correo Electrónico" name="principal_email" type="email"
                            wire:model="principal_email" placeholder="director@escuela.edu.do"
                            icon-left="heroicon-o-envelope" :error="$errors->first('principal_email')" required />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div x-data="{ show: false }" class="flex flex-col group">
                            <label class="text-[11px] font-bold uppercase tracking-wider mb-2 text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                                Contraseña <span class="text-state-error ml-0.5">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                    <x-heroicon-o-lock-closed class="w-5 h-5" />
                                </span>
                                <input :type="show ? 'text' : 'password'" wire:model="password" placeholder="Mínimo 8 caracteres"
                                    class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent rounded-none pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors" />
                                <button type="button" @click="show = !show" class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-orvian-orange transition-colors">
                                    <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                    <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" style="display:none;" />
                                </button>
                            </div>
                            @error('password') <p class="mt-1.5 text-xs text-state-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-data="{ show: false }" class="flex flex-col group">
                            <label class="text-[11px] font-bold uppercase tracking-wider mb-2 text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                                Confirmar Contraseña <span class="text-state-error ml-0.5">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                    <x-heroicon-o-lock-closed class="w-5 h-5" />
                                </span>
                                <input :type="show ? 'text' : 'password'" wire:model="password_confirmation" placeholder="Repite la contraseña"
                                    class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent rounded-none pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors" />
                                <button type="button" @click="show = !show" class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 hover:text-orvian-orange transition-colors">
                                    <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                    <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" style="display:none;" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl bg-amber-500/5 border border-amber-500/15">
                        <svg class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                        <p class="text-xs text-amber-400/80">Tu sesión como Owner se mantendrá activa. El director podrá acceder con sus propias credenciales desde el login.</p>
                    </div>

                </div>