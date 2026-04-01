<?php

namespace App\Services\Students;

use App\Models\Tenant\Student;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class StudentService
{
    public function generateQrCode(): string
    {
        do {
            $code = Str::upper(Str::random(32));
        } while (Student::where('qr_code', $code)->exists());
        
        return $code;
    }

    public function createStudent(array $data): Student
    {
        $data['qr_code'] = $this->generateQrCode();
        return Student::create($data);
    }

    public function updatePhoto(Student $student, UploadedFile $photo): void
    {
        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $path = $photo->store("schools/{$student->school_id}/students", 'public');
        $student->update(['photo_path' => $path]);
    }

    public function storeFaceEncoding(Student $student, array $encoding): void
    {
        $student->update(['face_encoding' => json_encode($encoding)]);
    }

    public function withdraw(Student $student, string $reason, ?Carbon $date = null): void
    {
        $student->update([
            'is_active' => false,
            'withdrawal_date' => $date ?? now(),
            'withdrawal_reason' => $reason,
        ]);
    }

    public function reactivate(Student $student): void
    {
        $student->update([
            'is_active' => true,
            'withdrawal_date' => null,
            'withdrawal_reason' => null,
        ]);
    }

    public function transferSection(Student $student, int $newSectionId): void
    {
        $oldSectionId = $student->school_section_id;
        
        $student->update(['school_section_id' => $newSectionId]);
        
        // Registrar en metadata
        $metadata = $student->metadata ?? [];
        $metadata['section_history'] = $metadata['section_history'] ?? [];
        $metadata['section_history'][] = [
            'from_section_id' => $oldSectionId,
            'to_section_id' => $newSectionId,
            'transferred_at' => now()->toISOString(),
            'transferred_by' => Auth::id(),
        ];
        $student->update(['metadata' => $metadata]);
    }
}