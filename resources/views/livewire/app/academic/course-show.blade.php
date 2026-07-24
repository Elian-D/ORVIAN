{{-- resources/views/livewire/app/academic/course-show.blade.php --}}
<div>
    <div class="p-4 md:p-6 space-y-6">

        <x-ui.page-header title="{{ $section->full_label }}">
            <x-slot:actions>
                <x-ui.button href="{{ route('app.academic.courses.index') }}"
                    type="ghost" size="sm" iconLeft="heroicon-o-arrow-left">
                    Volver
                </x-ui.button>
                @if(!$isEditing)
                    <x-ui.button wire:click="startEdit"
                        variant="secondary" size="sm" iconLeft="heroicon-o-pencil">
                        Editar
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Header de la sección --}}
        <div class="bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-dark-border p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center
                                {{ $section->technicalTitle
                                    ? 'bg-orvian-orange/10 dark:bg-orvian-orange/12'
                                    : 'bg-slate-100 dark:bg-dark-border' }}">
                        @if($section->technicalTitle)
                            <x-heroicon-o-cog-6-tooth class="w-7 h-7 text-orvian-orange" />
                        @else
                            <x-heroicon-o-academic-cap class="w-7 h-7 text-slate-400 dark:text-slate-300" />
                        @endif
                    </div>
                    <div>
                        @if($isEditing)
                            {{-- Formulario de edición --}}
                            <div class="flex items-center gap-3">
                                <x-ui.forms.input
                                    name="editingLabel"
                                    wire:model="editingLabel"
                                    placeholder="Ej: A"
                                    :error="$errors->first('editingLabel')"
                                    size="sm" />
                                <x-ui.forms.select
                                    name="editingShiftId"
                                    wire:model="editingShiftId"
                                    :error="$errors->first('editingShiftId')"
                                    size="sm">
                                    @foreach($this->shifts as $shift)
                                        <option value="{{ $shift->id }}">{{ $shift->type }}</option>
                                    @endforeach
                                </x-ui.forms.select>
                            </div>
                            <div class="flex gap-2 mt-2">
                                <x-ui.button wire:click="saveEdit" variant="primary" size="sm">
                                    Guardar
                                </x-ui.button>
                                <x-ui.button wire:click="cancelEdit" variant="ghost" size="sm">
                                    Cancelar
                                </x-ui.button>
                            </div>
                        @else
                            <h1 class="text-xl font-black text-slate-900 dark:text-white">
                                {{ $section->full_label }}
                            </h1>
                            <div class="flex items-center gap-2 mt-1">
                                @if($section->technicalTitle)
                                    <span class="text-xs font-semibold text-orvian-orange/70">
                                        {{ $section->technicalTitle->family?->name }}
                                    </span>
                                    <span class="text-slate-300 dark:text-slate-700">·</span>
                                @endif
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ $section->shift?->type ?? 'Sin tanda' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Badge de Estado --}}
                    <x-ui.badge 
                        :variant="$section->is_active ? 'success' : 'error'" 
                        size="sm"
                        class="font-bold uppercase tracking-widest text-[10px]">
                        {{ $section->is_active ? 'Activa' : 'Inactiva' }}
                    </x-ui.badge>

                    {{-- Botón de Acción Minimalista (Ghost) --}}
                    <x-ui.button 
                        wire:click="toggleStatus"
                        variant="{{ $section->is_active ? 'error' : 'success' }}" 
                        type="ghost" 
                        size="sm"
                        :iconLeft="$section->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-arrow-path'">
                        {{ $section->is_active ? 'Desactivar' : 'Reactivar' }}
                    </x-ui.button>
                </div>
            </div>

            {{-- Stats rápidas --}}
            <div class="grid grid-cols-3 gap-3 mt-6">
                @foreach([
                    ['label' => 'Total', 'value' => $this->stats['total']],
                    ['label' => 'Activos', 'value' => $this->stats['active']],
                    ['label' => 'Inactivos', 'value' => $this->stats['inactive']],
                ] as $stat)
                    <div class="rounded-xl p-3 bg-slate-50 dark:bg-dark-border
                                border border-slate-100 dark:border-dark-border text-center">
                        <p class="text-lg font-black text-slate-800 dark:text-white">
                            {{ $stat['value'] }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-wider mt-0.5
                                   text-slate-400 dark:text-slate-600">
                            {{ $stat['label'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Lista de estudiantes --}}
        <div class="bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-dark-border">
            <div class="p-5 border-b border-slate-100 dark:border-dark-border">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                    Estudiantes de esta sección
                </h3>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-dark-border">
                @forelse($this->students as $student)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <x-ui.student-avatar :student="$student" size="sm" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                                {{ $student->full_name }}
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                {{ $student->rnc ?? 'Sin cédula' }}
                            </p>
                        </div>
                        <x-ui.button
                            href="{{ route('app.academic.students.show', $student) }}"
                            type="ghost" size="sm" iconLeft="heroicon-o-eye">
                        </x-ui.button>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <p class="text-sm text-slate-400 dark:text-slate-600">
                            No hay estudiantes en esta sección.
                        </p>
                        <x-ui.button
                            href="{{ route('app.academic.enrollment-hub') }}"
                            type="ghost" size="sm" class="mt-3">
                            Asignar desde el Hub de Matriculación
                        </x-ui.button>
                    </div>
                @endforelse
            </div>
            @if($this->students->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-dark-border">
                    {{ $this->students->links('pagination.orvian-compact') }}
                </div>
            @endif
        </div>
    </div>
</div>