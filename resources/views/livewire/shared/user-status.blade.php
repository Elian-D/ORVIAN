@php
    $statuses = [
        'online'  => ['label' => 'En línea',    'color' => 'bg-green-500'],
        'away'    => ['label' => 'Ausente',      'color' => 'bg-amber-400'],
        'busy'    => ['label' => 'Ocupado',      'color' => 'bg-red-500'],
        'offline' => ['label' => 'Desconectado', 'color' => 'bg-slate-400'],
    ];
    $current = $statuses[$status] ?? $statuses['offline'];
@endphp

<div
    x-data="{
        statusOpen: false,
        isMobile: window.innerWidth < 768,
        openUp: false,
        openLeft: false,
        toggle(event) {
            this.isMobile = window.innerWidth < 768;
            if (this.statusOpen) { this.statusOpen = false; return; }

            if (!this.isMobile) {
                const rect   = event.currentTarget.getBoundingClientRect();
                const panelW = 176;
                const panelH = 176;
                this.openLeft = (rect.right + panelW) > window.innerWidth;
                this.openUp   = (rect.bottom + panelH) > window.innerHeight;
            }

            this.statusOpen = true;
        }
    }"
    @resize.window="isMobile = window.innerWidth < 768"
    class="relative">

    {{-- ── Trigger ─────────────────────────────────────────── --}}
    <button
        @click.stop="toggle($event)"
        class="flex w-full items-center justify-between gap-3 px-3 py-2 rounded-xl text-sm transition-colors
               text-slate-600 dark:text-slate-300
               hover:bg-slate-50 dark:hover:bg-white/5
               hover:text-slate-900 dark:hover:text-white">

        <div class="flex items-center gap-3">
            <span class="relative flex h-4 w-4 items-center justify-center">
                <span class="w-2.5 h-2.5 rounded-full {{ $current['color'] }}"></span>
                @if($status === 'online')
                    <span class="absolute inline-flex h-full w-full rounded-full {{ $current['color'] }} opacity-40 animate-ping"></span>
                @endif
            </span>
            <span>{{ $current['label'] }}</span>
        </div>

        {{-- Chevron solo en desktop --}}
        <x-heroicon-s-chevron-right
            x-show="!isMobile"
            class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200"
            ::class="{
                'rotate-90':  statusOpen && !openLeft,
                '-rotate-90': statusOpen && openLeft,
            }"
        />
        {{-- Chevron up en mobile --}}
        <x-heroicon-s-chevron-up
            x-show="isMobile"
            class="w-3.5 h-3.5 text-slate-400"
        />
    </button>

    {{-- ── Desktop: sub-dropdown posicionado ──────────────── --}}
    <div
        x-show="statusOpen && !isMobile"
        @click.away="statusOpen = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        style="display: none;"
        :class="{
            'left-full ml-1.5': !openLeft,
            'right-full mr-1.5': openLeft,
            'top-0':    !openUp,
            'bottom-0': openUp,
        }"
        class="absolute w-44 rounded-2xl shadow-2xl border p-1.5 z-[60]
               bg-white dark:bg-dark-card
               border-slate-100 dark:border-white/8">

        @foreach($statuses as $value => $data)
            <button
                wire:click="setStatus('{{ $value }}')"
                @click="statusOpen = false"
                @class([
                    'flex w-full items-center gap-3 px-3 py-2 rounded-xl text-sm transition-colors',
                    'bg-slate-50 dark:bg-white/5 font-semibold text-slate-900 dark:text-white' => $status === $value,
                    'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white' => $status !== $value,
                ])>
                <span class="relative flex h-4 w-4 items-center justify-center flex-shrink-0">
                    <span class="w-2.5 h-2.5 rounded-full {{ $data['color'] }}"></span>
                    @if($value === 'online')
                        <span class="absolute inline-flex h-full w-full rounded-full {{ $data['color'] }} opacity-40 animate-ping"></span>
                    @endif
                </span>
                <span class="flex-1 text-left">{{ $data['label'] }}</span>
                @if($status === $value)
                    <x-heroicon-s-check class="w-3.5 h-3.5 text-orvian-orange flex-shrink-0" />
                @endif
            </button>
        @endforeach
    </div>

    {{-- ── Mobile: bottom sheet (portal al body) ───────────── --}}
    <template x-teleport="body">
        <div
            class="fixed inset-0 z-[200] flex flex-col justify-end"
            :class="(statusOpen && isMobile) ? 'pointer-events-auto' : 'pointer-events-none'">

            {{-- Backdrop --}}
            <div
                x-show="statusOpen && isMobile"
                @click="statusOpen = false"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="display: none;"
                class="absolute inset-0 bg-black/40 backdrop-blur-sm">
            </div>

            {{-- Sheet --}}
            <div
                x-show="statusOpen && isMobile"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                style="display: none;"
                class="relative w-full rounded-t-3xl p-4 pb-8
                    bg-white dark:bg-dark-card
                    border-t border-slate-200 dark:border-white/8
                    shadow-2xl">

                {{-- Handle --}}
                <div class="w-10 h-1 rounded-full bg-slate-200 dark:bg-white/10 mx-auto mb-5"></div>

                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 px-2 mb-3">
                    Cambiar estado
                </p>

                <div class="flex flex-col gap-1">
                    @foreach($statuses as $value => $data)
                        <button
                            wire:click="setStatus('{{ $value }}')"
                            @click="statusOpen = false"
                            @class([
                                'flex items-center gap-4 px-4 py-3.5 rounded-2xl text-sm transition-colors',
                                'bg-slate-50 dark:bg-white/5 font-semibold text-slate-900 dark:text-white' => $status === $value,
                                'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5' => $status !== $value,
                            ])>
                            <span class="relative flex h-5 w-5 items-center justify-center flex-shrink-0">
                                <span class="w-3 h-3 rounded-full {{ $data['color'] }}"></span>
                                @if($value === 'online')
                                    <span class="absolute inline-flex h-full w-full rounded-full {{ $data['color'] }} opacity-40 animate-ping"></span>
                                @endif
                            </span>
                            <span class="flex-1 text-left text-base">{{ $data['label'] }}</span>
                            @if($status === $value)
                                <x-heroicon-s-check class="w-4 h-4 text-orvian-orange flex-shrink-0" />
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </template>

</div>