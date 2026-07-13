<div class="w-full max-w-3xl mx-auto p-4 md:p-8">

    <x-ui.page-header
        title="{{ $isEdit ? 'Editar Excusa' : 'Nueva Excusa' }}"
        description="Registra o ajusta una justificación de ausencia o tardanza."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" type="ghost" href="{{ route('app.attendance.excuses.index') }}">
                Volver
            </x-ui.button>
            <x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">
                    {{ $isEdit ? 'Guardar Cambios' : 'Registrar Excusa' }}
                </span>
                <span wire:loading wire:target="save">Guardando...</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="space-y-6">

        {{-- Card: Estudiante --}}
        <section class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
            <div class="flex items-center gap-3 mb-8 border-b border-slate-50 dark:border-dark-border pb-4">
                <div class="p-2 bg-orvian-orange/10 text-orvian-orange rounded-lg">
                    <x-heroicon-s-user class="w-6 h-6" />
                </div>
                <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Estudiante</h3>
            </div>

            <div class="flex flex-col gap-6">
                <x-ui.forms.select
                    label="Estudiante"
                    name="student_id"
                    iconLeft="heroicon-o-user"
                    wire:model.live="student_id"
                    :error="$errors->first('student_id')"
                    required
                    placeholder="Seleccione un estudiante"
                >
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}</option>
                    @endforeach
                </x-ui.forms.select>

                {{-- Vista previa grande: la secretaria debe poder identificar al
                     estudiante de un vistazo, no confundirlo con uno de nombre parecido --}}
                <div class="flex flex-col items-center gap-3 pt-2">
                    @if ($selectedStudent)
                        <div class="w-48 h-48 sm:w-56 sm:h-56 rounded-3xl overflow-hidden ring-4 ring-orvian-orange/10 shadow-lg bg-slate-100 dark:bg-dark-bg flex-shrink-0">
                            @if ($selectedStudent->photo_path)
                                <img
                                    src="{{ asset('storage/' . $selectedStudent->photo_path) }}"
                                    alt="{{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }}"
                                    class="w-full h-full object-cover"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-5xl font-black text-slate-300 dark:text-slate-600 uppercase">
                                    {{ Illuminate\Support\Str::substr($selectedStudent->first_name, 0, 1) }}{{ Illuminate\Support\Str::substr($selectedStudent->last_name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div class="text-center">
                            <p class="text-base font-bold text-slate-800 dark:text-white leading-none">
                                {{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1.5">
                                {{ $selectedStudent->full_section_name }}
                            </p>
                        </div>
                    @else
                        <div class="w-48 h-48 sm:w-56 sm:h-56 rounded-3xl bg-slate-50 dark:bg-dark-bg flex items-center justify-center border-2 border-dashed border-slate-200 dark:border-dark-border flex-shrink-0">
                            <x-heroicon-o-user class="w-16 h-16 text-slate-300" />
                        </div>
                        <p class="text-xs text-slate-400">Selecciona un estudiante para ver su foto.</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- Card: Motivo y Fechas --}}
        <section class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
            <div class="flex items-center gap-3 mb-8 border-b border-slate-50 dark:border-dark-border pb-4">
                <div class="p-2 bg-orvian-orange/10 text-orvian-orange rounded-lg">
                    <x-heroicon-s-tag class="w-6 h-6" />
                </div>
                <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Motivo de la Excusa</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <x-ui.forms.select
                    label="Motivo"
                    name="type"
                    iconLeft="heroicon-o-tag"
                    wire:model.live="type"
                    :error="$errors->first('type')"
                    required
                    placeholder=""
                >
                    @foreach (\App\Models\Tenant\AttendanceExcuse::TYPE_LABELS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.forms.select>

                @if ($type === \App\Models\Tenant\AttendanceExcuse::TYPE_MEDICAL)
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.forms.input
                            type="date"
                            label="Fecha de Inicio"
                            name="dateStart"
                            wire:model="dateStart"
                            :error="$errors->first('dateStart')"
                            required
                        />
                        <x-ui.forms.input
                            type="date"
                            label="Fecha de Fin"
                            name="dateEnd"
                            wire:model="dateEnd"
                            :error="$errors->first('dateEnd')"
                            hint="Mínimo dos días"
                            required
                        />
                    </div>
                @else
                    <div class="flex flex-col gap-3">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rango de Fechas</label>
                        <div class="flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5 dark:border-blue-500/30 dark:bg-blue-500/10">
                            <x-heroicon-s-information-circle class="h-4 w-4 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                            <p class="text-xs text-blue-900 dark:text-blue-200">
                                La excusa personal es válida automáticamente desde
                                <span class="font-bold">hoy ({{ \Carbon\Carbon::parse($dateStart)->format('d/m/Y') }})</span>
                                hasta
                                <span class="font-bold">mañana ({{ \Carbon\Carbon::parse($dateEnd)->format('d/m/Y') }})</span>.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6">
                <x-ui.forms.textarea
                    label="Motivo detallado"
                    name="reason"
                    placeholder="Explique brevemente la razón de la ausencia..."
                    wire:model="reason"
                    :error="$errors->first('reason')"
                    :rows="4"
                    required
                />
            </div>

            <div class="mt-6">
                <x-ui.forms.file-input
                    label="Archivo Adjunto"
                    name="attachment"
                    wire:model="attachment"
                    :error="$errors->first('attachment')"
                    accept=".pdf,.jpg,.jpeg,.png"
                    :hint="$type === \App\Models\Tenant\AttendanceExcuse::TYPE_MEDICAL
                        ? 'Obligatorio para excusas médicas — certificado o constancia (Max: 5MB).'
                        : 'Opcional — certificados médicos o notas de padres (Max: 5MB).'"
                />

                @if ($existingAttachmentPath && ! $attachment)
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Documento actual:
                        <a href="{{ Storage::url($existingAttachmentPath) }}" target="_blank" class="text-orvian-orange underline">
                            ver adjunto
                        </a>
                    </p>
                @endif
            </div>
        </section>
    </div>
</div>
