@php
    $isIconOnly = empty(trim($slot->toHtml())) && ($icon || $iconLeft || $iconRight);
    $finalIcon = $icon ?? $iconLeft ?? $iconRight;
@endphp

<button 
    {{ $attributes->merge(['class' => $getButtonClasses($isIconOnly), 'type' => 'button']) }}
    {{ $disabled ? 'disabled' : '' }}
>
    @if($isIconOnly)
        {{-- Caso: Solo Icono --}}
        <x-dynamic-component :component="$finalIcon" class="{{ $size === 'sm' ? 'w-4 h-4' : ($size === 'xl' ? 'w-7 h-7' : 'w-5 h-5') }} flex-shrink-0" />
    @else
        {{-- Caso: Botón con Texto --}}
        @if($iconLeft)
            <x-dynamic-component :component="$iconLeft" class="{{ $size === 'sm' ? 'w-4 h-4' : 'w-5 h-5' }} flex-shrink-0" />
        @endif

        <span>{{ $slot }}</span>

        @if($iconRight)
            <x-dynamic-component :component="$iconRight" class="{{ $size === 'sm' ? 'w-4 h-4' : 'w-5 h-5' }} flex-shrink-0" />
        @endif
    @endif
</button>