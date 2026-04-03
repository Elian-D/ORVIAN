<?php

namespace App\Models\Tenant;

use App\Models\User;
use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AttendanceExcuse extends Model
{
    use BelongsToSchool;

    // ── Constantes de Estado ──────────────────────────────────────
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // ── Constantes de Tipo de Excusa ──────────────────────────────
    public const TYPE_FULL_ABSENCE      = 'full_absence';
    public const TYPE_LATE_ARRIVAL      = 'late_arrival';
    public const TYPE_EARLY_DEPARTURE   = 'early_departure';
    public const TYPE_LICENSE           = 'license';
    public const TYPE_MEDICAL           = 'medical';  // Licencia médica extendida

    // Los tipos que implican una licencia activa (el estudiante puede entrar
    // físicamente aunque el sistema lo tenga justificado).
    public const LICENSE_TYPES = [
        self::TYPE_LICENSE,
        self::TYPE_MEDICAL,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING  => 'Pendiente',
        self::STATUS_APPROVED => 'Aprobada',
        self::STATUS_REJECTED => 'Rechazada',
    ];

    public const TYPE_LABELS = [
        self::TYPE_FULL_ABSENCE    => 'Ausencia Total',
        self::TYPE_LATE_ARRIVAL    => 'Llegada Tardía',
        self::TYPE_EARLY_DEPARTURE => 'Salida Anticipada',
        self::TYPE_LICENSE         => 'Licencia',
        self::TYPE_MEDICAL         => 'Licencia Médica',
    ];

    protected $fillable = [
        'school_id', 'student_id', 'date_start', 'date_end', 'type',
        'reason', 'attachment_path', 'status', 'submitted_by',
        'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'date_start'   => 'date',
        'date_end'     => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
    ];


    // ── Relaciones ────────────────────────────────────────────────

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Accessors ─────────────────────────────────────────────────

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::STATUS_LABELS[$this->status] ?? $this->status
        );
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::TYPE_LABELS[$this->type] ?? $this->type
        );
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeForDateRange($query, Carbon $start, Carbon $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('date_start', [$start, $end])
            ->orWhereBetween('date_end', [$start, $end])
            ->orWhere(function ($q2) use ($start, $end) {
                $q2->where('date_start', '<=', $start)
                    ->where('date_end', '>=', $end);
            });
        });
    }

    public function scopeLicense($query)
    {
        return $query->whereIn('type', self::LICENSE_TYPES);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isLicenseType(): bool
    {
        return in_array($this->type, self::LICENSE_TYPES);
    }

    public function coversDate(Carbon $date): bool
    {
        return $date->between($this->date_start, $this->date_end);
    }
}