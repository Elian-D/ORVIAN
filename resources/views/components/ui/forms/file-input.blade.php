<div class="flex flex-col group w-full" 
     x-data="{ 
        fileName: null,
        clear() { this.fileName = null; $refs.fileInput.value = ''; }
     }">
    
    {{-- Label --}}
    @if($label)
        <label for="{{ $id }}" 
            @class([
                "text-[11px] font-bold uppercase tracking-wider mb-2 transition-colors",
                "text-state-error" => $error,
                "text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange" => !$error
            ])>
            {{ $label }}
            @if($required) <span class="text-state-error ml-0.5">*</span> @endif
        </label>
    @endif

    {{-- Input Container --}}
    <div class="relative flex items-center min-h-[45px]">
        {{-- Icono Izquierdo --}}
        @if($iconLeft)
            <span @class([
                "absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none transition-colors",
                "text-state-error" => $error,
                "text-slate-400 group-focus-within:text-orvian-orange" => !$error
            ])>
                <x-dynamic-component :component="$iconLeft" class="w-5 h-5" />
            </span>
        @endif

        {{-- Fake Input Display (Line UI) --}}
        <div @class([
            "w-full border-0 border-b bg-transparent pl-7 pr-10 py-3 text-sm transition-colors flex items-center cursor-pointer",
            "border-state-error" => $error,
            "border-slate-200 dark:border-dark-border group-focus-within:border-orvian-orange" => !$error,
            "opacity-50 cursor-not-allowed" => $disabled
        ])
        @click="$refs.fileInput.click()">
            <span x-text="fileName ? fileName : 'Seleccionar archivo...'" 
                  :class="fileName ? 'text-slate-800 dark:text-white font-medium' : 'text-slate-400'">
            </span>
        </div>

        {{-- Input Real (Oculto) --}}
        <input 
            type="file" 
            x-ref="fileInput"
            id="{{ $id }}"
            name="{{ $name }}"
            accept="{{ $accept }}"
            {{ $multiple ? 'multiple' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' archivos' : $event.target.files[0]?.name"
            {{ $attributes->merge(['class' => 'hidden']) }}
        />

        {{-- Icono Derecho (Error o Limpiar) --}}
        <div class="absolute right-0 top-1/2 -translate-y-1/2 flex items-center gap-2">
            @if($error)
                <x-heroicon-s-exclamation-circle class="w-5 h-5 text-state-error" />
            @else
                <button type="button" x-show="fileName" @click.stop="clear()" class="text-slate-400 hover:text-state-error transition-colors">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            @endif
        </div>
    </div>

    {{-- Mensajes de Error o Hint --}}
    @if($error)
        <p class="mt-1.5 text-xs text-state-error font-medium animate-in fade-in slide-in-from-top-1">
            {{ $error }}
        </p>
    @elseif($hint)
        <p class="mt-1.5 text-xs text-slate-500">
            {{ $hint }}
        </p>
    @endif
</div>