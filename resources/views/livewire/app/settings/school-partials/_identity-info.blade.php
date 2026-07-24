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