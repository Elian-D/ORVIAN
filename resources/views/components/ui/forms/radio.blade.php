{{--
    x-ui.forms.radio
    ----------------
    Props: label, name, id, value, checked, description, disabled
    El punto interior naranja se logra con `text-orvian-orange` via @tailwindcss/forms.
    Agrupar múltiples radios con el mismo `name` para selección exclusiva.
--}}

<label
    for="{{ $id }}"
    class="flex items-start gap-3 cursor-pointer group {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
>
    {{-- Radio nativo estilizado --}}
    <input
        type="radio"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        @checked($checked)
        @disabled($disabled)
        {{ $attributes->merge([
            'class' => 'mt-0.5 w-5 h-5 bg-transparent cursor-pointer transition-colors duration-200
                        text-orvian-orange focus:ring-orvian-orange focus:ring-offset-0 focus:ring-2
                        border-2 border-slate-300 dark:border-dark-border
                        checked:border-orvian-navy dark:checked:border-orvian-orange
                        disabled:cursor-not-allowed',
        ]) }}
    />

    {{-- Texto --}}
    <div class="flex flex-col">
        <span class="text-sm font-medium text-slate-700 dark:text-slate-200 group-hover:text-orvian-navy dark:group-hover:text-white leading-snug transition-colors duration-200 select-none">
            {{ $label }}
        </span>
        @if ($description)
            <span class="text-xs text-slate-400 dark:text-slate-500 mt-0.5 leading-snug">
                {{ $description }}
            </span>
        @endif
    </div>
</label>
