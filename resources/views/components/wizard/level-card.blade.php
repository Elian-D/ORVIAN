
    {{-- 
        Nota: Para mantener el DRY, puedes crear un componente anónimo o inline para el card:
        resources/views/components/wizard/level-card.blade.php
    --}}
    @props(['level', 'selectedLevels'])
    <label @class([
        'group flex items-center gap-3 px-4 py-3.5 rounded-xl border-2 cursor-pointer transition-all duration-200',
        'border-orvian-orange bg-orvian-orange/5 shadow-sm' => in_array($level->id, $selectedLevels),
        'border-slate-200/60 dark:border-white/5 hover:border-slate-300 dark:hover:border-white/10 bg-white/40 dark:bg-transparent' => !in_array($level->id, $selectedLevels),
    ])>
        <div class="relative flex items-center justify-center">
            <input type="checkbox" wire:model.live="selectedLevels" value="{{ $level->id }}"
                class="peer w-5 h-5 rounded-md border-slate-300 dark:border-white/20 text-orvian-orange focus:ring-orvian-orange focus:ring-offset-0 bg-transparent transition-all" />
        </div>
        
        <div class="flex flex-col">
            <span @class([
                'text-[13px] font-bold transition-colors',
                'text-orvian-navy dark:text-white' => in_array($level->id, $selectedLevels),
                'text-slate-600 dark:text-slate-400 group-hover:text-slate-800' => !in_array($level->id, $selectedLevels),
            ])>
                {{ $level->name }}
            </span>
            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                {{ in_array($level->slug, ['primaria-primer-ciclo', 'secundaria-primer-ciclo']) ? '1ro, 2do y 3ro' : '4to, 5to y 6to' }}
            </span>
        </div>
    </label>