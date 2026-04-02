<?php

namespace App\Models\Tenant\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Subject extends Model
{
    // --- Constantes de Tipo ---
    const TYPE_BASIC = 'basic';
    const TYPE_TECHNICAL = 'technical';

    protected $fillable = [
        'technical_title_id', 
        'code', 
        'name',
        'type', 
        'hours_weekly', 
        'hours_total', 
        'color', 
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Getters / Accessors ────────────────────────────────

    /**
     * Retorna un label legible para humanos.
     * Uso: $subject->type_label
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                self::TYPE_BASIC => 'Básica/Académica',
                self::TYPE_TECHNICAL => 'Módulo Técnico',
                default => 'No definido',
            },
        );
    }

    /**
     * Helper estático para obtener todos los tipos (útil para selects en el UI)
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_BASIC => 'Básica/Académica',
            self::TYPE_TECHNICAL => 'Módulo Técnico',
        ];
    }

    // ── Relaciones ────────────────────────────────────────

    public function technicalTitle()
    {
        return $this->belongsTo(TechnicalTitle::class);
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherSubjectSection::class);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBasic(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BASIC);
    }

    public function scopeTechnical(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_TECHNICAL);
    }

    /**
     * Materias visibles para una escuela concreta.
     * Siempre incluye básicas + solo los módulos de sus títulos habilitados.
     */
    public function scopeAvailableForSchool(Builder $query, int $schoolId): Builder
    {
        $titleIds = \App\Models\Tenant\Academic\TechnicalTitle::whereHas(
            'schools', fn ($q) => $q->where('schools.id', $schoolId)
        )->pluck('id');

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($titleIds) {
                $q->where('type', self::TYPE_BASIC)
                  ->orWhereIn('technical_title_id', $titleIds);
            });
    }

    // ── Helpers ───────────────────────────────────────────

    public function isTechnical(): bool
    {
        return $this->type === self::TYPE_TECHNICAL;
    }

    public function isBasic(): bool
    {
        return $this->type === self::TYPE_BASIC;
    }
}