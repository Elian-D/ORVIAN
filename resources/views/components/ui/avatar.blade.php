<div class="relative inline-flex shrink-0">
    {{-- Contenedor del Avatar --}}
    <div {{ $attributes->merge(['class' => "relative flex items-center justify-center overflow-hidden rounded-full shadow-sm " . $sizeClass]) }}
         style="background-color: {{ $bgColor }};">
        
        @if($avatarPath)
            <img class="h-full w-full object-cover" 
                 src="{{ asset('storage/' . $avatarPath) }}" 
                 alt="Avatar">
        @else
            <span class="font-bold select-none uppercase" 
                  style="color: {{ $bgColor }}; filter: brightness(0.4);">
                {{ $initials }}
            </span>
        @endif
    </div>

    {{-- Indicador de Estado (Status) --}}
    @if($showStatus)
        <span @class([
            "absolute bottom-0 right-0 block rounded-full ring-white dark:ring-dark-card",
            $statusColor,
            $statusSize
        ]) title="{{ ucfirst($user?->status ?? 'offline') }}">
        </span>
    @endif
</div>