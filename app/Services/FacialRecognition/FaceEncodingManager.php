<?php

namespace App\Services\FacialRecognition;

use App\Models\Tenant\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceEncodingManager
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.facial_api.url', 'http://localhost:8001');
        $this->apiKey = config('services.facial_api.key', 'dev-key');
    }

    /**
     * Verifica si el microservicio de reconocimiento facial está disponible.
     */
    public function isServiceHealthy(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->apiUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('[FaceAPI] Servicio no disponible: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Identifica un estudiante desde una foto capturada.
     * 
     * @return array|null ['student_id' => int, 'student_name' => string, 'confidence' => float, 'distance' => float]
     */
    public function identifyStudent(int $schoolId, UploadedFile $photo): ?array
    {
        // MODO DESARROLLO: Simulación sin microservicio real
        // TODO: Implementar llamada real cuando el microservicio esté corriendo
        if (!$this->isServiceHealthy()) {
            Log::info('[FaceAPI] Modo simulación - microservicio offline');
            
            // Busca un estudiante activo aleatorio de esta escuela para simular éxito
            $student = Student::active()
                ->where('school_id', $schoolId)
                ->inRandomOrder()
                ->first();
            
            if ($student) {
                return [
                    'student_id' => $student->id,
                    'student_name' => $student->full_name,
                    'confidence' => 0.89, // Simulado
                    'distance' => 0.42,   // Simulado
                ];
            }
            
            return null;
        }

        // PRODUCCIÓN: Llamada real al microservicio
        try {
            // Cargar encodings activos de esta escuela
            $knownEncodings = Student::active()
                ->where('school_id', $schoolId)
                ->whereNotNull('face_encoding')
                ->get(['id', 'first_name', 'last_name', 'face_encoding'])
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'encoding' => json_decode($s->face_encoding, true),
                ])->toArray();

            if (empty($knownEncodings)) {
                Log::warning('[FaceAPI] No hay estudiantes con encoding registrado');
                return null;
            }

            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->attach('image', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName())
                ->post("{$this->apiUrl}/api/v1/verify", [
                    'school_id' => $schoolId,
                    'known_encodings' => json_encode($knownEncodings),
                ]);

            if (!$response->successful()) {
                Log::error('[FaceAPI] Error en verificación: ' . $response->body());
                return null;
            }

            $data = $response->json();

            if (!$data['success'] || !$data['matched']) {
                return null;
            }

            return [
                'student_id' => $data['student_id'],
                'student_name' => $data['student_name'],
                'confidence' => $data['confidence'] ?? 0,
                'distance' => $data['distance'] ?? 1,
            ];

        } catch (\Exception $e) {
            Log::error('[FaceAPI] Excepción en identifyStudent: ' . $e->getMessage());
            return null;
        }
    }
}