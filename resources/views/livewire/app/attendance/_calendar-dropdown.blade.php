<div x-show="open" @click.outside="open = false"
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
     class="absolute top-full left-0 mt-2 z-50 w-72 bg-white dark:bg-dark-card rounded-2xl border border-slate-200 dark:border-dark-border shadow-xl p-4"
     style="display: none;">

    <div class="flex items-center justify-between mb-4">
        <button @click="prevMonth()" type="button" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
            <x-heroicon-s-chevron-left class="w-4 h-4" />
        </button>
        <span x-text="monthLabel" class="text-sm font-bold text-slate-800 dark:text-white capitalize"></span>
        <button @click="nextMonth()" type="button" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
            <x-heroicon-s-chevron-right class="w-4 h-4" />
        </button>
    </div>

    <div class="grid grid-cols-7 gap-1 mb-1">
        @foreach(['L','M','X','J','V','S','D'] as $day)
            <div class="text-center text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase">{{ $day }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-1">
        <template x-for="day in days" :key="day.date">
            <button @click="selectDate(day.date)" type="button"
                :class="{
                    'relative aspect-square flex items-center justify-center rounded-lg text-xs font-bold transition-all': true,
                    'opacity-25 pointer-events-none': !day.curr,
                    'bg-orvian-orange text-white shadow-md': day.sel,
                    'ring-2 ring-orvian-orange ring-offset-1 dark:ring-offset-dark-card text-orvian-orange': day.today && !day.sel,
                    'hover:bg-slate-100 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300': !day.sel && day.curr,
                }" x-text="day.day">
            </button>
        </template>
    </div>
</div>
