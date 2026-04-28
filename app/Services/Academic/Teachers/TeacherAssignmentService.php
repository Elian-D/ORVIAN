<?php

namespace App\Services\Academic\Teachers;

use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Academic\Subject;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\Tenant\Teacher;
use Illuminate\Support\Collection;

class TeacherAssignmentService
{
    /**
     * Asignar una materia a un maestro en una sección para el año activo.
     * Lanza excepción si ya existe la combinación (unique constraint).
     */
    public function assign(Teacher $teacher, int $subjectId, int $sectionId): TeacherSubjectSection
    {
        $year = AcademicYear::where('school_id', $teacher->school_id)
            ->where('is_active', true)
            ->firstOrFail();

        return TeacherSubjectSection::create([
            'teacher_id'        => $teacher->id,
            'subject_id'        => $subjectId,
            'school_section_id' => $sectionId,
            'academic_year_id'  => $year->id,
            'is_active'         => true,
        ]);
    }

    /**
     * Eliminar una asignación. No elimina físicamente si tiene registros de asistencia;
     * en ese caso la desactiva.
     */
    public function remove(TeacherSubjectSection $assignment): void
    {
        $hasAttendance = $assignment->classroomAttendanceRecords()->exists();

        if ($hasAttendance) {
            $assignment->update(['is_active' => false]);
        } else {
            $assignment->delete();
        }
    }

    /**
     * Obtener las materias disponibles para asignar a un maestro en una sección.
     * Solo muestra materias que la escuela tiene habilitadas y que el maestro
     * aún no tiene en esa sección en el año activo.
     */
    public function getAvailableSubjects(Teacher $teacher, int $sectionId): Collection
    {
        $section = SchoolSection::with('technicalTitle')->find($sectionId);
        $year    = AcademicYear::where('school_id', $teacher->school_id)
                    ->where('is_active', true)->first();

        $alreadyAssigned = TeacherSubjectSection::where('teacher_id', $teacher->id)
            ->where('school_section_id', $sectionId)
            ->where('academic_year_id', $year?->id)
            ->pluck('subject_id');

        return Subject::availableForSchool($teacher->school_id)
            ->whereNotIn('id', $alreadyAssigned)
            ->active()
            ->get();
    }
}