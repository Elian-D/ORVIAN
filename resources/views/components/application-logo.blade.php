@props([
    'type' => 'full', // 'full' o 'icon'
    'mode' => 'dynamic' // 'dynamic', 'light', 'dark'
])

@php
    $isFull = $type === 'full';
    
    // Definimos las rutas de las imágenes
    $lightLogo = $isFull ? asset('img/logos/logo-full-light.svg') : asset('img/logos/logo-icon-light.svg');
    $darkLogo  = $isFull ? asset('img/logos/logo-full-dark.svg') : asset('img/logos/logo-icon-dark.svg');

    // Clases base para las imágenes
    $baseClass = "h-full w-auto max-w-full transition-opacity duration-300";
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-center']) }}>
    
    @if($mode === 'dynamic' || $mode === 'light')
        <img src="{{ $lightLogo }}" 
             alt="ORVIAN Logo"
             @class([
                $baseClass,
                'block dark:hidden' => $mode === 'dynamic',
                'block' => $mode === 'light',
                'hidden' => $mode === 'dark',
             ])>
    @endif

    @if($mode === 'dynamic' || $mode === 'dark')
        <img src="{{ $darkLogo }}" 
             alt="ORVIAN Logo"
             @class([
                $baseClass,
                'hidden dark:block' => $mode === 'dynamic',
                'block' => $mode === 'dark',
                'hidden' => $mode === 'light',
             ])>
    @endif
</div>