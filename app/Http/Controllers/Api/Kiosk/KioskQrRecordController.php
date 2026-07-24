<?php

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Requests\Kiosk\RecordQrRequest;
use App\Services\Attendance\PlantelAttendanceService;
use Illuminate\Http\JsonResponse;

class KioskQrRecordController
{
    public function __invoke(
        RecordQrRequest $request,
        PlantelAttendanceService $service
    ): JsonResponse {
        $school = $request->user();

        $result = $service->recordByQr(
            schoolId:  $school->id,
            sessionId: $request->validated('session_id'),
            qrCode:    $request->validated('qr_code'),
        );

        if ($result->failed()) {
            return response()->json([
                'success' => false,
                'error'   => $result->errorCode(),   // 'NOT_FOUND' | 'ALREADY_RECORDED' | 'SESSION_CLOSED'
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
            'status'     => $result->attendanceStatus,  // 'present' | 'late'
            'recorded_at'=> $result->recordedAt->toIso8601String(),
        ]);
    }
}