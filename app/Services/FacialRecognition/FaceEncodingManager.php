<?php

namespace App\Services\FacialRecognition;

use App\Models\Tenant\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FaceEncodingManager
{
    public function __construct(private FacialApiClient $client) {}

    public function isServiceHealthy(): bool
    {
        try {
            $this->client->health();
            return true;
        } catch (\Exception $e) {
            Log::warning('[FaceEncodingManager] Servicio no disponible: ' . $e->getMessage());
            return false;
        }
    }

    public function enrollStudent(Student $student, UploadedFile $photo): bool
    {
        try {
            $result = $this->client->enrollFace($student->id, $student->school_id, $photo);

            if (! ($result['success'] ?? false)) {
                Log::warning('[FaceEncodingManager] Enrollment fallido para student ' . $student->id . ': ' . ($result['message'] ?? 'sin mensaje'));
                return false;
            }

            $student->update(['face_encoding' => json_encode($result['encoding'])]);
            return true;
        } catch (\Exception $e) {
            Log::error('[FaceEncodingManager] Excepción en enrollStudent: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array{student_id: int, student_name: string, confidence: float, distance: float}|null
     */
    public function identifyStudent(int $schoolId, UploadedFile $photo): ?array
    {
        try {
            $knownEncodings = Student::active()
                ->where('school_id', $schoolId)
                ->whereNotNull('face_encoding')
                ->get(['id', 'first_name', 'last_name', 'face_encoding'])
                ->map(fn($s) => [
                    'id'       => $s->id,
                    'name'     => $s->full_name,
                    'encoding' => json_decode($s->face_encoding, true),
                ])->toArray();

            if (empty($knownEncodings)) {
                Log::warning('[FaceEncodingManager] No hay estudiantes con encoding en school ' . $schoolId);
                return null;
            }

            $result = $this->client->verifyFace($schoolId, $knownEncodings, $photo);

            if (! ($result['success'] ?? false) || ! ($result['matched'] ?? false)) {
                return null;
            }

            return [
                'student_id'   => $result['student_id'],
                'student_name' => $result['student_name'],
                'confidence'   => $result['confidence'] ?? 0.0,
                'distance'     => $result['distance'] ?? 1.0,
            ];
        } catch (\Exception $e) {
            Log::error('[FaceEncodingManager] Excepción en identifyStudent: ' . $e->getMessage());
            return null;
        }
    }
}
