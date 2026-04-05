<?php

namespace App\Models\Tenant\Academic;

use App\Traits\BelongsToSchool;
use App\Models\Tenant\Academic\TechnicalTitle;
use App\Models\Tenant\School;
use App\Models\Tenant\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSection extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'school_shift_id', // ← NUEVO
        'grade_id',
        'label',
        'technical_title_id',
    ];


    // Relaciones
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function shift() // ← NUEVA RELACIÓN
    {
        return $this->belongsTo(SchoolShift::class, 'school_shift_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function technicalTitle()
    {
        return $this->belongsTo(TechnicalTitle::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    // Scopes
    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('school_shift_id', $shiftId);
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'shift:id,type,start_time,end_time',
            'grade:id,name,level_id,cycle',
            'grade.level:id,name',
            'technicalTitle:id,name,code',
            'technicalTitle.family:id,name',
        ]);
    }

    public function getFullLabelAttribute(): string
    {
        $gradeName = $this->grade ? $this->grade->name : 'Sin Grado';
        $sectionLabel = $this->label ?? 'Sin Letra';
        
        $name = "{$gradeName} - {$sectionLabel}";

        if ($this->technicalTitle) {
            $name .= " ({$this->technicalTitle->name})";
        }

        // Corregimos la lógica: Si hay un turno y no es Jornada Extendida, mostrarlo
        if ($this->shift && $this->shift->type !== 'Jornada Extendida') {
            $name .= " [{$this->shift->type}]";
        }

        return $name;
    }
}