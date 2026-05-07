<?php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\Grade;
use App\Models\Tenant\Academic\Level;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\Academic\TechnicalFamily;
use App\Models\Tenant\Academic\TechnicalTitle;
use App\Models\Tenant\School;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CourseForm extends Component
{
    // ── Estado del wizard ─────────────────────────────────────────
    public int    $step = 1;

    // Paso 1: Nivel
    public ?int   $selectedLevelId = null;

    // Paso 2: Grado
    public ?int   $selectedGradeId = null;

    // Paso 3: Tipo + Título Técnico (solo si grade->allows_technical = true)
    public string $sectionType     = 'academic';
    public ?int   $tempFamilyId    = null;
    public ?int   $tempTitleId     = null;
    public ?int   $selectedTitleId = null;   // título confirmado para crear la sección

    // Paso 4 (o 3 si no hay técnico): Paralelo + Tanda
    public string $label   = '';
    public ?int   $shiftId = null;

    // ── Computed: Escuela del usuario actual ──────────────────────

    #[Computed]
    public function school(): School
    {
        return School::find(Auth::user()->school_id);
    }

    /**
     * ¿La modalidad de la escuela requiere títulos técnicos?
     * Replica la misma lógica del BaseSchoolWizard.
     */
    #[Computed]
    public function schoolNeedsTechnical(): bool
    {
        return in_array($this->school->modalidad, [
            School::MODALITY_TECHNICAL,
            School::MODALITY_TECHNICAL_BACHILLER,
            School::MODALITY_MIXED,
            School::MODALITY_ARTS,
        ]);
    }

    // ── Computed: Pasos y progreso ────────────────────────────────

    /**
     * Número real de pasos según el grado seleccionado.
     * - Sin grado aún (o grado sin técnico): asumimos 3 para la barra inicial.
     * - Con grado que permite técnico: 4 pasos.
     *
     * IMPORTANTE: Usamos esta propiedad solo para MOSTRAR en la barra.
     * La navegación usa $step directamente.
     */
    #[Computed]
    public function totalVisualSteps(): int
    {
        // Antes de seleccionar grado no sabemos, mostramos 3 (optimista)
        if ($this->selectedGradeId === null) {
            return 3;
        }

        return $this->gradeAllowsTechnical ? 4 : 3;
    }

    /**
     * Paso visual para la barra (siempre incrementa, nunca retrocede por el salto).
     *
     * Mapa lógico → visual cuando el paso 3 se salta:
     *   step 1 → visual 1
     *   step 2 → visual 2
     *   step 4 → visual 3  (saltamos el 3 lógico, pero la barra muestra 3/3)
     */
    #[Computed]
    public function visualStep(): int
    {
        if ($this->step === 4 && ! $this->gradeAllowsTechnical) {
            return 3;
        }

        return $this->step;
    }

    #[Computed]
    public function progressPercent(): int
    {
        return (int) round(($this->visualStep / $this->totalVisualSteps) * 100);
    }

    #[Computed]
    public function stepLabel(): string
    {
        return match ($this->step) {
            1 => 'Seleccionar nivel',
            2 => 'Seleccionar grado',
            3 => 'Tipo de sección',
            4 => 'Configurar paralelo',
            default => '',
        };
    }

    // ── Computed: Datos del wizard ────────────────────────────────

    #[Computed]
    public function levels(): \Illuminate\Database\Eloquent\Collection
    {
        $schoolId = Auth::user()->school_id;

        $enabledIds = DB::table('school_levels')
            ->where('school_id', $schoolId)
            ->pluck('level_id');

        if ($enabledIds->isEmpty()) {
            return Level::with('grades')->orderBy('id')->get();
        }

        return Level::with('grades')
            ->whereIn('id', $enabledIds)
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function grades(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->selectedLevelId) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Grade::where('level_id', $this->selectedLevelId)
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function selectedGrade(): ?Grade
    {
        return $this->selectedGradeId ? Grade::find($this->selectedGradeId) : null;
    }

    #[Computed]
    public function gradeAllowsTechnical(): bool
    {
        return $this->selectedGrade?->allows_technical ?? false;
    }

    /**
     * Familias técnicas filtradas por la modalidad de la escuela.
     * Mismo patrón que BaseSchoolWizard::families().
     */
    #[Computed]
    public function families(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->schoolNeedsTechnical) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        $modality = $this->school->modalidad === School::MODALITY_ARTS
            ? TechnicalFamily::MODALITY_ARTS
            : TechnicalFamily::MODALITY_TECHNICAL;

        return TechnicalFamily::where('modality', $modality)
            ->orderBy('name')
            ->get();
    }

    /**
     * Títulos del family seleccionado temporalmente.
     * Se recarga cuando cambia $tempFamilyId.
     */
    #[Computed]
    public function titlesForFamily(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->tempFamilyId) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return TechnicalTitle::where('technical_family_id', $this->tempFamilyId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Título ya confirmado (el que se usará para crear la sección).
     */
    #[Computed]
    public function confirmedTitle(): ?TechnicalTitle
    {
        return $this->selectedTitleId
            ? TechnicalTitle::with('family')->find($this->selectedTitleId)
            : null;
    }

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    /**
     * Secciones ya existentes para el grado/tipo elegido.
     * Informativo en el paso 4 — ayuda al usuario a evitar duplicados.
     */
    #[Computed]
    public function existingSections(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->selectedGradeId) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return SchoolSection::with('shift')
            ->where('school_id', Auth::user()->school_id)
            ->where('grade_id', $this->selectedGradeId)
            ->when(
                $this->sectionType === 'technical' && $this->selectedTitleId,
                fn ($q) => $q->where('technical_title_id', $this->selectedTitleId),
                fn ($q) => $q->whereNull('technical_title_id'),
            )
            ->orderBy('label')
            ->get();
    }

    // ── Hooks ─────────────────────────────────────────────────────

    public function updatedTempFamilyId(?string $value): void
    {
        $this->tempFamilyId = $value ? (int) $value : null;
        $this->tempTitleId  = null;
        unset($this->titlesForFamily);
    }

    public function updatedTempTitleId(?string $value): void
    {
        $this->tempTitleId = $value ? (int) $value : null;
    }

    public function updatedShiftId(?string $value): void
    {
        $this->shiftId = $value ? (int) $value : null;
    }

    public function updatedSelectedGradeId(?string $value): void
    {
        $this->selectedGradeId = $value ? (int) $value : null;
        unset($this->selectedGrade, $this->gradeAllowsTechnical, $this->grades, $this->existingSections);
    }

    public function updatedSelectedLevelId(?string $value): void
    {
        $this->selectedLevelId = $value ? (int) $value : null;
        $this->selectedGradeId = null;
        unset($this->grades, $this->selectedGrade, $this->gradeAllowsTechnical);
    }

    public function updatedSectionType(): void
    {
        // Al cambiar de técnico a académico, limpiar selección de título
        if ($this->sectionType === 'academic') {
            $this->tempFamilyId    = null;
            $this->tempTitleId     = null;
            $this->selectedTitleId = null;
        }
    }

    // ── Navegación ────────────────────────────────────────────────

    public function nextStep(): void
    {
        $this->validateCurrentStep();

        // Saltar paso 3 si el grado no admite técnico
        if ($this->step === 2 && ! $this->gradeAllowsTechnical) {
            $this->sectionType     = 'academic';
            $this->selectedTitleId = null;
            $this->step            = 4;
            return;
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        if ($this->step <= 1) {
            return;
        }

        // Si el paso 3 fue saltado, al retroceder desde 4 volver al 2
        if ($this->step === 4 && ! $this->gradeAllowsTechnical) {
            $this->step = 2;
            return;
        }

        $this->step--;
    }

    protected function validateCurrentStep(): void
    {
        match ($this->step) {
            1 => $this->validate([
                'selectedLevelId' => 'required|integer|exists:levels,id',
            ]),
            2 => $this->validate([
                'selectedGradeId' => 'required|integer|exists:grades,id',
            ]),
            3 => $this->validateStep3(),
            4 => $this->validate([
                'label'   => 'required|string|max:10',
                'shiftId' => 'required|integer|exists:school_shifts,id',
            ]),
        };
    }

    protected function validateStep3(): void
    {
        $this->validate([
            'sectionType'     => 'required|in:academic,technical',
            'selectedTitleId' => 'nullable|required_if:sectionType,technical|exists:technical_titles,id',
        ], [
            'selectedTitleId.required_if' => 'Debes seleccionar y confirmar un título técnico.',
            'selectedTitleId.exists'      => 'El título seleccionado no es válido.',
        ]);
    }

    /**
     * Confirma la selección de un título técnico.
     * Separado de nextStep() para que el usuario pueda revisar antes de continuar.
     */
    public function confirmTitle(): void
    {
        // Castear explícitamente antes de validar — Livewire entrega strings del select
        $this->tempTitleId = $this->tempTitleId ? (int) $this->tempTitleId : null;

        $this->validate([
            'tempTitleId' => 'required|integer|min:1|exists:technical_titles,id',
        ], [
            'tempTitleId.required' => 'Selecciona un título antes de confirmar.',
            'tempTitleId.exists'   => 'El título seleccionado no es válido.',
        ]);

        $this->selectedTitleId = $this->tempTitleId;

        // Invalidar computed que dependen del selectedTitleId
        unset($this->confirmedTitle);
        unset($this->existingSections);
    }

    public function clearTitle(): void
    {
        $this->selectedTitleId = null;
        $this->tempFamilyId    = null;
        $this->tempTitleId     = null;
        unset($this->confirmedTitle, $this->titlesForFamily, $this->existingSections);
    }

    // ── Crear sección ─────────────────────────────────────────────

    public function create(): void
    {
        $this->shiftId = $this->shiftId ? (int) $this->shiftId : null;

        $this->validateCurrentStep(); // paso 4

        $label    = strtoupper(trim($this->label));
        $schoolId = Auth::user()->school_id;
        $titleId  = $this->sectionType === 'technical' ? $this->selectedTitleId : null;

        $exists = SchoolSection::where('school_id', $schoolId)
            ->where('grade_id', $this->selectedGradeId)
            ->where('label', $label)
            ->where('school_shift_id', $this->shiftId)
            ->where('technical_title_id', $titleId)
            ->exists();

        if ($exists) {
            $this->addError('label', 'Ya existe una sección con ese paralelo, tanda y tipo para este grado.');
            return;
        }

        $section = SchoolSection::create([
            'school_id'          => $schoolId,
            'grade_id'           => $this->selectedGradeId,
            'school_shift_id'    => $this->shiftId,
            'label'              => $label,
            'technical_title_id' => $titleId,
            'is_active'          => true,
        ]);

        $this->dispatch(
            'notify-redirect',
            type: 'success',
            message: "Sección {$section->full_label} creada correctamente."
        );

        $this->redirect(route('app.academic.courses.index'));
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.academic.course-form');

        return $view->layout('layouts.app-module', config('modules.academico'));
    }
}