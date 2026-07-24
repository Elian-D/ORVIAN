<?php

namespace App\Http\Requests\Kiosk;

use Illuminate\Foundation\Http\FormRequest;

class RecordQrRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:daily_attendance_sessions,id'],
            'qr_code'    => ['required', 'string', 'max:100'],
        ];
    }
}