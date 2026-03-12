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

    protected $fillable = ['school_id', 'type'];
}