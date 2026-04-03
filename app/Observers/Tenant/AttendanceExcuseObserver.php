<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\AttendanceExcuse;
use App\Models\Tenant\ClassroomAttendanceRecord;
use App\Models\Tenant\PlantelAttendanceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceExcuseObserver
{
    /**
     * Cuando una excusa pasa a estado APPROVED, aplicarla retroactivamente
     * sobre todos los registros de asistencia (plantel y aula) que estén
     * dentro del rango de fechas y pertenezcan al estudiante.
     *
     * Registros afectados:
     * - PlantelAttendanceRecord con status = 'absent'
     * - ClassroomAttendanceRecord con status = 'absent'
     *
     * Solo se actualizan registros 'absent' — no se toca 'present', 'late'
     * ni 'excused' ya existentes.
     */
    public function updated(AttendanceExcuse $excuse): void
    {
        // Solo actuar cuando el cambio es hacia STATUS_APPROVED
        if (
            ! $excuse->wasChanged('status')
            || $excuse->status !== AttendanceExcuse::STATUS_APPROVED
        ) {
            return;
        }

        DB::transaction(function () use ($excuse) {
            $this->applyToPlantelRecords($excuse);
            $this->applyToClassroomRecords($excuse);
        });

        Log::info('Excusa aplicada retroactivamente', [
            'excuse_id'   => $excuse->id,
            'student_id'  => $excuse->student_id,
            'date_start'  => $excuse->date_start->toDateString(),
            'date_end'    => $excuse->date_end->toDateString(),
        ]);
    }

    // ── Privados ──────────────────────────────────────────────────

    protected function applyToPlantelRecords(AttendanceExcuse $excuse): void
    {
        $updated = PlantelAttendanceRecord::where('student_id', $excuse->student_id)
            ->where('status', PlantelAttendanceRecord::STATUS_ABSENT)
            ->whereBetween('date', [
                $excuse->date_start->toDateString(),
                $excuse->date_end->toDateString(),
            ])
            ->update([
                'status' => PlantelAttendanceRecord::STATUS_EXCUSED,
                'notes'  => DB::raw(
                    "CONCAT(COALESCE(notes, ''), ' [Justificado por excusa #".$excuse->id."]')"
                ),
            ]);

        Log::debug("Plantel: {$updated} registros actualizados a 'excused'", [
            'excuse_id' => $excuse->id,
        ]);
    }

    protected function applyToClassroomRecords(AttendanceExcuse $excuse): void
    {
        $updated = ClassroomAttendanceRecord::where('student_id', $excuse->student_id)
            ->where('status', ClassroomAttendanceRecord::STATUS_ABSENT)
            ->whereBetween('date', [
                $excuse->date_start->toDateString(),
                $excuse->date_end->toDateString(),
            ])
            ->update([
                'status'        => ClassroomAttendanceRecord::STATUS_EXCUSED,
                'teacher_notes' => DB::raw(
                    "CONCAT(COALESCE(teacher_notes, ''), ' [Justificado por excusa #".$excuse->id."]')"
                ),
            ]);

        Log::debug("Aula: {$updated} registros actualizados a 'excused'", [
            'excuse_id' => $excuse->id,
        ]);
    }
}