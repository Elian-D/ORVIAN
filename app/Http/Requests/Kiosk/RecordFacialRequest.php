<?php

namespace App\Http\Requests\Kiosk;

use Illuminate\Foundation\Http\FormRequest;

class RecordFacialRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:daily_attendance_sessions,id'],
            'photo'      => ['required', 'file', 'mimes:jpeg,jpg,png', 'max:5120'],  // 5MB máx.
        ];
    }
}