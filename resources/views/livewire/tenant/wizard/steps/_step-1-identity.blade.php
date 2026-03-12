                <div x-show="$wire.step === 1" class="space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ui.forms.input
                            label="Código SIGERD"
                            name="sigerd_code"
                            wire:model="sigerd_code"
                            placeholder="Ej. 08-0054"
                            icon-left="heroicon-o-hashtag"
                            hint="Código único asignado por el MINERD"
                            :error="$errors->first('sigerd_code')"
                            required
                        />
                        <x-ui.forms.input
                            label="Nombre del Centro"
                            name="name"
                            wire:model="name"
                            placeholder="Ej. Liceo Juan Pablo Duarte"
                            icon-left="heroicon-o-building-library"
                            :error="$errors->first('name')"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Régimen de Gestión <span class="text-state-error ml-0.5">*</span>
                        </p>
                        <div class="flex gap-2 flex-wrap">
                            @foreach($this->regimenesLabels() as $key => $label)
                                <button
                                    type="button"
                                    wire:click="$set('regimen_gestion', '{{ $key }}')"
                                    @class([
                                        'px-5 py-2 rounded-xl text-xs font-bold border-2 transition-all',
                                        'border-orvian-orange bg-orvian-orange/10 text-orvian-orange' => $regimen_gestion === $key,
                                        'border-slate-200 dark:border-white/8 text-slate-500 hover:border-slate-300 dark:hover:border-white/15' => $regimen_gestion !== $key,
                                    ])>{{ $label }}</button>
                            @endforeach
                        </div>
                        @error('regimen_gestion') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Modalidad del Centro <span class="text-state-error ml-0.5">*</span>
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($this->modalidadesDescripcion() as $key => $data)
                                <button
                                    type="button"
                                    wire:click="$set('modalidad', '{{ $key }}')"
                                    @class([
                                        'p-4 rounded-2xl border-2 text-left transition-all duration-200',
                                        'border-orvian-orange bg-orvian-orange/5 ring-1 ring-orvian-orange/20' => $modalidad === $key,
                                        'border-slate-200 dark:border-white/6 hover:border-slate-300 dark:hover:border-white/15' => $modalidad !== $key,
                                    ])>
                                    <p class="font-black text-sm text-slate-800 dark:text-white">{{ $data['label'] }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5 leading-relaxed">{{ $data['description'] }}</p>
                                </button>
                            @endforeach
                        </div>
                        @error('modalidad') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
