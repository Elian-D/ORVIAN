<?php

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Requests\Kiosk\RecordFacialRequest;
use App\Services\Attendance\PlantelAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage; // IMPORTANTE: Asegúrate de tener esta importación

class KioskFacialRecordController
{
    public function __invoke(
        RecordFacialRequest $request,
        PlantelAttendanceService $service
    ): JsonResponse {
        $school = $request->user();

        $result = $service->recordByFacial(
            schoolId:  $school->id,
            sessionId: $request->validated('session_id'),
            photo:     $request->file('photo'),
        );

        if ($result->failed()) {
            return response()->json([
                'success' => false,
                'error'   => $result->errorCode(),   // 'NO_MATCH' | 'MULTIPLE_FACES' | 'LOW_CONFIDENCE' | 'SESSION_CLOSED'
                'message' => $result->errorMessage(),
            ], 422);
        }

        /**
         * Solución al formateo de la URL:
         * Como tu base de datos guarda "schools/1/students/archivo.jpg", al concatenarlo con 'storage/'
         * y pasarlo por asset(), Laravel generará automáticamente:
         * Local: http://orvian.test/storage/schools/1/students/archivo.jpg
         * Prod:  https://orvian.com.do/storage/schools/1/students/archivo.jpg
         *
         * Nota: Usamos asset() aquí porque evita las falsas alertas de error en el IDE que suele dar Storage::url()
         */
        $photoUrl = $result->student->photo_path 
            ? asset('storage/' . $result->student->photo_path) 
            : null;

        return response()->json([
            'success'    => true,
            'student'    => [
                'id'         => $result->student->id,
                'full_name'  => $result->student->full_name,
                'photo_url'  => $photoUrl, // Enviamos la URL absoluta resuelta
            ],
            'status'     => $result->attendanceStatus,
            'confidence' => $result->confidence,
            'recorded_at'=> $result->recordedAt->toIso8601String(),
        ]);
    }
}