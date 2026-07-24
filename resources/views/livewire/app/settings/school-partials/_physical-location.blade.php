{{-- SECCIÓN: Ubicación Física --}}
<div class="border-t border-slate-100 dark:border-dark-border bg-slate-50/30 dark:bg-slate-800/10 p-6 xl:p-8">
    <div class="px-6 py-4">
        <h2 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">
            Ubicación Física
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Dirección geográfica para reportes oficiales y geolocalización.
        </p>
    </div>
    
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-ui.forms.select 
            label="Provincia" 
            name="province_id"
            wire:model.live="province_id"
            iconLeft="heroicon-o-globe-americas"
            :error="$errors->first('province_id')"
            required
        >
            
            @foreach($this->provinces as $province)
                <option value="{{ $province->id }}">{{ $province->name }}</option>
            @endforeach
        </x-ui.forms.select>

        <x-ui.forms.select 
            label="Municipio" 
            name="municipality_id"
            wire:model="municipality_id"
            iconLeft="heroicon-o-building-office-2"
            :disabled="!$province_id"
            :hint="!$province_id ? 'Selecciona primero la Provincia' : ''"
            :error="$errors->first('municipality_id')"
            required
        >
            
            @foreach($this->municipalities as $municipality)
                <option value="{{ $municipality->id }}">{{ $municipality->name }}</option>
            @endforeach
        </x-ui.forms.select>

        <div class="md:col-span-2">
            <x-ui.forms.textarea 
                label="Dirección Detallada" 
                name="address_detail"
                wire:model="address_detail" 
                placeholder="Calle, número, sector y puntos de referencia..."
                hint="Opcional - Facilita la localización del centro"
                rows="3"
                :error="$errors->first('address_detail')"
            />
        </div>
        {{-- Mapa de Geolocalización --}}
        <div class="md:col-span-2 mt-4 space-y-4" 
            wire:ignore
            x-data="{ 
                tempLat: @entangle('latitude'), 
                tempLng: @entangle('longitude') 
            }"
            x-on:location-updated.window="tempLat = $event.detail.lat; tempLng = $event.detail.lng; $wire.saveLocation($event.detail.lat, $event.detail.lng)">
            
            <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                    Coordenadas Geográficas
                </label>
                <div class="flex items-center gap-2 px-2 py-0.5 bg-blue-50 dark:bg-blue-500/10 rounded-md">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                    <span class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-tight">Mapa Interactivo</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Mapa --}}
                <div class="lg:col-span-3 h-[300px] w-full relative rounded-xl overflow-hidden border border-slate-200 dark:border-dark-border group">
                    <x-admin.school-location-map 
                        :lat="$latitude" 
                        :lng="$longitude" 
                        :name="$name" 
                        :editable="true" 
                    />
                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-orvian-navy/80 backdrop-blur text-white text-[9px] px-3 py-1.5 rounded-lg pointer-events-none z-20 font-medium border border-white/10">
                        Haz clic en el mapa o arrastra el pin para ajustar la posición
                    </div>
                </div>

                {{-- Lectura de Coordenadas --}}
                <div class="flex flex-col gap-3">
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-dark-border">
                        <p class="text-[9px] uppercase font-bold text-slate-400 mb-1">Latitud</p>
                        <p class="text-xs font-mono text-slate-700 dark:text-slate-300 select-all" x-text="tempLat || 'Pendiente'"></p>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-dark-border">
                        <p class="text-[9px] uppercase font-bold text-slate-400 mb-1">Longitud</p>
                        <p class="text-xs font-mono text-slate-700 dark:text-slate-300 select-all" x-text="tempLng || 'Pendiente'"></p>
                    </div>
                    <div class="p-3 bg-orvian-orange/5 rounded-xl border border-orvian-orange/10">
                        <p class="text-[10px] leading-tight text-orvian-orange/80 font-medium">
                            <x-heroicon-s-information-circle class="w-3 h-3 inline mr-1 mb-0.5" />
                            Estas coordenadas se utilizan para ubicar el centro en el mapa público.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>