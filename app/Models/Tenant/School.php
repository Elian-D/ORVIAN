<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use App\Models\Geo\District;
use App\Models\Geo\Municipality;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class School extends Model
{
    // Constantes de Modalidad
    const MODALITY_ACADEMIC = 'Académica';
    const MODALITY_TECHNICAL = 'Técnico-Profesional';
    const MODALITY_ARTS = 'Artes';
    const MODALITY_PREPARA = 'Prepara';

    // Constantes de Sector
    const SECTOR_PUBLIC = 'Público';
    const SECTOR_PRIVATE = 'Privado';

    // Constantes de Jornada
    const SHIFT_EXTENDED = 'Jornada Extendida';
    const SHIFT_MORNING = 'Matutina';
    const SHIFT_AFTERNOON = 'Vespertina';
    const SHIFT_NIGHT = 'Nocturna';

    protected $fillable = [
        'sigerd_code', 'name', 'modalidad', 'sector', 
        'jornada', 'district_id', 'municipality_id', 
        'plan_id', 'is_active', 'is_configured'
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}