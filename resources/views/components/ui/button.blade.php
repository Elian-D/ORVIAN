@php
    $isIconOnly = empty(trim($slot->toHtml())) && ($icon || $iconLeft || $iconRight);
    $finalIcon  = $icon ?? $iconLeft ?? $iconRight;
    $tag        = $tag();  // 'button' o 'a'

    $iconSize = match($size) {
        'sm'    => 'w-4 h-4',
        'xl'    => 'w-7 h-7',
        default => 'w-5 h-5',
    };

    // Estilos inline para hex — vacío si no hay hex
    $hexStyle = $hexStyles();
@endphp

{{--
    Nota de implementación:
    ─────────────────────────────────────────────────────────────────
    - $tag()      → 'button' si no hay href, 'a' si hay href
    - $hexStyles() → estilos inline calculados si se pasa prop hex
    - wire:loading.class añade opacidad cuando Livewire está cargando.
      Combinado con wire:loading.attr="disabled" en el elemento se logra
      bloqueo completo durante la petición sin JS adicional.
    - aria-label obligatorio en modo icono para accesibilidad — si no
      se pasa el atributo aria-label externamente, se infiere del componente
      del ícono como fallback.
--}}

<{{ $tag }}
    @if($tag === 'button')
        type="{{ $attributes->get('type', 'button') }}"
        {{ $disabled ? 'disabled' : '' }}
    @else
        href="{{ $href }}"
        @if($disabled) aria-disabled="true" tabindex="-1" @endif
    @endif
    @if($isIconOnly && !$attributes->has('aria-label') && $finalIcon)
        aria-label="{{ str_replace(['heroicon-s-', 'heroicon-o-'], '', $finalIcon) }}"
    @endif
    {{ $attributes->except(['type', 'href'])->merge([
        'class' => $getButtonClasses($isIconOnly),
        'style' => $hexStyle,
        // Livewire loading: opacidad + no interacción durante petición
        'wire:loading.class' => 'opacity-60 pointer-events-none',
    ]) }}
>
    @if($isIconOnly)
        {{-- Modo solo icono — cuadrado automático --}}
        <x-dynamic-component
            :component="$finalIcon"
            class="{{ $iconSize }} flex-shrink-0"
            aria-hidden="true"
        />

    @else
        {{-- Modo con texto — icono izquierdo + slot + icono derecho --}}
        @if($iconLeft)
            <x-dynamic-component
                :component="$iconLeft"
                class="{{ $iconSize }} flex-shrink-0"
                aria-hidden="true"
            />
        @endif

        {{-- span limpio sin espacios extra que romperían la detección de isIconOnly --}}
        <span>{{ $slot }}</span>

        @if($iconRight)
            <x-dynamic-component
                :component="$iconRight"
                class="{{ $iconSize }} flex-shrink-0"
                aria-hidden="true"
            />
        @endif
    @endif

</{{ $tag }}>