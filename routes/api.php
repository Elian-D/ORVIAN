<?php

use App\Http\Controllers\Api\Kiosk\KioskStatusController;
use App\Http\Controllers\Api\Kiosk\KioskQrRecordController;
use App\Http\Controllers\Api\Kiosk\KioskFacialRecordController;
use Illuminate\Routing\Route;

Route::prefix('v1/kiosk')
    ->middleware(['auth:sanctum', 'ability:kiosk'])
    ->group(function () {

        // GET /api/v1/kiosk/status
        Route::get('status', KioskStatusController::class);

        // POST /api/v1/kiosk/record/qr
        Route::post('record/qr', KioskQrRecordController::class);

        // POST /api/v1/kiosk/record/facial
        Route::post('record/facial', KioskFacialRecordController::class);
    });