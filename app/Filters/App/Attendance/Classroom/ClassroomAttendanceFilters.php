<?php

namespace App\Filters\App\Attendance\Classroom;

use App\Filters\Base\QueryFilter;

class ClassroomAttendanceFilters extends QueryFilter
{
    protected function filters(): array
    {
        return [
            'search'     => SearchFilter::class,
            'date_from'  => DateFromFilter::class,
            'date_to'    => DateToFilter::class,
            'section_id' => SectionFilter::class,
            'subject_id' => SubjectFilter::class,
            'teacher_id' => TeacherFilter::class,
            'status'     => StatusFilter::class,
        ];
    }
}
