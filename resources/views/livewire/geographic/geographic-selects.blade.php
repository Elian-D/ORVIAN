<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 border p-4 rounded-lg bg-gray-50">
    
    {{-- Provincia --}}
    <div>
        <label class="block text-sm font-medium text-gray-700">Provincia</label>
        <select wire:model.live="province_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <option value="">Seleccione una provincia...</option>
            @foreach($provinces as $province)
                <option value="{{ $province->id }}">{{ $province->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Municipio --}}
    <div class="{{ empty($municipalities) ? 'opacity-50' : '' }}">
        <label class="block text-sm font-medium text-gray-700">Municipio</label>
        <select wire:model.live="municipality_id" @disabled(empty($municipalities)) class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
            <option value="">Seleccione municipio...</option>
            @foreach($municipalities as $municipality)
                <option value="{{ $municipality->id }}">{{ $municipality->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Distrito --}}
    <div class="{{ empty($districts) ? 'opacity-50' : '' }}">
        <label class="block text-sm font-medium text-gray-700">Distrito Municipal</label>
        <select wire:model.live="district_id" @disabled(empty($districts)) class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
            <option value="">Seleccione distrito...</option>
            @foreach($districts as $district)
                <option value="{{ $district->id }}">{{ $district->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Sección --}}
    <div class="{{ empty($sections) ? 'opacity-50' : '' }}">
        <label class="block text-sm font-medium text-gray-700">Sector</label>
        <select wire:model.live="section_id" @disabled(empty($sections)) class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
            <option value="">Seleccione sector...</option>
            @foreach($sections as $section)
                <option value="{{ $section->id }}">{{ $section->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Barrio / Paraje --}}
    <div class="{{ empty($neighborhoods) ? 'opacity-50' : '' }}">
        <label class="block text-sm font-medium text-gray-700">Barrio</label>
        <select wire:model.live="neighborhood_id" @disabled(empty($neighborhoods)) class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
            <option value="">Seleccione barrio...</option>
            @foreach($neighborhoods as $neighborhood)
                <option value="{{ $neighborhood->id }}">{{ $neighborhood->name }}</option>
            @endforeach
        </select>
    </div>

</div>