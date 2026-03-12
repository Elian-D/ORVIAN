<?php

namespace App\Models\Tenant\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalFamily extends Model
{
    // Constantes para evitar errores de redacción en la base de datos
    const MODALITY_TECHNICAL = 'Técnico Profesional';
    const MODALITY_ARTS      = 'Modalidad en Artes';

    protected $fillable = ['code', 'name', 'modality', 'ordenance'];

    public function titles(): HasMany
    {
        return $this->hasMany(TechnicalTitle::class);
    }
}