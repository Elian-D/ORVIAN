<div {{ $attributes->merge(['class' => 'relative inline-flex shrink-0 group']) }}>
    <div class="relative flex items-center justify-center overflow-hidden rounded-xl shadow-sm {{ $sizeClass }}"
         style="background-color: {{ $bgColor }};">
        
        @if($logoPath)
            <img class="h-full w-full object-cover" 
                 src="{{ asset('storage/' . $logoPath) }}" 
                 alt="Logo Escuela">
        @else
            <span class="font-bold select-none uppercase tracking-wider" 
                  style="color: {{ $bgColor }}; filter: brightness(0.4);">
                {{ $initials }}
            </span>
        @endif

        {{-- Lógica de Edición (Hover) --}}
        @if($uploadModel)
            <label class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-1 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white">
                <x-heroicon-o-camera class="w-1/3 h-1/3" />
                @if($size !== 'sm' && $size !== 'xs')
                    <span class="text-[10px] font-bold uppercase tracking-tighter">Editar</span>
                @endif
                <input type="file" class="hidden" wire:model.live="{{ $uploadModel }}" accept="image/*">
            </label>
            
            {{-- Indicador de carga de Livewire --}}
            <div wire:loading wire:target="{{ $uploadModel }}" 
                class="absolute inset-0 z-20 flex items-center justify-center bg-black/50 backdrop-blur-[1px]">
                {{-- Aseguramos que el loading tenga su propio espacio --}}
                <div class="flex items-center justify-center w-full h-full">
                    <x-ui.loading size="sm" class="text-white" />
                </div>
            </div>
        @endif

        <div class="absolute inset-0 ring-1 ring-inset ring-black/5 rounded-xl"></div>
    </div>
</div>