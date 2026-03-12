                <div x-show="$wire.step === 2" style="display:none;" class="space-y-6">

                    <div class="space-y-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Geografía MINERD</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <x-ui.forms.select
                                label="Regional Educativa"
                                name="regional_education_id"
                                wire:model.live="regional_education_id"
                                icon-left="heroicon-o-map"
                                :error="$errors->first('regional_education_id')"
                                required
                            >
                                @foreach($this->regionalEducations as $r)
                                    <option value="{{ $r->id }}">{{ $r->id }} {{ $r->name }}</option>
                                @endforeach
                            </x-ui.forms.select>

                            <div class="{{ !$regional_education_id ? 'opacity-50 pointer-events-none' : '' }}">
                                <x-ui.forms.select
                                    label="Distrito Educativo"
                                    name="educational_district_id"
                                    wire:model="educational_district_id"
                                    icon-left="heroicon-o-map-pin"
                                    :disabled="!$regional_education_id"
                                    :hint="!$regional_education_id ? 'Selecciona primero la Regional' : ''"
                                    :error="$errors->first('educational_district_id')"
                                    required
                                >
                                    @foreach($this->educationalDistricts as $d)
                                        <option value="{{ $d->id }}">{{ $d->id }} {{ $d->name }}</option>
                                    @endforeach
                                </x-ui.forms.select>
                            </div>

                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Geografía Política</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <x-ui.forms.select
                                label="Provincia"
                                name="province_id"
                                wire:model.live="province_id"
                                icon-left="heroicon-o-globe-americas"
                                :error="$errors->first('province_id')"
                                required
                            >
                                @foreach($this->provinces as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </x-ui.forms.select>

                            <div class="{{ !$province_id ? 'opacity-50 pointer-events-none' : '' }}">
                                <x-ui.forms.select
                                    label="Municipio"
                                    name="municipality_id"
                                    wire:model="municipality_id"
                                    icon-left="heroicon-o-building-office"
                                    :disabled="!$province_id"
                                    :hint="!$province_id ? 'Selecciona primero la Provincia' : ''"
                                    :error="$errors->first('municipality_id')"
                                    required
                                >
                                    @foreach($this->municipalities as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </x-ui.forms.select>
                            </div>

                        </div>
                    </div>

                    <div class="space-y-3 p-5 rounded-2xl border border-slate-200 dark:border-white/6 bg-slate-50/30 dark:bg-white/[0.01]">
                        <div class="flex items-center gap-2">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Dirección Física</p>
                            <x-ui.badge variant="info" size="sm" :dot="false">Recomendado</x-ui.badge>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <x-ui.forms.input label="Calle / Avenida" name="address" wire:model="address"
                                    placeholder="Ej. Calle Restauración" icon-left="heroicon-o-map-pin" />
                            </div>
                            <x-ui.forms.input label="Número" name="address_number" wire:model="address_number" placeholder="Ej. #42" />
                            <x-ui.forms.input label="Barrio / Sector" name="neighborhood" wire:model="neighborhood" placeholder="Ej. Los Jardines" />
                            <div class="md:col-span-2">
                                <x-ui.forms.input label="Referencias" name="address_reference" wire:model="address_reference"
                                    placeholder="Ej. Frente al parque central" icon-left="heroicon-o-information-circle" />
                            </div>
                            <x-ui.forms.input label="Teléfono del Centro" name="phone" wire:model="phone"
                                type="tel" placeholder="(809) 000-0000" icon-left="heroicon-o-phone" />
                        </div>
                    </div>

                </div>
