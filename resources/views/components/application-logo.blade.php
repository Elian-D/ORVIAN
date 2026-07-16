@props([
    'type' => 'full', // 'full' o 'icon'
    'mode' => 'dynamic' // 'dynamic', 'light', 'dark'
])

@php
    $isFull = $type === 'full';

    // Clases base para las imágenes
    $baseClass = "h-full w-auto max-w-full transition-opacity duration-300";

    // Resolución de logo por tenant (REQ-07.6):
    // Sin school_id (SuperAdmin) → logo ORVIAN de siempre.
    // Con school_id → logo del centro si tiene logo_path, si no, fallback a ORVIAN.
    // El logo de un centro es un solo archivo (no hay variante clara/oscura ni
    // full/icon separadas) — se reescala la misma imagen en ambos estados.
    $schoolLogoUrl = null;
    $user = auth()->user();

    if ($user && $user->school_id) {
        $school = $user->school;
        if ($school && $school->logo_path) {
            $schoolLogoUrl = asset('storage/' . $school->logo_path);
        }
    }

    // Definimos las rutas de las imágenes ORVIAN (fallback)
    $lightLogo = $isFull ? asset('img/logos/logo-full-light.svg') : asset('img/logos/logo-icon-light.svg');
    $darkLogo  = $isFull ? asset('img/logos/logo-full-dark.svg') : asset('img/logos/logo-icon-dark.svg');
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-center']) }}>

    @if($schoolLogoUrl)
        <img src="{{ $schoolLogoUrl }}" alt="{{ $user->school->name }}" class="{{ $baseClass }} object-contain">
    @elseif($mode === 'dynamic' || $mode === 'light')
        <img src="{{ $lightLogo }}" 
             alt="ORVIAN Logo"
             @class([
                $baseClass,
                'block dark:hidden' => $mode === 'dynamic',
                'block' => $mode === 'light',
                'hidden' => $mode === 'dark',
             ])>
    @endif

    @if(!$schoolLogoUrl && ($mode === 'dynamic' || $mode === 'dark'))
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