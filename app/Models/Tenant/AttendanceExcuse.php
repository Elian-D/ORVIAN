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
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    // ── Constantes de Tipo de Excusa ──────────────────────────────
    // 'type' ya no declara forma temporal (llegada tardía, salida
    // anticipada, ausencia completa) — eso es un estado de movimiento
    // real que vive en PlantelAttendanceRecord. Aquí solo se declara el motivo.
    public const TYPE_MEDICAL                = 'medical';
    public const TYPE_PERSONAL               = 'personal';

    public const STATUS_LABELS = [
        self::STATUS_PENDING   => 'Pendiente',
        self::STATUS_CONFIRMED => 'Confirmada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    public const TYPE_LABELS = [
        self::TYPE_MEDICAL                => 'Motivo Médico',
        self::TYPE_PERSONAL               => 'Motivo Personal',
    ];

    protected $fillable = [
        'school_id', 'student_id', 'date_start', 'date_end', 'type',
        'reason', 'attachment_path', 'status',
        'submitted_by', 'submitted_at', 'reviewed_by', 'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'date_start'       => 'date',
        'date_end'         => 'date',
        'submitted_at'     => 'datetime',
        'reviewed_at'      => 'datetime',
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

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
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


    // ── Helpers ───────────────────────────────────────────────────

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function coversDate(Carbon $date): bool
    {
        return $date->between($this->date_start, $this->date_end);
    }
}