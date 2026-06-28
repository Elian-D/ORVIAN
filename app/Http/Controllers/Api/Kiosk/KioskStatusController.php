<?php

namespace App\Http\Controllers\Api\Kiosk;

use App\Models\Tenant\DailyAttendanceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KioskStatusController
{
    public function __invoke(Request $request): JsonResponse
    {
        $school = $request->user();

        // Cargar el plan con sus features en una sola query
        $school->loadMissing('plan.features');

        $session = DailyAttendanceSession::query()
            ->where('school_id', $school->id)
            ->whereDate('date', today())
            ->active()
            ->with('shift')
            ->first();

        return response()->json([
            'school_name'      => $school->name,
            'session_active'   => (bool) $session,
            'session_id'       => $session?->id,
            'server_time'      => now()->toIso8601String(),
            'pin_hash'         => $school->kiosk_pin,

            // Features del plan — Electron decide qué interfaz mostrar
            'features' => [
                'attendance_qr'     => $school->plan?->hasFeature('attendance_qr') ?? false,
                'attendance_facial' => $school->plan?->hasFeature('attendance_facial') ?? false,
            ],
        ]);
    }
}