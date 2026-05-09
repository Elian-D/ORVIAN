{{-- resources/views/components/academic/course-card.blade.php --}}
@php
    $isTech = $type === 'technical';
    $baseBorder = $isTech 
        ? 'border-l-orvian-orange border-t-slate-200 border-r-slate-200 border-b-slate-200 dark:border-t-dark-border dark:border-r-dark-border dark:border-b-dark-border dark:border-l-orvian-orange' 
        : 'border-slate-200 dark:border-dark-border';
@endphp

<div {{ $attributes->merge(['class' => "bg-white dark:bg-dark-card rounded-2xl border {$baseBorder} shadow-sm transition-all hover:shadow-md border-l-[3px] p-4 flex flex-col h-full"]) }}>
    
    {{-- Header de la Card --}}
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">
                {{ $grade['name'] }}
            </h3>
            
            @if($isTech && $techGroup)
                <p class="text-[10px] font-bold uppercase tracking-widest text-orvian-orange mt-0.5 line-clamp-1" 
                   title="{{ $techGroup['title'] }}">
                    {{ $techGroup['title'] }}
                </p>
            @else
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mt-0.5">
                    General / Académico
                </p>
            @endif
        </div>
        <div class="flex-shrink-0">
            @if($isTech)
                <div class="w-8 h-8 rounded-xl bg-orvian-orange/10 flex items-center justify-center">
                    <x-heroicon-s-wrench-screwdriver class="w-4 h-4 text-orvian-orange" />
                </div>
            @else
                <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-white/5 flex items-center justify-center">
                    <x-heroicon-s-book-open class="w-4 h-4 text-slate-400 dark:text-slate-500" />
                </div>
            @endif
        </div>
    </div>

    {{-- Lista de Burbujas (Secciones) --}}
    <div class="flex flex-wrap gap-2 mt-auto">
        @forelse($sections as $section)
            <div class="group relative flex items-center gap-2 px-2.5 py-1.5 rounded-lg border transition-all
                {{ $section->is_active 
                    ? 'bg-slate-50 dark:bg-white/5 border-slate-200 dark:border-white/10 hover:border-orvian-orange/50 dark:hover:border-orvian-orange/50' 
                    : 'bg-slate-50/50 dark:bg-white/5 border-dashed border-slate-200 dark:border-white/10 opacity-60' }}">
                
                {{-- Link Principal de la Sección --}}
                <a href="{{ route('app.academic.courses.show', $section->id) }}" 
                   class="flex items-center gap-2 cursor-pointer">
                    <span class="text-xs font-black {{ $section->is_active ? 'text-orvian-navy dark:text-white' : 'text-slate-500' }}">
                        {{ $section->label }}
                    </span>
                    
                    {{-- Contador de Estudiantes Activos --}}
                    <div @class([
                        'flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold transition-colors',
                        'bg-slate-200/50 text-slate-600 dark:bg-white/10 dark:text-slate-400' => $section->is_active,
                        'bg-slate-100 text-slate-400' => !$section->is_active
                    ])>
                        <x-heroicon-s-users class="w-2.5 h-2.5" />
                        {{ count($section->students) }}
                    </div>
                </a>

                {{-- Acciones (Hover) Solo cuando NO hay estudiantes en la sección--}}
                @if(count($section->students) === 0)
                    <div class="absolute -top-3 -right-2 hidden group-hover:flex items-center gap-1 bg-white dark:bg-dark-card shadow-sm border border-slate-200 dark:border-dark-border rounded-md px-1 py-0.5 z-10">
                        <button wire:click="toggleSectionStatus({{ $section->id }})" 
                                class="p-1 rounded text-slate-400 hover:text-orvian-navy dark:hover:text-white transition-colors"
                                title="{{ $section->is_active ? 'Desactivar curso' : 'Activar curso' }}">
                            @if($section->is_active)
                                <x-heroicon-o-eye-slash class="w-3 h-3" />
                            @else
                                <x-heroicon-o-eye class="w-3 h-3" />
                            @endif
                        </button>
                    
                    {{-- Cambiado students_count por count($section->students) para consistencia --}}
                        <button wire:click="confirmDelete({{ $section->id }})" 
                                class="p-1 rounded text-slate-400 hover:text-red-500 transition-colors"
                                title="Eliminar curso vacío">
                            <x-heroicon-o-trash class="w-3 h-3" />
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <span class="text-xs text-slate-400 italic py-1">Sin secciones activas</span>
        @endforelse
    </div>
</div>