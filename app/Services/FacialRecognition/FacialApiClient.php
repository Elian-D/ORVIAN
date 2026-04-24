<?php

namespace App\Services\FacialRecognition;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class FacialApiClient
{
    private string $url;
    private string $key;

    public function __construct()
    {
        $this->url = config('services.facial_api.url', 'http://localhost:8001');
        $this->key = config('services.facial_api.key', '');
    }

    public function health(): array
    {
        $response = Http::timeout(5)->get("{$this->url}/health");
        $this->assertSuccessful($response);
        return $response->json();
    }

    public function enrollFace(int $studentId, int $schoolId, UploadedFile $image): array
    {
        $response = Http::timeout(30)
            ->withHeaders(['X-API-Key' => $this->key])
            ->attach('image', file_get_contents($image->getRealPath()), $image->getClientOriginalName())
            ->post("{$this->url}/api/v1/enroll/", [
                'student_id' => $studentId,
                'school_id'  => $schoolId,
            ]);

        $this->assertSuccessful($response);
        return $response->json();
    }

    public function verifyFace(int $schoolId, array $knownEncodings, UploadedFile $image): array
    {
        $response = Http::timeout(30)
            ->withHeaders(['X-API-Key' => $this->key])
            ->attach('image', file_get_contents($image->getRealPath()), $image->getClientOriginalName())
            ->post("{$this->url}/api/v1/verify/", [
                'school_id'       => $schoolId,
                'known_encodings' => json_encode($knownEncodings),
            ]);

        $this->assertSuccessful($response);
        return $response->json();
    }

    private function assertSuccessful(Response $response): void
    {
        if (! $response->successful()) {
            throw new \Exception("[FacialApiClient] HTTP {$response->status()}: {$response->body()}");
        }
    }
}
