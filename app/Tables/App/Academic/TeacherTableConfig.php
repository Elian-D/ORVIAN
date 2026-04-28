<?php

namespace App\Tables\App\Academic;

use App\Tables\Contracts\TableConfig;
use App\Tables\Concerns\HasResponsiveColumns;

class TeacherTableConfig implements TableConfig
{
    use HasResponsiveColumns;

    /**
     * Catálogo completo de columnas disponibles para el módulo de docentes.

     */
    public static function allColumns(): array
    {
        return [
            'full_name'         => 'Nombre Completo',
            'employee_code'     => 'Código de Empleado',
            'specialization'    => 'Especialización',
            'employment_type'   => 'Tipo de Contrato',
            'assignments_count' => 'Asignaciones',
            'status'            => 'Estado',
            'has_user_account' => 'Acceso',
        ];
    }


    /**
     * Columnas visibles por defecto en resolución de escritorio.

     */
    public static function defaultDesktop(): array
    {
        return [
            'full_name', 'employee_code', 'specialization',
            'employment_type', 'assignments_count', 'status',
        ];
    }

    /**
     * Columnas esenciales visibles en dispositivos móviles.

     */
    public static function defaultMobile(): array
    {
        return ['full_name', 'specialization', 'status'];
    }

    /**
     * Mapeo de los filtros del componente Livewire a labels legibles para los chips.
     *
     */
    public static function filterLabels(): array
    {
        return [
            'search'          => 'Búsqueda',
            'status'          => 'Estado',
            'employment_type' => 'Tipo de Contrato',
            'has_user'        => 'Con acceso al sistema',
        ];
    }
}