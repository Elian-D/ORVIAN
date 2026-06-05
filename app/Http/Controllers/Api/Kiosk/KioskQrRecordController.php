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

        return response()->json([
            'success'    => true,
            'student'    => [
                'id'         => $result->student->id,
                'full_name'  => $result->student->full_name,
                'photo_url'  => $result->student->photo_url,
            ],
            'status'     => $result->attendanceStatus,  // 'present' | 'late'
            'recorded_at'=> $result->recordedAt->toIso8601String(),
        ]);
    }
}