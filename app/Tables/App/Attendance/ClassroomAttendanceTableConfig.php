<?php

namespace App\Tables\App\Attendance;

use App\Tables\Concerns\HasResponsiveColumns;
use App\Tables\Contracts\TableConfig;

class ClassroomAttendanceTableConfig implements TableConfig
{
    use HasResponsiveColumns;

    public static function allColumns(): array
    {
        return [
            'date'      => 'Fecha',
            'student'   => 'Estudiante',
            'section'   => 'Sección',
            'subject'   => 'Materia',
            'teacher'   => 'Maestro',
            'class_time'=> 'Hora de Clase',
            'status'    => 'Estado',
            'notes'     => 'Notas',
        ];
    }

    public static function defaultDesktop(): array
    {
        return ['date', 'student', 'section', 'subject', 'teacher', 'class_time', 'status'];
    }

    public static function defaultMobile(): array
    {
        return ['student', 'subject', 'status'];
    }

    public static function filterLabels(): array
    {
        return [
            'search'     => 'Búsqueda',
            'date_from'  => 'Desde',
            'date_to'    => 'Hasta',
            'section_id' => 'Sección',
            'subject_id' => 'Materia',
            'teacher_id' => 'Maestro',
            'status'     => 'Estado',
        ];
    }
}
