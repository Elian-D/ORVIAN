{{--
    resources/views/components/ui/skeleton.blade.php
    ------------------------------------------------
    Componente skeleton polimórfico. Una sola fuente de verdad para
    todos los estados de carga de ORVIAN.

    PROPS:
      type — variante visual (ver lista abajo)
      rows — número de filas (solo aplica a type="table", default: 5)
      cols — número de columnas aproximadas (type="table", default: 4)

    VARIANTES:
      table       — tabla con toolbar + filas shimmer
      card        — tarjeta con imagen + título + texto
      avatar-text — fila de avatar + nombre + subtítulo (para listas de usuarios)
      stats       — card de estadística con icono + número + label
      form        — formulario con campos apilados

    USO:
      <x-ui.skeleton type="table" :rows="10" />
      <x-ui.skeleton type="card" />
      <x-ui.skeleton type="stats" />

    EN DataTable::placeholder():
      return view('components.ui.skeleton', ['type' => 'table', 'rows' => $this->perPage]);
--}}

@props([
    'type' => 'table',
    'rows' => 5,
    'cols' => 4,
])

{{-- Base compartida: animate-pulse + colores de tema --}}
@php
    $bar  = 'rounded bg-slate-200 dark:bg-slate-700/60';
    $circ = 'rounded-full bg-slate-200 dark:bg-slate-700/60';
@endphp

<div class="w-full animate-pulse" aria-hidden="true" aria-label="Cargando...">

    {{-- ══════════════════════════════════════
         TABLE
    ══════════════════════════════════════ --}}
    @if($type === 'table')
        <div class="flex flex-col gap-3">

            {{-- Toolbar: acciones --}}
            <div class="flex items-center gap-2">
                <div class="{{ $bar }} h-9 w-32"></div>
            </div>

            {{-- Toolbar: filtros --}}
            <div class="flex items-center gap-2">
                <div class="{{ $bar }} h-9 flex-1 max-w-sm"></div>
                <div class="{{ $bar }} h-9 w-24"></div>
                <div class="{{ $bar }} h-9 w-24"></div>
                <div class="{{ $bar }} h-9 w-28"></div>
            </div>

            {{-- Tabla --}}
            <div class="w-full rounded-xl border border-slate-200 dark:border-dark-border overflow-hidden
                        bg-white dark:bg-dark-card">

                {{-- Cabecera --}}
                <div class="flex items-center gap-6 px-4 py-3.5
                            border-b border-slate-100 dark:border-dark-border
                            bg-slate-50/80 dark:bg-white/[0.03]">
                    @for($c = 0; $c < $cols; $c++)
                        <div class="{{ $bar }} h-3 {{ $c === 0 ? 'w-24' : ($c === $cols - 1 ? 'w-16 ml-auto' : 'w-20') }}"></div>
                    @endfor
                </div>

                {{-- Filas --}}
                @for($i = 0; $i < $rows; $i++)
                    <div class="flex items-center gap-6 px-4 py-4
                                border-b border-slate-100 dark:border-dark-border last:border-0"
                         style="opacity: {{ 1 - ($i * 0.1) }}">

                        {{-- Columna nombre (avatar + texto) --}}
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="{{ $circ }} w-8 h-8 flex-shrink-0"></div>
                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="{{ $bar }} h-3.5 w-32"></div>
                                <div class="{{ $bar }} h-2.5 w-20"></div>
                            </div>
                        </div>

                        {{-- Columnas de datos --}}
                        @for($c = 1; $c < $cols - 1; $c++)
                            <div class="{{ $bar }} h-3 {{ $c % 2 === 0 ? 'w-24' : 'w-20' }} hidden md:block"></div>
                        @endfor

                        {{-- Columna acciones --}}
                        <div class="flex items-center gap-1 ml-auto">
                            <div class="{{ $bar }} h-8 w-8 rounded-lg"></div>
                            <div class="{{ $bar }} h-8 w-8 rounded-lg"></div>
                        </div>
                    </div>
                @endfor
            </div>

            {{-- Footer: conteo + paginación --}}
            <div class="flex items-center justify-between px-1">
                <div class="{{ $bar }} h-3 w-36"></div>
                <div class="flex items-center gap-1">
                    @for($p = 0; $p < 4; $p++)
                        <div class="{{ $bar }} h-8 w-8 rounded-lg"></div>
                    @endfor
                </div>
            </div>
        </div>

    {{-- ══════════════════════════════════════
         CARD
    ══════════════════════════════════════ --}}
    @elseif($type === 'card')
        <div class="rounded-2xl border border-slate-200 dark:border-dark-border
                    bg-white dark:bg-dark-card overflow-hidden">
            <div class="{{ $bar }} h-40 rounded-none"></div>
            <div class="p-5 space-y-3">
                <div class="{{ $bar }} h-5 w-3/4"></div>
                <div class="{{ $bar }} h-3 w-full"></div>
                <div class="{{ $bar }} h-3 w-5/6"></div>
                <div class="{{ $bar }} h-3 w-2/3"></div>
                <div class="{{ $bar }} h-9 w-28 rounded-xl mt-4"></div>
            </div>
        </div>

    {{-- ══════════════════════════════════════
         AVATAR-TEXT (lista de usuarios)
    ══════════════════════════════════════ --}}
    @elseif($type === 'avatar-text')
        <div class="space-y-3">
            @for($i = 0; $i < $rows; $i++)
                <div class="flex items-center gap-3" style="opacity: {{ 1 - ($i * 0.15) }}">
                    <div class="{{ $circ }} w-10 h-10 flex-shrink-0"></div>
                    <div class="space-y-1.5 flex-1">
                        <div class="{{ $bar }} h-3.5 w-36"></div>
                        <div class="{{ $bar }} h-2.5 w-24"></div>
                    </div>
                    <div class="{{ $bar }} h-6 w-16 rounded-full"></div>
                </div>
            @endfor
        </div>

    {{-- ══════════════════════════════════════
         STATS (tarjeta de métrica)
    ══════════════════════════════════════ --}}
    @elseif($type === 'stats')
        <div class="rounded-2xl border border-slate-200 dark:border-dark-border
                    bg-white dark:bg-dark-card p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="{{ $bar }} h-3 w-24"></div>
                <div class="{{ $circ }} w-9 h-9"></div>
            </div>
            <div class="{{ $bar }} h-8 w-32"></div>
            <div class="{{ $bar }} h-2.5 w-40"></div>
        </div>

    {{-- ══════════════════════════════════════
         FORM (formulario apilado)
    ══════════════════════════════════════ --}}
    @elseif($type === 'form')
        <div class="space-y-6">
            @for($i = 0; $i < $rows; $i++)
                <div class="space-y-1.5" style="opacity: {{ 1 - ($i * 0.08) }}">
                    <div class="{{ $bar }} h-2.5 w-24"></div>
                    <div class="{{ $bar }} h-10 w-full rounded-xl"></div>
                </div>
            @endfor
            <div class="flex justify-end gap-2 pt-2">
                <div class="{{ $bar }} h-9 w-24 rounded-xl"></div>
                <div class="{{ $bar }} h-9 w-28 rounded-xl"></div>
            </div>
        </div>

    @endif

</div>