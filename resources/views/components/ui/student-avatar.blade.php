<div class="relative inline-flex shrink-0">
    {{-- Contenedor Principal --}}
    <div {{ $attributes->merge(['class' => "relative flex items-center justify-center overflow-hidden rounded-2xl shadow-sm transition-all duration-200 " . $sizeClass]) }}
         style="background-color: {{ $bgColor }}20; border: 1px solid {{ $bgColor }}40;">
        
        @if($student->photo_path)
            {{-- Imagen del Estudiante --}}
            <img class="h-full w-full object-cover" 
                 src="{{ asset('storage/' . $student->photo_path) }}" 
                 alt="{{ $student->full_name }}">
        @else
            {{-- Iniciales con color generado --}}
            <span class="font-black select-none uppercase tracking-tighter" 
                  style="color: {{ $bgColor }};">
                {{ $initials }}
            </span>
        @endif
    </div>

    {{-- Badge de QR (Si aplica) --}}
    @if($showQr)
        <div @class([
            "absolute -bottom-1 -right-1 flex items-center justify-center rounded-lg shadow-sm border ring-2 ring-white dark:ring-dark-card bg-white dark:bg-slate-800",
            $qrBadgeSize
        ]) title="Código QR disponible">
            <x-heroicon-s-qr-code class="w-full h-full text-slate-900 dark:text-white" />
        </div>
    @endif

    {{-- Overlay de Estado (Opcional, similar al de usuario) --}}
    @if(!$student->is_active)
        <div class="absolute inset-0 bg-slate-100/50 dark:bg-slate-900/50 backdrop-grayscale rounded-2xl flex items-center justify-center">
            <x-heroicon-s-no-symbol class="w-1/2 h-1/2 text-red-500" />
        </div>
    @endif
</div>