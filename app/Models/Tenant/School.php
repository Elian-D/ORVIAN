<?php

namespace App\Models\Tenant;

use App\Models\Geo\{District, Municipality, RegionalEducation, EducationalDistrict};
use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Academic\Level;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\Academic\TechnicalTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class School extends Model
{
    use HasFactory;

    /** @var string REGIMEN_CONSTANTS */
    const REGIMEN_PUBLIC      = 'Público';
    const REGIMEN_PRIVATE     = 'Privado';
    const REGIMEN_SEMIOFFICIAL = 'Semioficial';

    /** @var string MODALITY_CONSTANTS */
    const MODALITY_ACADEMIC           = 'Académica';
    const MODALITY_TECHNICAL          = 'Técnico Profesional';
    const MODALITY_TECHNICAL_BACHILLER = 'Bachiller Técnico';
    const MODALITY_ARTS               = 'Modalidad en Artes';
    const MODALITY_PREPARA            = 'Prepara';
    const MODALITY_MIXED              = 'Mixto';

    /** @var string SHIFT_CONSTANTS */
    const SHIFT_EXTENDED  = 'Jornada Extendida';
    const SHIFT_MORNING   = 'Matutina';
    const SHIFT_AFTERNOON = 'Vespertina';
    const SHIFT_NIGHT     = 'Nocturna';

    protected $fillable = [
        'sigerd_code', 
        'name', 
        'regimen_gestion',
        'modalidad',  
        'phone',
        'address_detail',
        'regional_education_id',
        'educational_district_id',
        'municipality_id', 
        'plan_id', 
        'is_active', 
        'is_configured',
        'stub_expires_at'
    ];

    protected $casts = [
    'is_configured'   => 'boolean',
    'stub_expires_at' => 'datetime',
];


    /*
    |--------------------------------------------------------------------------
    | Helpers & Logic
    |--------------------------------------------------------------------------
    */

    /**
     * Retorna las modalidades con sus respectivas etiquetas y descripciones
     * para el Wizard de configuración.
     */
    public static function modalidadesDescripcion(): array
    {
        return [
            self::MODALITY_ACADEMIC => [
                'label' => 'Modalidad Académica',
                'description' => 'Formación general en ciencias y humanidades. El liceo tradicional enfocado en la preparación universitaria.'
            ],
            self::MODALITY_TECHNICAL => [
                'label' => 'Técnico-Profesional',
                'description' => 'Formación técnica especializada (Politécnico) que permite al estudiante insertarse al mercado laboral.'
            ],
            self::MODALITY_TECHNICAL_BACHILLER => [
                'label' => 'Bachiller Técnico',
                'description' => 'Enfoque técnico específico con una carga horaria orientada a la especialización técnica.'
            ],
            self::MODALITY_ARTS => [
                'label' => 'Modalidad en Artes',
                'description' => 'Desarrollo de competencias artísticas en menciones como Música, Artes Visuales, Artes Escénicas y Cine.'
            ],
            self::MODALITY_PREPARA => [
                'label' => 'PREPARA',
                'description' => 'Programa de educación a distancia para adultos o personas con sobreedad, generalmente en tandas especiales.'
            ],
            self::MODALITY_MIXED => [
                'label' => 'Modalidad Mixta',
                'description' => 'Centros educativos que ofrecen simultáneamente la Modalidad Académica y Técnico-Profesional.'
            ],
        ];
    }

    /**
     * Retorna los labels para el Régimen de Gestión.
     */
    public static function regimenesLabels(): array
    {
        return [
            self::REGIMEN_PUBLIC       => 'Público',
            self::REGIMEN_PRIVATE      => 'Privado',
            self::REGIMEN_SEMIOFFICIAL  => 'Semioficial',
        ];
    }

    /**
     * Retorna el label específico de una modalidad o el array completo.
     * * @param string|null $key
     * @return string|array
     */
    public static function modalidadLabel(?string $key = null): string|array
    {
        $labels = [
            self::MODALITY_ACADEMIC           => 'Académica (Liceo)',
            self::MODALITY_TECHNICAL          => 'Técnico-Profesional (Politécnico)',
            self::MODALITY_TECHNICAL_BACHILLER => 'Bachiller Técnico',
            self::MODALITY_ARTS               => 'Modalidad en Artes',
            self::MODALITY_PREPARA            => 'Prepara',
            self::MODALITY_MIXED              => 'Mixto (Liceo + Politécnico)',
        ];

        if ($key) {
            return $labels[$key] ?? 'No definida';
        }

        return $labels;
    }

    /**
     * Retorna los labels para las Tandas/Jornadas.
     */
    public static function jornadasLabels(): array
    {
        return [
            self::SHIFT_EXTENDED  => 'Jornada Extendida',
            self::SHIFT_MORNING   => 'Matutina',
            self::SHIFT_AFTERNOON => 'Vespertina',
            self::SHIFT_NIGHT     => 'Nocturna',
        ];
    }

    /**
     * Verifica acceso a funcionalidades según el plan SaaS.
     */
    public function canAccess(string $featureSlug): bool
    {
        return $this->plan && $this->plan->hasFeature($featureSlug);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Dentro de la clase School
    public function technicalTitles(): BelongsToMany
    {
        return $this->belongsToMany(TechnicalTitle::class, 'school_technical_titles');
    }

    /** Geografía Educativa (Estructura MINERD) */
    public function regional(): BelongsTo
    {
        return $this->belongsTo(RegionalEducation::class, 'regional_education_id');
    }

    public function educationalDistrict(): BelongsTo
    {
        return $this->belongsTo(EducationalDistrict::class, 'educational_district_id');
    }

    /** Geografía Política/Territorial */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
    /**
 * Relación con los niveles educativos de la escuela (Many-to-Many).
 * Asegúrate de tener la tabla pivote 'school_levels'.
 */
public function levels(): BelongsToMany
{
    return $this->belongsToMany(Level::class, 'school_levels');
}

/**
 * Relación con las tandas/jornadas de la escuela.
 */
public function shifts(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(SchoolShift::class);
}

/**
 * Relación con los años académicos.
 */
public function academicYears(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(AcademicYear::class);
}
}