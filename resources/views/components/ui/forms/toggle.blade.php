{{-- resources/views/components/ui/forms/toggle.blade.php --}}
@php
    $wireModel = $attributes->wire('model');
@endphp

<div
    x-data="{ 
        {{-- Si hay wire:model, lo entrelazamos. Si no, usamos el prop checked --}}
        on: @if($wireModel->value()) 
                @entangle($wireModel) 
            @else 
                {{ $checked ? 'true' : 'false' }} 
            @endif,
        
        toggle() {
            if ({{ $disabled ? 'true' : 'false' }}) return;
            this.on = !this.on;
        }
    }"
    class="flex items-center justify-between gap-4 {{ $disabled ? 'opacity-50' : '' }}"
>
    {{-- Label y descripción --}}
    <div class="flex flex-col">
        @if ($label)
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 leading-snug select-none">
                {{ $label }}
            </span>
        @endif
        @if ($description)
            <span class="text-xs text-slate-400 dark:text-slate-500 mt-0.5 leading-snug select-none">
                {{ $description }}
            </span>
        @endif
    </div>

    {{-- Toggle visual --}}
    <div class="relative flex-shrink-0">
        {{-- 
            Eliminamos el x-model de aquí para evitar conflictos, 
            ya que 'on' ya está entrelazado con wire:model arriba 
        --}}
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            :checked="on"
            class="sr-only"
            @disabled($disabled)
            {{ $attributes->whereDoesntStartWith('wire:model') }}
        />

        {{-- Pista del toggle --}}
        <button
            type="button"
            role="switch"
            :aria-checked="on"
            @click="toggle()"
            :class="on
                ? 'bg-orvian-orange shadow-orvian-orange/30 shadow-md'
                : 'bg-slate-200 dark:bg-dark-border'"
            class="relative inline-flex h-6 w-11 items-center rounded-full
                   transition-all duration-200 focus:outline-none
                   focus-visible:ring-2 focus-visible:ring-orvian-orange focus-visible:ring-offset-2
                   {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}"
        >
            {{-- Bolita --}}
            <span
                :class="on ? 'translate-x-6' : 'translate-x-1'"
                class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm
                       transition-transform duration-200"
            ></span>
        </button>
    </div>
</div>