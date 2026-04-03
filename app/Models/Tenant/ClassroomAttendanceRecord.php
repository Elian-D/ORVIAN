<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Scopes\SchoolScope;
use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ClassroomAttendanceRecord extends Model
{
    use BelongsToSchool;

    // ── Constantes de Estado ──────────────────────────────────────
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT  = 'absent';
    public const STATUS_LATE    = 'late';
    public const STATUS_EXCUSED = 'excused';

    public const STATUS_LABELS = [
        self::STATUS_PRESENT => 'Presente',
        self::STATUS_ABSENT  => 'Ausente',
        self::STATUS_LATE    => 'Tardanza',
        self::STATUS_EXCUSED => 'Justificado',
    ];

    protected $fillable = [
        'school_id', 'student_id', 'teacher_subject_section_id',
        'teacher_id', 'date', 'class_time', 'status', 'teacher_notes', 'metadata',
    ];

    protected $casts = [
        'date'       => 'date',
        'metadata'   => 'array',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function assignment()
    {
        return $this->belongsTo(TeacherSubjectSection::class, 'teacher_subject_section_id');
    }

    // ── Accessors ─────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::STATUS_LABELS[$this->status] ?? $this->status
        );
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopePresent($query)
    {
        return $query->where('status', self::STATUS_PRESENT);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    public function scopeLate($query)
    {
        return $query->where('status', self::STATUS_LATE);
    }

    public function scopeExcused($query)
    {
        return $query->where('status', self::STATUS_EXCUSED);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'student:id,first_name,last_name,photo_path',
            'teacher:id,first_name,last_name',
            'assignment.subject:id,name,code,color',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isPresent(): bool
    {
        return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE]);
    }

    public function isExcused(): bool
    {
        return $this->status === self::STATUS_EXCUSED;
    }
}