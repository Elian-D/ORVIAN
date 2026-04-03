<div>
    <x-app.module-toolbar>
        <x-slot:title>
            Pase de Lista — {{ $assignment?->subject->name }}
            <span class="text-sm font-normal text-slate-500">
                {{ $assignment?->section->label }} · {{ \Carbon\Carbon::parse($attendanceDate)->isoFormat('D MMM YYYY') }}
            </span>
        </x-slot:title>
    </x-app.module-toolbar>

    @if ($submitted)
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg mb-4 dark:bg-green-900/20 dark:border-green-800">
            <p class="text-green-700 dark:text-green-300 font-medium">
                ✅ Pase de lista guardado: {{ $submitResult['recorded'] }} registros.
                @if ($submitResult['skipped'] > 0)
                    <span class="text-amber-600">⚠ {{ $submitResult['skipped'] }} omitidos.</span>
                @endif
            </p>
        </div>
    @endif

    <div class="space-y-2">
        @foreach ($assignment?->section->students ?? [] as $student)
            @php
                $isExcused = in_array($student->id, $excusedStudentIds);
                $current   = $statuses[$student->id] ?? 'present';
            @endphp

            <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700
                        {{ $isExcused ? 'opacity-75 bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-700' : '' }}">

                {{-- Avatar + Nombre --}}
                <div class="flex items-center gap-3">
                    dasdasd
                    <x-ui.student-avatar :student="$student" size="sm" />
                    <div>
                        <p class="font-medium text-slate-800 dark:text-slate-100 text-sm">
                            {{ $student->full_name }}
                        </p>
                        @if ($isExcused)
                            <p class="text-xs text-amber-600 dark:text-amber-400">
                                🗒 Excusa aprobada para hoy
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Botones de estado --}}
                <div class="flex gap-1">
                    @foreach (\App\Models\Tenant\ClassroomAttendanceRecord::STATUS_LABELS as $value => $label)
                        <button
                            wire:click="setStatus({{ $student->id }}, '{{ $value }}')"
                            @disabled($isExcused && $value === 'present')
                            class="px-2.5 py-1 rounded text-xs font-medium transition-all
                                {{ $current === $value
                                    ? match($value) {
                                        'present' => 'bg-green-500 text-white',
                                        'absent'  => 'bg-red-500 text-white',
                                        'late'    => 'bg-amber-500 text-white',
                                        'excused' => 'bg-blue-500 text-white',
                                        default   => 'bg-slate-500 text-white'
                                    }
                                    : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}
                                {{ ($isExcused && $value === 'present') ? 'cursor-not-allowed opacity-40' : '' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if (! $submitted)
        <div class="mt-6 flex justify-end">
            <x-ui.button wire:click="submitAttendance" variant="primary">
                Guardar Pase de Lista
            </x-ui.button>
        </div>
    @endif
</div>