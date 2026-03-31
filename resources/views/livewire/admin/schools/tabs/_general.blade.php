<div class="space-y-6">
        {{-- B. CARD DE AÑO ESCOLAR (Nuevo) --}}
    @if($this->currentAcademicYear)
        <div class="bg-white dark:bg-dark-card border border-slate-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl">
                        <x-heroicon-o-calendar-days class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">Año Escolar Vigente</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Periodo Académico</p>
                    </div>
                </div>
                
                {{-- Badge de Estado Dinámico --}}
                <x-ui.badge 
                    variant="{{ $this->currentAcademicYear->is_active ? 'success' : 'info' }}" 
                    size="md"
                >
                    {{ $this->currentAcademicYear->is_active ? 'En vigencia' : 'Finalizado' }}
                </x-ui.badge>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-admin.info-item 
                    label="Nombre del Ciclo" 
                    :value="$this->currentAcademicYear->name"
                    icon="heroicon-s-academic-cap" 
                />

                <x-admin.info-item 
                    label="Fecha de Inicio" 
                    :value="$this->currentAcademicYear->start_date->format('d/m/Y')" 
                    icon="heroicon-s-calendar" 
                />

                <x-admin.info-item 
                    label="Fecha de Cierre" 
                    :value="$this->currentAcademicYear->end_date->format('d/m/Y')" 
                    icon="heroicon-s-calendar" 
                />
            </div>
        </div>
    @endif
    {{-- Grid de Información Detallada (Optimizado a 3 columnas) --}}
    <div class="bg-white dark:bg-dark-card border border-slate-200 dark:border-gray-800 rounded-3xl p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-6 shadow-sm dark:shadow-none">
        
        <x-admin.info-item 
            label="Director/a" 
            :value="$school->principal?->name ?? 'No asignado'" 
            icon="heroicon-s-user" 
        />
        
        <x-admin.info-item 
            label="Código SIGERD" 
            :value="$school->sigerd_code ?? 'Sin código'" 
            icon="heroicon-s-identification" 
        />

        <x-admin.info-item 
            label="Régimen de Gestión" 
            :value="$school->regimen_gestion ?? 'N/A'" 
            icon="heroicon-s-briefcase" 
        />

        <x-admin.info-item 
            label="Modalidad" 
            :value="$school->modalidad ?? 'Académica'" 
            icon="heroicon-s-academic-cap" 
        />

        <x-admin.info-item 
            label="Regional Educativa" 
            :value="$school->regional?->name" 
            icon="heroicon-s-map" 
        />

        <x-admin.info-item 
            label="Distrito Educativo" 
            :value="$school->educationalDistrict?->name" 
            icon="heroicon-s-building-library" 
        />

        <x-admin.info-item 
            label="Municipio / Localidad" 
            :value="$school->municipality?->name" 
            icon="heroicon-s-map-pin" 
        />

        <x-admin.info-item 
            label="Teléfono" 
            :value="$school->phone ?? 'No disponible'" 
            icon="heroicon-s-phone" 
        />

        <x-admin.info-item 
            label="Fecha de Registro" 
            :value="$school->created_at->format('d/m/Y')" 
            icon="heroicon-s-calendar" 
        />
    </div>

    {{-- Sección de Ubicación y Mapa --}}
    <div class="bg-white dark:bg-dark-card border border-slate-200 dark:border-gray-800 rounded-3xl p-8 shadow-sm dark:shadow-none">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-orvian-orange/10">
                    <x-heroicon-s-map-pin class="w-5 h-5 text-orvian-orange" />
                </div>
                <div>
                    <h3 class="text-orvian-navy dark:text-white font-bold">Localización</h3>
                    <p class="text-[10px] text-slate-400 dark:text-gray-500 font-bold uppercase tracking-widest">Referencias geográficas</p>
                </div>
            </div>

            <x-ui.button 
                type="ghost" 
                variant="secondary" 
                size="sm" 
                iconLeft="heroicon-s-pencil-square"
                x-on:click="$dispatch('open-modal', 'edit-location-modal')"
            >
                Editar Ubicación
            </x-ui.button>
        </div>
        
        <div class="flex flex-col gap-10 items-stretch">
            <div class="lg:col-span-3 h-[300px]">
                {{-- MAPA DE VISTA (Refrescable vía wire:key) --}}
                @if($school->latitude && $school->longitude)
                    <div wire:key="display-map-{{ $school->latitude }}-{{ $school->longitude }}" class="h-full">
                        <x-admin.school-location-map 
                            :lat="$school->latitude" 
                            :lng="$school->longitude" 
                            :name="$school->name" 
                            :editable="false" 
                        />
                    </div>
                @else
                    <div class="h-full bg-slate-50 dark:bg-gray-900 rounded-3xl border border-dashed border-slate-200 dark:border-gray-800 flex flex-col items-center justify-center text-slate-400">
                        <x-heroicon-o-map-pin class="w-8 h-8 opacity-40 mb-2" />
                        <span class="text-[10px] uppercase font-bold tracking-widest">Sin ubicación registrada</span>
                    </div>
                @endif
            </div>
            <div class="lg:col-span-2 space-y-6">
                @if($school->address_detail)
                    <div class="p-6 rounded-2xl bg-slate-50 dark:bg-gray-900/50 border border-slate-100 dark:border-gray-800/50 h-full flex flex-col justify-center">
                        <p class="text-[10px] text-slate-400 dark:text-gray-500 font-bold uppercase tracking-widest mb-3">Referencia de Dirección</p>
                        <p class="text-slate-600 dark:text-gray-400 text-sm leading-relaxed italic italic">"{{ $school->address_detail }}"</p>
                    </div>
                @else
                    <div class="flex items-center gap-3 text-slate-400 italic text-sm h-full p-6 border border-dashed border-slate-200 dark:border-gray-800 rounded-2xl">
                        <x-heroicon-o-information-circle class="w-5 h-5 opacity-50" />
                        Sin referencias de dirección.
                    </div>
                @endif
            </div>
        </div>
    </div>
    

    <x-modal name="edit-location-modal" maxWidth="xl">
        <div 
            x-data="{ 
                tempLat: {{ $school->latitude ?? 'null' }}, 
                tempLng: {{ $school->longitude ?? 'null' }} 
            }"
            x-on:location-updated.window="tempLat = $event.detail.lat; tempLng = $event.detail.lng"
            class="px-6 py-5 bg-white dark:bg-dark-card"
        >
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-base font-bold text-slate-800 dark:text-white">Actualizar Geolocalización</h2>
                <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 dark:bg-blue-500/10 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-tighter">Modo Editor</span>
                </div>
            </div>

            <div class="space-y-6">
                <div class="h-[400px] w-full relative group">
                    <x-admin.school-location-map 
                        :lat="$school->latitude" 
                        :lng="$school->longitude" 
                        :name="$school->name" 
                        :editable="true" 
                    />
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/70 backdrop-blur text-white text-[10px] px-4 py-2 rounded-full pointer-events-none z-20">
                        Haz clic en el mapa o arrastra el pin azul
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-50 dark:bg-gray-900 rounded-xl border border-slate-100 dark:border-gray-800">
                        <p class="text-[9px] uppercase font-bold text-slate-400 mb-1">Latitud</p>
                        <p class="text-xs font-mono text-slate-700 dark:text-slate-300" x-text="tempLat || '---'"></p>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-gray-900 rounded-xl border border-slate-100 dark:border-gray-800">
                        <p class="text-[9px] uppercase font-bold text-slate-400 mb-1">Longitud</p>
                        <p class="text-xs font-mono text-slate-700 dark:text-slate-300" x-text="tempLng || '---'"></p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-5 border-t border-slate-100 dark:border-dark-border">
                <x-ui.button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'edit-location-modal')">
                    Descartar
                </x-ui.button>
                <x-ui.button 
                    variant="primary" 
                    size="sm" 
                    x-on:click="$wire.saveLocation(tempLat, tempLng).then(() => $dispatch('close-modal', 'edit-location-modal'))"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="saveLocation">Guardar Ubicación</span>
                    <span wire:loading wire:target="saveLocation">Guardando...</span>
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>
