{{--
    x-ui.forms.select
    -----------------
    Props: label, name, id, placeholder, iconLeft, error, hint, required, disabled
    Slot: <option> elements
--}}

<div class="flex flex-col group">

    {{-- Label --}}
    @if ($label)
        <label
            for="{{ $id }}"
            class="text-[11px] font-bold uppercase tracking-wider mb-2 transition-colors duration-200
                   {{ $error
                       ? 'text-state-error'
                       : 'text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange' }}"
        >
            {{ $label }}
            @if ($required)
                <span class="text-state-error ml-0.5">*</span>
            @endif
        </label>
    @endif

    {{-- Select wrapper --}}
    <div class="relative flex items-center">

        {{-- Icono izquierdo opcional --}}
        @if ($iconLeft)
            <span class="{{ $iconWrapClasses() }} {{ $iconColorClasses() }}">
                <x-dynamic-component :component="$iconLeft" class="w-5 h-5" />
            </span>
        @endif

        <select
            name="{{ $name }}"
            id="{{ $id }}"
            @disabled($disabled)
            @required($required)
            {{ $attributes->merge(['class' => $selectClasses()]) }}
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>


    </div>

    {{-- Mensaje de error o hint --}}
    @if ($error)
        <p class="mt-1.5 text-xs font-medium text-state-error flex items-center gap-1">
            {{ $error }}
        </p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">
            {{ $hint }}
        </p>
    @endif

</div>
