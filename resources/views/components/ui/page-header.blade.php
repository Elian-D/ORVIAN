@props([
    'title'       => '',
    'description' => null,
    'count'       => null,   // número de registros totales, ej: $users->total()
    'countLabel'  => 'registros',
])

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">

    {{-- Izquierda: título + subtítulo + contador --}}
    <div class="min-w-0">
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white leading-tight">
                {{ $title }}
            </h1>

            @if($count !== null)
                <x-ui.badge variant="slate" size="sm" :dot="false">{{ number_format($count) }} {{ $countLabel }}</x-ui.badge>
            @endif
        </div>

        @if($description)
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5">
                {{ $description }}
            </p>
        @endif
    </div>

    {{-- Derecha: acciones primarias (botones del módulo) --}}
    @if(isset($actions))
        <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
            {{ $actions }}
        </div>
    @endif

</div>