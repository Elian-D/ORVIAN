<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\Student;
use App\Services\Students\StudentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentObserver
{
    public function creating(Student $student): void
    {
        if (empty($student->qr_code)) {
            $student->qr_code = app(StudentService::class)->generateQrCode();
        }
    }

    public function updated(Student $student): void
    {
        if ($student->isDirty('is_active') && !$student->is_active) {
            Log::info('Estudiante dado de baja', [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'school_id' => $student->school_id,
                'withdrawal_reason' => $student->withdrawal_reason,
            ]);
        }
    }

    public function deleted(Student $student): void
    {
        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }
    }
}