<?php
namespace App\Services\Attendance;

use App\Models\Tenant\Student;
use Carbon\Carbon;

class AttendanceResult
{
    private function __construct(
        public readonly bool     $success,
        public readonly ?Student $student      = null,
        public readonly ?string  $attendanceStatus = null,
        public readonly ?float   $confidence   = null,
        public readonly ?Carbon  $recordedAt   = null,
        public readonly ?string  $error        = null,
        public readonly ?string  $message      = null,
    ) {}

    public static function ok(
        Student $student,
        string  $attendanceStatus,
        Carbon  $recordedAt,
        ?float  $confidence = null,
    ): self {
        return new self(
            success:          true,
            student:          $student,
            attendanceStatus: $attendanceStatus,
            confidence:       $confidence,
            recordedAt:       $recordedAt,
        );
    }

    public static function fail(string $errorCode, string $message): self
    {
        return new self(
            success: false,
            error:   $errorCode,
            message: $message,
        );
    }

    public function failed(): bool    { return ! $this->success; }
    public function errorCode(): ?string    { return $this->error; }
    public function errorMessage(): ?string { return $this->message; }
}