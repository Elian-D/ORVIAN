    <aside
        class="hidden md:flex flex-col gap-2 w-56 flex-shrink-0 sticky top-8 self-start max-h-[calc(100vh-4rem)] overflow-y-auto"
        x-show="!$wire.showIntro"
        style="display:none;"
    >
        <div class="space-y-0.5">
            @foreach(array_slice($stepNames, 0, $totalSteps, true) as $idx => $s)
                <button
                    wire:click="{{ $idx < $step ? 'goToStep(' . $idx . ')' : '' }}"
                    @class([
                        'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 text-left',
                        'hover:bg-white/5 cursor-pointer' => $idx < $step,
                        'cursor-default'                  => $idx >= $step,
                    ])>

                    <div @class([
                        'w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-300',
                        'bg-orvian-orange shadow-[0_0_20px_rgba(247,137,4,0.4)] text-white'                         => $step === $idx,
                        'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 ring-1 ring-emerald-500/10' => $step > $idx,
                        'bg-slate-800/50 text-slate-600 border border-slate-700/40'                                  => $step < $idx,
                    ])>
                        @if($step > $idx)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span class="text-[11px] font-black leading-none">{{ $idx }}</span>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <p @class([
                            'text-[11px] font-black uppercase tracking-wider leading-none truncate',
                            'text-orvian-orange' => $step === $idx,
                            'text-emerald-400'   => $step > $idx,
                            'text-slate-500'     => $step < $idx,
                        ])>{{ $s['name'] }}</p>
                        <p class="text-[10px] text-slate-600 dark:text-slate-600 mt-0.5 truncate">{{ $s['sub'] }}</p>
                    </div>
                </button>

                @if($idx < $totalSteps)
                    <div class="w-px h-2.5 {{ $step > $idx ? 'bg-emerald-500/25' : 'bg-slate-800/50' }} transition-colors duration-500" style="margin-left:1.75rem"></div>
                @endif
            @endforeach
        </div>

        @if($step > 1)
            <div class="mt-4 p-3.5 rounded-2xl bg-white/[0.03] border border-white/[0.06] space-y-3">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Resumen</p>

                @if($name)
                    <div>
                        <p class="text-[9px] uppercase text-slate-600">Centro</p>
                        <p class="text-[11px] font-bold text-slate-300 leading-tight truncate">{{ $name }}</p>
                        @if($sigerd_code)
                            <p class="text-[10px] text-slate-500 font-mono">{{ $sigerd_code }}</p>
                        @endif
                    </div>
                @endif

                @if($step > 2 && $regional_education_id)
                    <div>
                        <p class="text-[9px] uppercase text-slate-600">Regional</p>
                        <p class="text-[10px] text-slate-400 leading-tight">
                            {{ optional(\App\Models\Geo\RegionalEducation::find($regional_education_id))->name }}
                        </p>
                    </div>
                @endif

                @if($step > 3 && count($selectedLevels))
                    <div>
                        <p class="text-[9px] uppercase text-slate-600">Niveles</p>
                        <p class="text-[10px] text-slate-400">{{ count($selectedLevels) }} nivel(es)</p>
                    </div>
                @endif

                @if($plan_id)
                    <div>
                        <p class="text-[9px] uppercase text-slate-600">Plan</p>
                        <p class="text-[11px] font-bold text-orvian-orange">
                            {{ optional(\App\Models\Tenant\Plan::find($plan_id))->name }}
                        </p>
                    </div>
                @endif
            </div>
        @endif
    </aside>
