<?php

namespace App\Tables\App\Attendance;

use App\Tables\Concerns\HasResponsiveColumns;
use App\Tables\Contracts\TableConfig;

class PlantelAttendanceTableConfig implements TableConfig
{
    use HasResponsiveColumns;

    public static function allColumns(): array
    {
        return [
            'date'          => 'Fecha',
            'student'       => 'Estudiante',
            'rnc'           => 'Cédula',
            'section'       => 'Sección',
            'shift'         => 'Tanda',
            'time'          => 'Hora',
            'status'        => 'Estado',
            'method'        => 'Método',
            'registered_by' => 'Registrado Por',
        ];
    }

    public static function defaultDesktop(): array
    {
        return ['date', 'student', 'section', 'time', 'status', 'method', 'verified'];
    }

    public static function defaultMobile(): array
    {
        return ['student', 'status'];
    }

    public static function filterLabels(): array
    {
        return [
            'search'     => 'Búsqueda',
            'date_from'  => 'Desde',
            'date_to'    => 'Hasta',
            'section_id' => 'Sección',
            'status'     => 'Estado',
            'method'     => 'Método',
            'verified'   => 'Verificación',
        ];
    }
}
