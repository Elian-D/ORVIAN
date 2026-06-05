<?php

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Requests\Kiosk\RecordFacialRequest;
use App\Services\Attendance\PlantelAttendanceService;
use Illuminate\Http\JsonResponse;

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

        return response()->json([
            'success'    => true,
            'student'    => [
                'id'         => $result->student->id,
                'full_name'  => $result->student->full_name,
                'photo_url'  => $result->student->photo_url,
            ],
            'status'     => $result->attendanceStatus,
            'confidence' => $result->confidence,
            'recorded_at'=> $result->recordedAt->toIso8601String(),
        ]);
    }
}