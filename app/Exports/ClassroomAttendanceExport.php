<?php

namespace App\Exports;

use App\Filters\App\Attendance\Classroom\ClassroomAttendanceFilters;
use App\Models\Tenant\ClassroomAttendanceRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassroomAttendanceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected int    $schoolId,
        protected array  $filters = [],
        // Restringe al maestro cuando sea necesario (null = sin restricción)
        protected ?int   $teacherScope = null,
    ) {}

    public function collection(): \Illuminate\Support\Collection
    {
        $query = ClassroomAttendanceRecord::where('school_id', $this->schoolId)
            ->with([
                'student:id,first_name,last_name,rnc',
                'teacher:id,first_name,last_name',
                'assignment.subject:id,name',
                'assignment.section' => fn ($q) => $q->with('grade:id,name'),
            ]);

        if ($this->teacherScope) {
            $query->where('teacher_id', $this->teacherScope);
        }

        return (new ClassroomAttendanceFilters($this->filters))
            ->apply($query)
            ->orderByDesc('date')
            ->orderBy('class_time')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Estudiante',
            'Materia',
            'Sección',
            'Maestro',
            'Estado',
            'Notas',
        ];
    }

    public function map($record): array
    {
        $grade   = $record->assignment?->section?->grade?->name;
        $section = $record->assignment?->section?->label;

        return [
            $record->date->isoFormat('D MMM YYYY'),
            $record->student->full_name,
            $record->assignment?->subject?->name ?? '—',
            $grade && $section ? "{$grade}° - {$section}" : '—',
            $record->teacher?->full_name ?? '—',
            $record->status_label,
            $record->teacher_notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
