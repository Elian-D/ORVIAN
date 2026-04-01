<?php

namespace App\Livewire\Tenant;

use App\Models\Geo\EducationalDistrict;
use App\Models\Geo\RegionalEducation;
use App\Models\Geo\Municipality;
use App\Models\Geo\Province;
use App\Models\Tenant\Academic\Level;
use App\Models\Tenant\Academic\TechnicalFamily;
use App\Models\Tenant\Academic\TechnicalTitle;
use App\Models\Tenant\Plan;
use App\Models\Tenant\School;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Clase base para los wizards de configuración de escuela.
 *
 * No se registra como ruta directamente. Sus hijos la extienden:
 *   - SchoolWizard       → /setup   (Owner crea escuela + Director)         totalSteps = 5
 *   - TenantSetupWizard  → /wizard  (Principal configura su escuela stub)   totalSteps = 4
 */
abstract class BaseSchoolWizard extends Component
{
    public int  $step       = 1;
    public int  $totalSteps = 5;

    /** Pantalla de bienvenida antes del paso 1. */
    public bool $showIntro = true;

    // ── Paso 1: Identidad ──────────────────────────────────────────
    public string $sigerd_code     = '';
    public string $name            = '';
    public string $regimen_gestion = School::REGIMEN_PUBLIC;
    public string $modalidad       = School::MODALITY_ACADEMIC;

    // ── Paso 2: Ubicación ──────────────────────────────────────────
    public string $regional_education_id   = '';
    public string $educational_district_id = '';
    public ?int   $province_id             = null;
    public ?int   $municipality_id         = null;
    public string $address                 = '';
    public string $address_number          = '';
    public string $neighborhood            = '';
    public string $address_reference       = '';
    public string $phone                   = '';

    // ── Paso 3: Académico ──────────────────────────────────────────
    public array  $selectedLevels  = [];
    public array  $selectedShifts  = [];
    public string $year_name       = '';
    public string $start_date      = '';
    public string $end_date        = '';
    public ?int   $temp_family_id  = null;
    public ?int   $temp_title_id   = null;
    public array  $selectedTitles  = [];
    public bool  $needsTitles   = false;
    public array  $titleOptions    = [];
    public array $selectedSectionLabels = [];

    // ── Paso 4: Plan ───────────────────────────────────────────────
    public ?int $plan_id       = null;
    public bool $billingAnnual = false;

    // ── Pantalla de carga ──────────────────────────────────────────
    public bool $isProcessing = false;

    // ── Propiedades Computadas ─────────────────────────────────────

    #[Computed]
    public function regionalEducations()
    {
        return RegionalEducation::orderBy('id')->get();
    }

