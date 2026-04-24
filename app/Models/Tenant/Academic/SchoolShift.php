<?php

namespace App\Models\Tenant\Academic;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SchoolShift extends Model
{
    use BelongsToSchool;

    const TYPE_MORNING   = 'Matutina';
    const TYPE_AFTERNOON = 'Vespertina';
    const TYPE_EXTENDED  = 'Jornada Extendida';
    const TYPE_NIGHT     = 'Nocturna';

    protected $fillable = [
        'school_id',
        'type',
        'start_time',
        'end_time',
    ];

    /**
     * Al castear a 'datetime:H:i', Laravel nos devuelve 
     * un objeto Carbon cuando accedemos a start_time.
     */
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
    ];

    // Relación inversa
    public function sections()
    {
        return $this->hasMany(SchoolSection::class);
    }

    // Scope útil
    public function scopeWithSectionCount($query)
    {
        return $query->withCount('sections');
    }
}