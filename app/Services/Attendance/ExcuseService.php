<?php

namespace App\Services\Attendance;

use App\Models\Tenant\AttendanceExcuse;
use App\Models\Tenant\PlantelAttendanceRecord;
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
        // Explícito en vez de confiar en el default de columna: sin esto, el
        // objeto en memoria queda con status=null (Eloquent no relee la fila
        // tras el INSERT), y cualquier código que encadene confirmExcuse()
        // sobre el mismo objeto recién creado falla el guard isPending().
        $data['status'] = AttendanceExcuse::STATUS_PENDING;

        return AttendanceExcuse::create($data);
    }

    /**
     * Confirmar una excusa.
     * El Observer `AttendanceExcuseObserver` fue elimiando por una contradicción de responsabilidades: la aprobación de excusas no deben cabiar los registros globalmente (lo hacía), pero como eliminado ese objetivo , el Observer ya no tiene sentido. La confirmación solo registra quién la confirmó (pasando por el proceso de validar que esté bien) y cuándo.
     *
     * Única transición válida: pending → confirmed. Confirmar una excusa que
     * ya fue confirmada o cancelada no tiene sentido en esta máquina de estados.
     */
    public function confirmExcuse(AttendanceExcuse $excuse, ?string $notes = null): void
    {
        if (! $excuse->isPending()) {
            throw new \LogicException('Solo se puede confirmar una excusa que esté pendiente.');
        }

        $this->validateForConfirmation($excuse);

        $excuse->update([
            'status'       => AttendanceExcuse::STATUS_CONFIRMED,
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Cancelar una excusa.
     *
     * Única transición válida: confirmed → cancelled. Una excusa pendiente
     * nunca se cancela — si estaba mal, se corrige editándola (ExcuseForm)
     * mientras siga en pending. Cancelar solo aplica cuando el error se
     * detecta después de haber confirmado.
     */
    public function cancelExcuse(AttendanceExcuse $excuse, string $notes): void
    {
        if (! $excuse->isConfirmed()) {
            throw new \LogicException('Solo se puede cancelar una excusa que ya esté confirmada. Si está pendiente, corrígela editándola.');
        }

        $excuse->update([
            'status'       => AttendanceExcuse::STATUS_CANCELLED,
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    // ── Reglas de Negocio ─────────────────────────────────────────

    /**
     * Ninguna excusa se crea con fecha pasada — se registra en el momento,
     * nunca de forma retroactiva. Se evalúa en cada guardado (crear Y editar)
     * — depende únicamente del propio valor, nunca queda obsoleta.
     */
    public function canCreateForDate(Carbon $dateStart): bool
    {
        return $dateStart->greaterThanOrEqualTo(today());
    }

    /**
     * Se evalúa EXCLUSIVAMENTE al confirmar (dentro de confirmExcuse()), nunca
     * al crear ni al editar — es la única forma de que la validación no quede
     * obsoleta por ediciones posteriores al rango de fechas.
     */
    public function validateForConfirmation(AttendanceExcuse $excuse): void
    {
        if ($this->hasOverlappingConfirmedExcuse(
            $excuse->student_id, $excuse->date_start, $excuse->date_end, excludeId: $excuse->id
        )) {
            throw new \LogicException('Ya existe otra excusa confirmada que se traslapa con este rango de fechas.');
        }
    }

    /**
     * Traslape general — bloquea confirmar una excusa (de cualquier tipo) si el
     * estudiante ya tiene otra CONFIRMADA cuyo rango toca el rango solicitado.
     * Cruza tipos a propósito: una médica confirmada bloquea intentar confirmar
     * una personal en esas mismas fechas, y viceversa. Una excusa para después
     * de que la anterior ya terminó SÍ se permite — no hay traslape real.
     */
    public function hasOverlappingConfirmedExcuse(int $studentId, Carbon $dateStart, Carbon $dateEnd, ?int $excludeId = null): bool
    {
        return AttendanceExcuse::where('student_id', $studentId)
            ->where('status', AttendanceExcuse::STATUS_CONFIRMED)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('date_start', '<=', $dateEnd->toDateString())
            ->where('date_end', '>=', $dateStart->toDateString())
            ->exists();
    }

    // ── Consultas de Cobertura ────────────────────────────────────

    /**
     * Verificar si un estudiante tiene una excusa confirmada para una fecha concreta.
     * Usado en markAbsences() para decidir el estado del registro.
     */
    public function hasConfirmedExcuseForDate(int $studentId, Carbon $date): bool
    {
        return AttendanceExcuse::where('student_id', $studentId)
            ->confirmed()
            ->where('date_start', '<=', $date->toDateString())
            ->where('date_end', '>=', $date->toDateString())
            ->exists();
    }

    /**
     * Excusa de varios días que todavía tiene jornadas pendientes después de
     * hoy. Solo aplica a excusas de más de un día — una excusa de un solo día
     * que se resuelve el mismo día no es una sorpresa para nadie, es justo lo
     * esperado.
     */
    public function getMultiDayExcuseStillPendingForStudent(int $studentId, Carbon $date): ?AttendanceExcuse
    {
        return AttendanceExcuse::where('student_id', $studentId)
            ->confirmed()
            ->where('date_start', '<=', $date->toDateString())
            ->where('date_end', '>', $date->toDateString()) // aún quedan días después de hoy
            ->first();
    }

    /**
     * Devuelve una colección de IDs de estudiantes con excusa confirmada para la fecha dada.
     *
     * Diseñado para una sola consulta que abastece a ClassroomAttendanceLive al cargar
     * la lista de estudiantes — evita N+1 al renderizar el pase de lista.
     *
     * @return Collection<int>
     */
    public function getCoveredStudentsForDate(Carbon $date): Collection
    {
        return AttendanceExcuse::confirmed()
            ->where('date_start', '<=', $date->toDateString())
            ->where('date_end', '>=', $date->toDateString())
            ->pluck('student_id')
            ->unique()
            ->values();
    }

    // /**
    //  * Estudiantes cuya excusa debe aplicar en este momento. "No ha llegado"
    //  * excluye explícitamente a quien ya tiene registro de plantel presente/tarde
    //  * hoy — la llegada invalida la excusa hacia adelante, sin necesidad de
    //  * ninguna acción manual.
    //  *
    //  * Comentado (no eliminado): sin dependencia activa desde que Aula quedó
    //  * oculta (REQ-05.13, ClassroomAttendanceLive era el único caller). Se
    //  * deja para cuando se retome ese trabajo.
    //  */
    // public function getActivelyExcusedStudentIds(Carbon $date): Collection
    // {
    //     return AttendanceExcuse::confirmed()
    //         ->where('date_start', '<=', $date->toDateString())
    //         ->where('date_end', '>=', $date->toDateString())
    //         ->whereDoesntHave('student.plantelAttendanceRecords', fn ($q) => $q
    //             ->whereDate('date', $date)
    //             ->whereIn('status', [PlantelAttendanceRecord::STATUS_PRESENT, PlantelAttendanceRecord::STATUS_LATE])
    //         )
    //         ->pluck('student_id')
    //         ->unique()
    //         ->values();
    // }
}