<?php

namespace App\Models\Tenant\Academic;

use App\Traits\BelongsToSchool;
use App\Models\Tenant\Academic\TechnicalTitle;
use App\Models\Tenant\School;
use App\Models\Tenant\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolSection extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'school_shift_id', 'grade_id',
        'label', 'technical_title_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean', // ← agregar
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
        
        // 1. Iniciamos con la base: "1ro Secundaria - A"
        $name = "{$gradeName} - {$sectionLabel}";

        // 2. Manejo de Título Técnico (Ocupando menos espacio)
        if ($this->technicalTitle) {
            // Priorizamos un campo 'short_name' o 'alias' si existe en tu tabla technical_titles
            // Si no existe, podrías usar una lógica de truncado o simplemente el nombre
            $techDisplay = $this->technicalTitle->short_name ?? $this->technicalTitle->name;
            $name .= " ({$techDisplay})";
        }

        // 3. Lógica de Tanda Dinámica (Holding Pool de contexto)
        // Usamos una variable estática para cachear el conteo durante la ejecución 
        // y evitar el problema de N+1 (muchas consultas en un solo request).
        static $shiftsCount = null;

        if ($shiftsCount === null && $this->school_id) {
            $shiftsCount = DB::table('school_shifts')
                ->where('school_id', $this->school_id)
                ->count();
        }

        // Solo mostramos la tanda si la escuela tiene más de una registrada
        if ($shiftsCount > 1 && $this->shift) {
            $name .= " [{$this->shift->type}]";
        }

        return $name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: lo que muestra el Index (activas, no eliminadas)
    public function scopeVisible($query)
    {
        return $query->where('is_active', true);
        // SoftDeletes aplica automáticamente whereNull('deleted_at')
    }

    // Scope: incluye historial para reportes y auditoría
    public function scopeWithHistory($query)
    {
        return $query->withTrashed();
    }

    // Scope: secciones que el wizard creó y nunca tuvieron estudiantes
    public function scopeEmpty($query)
    {
        return $query->doesntHave('students');
    }
}