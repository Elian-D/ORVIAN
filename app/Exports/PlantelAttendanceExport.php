<?php

namespace App\Exports;

use App\Filters\App\Attendance\Plantel\PlantelAttendanceFilters;
use App\Models\Tenant\PlantelAttendanceRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlantelAttendanceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected int   $schoolId,
        protected array $filters = [],
    ) {}

    public function collection(): \Illuminate\Support\Collection
    {
        return (new PlantelAttendanceFilters($this->filters))
            ->apply(
                PlantelAttendanceRecord::where('school_id', $this->schoolId)
                    ->with([
                        'student:id,first_name,last_name,rnc,school_section_id',
                        'student.section' => fn ($q) => $q->with('grade:id,name'),
                        'shift:id,type',
                        'registeredBy:id,name',
                    ])
            )
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Estudiante',
            'Cédula',
            'Sección',
            'Tanda',
            'Hora',
            'Estado',
            'Método',
            'Registrado Por',
        ];
    }

    public function map($record): array
    {
        $grade   = $record->student->section?->grade?->name;
        $section = $record->student->section?->label;

        return [
            $record->date->isoFormat('D MMM YYYY'),
            $record->student->full_name,
            $record->student->rnc ?? '—',
            $grade && $section ? "{$grade}° - {$section}" : '—',
            $record->shift?->type ?? '—',
            $record->time?->format('h:i A') ?? '—',
            $record->status_label,
            $record->method_label,
            $record->registeredBy?->name ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
