            <div class="px-8 pt-6 pb-5 border-b border-slate-200 dark:border-white/5">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-orvian-orange">
                        Paso {{ $step }} de {{ $totalSteps }}
                    </span>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-white/5"></div>
                    <div class="flex gap-1">
                        @for($i = 1; $i <= $totalSteps; $i++)
                            <div @class([
                                'h-1.5 rounded-full transition-all duration-500',
                                'bg-orvian-orange w-8 shadow-[0_0_8px_rgba(247,137,4,0.5)]' => $step === $i,
                                'bg-emerald-500/60 w-4' => $step > $i,
                                'bg-slate-300 dark:bg-slate-700/60 w-4' => $step < $i,
                            ])></div>
                        @endfor
                    </div>
                </div>
                <h2 class="text-base font-black text-slate-800 dark:text-white uppercase tracking-tight">
                    {{ $stepNames[$step]['name'] }}
                    <span class="text-orvian-orange mx-1">//</span>
                    <span class="text-slate-400 font-medium text-sm normal-case tracking-normal">{{ $stepNames[$step]['sub'] }}</span>
                </h2>
            </div>