<?php

namespace App\Models\Tenant;

use App\Models\Geo\{District, Municipality, RegionalEducation, EducationalDistrict};
use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Academic\Level;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\Academic\TechnicalTitle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

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
        'logo_path',
        'regimen_gestion',
        'modalidad',  
        'phone',
        'address_detail',
        'latitude',
        'longitude',
        'regional_education_id',
        'educational_district_id',
        'municipality_id', 
        'province_id',
        'plan_id', 
        'is_active', 
        'is_suspended',
        'is_configured',
        'stub_expires_at'
    ];

    protected $casts = [
    'is_active'       => 'boolean', 
    'is_suspended'    => 'boolean', 
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

    /**
     * Determina si el centro puede operar normalmente.
     * Debe estar activo y NO estar suspendido.
     */
    public function isOperational(): bool
    {
        return $this->is_active && !$this->is_suspended;
    }

    /**
     * Retorna el estado semántico para la UI.
     */
    public function getStatusLabel(): string
    {
        if (!$this->is_active) return 'Inactivo';
        if ($this->is_suspended) return 'Suspendido';
        return 'Activo';
    }
    
    /**
     * Retorna el color para los badges de la UI.
     */
    public function getStatusVariant(): string
    {
        if (!$this->is_active) return 'slate';
        if ($this->is_suspended) return 'error'; // Rojo o Ámbar según tu paleta
        return 'success';
    }

    /**
     * Retorna el año académico activo de la escuela.
     * Si no hay uno marcado como activo, retorna el más reciente.
     */
    public function activeYear()
    {
        return $this->academicYears()
            ->where('is_active', true)
            ->first() 
            ?? $this->academicYears()->latest('start_date')->first();
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

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        // Usamos el string del namespace para evitar problemas de importación circular
        return $this->hasMany(\App\Models\User::class);
    }
    
    public function principal() 
    {
        return $this->hasOne(\App\Models\User::class, 'school_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('roles.name', 'School Principal')
                    // Forzamos a que coincida el school_id de la tabla de permisos 
                    // con el school_id del usuario
                    ->whereColumn('model_has_roles.school_id', 'users.school_id'); 
            });
    }

    /**
     * Relación con la Provincia (Geografía Política)
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Geo\Province::class);
    }
}