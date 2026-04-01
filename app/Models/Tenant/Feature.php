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
     * Obtiene el icono asociado al slug del módulo.
     */
    public function getIcon(): string
    {
        return match ($this->slug) {
            'attendance_qr'           => 'heroicon-o-qr-code',
            'attendance_facial'       => 'heroicon-o-user-circle',
            'academic_grades'         => 'heroicon-o-clipboard-document-check',
            'academic_excel_import'   => 'heroicon-o-table-cells',
            'classroom_internal'      => 'heroicon-o-academic-cap',
            'reports_advanced'        => 'heroicon-o-chart-bar-square',
            default                   => 'heroicon-o-check-circle',
        };
    }
}