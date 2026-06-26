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