    #[Computed]
    public function educationalDistricts()
    {
        if (!$this->regional_education_id) return collect();
        return EducationalDistrict::where('regional_education_id', $this->regional_education_id)
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function provinces()
    {
        return Province::orderBy('name')->get();
    }

    #[Computed]
    public function municipalities()
    {
        if (!$this->province_id) return collect();
        return Municipality::where('province_id', $this->province_id)->orderBy('name')->get();
    }

    #[Computed]
    public function levels()
    {
        return Level::orderBy('id')->get();
    }

    #[Computed]
    public function shifts(): array
    {
        return [
            School::SHIFT_MORNING   => 'Matutina',
            School::SHIFT_AFTERNOON => 'Vespertina',
            School::SHIFT_EXTENDED  => 'Jornada Extendida',
            School::SHIFT_NIGHT     => 'Nocturna',
        ];
    }

    #[Computed]
    public function families()
    {
        if (!$this->modalityNeedsTechnical()) return collect();
        $group = $this->modalidad === School::MODALITY_ARTS
            ? TechnicalFamily::MODALITY_ARTS
            : TechnicalFamily::MODALITY_TECHNICAL;
        return TechnicalFamily::where('modality', $group)->orderBy('name')->get();
    }

    private function loadTitleOptions(): void
    {
        if (!$this->temp_family_id) {
            $this->titleOptions = [];
            return;
        }

        $this->titleOptions = TechnicalTitle::where('technical_family_id', (int) $this->temp_family_id)
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id'   => $t->id,
                'name' => $t->name,
                'code' => $t->code,
            ])
            ->toArray();
    }

    #[Computed]
    public function displayTitles()
    {
        if (empty($this->selectedTitles)) return collect();
        return TechnicalTitle::with('family')->whereIn('id', $this->selectedTitles)->get();
    }

    #[Computed]
    public function plans()
    {
        return Plan::with('features')->where('is_active', true)->get();
    }

    #[Computed]
    public function selectedPlan()
    {
        return $this->plan_id ? $this->plans->firstWhere('id', $this->plan_id) : null;
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function regimenesLabels(): array
    {
        return [
            School::REGIMEN_PUBLIC       => 'Público',
            School::REGIMEN_PRIVATE      => 'Privado',
            School::REGIMEN_SEMIOFFICIAL => 'Semioficial',
        ];
    }

    public function modalidadesDescripcion(): array
    {
        return [
            School::MODALITY_ACADEMIC            => ['label' => 'Académica',             'description' => 'Centro académico de bachillerato general.'],
            School::MODALITY_TECHNICAL           => ['label' => 'Técnico Profesional',       'description' => 'Centro técnico-profesional con títulos MINERD.'],
            School::MODALITY_TECHNICAL_BACHILLER => ['label' => 'Bachiller Técnico', 'description' => 'Centro con bachilleratos técnicos específicos.'],
            School::MODALITY_ARTS                => ['label' => 'Modalidad en Artes',             'description' => 'Bachillerato en artes: visual, música, teatro, danza y más.'],
            School::MODALITY_PREPARA             => ['label' => 'PREPARA',           'description' => 'Modalidad de preparación vocacional y técnica básica.'],
            School::MODALITY_MIXED               => ['label' => 'Mixto',             'description' => 'Combina bachillerato académico y técnico-profesional.'],
        ];
    }

    public function modalidadLabel(): string
    {
        return $this->modalidadesDescripcion()[$this->modalidad]['label'] ?? $this->modalidad;
    }

    public function modalityNeedsTechnical(): bool
    {
        return in_array($this->modalidad, [
            School::MODALITY_TECHNICAL,
            School::MODALITY_TECHNICAL_BACHILLER,
            School::MODALITY_MIXED,
            School::MODALITY_ARTS,
        ]);
    }

    // Constante helper (array PHP, no tabla de BD)
    public function availableSectionLabels(): array
    {
        return ['A','B','C','D','E','F','G','H','I','J','K','L'];
    }

    // ── Hooks ──────────────────────────────────────────────────────

    public function updatedRegionalEducationId(): void
    {
        $this->educational_district_id = '';
        unset($this->educationalDistricts);
    }

    public function updatedProvinceId(): void
    {
        $this->municipality_id = null;
        unset($this->municipalities);
    }

    public function updatedModalidad(): void
    {
        $this->needsTitles    = $this->modalityNeedsTechnical();
        $this->selectedTitles = [];
        $this->temp_family_id = null;
        $this->temp_title_id  = null;
        $this->titleOptions   = [];
        unset($this->families);
        unset($this->displayTitles);
    }

    public function updatedTempFamilyId(): void
    {
        $this->temp_title_id = null;
        $this->loadTitleOptions(); // ← reemplaza el unset()
    }

    // ── Acciones de intro ──────────────────────────────────────────

    public function startWizard(): void
    {
        $this->showIntro = false;
    }

    // ── Navegación ─────────────────────────────────────────────────

    public function nextStep(): void
    {
        $this->validateStep($this->step);
        $this->step++;
    }

    public function prevStep(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->step) $this->step = $step;
    }

    // ── Títulos ────────────────────────────────────────────────────

    public function addTitle(): void
    {
        if ($this->temp_title_id && !in_array($this->temp_title_id, $this->selectedTitles)) {
            $this->selectedTitles[] = (int) $this->temp_title_id;
        }
        $this->temp_title_id = null;
    }

    public function removeTitle(int $titleId): void
    {
        $this->selectedTitles = array_values(array_diff($this->selectedTitles, [$titleId]));
        unset($this->displayTitles);
    }

    // ── Validaciones ───────────────────────────────────────────────

    protected function sigerdUniqueRule()
    {
        return Rule::unique('schools', 'sigerd_code');
    }

    protected function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validate([
                'sigerd_code'     => ['required', 'string', 'max:20', $this->sigerdUniqueRule()],
                'name'            => ['required', 'string', 'min:5', 'max:255'],
                'regimen_gestion' => ['required', Rule::in(array_keys($this->regimenesLabels()))],
                'modalidad'       => ['required', Rule::in(array_keys($this->modalidadesDescripcion()))],
            ], [
                'sigerd_code.unique' => 'Ya existe una escuela con este código SIGERD.',
            ]),
            2 => $this->validate([
                'regional_education_id'   => ['required'],
                'educational_district_id' => ['required'],
                'province_id'             => ['required', 'exists:provinces,id'],
                'municipality_id'         => ['required', 'exists:municipalities,id'],
            ]),
            3 => $this->validate(
                array_merge(
                    [
                        'selectedLevels' => array_filter([
                            'required',
                            'array',
                            'min:1',
                            // Si la modalidad necesita técnicos, Secundaria es obligatoria
                            $this->modalityNeedsTechnical()
                                ? function (string $attribute, mixed $value, \Closure $fail): void {
                                    $secundariaId = \App\Models\Tenant\Academic\Level::where('slug', 'secundaria-segundo-ciclo')->value('id');
                                    if (! in_array((int) $secundariaId, array_map('intval', $value))) {
                                        $fail('Las modalidades técnicas requieren el nivel Secundaria, ya que los títulos se imparten en 4to, 5to y 6to (Segundo Ciclo).');
                                    }
                                }
                                : null,
                        ]),
                        'selectedSectionLabels' => ['required', 'array', 'min:1'],
                        'selectedShifts'        => ['required', 'array', 'min:1'],
                        // Nuevas reglas para Fechas:
                        'start_date' => [
                            'required', 
                            'date',
                            function ($attribute, $value, $fail) {
                                $limits = $this->getAcademicYearLimits();
                                if ($value < $limits['min'] || $value > $limits['max']) {
                                    $fail("La fecha de inicio debe estar dentro del año escolar ({$limits['min']} a {$limits['max']}).");
                                }
                            }
                        ],
                        'end_date' => [
                            'required', 
                            'date', 
                            'after:start_date',
                            function ($attribute, $value, $fail) {
                                $limits = $this->getAcademicYearLimits();
                                if ($value < $limits['min'] || $value > $limits['max']) {
                                    $fail("La fecha de cierre debe estar dentro del año escolar ({$limits['min']} a {$limits['max']}).");
                                }
                            }
                        ],
                    ],
                    $this->modalityNeedsTechnical()
                        ? ['selectedTitles' => ['required', 'array', 'min:1']]
                        : []
                ),
                
                [
                    'selectedLevels.required'        => 'Debes seleccionar al menos un nivel educativo.',
                    'selectedSectionLabels.required' => 'Debes elegir al menos una sección (ej. A).',
                    'selectedTitles.required'        => 'Esta modalidad requiere al menos un título técnico.',
                    'end_date.after'                 => 'La fecha de cierre debe ser posterior a la de inicio.',
                ]
            ),
            4 => $this->validate(['plan_id' => ['required', 'exists:plans,id']]),
            default => null,
        };
    }

    // ── Ensambladores de Datos ─────────────────────────────────────

    protected function schoolPayload(): array
    {
        return [
            'sigerd_code'             => $this->sigerd_code,
            'name'                    => $this->name,
            'regimen_gestion'         => $this->regimen_gestion,
            'modalidad'               => $this->modalidad,
            'regional_education_id'   => $this->regional_education_id,
            'educational_district_id' => $this->educational_district_id,
            'municipality_id'         => $this->municipality_id,
            'province_id'             => $this->province_id,
            'phone'                   => $this->phone,
            'address_detail'          => implode(', ', array_filter([
                $this->address,
                $this->address_number,
                $this->neighborhood,
                $this->address_reference,
            ])),
        ];
    }

    /**
     * Calcula los límites del año escolar actual basados en la fecha del servidor.
     */
    protected function getAcademicYearLimits(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Si estamos en Agosto (8) o posterior, el año escolar empieza este año.
        // Si estamos antes de Agosto, el año escolar empezó el año pasado.
        $startYear = $currentMonth >= 8 ? $currentYear : $currentYear - 1;
        $endYear = $startYear + 1;
        
        return [
            'name' => "{$startYear}-{$endYear}",
            'min'  => "{$startYear}-08-01",
            'max'  => "{$endYear}-07-31",
        ];
    }

    /**
     * Sobrescribimos el payload académico para inyectar el nombre del año calculado.
     */
    protected function academicPayload(): array
    {
        // Obtenemos el nombre calculado en backend, ignorando cualquier cosa que pudiera estar en $this->year_name
        $computedYearName = $this->getAcademicYearLimits()['name'];

        return [
            'level_ids'      => $this->selectedLevels,
            'section_labels' => $this->selectedSectionLabels,
            'shift_ids'      => $this->selectedShifts,
            'title_ids'      => $this->selectedTitles,
            'year_name'      => $computedYearName, // Asignación fuerte desde el backend
            'start_date'     => $this->start_date,
            'end_date'       => $this->end_date,
        ];
    }
}