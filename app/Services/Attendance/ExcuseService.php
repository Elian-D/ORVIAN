<?php

namespace App\Services\Attendance;

use App\Models\Tenant\AttendanceExcuse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ExcuseService
{
    // ── CRUD de Excusas ───────────────────────────────────────────

    public function submitExcuse(array $data): AttendanceExcuse
    {
        $data['submitted_at'] = now();
        $data['submitted_by'] = Auth::id();

        return AttendanceExcuse::create($data);
    }

    /**
     * Aprobar una excusa.
     * El Observer `AttendanceExcuseObserver` reacciona a este cambio de estado
     * y aplica la excusa retroactivamente sobre registros existentes.
     */
    public function approveExcuse(AttendanceExcuse $excuse, ?string $notes = null): void
    {
        $excuse->update([
            'status'       => AttendanceExcuse::STATUS_APPROVED,
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    public function rejectExcuse(AttendanceExcuse $excuse, string $notes): void
    {
        $excuse->update([
            'status'       => AttendanceExcuse::STATUS_REJECTED,
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    // ── Consultas de Cobertura ────────────────────────────────────

    /**
     * Verificar si un estudiante tiene una excusa aprobada para una fecha concreta.
     * Usado en markAbsences() para decidir el estado del registro.
     */
    public function hasApprovedExcuseForDate(int $studentId, Carbon $date): bool
    {
        return AttendanceExcuse::where('student_id', $studentId)
            ->approved()
            ->where('date_start', '<=', $date->toDateString())
            ->where('date_end', '>=', $date->toDateString())
            ->exists();
    }

    /**
     * Obtener la excusa activa de tipo licencia para un estudiante en una fecha.
     * Retorna null si no tiene licencia activa.
     *
     * Usado en recordAttendance() para emitir la alerta de "licencia activa ha ingresado".
     */
    public function getActiveLicenseForStudent(int $studentId, Carbon $date): ?AttendanceExcuse
    {
        return AttendanceExcuse::where('student_id', $studentId)
            ->approved()
            ->license()
            ->where('date_start', '<=', $date->toDateString())
            ->where('date_end', '>=', $date->toDateString())
            ->first();
    }

    /**
     * Devuelve una colección de IDs de estudiantes con excusa aprobada para la fecha dada.
     *
     * Diseñado para una sola consulta que abastece a ClassroomAttendanceLive al cargar
     * la lista de estudiantes — evita N+1 al renderizar el pase de lista.
     *
     * @return Collection<int>
     */
    public function getCoveredStudentsForDate(Carbon $date): Collection
    {
        return AttendanceExcuse::approved()
            ->where('date_start', '<=', $date->toDateString())
            ->where('date_end', '>=', $date->toDateString())
            ->pluck('student_id')
            ->unique()
            ->values();
    }

    /**
     * Obtener excusas de un estudiante en un rango de fechas.
     * Útil para el historial en StudentShow.
     */
    public function getStudentExcuses(int $studentId, Carbon $from, Carbon $to): Collection
    {
        return AttendanceExcuse::where('student_id', $studentId)
            ->forDateRange($from, $to)
            ->orderBy('date_start', 'desc')
            ->get();
    }
}