<?php

namespace App\Tables\App\Attendance;

use App\Tables\Contracts\TableConfig;
use App\Tables\Concerns\HasResponsiveColumns;

class ExcuseTableConfig implements TableConfig
{
    use HasResponsiveColumns;

    public static function allColumns(): array
    {
        return [
            'student'    => 'Estudiante',
            'date_range' => 'Período',
            'type'       => 'Tipo',
            'status'     => 'Estado',
            'submitted'  => 'Enviado por',
        ];
    }

    public static function defaultDesktop(): array
    {
        return ['student', 'date_range', 'type', 'status', 'submitted'];
    }

    public static function defaultMobile(): array
    {
        return ['student', 'status'];
    }

    public static function filterLabels(): array
    {
        return [
            'student'    => 'Estudiante',
            'status'     => 'Estado',
            'date_range' => 'Rango de Fechas',
        ];
    }
}