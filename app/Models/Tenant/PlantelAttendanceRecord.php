<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Academic\SchoolShift;
use App\Models\User;
use App\Scopes\SchoolScope;
use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class PlantelAttendanceRecord extends Model
{
    use BelongsToSchool;

    // ── Constantes de Estado ──────────────────────────────────────
    public const STATUS_PRESENT = 'present';
    public const STATUS_LATE    = 'late';
    public const STATUS_ABSENT  = 'absent';
    public const STATUS_EXCUSED = 'excused';

    // ── Constantes de Método ──────────────────────────────────────
    public const METHOD_MANUAL  = 'manual';
    public const METHOD_QR      = 'qr';
    public const METHOD_FACIAL  = 'facial';

    // Labels en español para UI
    public const STATUS_LABELS = [
        self::STATUS_PRESENT => 'Presente',
        self::STATUS_LATE    => 'Tardanza',
        self::STATUS_ABSENT  => 'Ausente',
        self::STATUS_EXCUSED => 'Justificado',
    ];

    public const METHOD_LABELS = [
        self::METHOD_MANUAL  => 'Manual',
        self::METHOD_QR      => 'Código QR',
        self::METHOD_FACIAL  => 'Reconocimiento Facial',
    ];

    protected $fillable = [
        'school_id', 'student_id', 'daily_attendance_session_id',
        'school_shift_id', 'date', 'time', 'status', 'method',
        'registered_by', 'temperature', 'notes', 'metadata',
        'verified_at', 'verified_by',
    ];

    protected $casts = [
        'date'        => 'date',
        'time'        => 'datetime',
        'temperature' => 'decimal:2',
        'metadata'    => 'array',
        'verified_at' => 'datetime',
    ];


    // ── Relaciones ────────────────────────────────────────────────

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(DailyAttendanceSession::class, 'daily_attendance_session_id');
    }

    public function shift()
    {
        return $this->belongsTo(SchoolShift::class, 'school_shift_id');
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Accessors ─────────────────────────────────────────────────

    /**
     * Label en español del estado actual.
     * Uso en Blade: {{ $record->status_label }}
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::STATUS_LABELS[$this->status] ?? $this->status
        );
    }

    protected function methodLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::METHOD_LABELS[$this->method] ?? $this->method
        );
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopePresent($query)
    {
        return $query->where('status', self::STATUS_PRESENT);
    }

    public function scopeLate($query)
    {
        return $query->where('status', self::STATUS_LATE);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    public function scopeExcused($query)
    {
        return $query->where('status', self::STATUS_EXCUSED);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopePending($query)
    {
        return $query->whereNull('verified_at');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeWithIndexRelations($query)
    {
        return $query->with([
            'student:id,first_name,last_name,photo_path',
            'shift:id,name',
            'registeredBy:id,name',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isPresent(): bool
    {
        return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE]);
    }

    public function isAbsent(): bool
    {
        return $this->status === self::STATUS_ABSENT;
    }

    public function isExcused(): bool
    {
        return $this->status === self::STATUS_EXCUSED;
    }
}