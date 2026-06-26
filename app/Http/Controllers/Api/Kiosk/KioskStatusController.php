<?php

namespace App\Http\Controllers\Api\Kiosk;

use App\Models\Tenant\DailyAttendanceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KioskStatusController
{
    public function __invoke(Request $request): JsonResponse
    {
        $school = $request->user(); // El tokenable es el modelo School

        $session = DailyAttendanceSession::query()
            ->where('school_id', $school->id)
            ->whereDate('date', today())
            ->active() // <--- Usamos tu scope local en lugar de ->where('status', 'open')
            ->first();

        return response()->json([
            'session_active' => (bool) $session,
            'session_id'     => $session?->id,
            'session_date'   => $session?->date?->toDateString(),
            'school_name'    => $school->name,
            'server_time'    => now()->toIso8601String(),
        ]);
    }
}