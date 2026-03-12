{{--
    x-ui.forms.toggle
    -----------------
    Props: label, name, id, checked, description, disabled
    Interactividad: Alpine.js. Compatible con wire:model pasando el atributo al <input type="checkbox">.
    Para Livewire: <x-ui.forms.toggle name="advanced" wire:model="advanced" />
--}}

<div
    x-data="{ on: {{ $checked ? 'true' : 'false' }} }"
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
            Input oculto que mantiene el valor real.
            Se le pasan wire:model / x-model via $attributes para integración con Livewire.
        --}}
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            x-model="on"
            @disabled($disabled)
            {{ $attributes->merge(['class' => 'sr-only']) }}
        />

        {{-- Pista del toggle --}}
        <button
            type="button"
            role="switch"
            :aria-checked="on"
            @click="!{{ $disabled ? 'false' : 'true' }} && (on = !on)"
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
