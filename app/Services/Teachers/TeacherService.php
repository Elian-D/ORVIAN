<?php

namespace App\Services\Teachers;

use App\Models\Tenant\Teacher;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeacherService
{
    public function generateQrCode(): string
    {
        do {
            $code = 'TCH-' . Str::upper(Str::random(28));
        } while (Teacher::where('qr_code', $code)->exists());
        
        return $code;
    }

    public function generateEmployeeCode(int $schoolId): string
    {
        $year = now()->year;
        $count = Teacher::where('school_id', $schoolId)->count() + 1;
        return sprintf('EMP-%d-%04d', $year, $count);
    }

    public function createTeacher(array $data): Teacher
    {
        $data['qr_code'] = $this->generateQrCode();
        
        if (empty($data['employee_code'])) {
            $data['employee_code'] = $this->generateEmployeeCode($data['school_id']);
        }
        
        return Teacher::create($data);
    }

    public function updatePhoto(Teacher $teacher, UploadedFile $photo): void
    {
        if ($teacher->photo_path) {
            Storage::disk('public')->delete($teacher->photo_path);
        }

        $path = $photo->store("schools/{$teacher->school_id}/teachers", 'public');
        $teacher->update(['photo_path' => $path]);
    }

    public function terminate(Teacher $teacher, string $reason, ?Carbon $date = null): void
    {
        $teacher->update([
            'is_active' => false,
            'termination_date' => $date ?? now(),
            'termination_reason' => $reason,
        ]);

        // Remover asignaciones futuras
        $teacher->assignments()
            ->whereHas('academicYear', fn($q) => $q->where('is_active', true))
            ->delete();
    }

    public function reactivate(Teacher $teacher): void
    {
        $teacher->update([
            'is_active' => true,
            'termination_date' => null,
            'termination_reason' => null,
        ]);
    }
}