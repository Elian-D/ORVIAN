<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['name', 'slug', 'module', 'is_active'];

    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }

    /**
     * Retorna el slug del módulo para ser usado con x-ui.module-icon.
     * Los SVGs viven en public/assets/icons/modules/{slug}.svg
     */
    public function getIcon(): string
    {
        return match ($this->slug) {
            'attendance_qr',
            'attendance_facial'     => 'asistencia',
            'academic_grades',
            'academic_excel_import' => 'notas',
            'classroom_internal'    => 'classroom',
            'reports_advanced'      => 'reportes',
            default                 => 'administracion',
        };
    }
}