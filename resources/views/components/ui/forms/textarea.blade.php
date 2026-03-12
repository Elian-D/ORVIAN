{{--
    x-ui.forms.textarea
    -------------------
    Props: label, name, id, placeholder, rows, error, hint, required, disabled, readonly, resize
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

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @disabled($disabled)
        @readonly($readonly)
        @required($required)
        {{ $attributes->merge(['class' => $textareaClasses()]) }}
    ></textarea>

    {{-- Mensaje de error o hint --}}
    @if ($error)
        <p class="mt-1.5 text-xs font-medium text-state-error">
            {{ $error }}
        </p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">
            {{ $hint }}
        </p>
    @endif

</div>
