<?php

namespace App\Models\Tenant\Academic;

use App\Models\Tenant\Teacher;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant\ClassroomAttendanceRecord;
use Illuminate\Database\Eloquent\Relations\HasMany;


class TeacherSubjectSection extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'subject_id',
        'school_section_id',
        'academic_year_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * El maestro asignado.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * La materia/asignatura.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * La sección (Grado + Aula).
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(SchoolSection::class, 'school_section_id');
    }

    /**
     * El periodo escolar de la asignación.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }


    /**
     * Registros de asistencia vinculados a esta asignación específica.
     */
    public function classroomAttendanceRecords(): HasMany
    {
        return $this->hasMany(ClassroomAttendanceRecord::class, 'teacher_subject_section_id');
    }
}