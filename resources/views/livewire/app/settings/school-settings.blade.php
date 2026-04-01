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
            <div class="border-t border-slate-100 dark:border-dark-border bg-slate-50/30 dark:bg-slate-800/10 p-6 xl:p-8">
                {{-- Header Unificado --}}
                <div class="px-6 py-4">
                    <h2 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">
                        Identidad e Información Institucional
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Gestiona el branding y los datos legales de identificación ante el MINERD.
                    </p>
                </div>
                
                <div class="p-6 xl:p-8">
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
                        
                        {{-- COLUMNA 1: Identidad Visual (Logo) --}}
                        <div class="flex flex-col items-center  text-center xl:text-left space-y-4 border-b xl:border-b-0 xl:border-r border-slate-100 dark:border-dark-border pb-8 xl:pb-0 xl:pr-10">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">
                                Logotipo
                            </label>
                            

                                <x-ui.school-logo :school="$school" size="2xl" uploadModel="newLogo" />

                            <div class="space-y-3 w-full">
                                <div class="flex flex-col gap-2">
                                    <label class="cursor-pointer group">
                                        <input type="file" wire:model="newLogo" accept="image/*" class="hidden">
                                        <span class="inline-flex items-center justify-center gap-2 w-full px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-orvian-orange/10 hover:text-orvian-orange dark:hover:bg-orvian-orange/20 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg transition-all border border-slate-200 dark:border-dark-border group-hover:border-orvian-orange/30">
                                            <x-heroicon-o-photo class="w-4 h-4" />
                                            {{ $school->logo_path ? 'Cambiar Logotipo' : 'Subir Logotipo' }}
                                        </span>
                                    </label>

                                    @if($school->logo_path)
                                        <button
                                            type="button"
                                            wire:click="$set('school.logo_path', null)"
                                            wire:confirm="¿Estás seguro de eliminar el logotipo institucional?"
                                            class="text-[11px] text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 font-bold uppercase tracking-tighter transition-colors"
                                        >
                                            Eliminar Imagen
                                        </button>
                                    @endif
                                </div>

                                <p class="text-[10px] leading-relaxed text-slate-400 dark:text-slate-500 px-2">
                                    Formatos: <span class="text-slate-600 dark:text-slate-300">PNG, JPG, WEBP</span><br>
                                    Tamaño máximo: <span class="text-slate-600 dark:text-slate-300">2MB</span>
                                </p>
                            </div>

                            @error('newLogo')
                                <p class="text-xs text-red-600 dark:text-red-400 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- COLUMNA 2-3: Información General (Campos) --}}
                        <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <div class="md:col-span-2">
                                <x-ui.forms.input 
                                    label="Nombre de la Institución" 
                                    name="name"
                                    wire:model="name"
                                    iconLeft="heroicon-o-building-library"
                                    placeholder="Ej: Liceo Juan Pablo Duarte"
                                    :error="$errors->first('name')"
                                    required 
                                />
                            </div>
                            
                            <x-ui.forms.input 
                                label="Código SIGERD" 
                                name="sigerd_code"
                                wire:model="sigerd_code" 
                                iconLeft="heroicon-o-hashtag"
                                placeholder="01234567"
                                hint="Dígitos únicos del MINERD"
                                :error="$errors->first('sigerd_code')"
                                required 
                            />

                            <x-ui.forms.input 
                                label="Teléfono de Contacto" 
                                name="phone"
                                wire:model="phone" 
                                iconLeft="heroicon-o-phone"
                                placeholder="809-000-0000"
                                hint="Formato dominicano"
                                :error="$errors->first('phone')"
                                required 
                            />

                            <x-ui.forms.select 
                                label="Régimen de Gestión" 
                                name="regimen_gestion"
                                wire:model="regimen_gestion"
                                iconLeft="heroicon-o-building-office"
                                :error="$errors->first('regimen_gestion')"
                                required
                            >
                                <option value="Público">Público</option>
                                <option value="Privado">Privado</option>
                                <option value="Semioficial">Semioficial</option>
                            </x-ui.forms.select>

                            <x-ui.forms.select 
                                label="Modalidad" 
                                name="modalidad"
                                wire:model="modalidad"
                                iconLeft="heroicon-o-academic-cap"
                                :error="$errors->first('modalidad')"
                                required
                            >
                                <option value="Académica">Académica</option>
                                <option value="Técnico Profesional">Técnico Profesional</option>
                                <option value="Artes">Artes</option>
                            </x-ui.forms.select>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SECCIÓN: Estructura Educativa --}}
            <div class="border-t border-slate-100 dark:border-dark-border bg-slate-50/30 dark:bg-slate-800/10 p-6 xl:p-8">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">
                        Estructura Educativa MINERD
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Ubicación jerárquica del centro dentro de la estructura administrativa.
                    </p>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-ui.forms.select 
                        label="Regional de Educación" 
                        name="regional_education_id"
                        wire:model.live="regional_education_id"
                        iconLeft="heroicon-o-map"
                        :error="$errors->first('regional_education_id')"
                        required
                    >
                        
                        @foreach($this->regionals as $regional)
                            <option value="{{ $regional->id }}"> {{ $regional->id }}  {{ $regional->name }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <x-ui.forms.select 
                        label="Distrito Educativo" 
                        name="educational_district_id"
                        wire:model="educational_district_id"
                        iconLeft="heroicon-o-map-pin"
                        :disabled="!$regional_education_id"
                        :hint="!$regional_education_id ? 'Selecciona primero la Regional' : ''"
                        :error="$errors->first('educational_district_id')"
                        required
                    >
                        
                        @foreach($this->districts as $district)
                            <option value="{{ $district->id }}">{{ $district->id }} {{ $district->name }}</option>
                        @endforeach
                    </x-ui.forms.select>
                </div>
            </div>

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
            {{-- SECCIÓN: Control de Operaciones --}}
            <div class="border-t border-slate-100 dark:border-dark-border bg-slate-50/30 dark:bg-slate-800/10 p-6 xl:p-8">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">
                        Control de Operaciones
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Parámetros críticos del ciclo escolar actual.
                    </p>
                </div>
                
                <div class="p-6">
                    <div class="max-w-md">
                        <x-ui.forms.input 
                            label="Año Escolar Activo" 
                            name="current_academic_year"
                            wire:model="current_academic_year"
                            iconLeft="heroicon-o-calendar"
                            readonly
                            hint="Para cambiar el ciclo, debe iniciar el proceso de 'Cierre de Año' en el módulo académico."
                            class="bg-slate-100/50 dark:bg-slate-800/50 cursor-not-allowed"
                        />
                        
                        <div class="mt-4 p-4 rounded-xl border border-blue-100 dark:border-blue-500/20 bg-blue-50/50 dark:bg-blue-500/5 flex gap-3">
                            <x-heroicon-s-information-circle class="w-5 h-5 text-blue-500 shrink-0" />
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-tight">Nota de Seguridad</p>
                                <p class="text-[11px] leading-relaxed text-blue-600/80 dark:text-blue-400/80">
                                    El año escolar es un parámetro estructural. Su modificación está restringida para prevenir inconsistencias en actas, calificaciones y registros de asistencia ya generados.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                            {{-- En lugar de wire:click="save", disparamos el evento de apertura del modal --}}
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
                {{-- Botón Cancelar: Cierra el modal --}}
                <x-ui.button 
                    variant="secondary" 
                    x-on:click="$dispatch('close')"
                >
                    Cancelar
                </x-ui.button>

                {{-- Botón Confirmar: Este sí ejecuta el save de Livewire --}}
                <x-ui.button
                    variant="primary"
                    wire:click="save"
                    {{-- Cerramos el modal cuando la acción de Livewire termine --}}
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