<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\Teacher;
use App\Services\Academic\Teachers\TeacherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TeacherObserver
{
    public function creating(Teacher $teacher): void
    {
        if (empty($teacher->qr_code)) {
            $teacher->qr_code = app(TeacherService::class)->generateQrCode();
        }

        if (empty($teacher->employee_code)) {
            $teacher->employee_code = app(TeacherService::class)
                ->generateEmployeeCode($teacher->school_id);
        }
    }

    public function updated(Teacher $teacher): void
    {
        if ($teacher->isDirty('is_active') && !$teacher->is_active) {
            Log::info('Maestro dado de baja', [
                'teacher_id' => $teacher->id,
                'full_name' => $teacher->full_name,
                'school_id' => $teacher->school_id,
                'termination_reason' => $teacher->termination_reason,
            ]);
        }
    }

    public function deleted(Teacher $teacher): void
    {
        if ($teacher->photo_path) {
            Storage::disk('public')->delete($teacher->photo_path);
        }
    }
}