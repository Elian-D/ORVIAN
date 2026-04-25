<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\Student;
use App\Models\User;
use App\Services\Students\StudentService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentObserver
{
    public function creating(Student $student): void
    {
        if (empty($student->qr_code)) {
            $student->qr_code = app(StudentService::class)->generateQrCode();
        }
    }

    public function created(Student $student): void
    {
        // Generar QR ya se hace en `creating`, aquí solo el User
        if (blank($student->rnc) || User::where('email', $this->buildEmail($student->rnc))->exists()) {
            return;
        }

        $email = $this->buildEmail($student->rnc);

        $user = User::create([
            'name'       => $student->full_name,
            'email'      => $email,
            'password'   => Hash::make('12345678'), // Contraseña por defecto, se recomienda cambiarla en el primer login
            'school_id'  => $student->school_id,
            'status'     => 'inactive',
        ]);

        // Asignar rol Student en scope del tenant
        setPermissionsTeamId($student->school_id);
        $user->assignRole('Student');

        // Vincular el user_id al estudiante
        $student->updateQuietly(['user_id' => $user->id]);
    }

    private function buildEmail(string $rnc): string
    {
        return $this->cleanRnc($rnc) . '@orvian.com.do';
    }

    private function cleanRnc(string $rnc): string
    {
        return str_replace('-', '', $rnc);
    }

    public function updated(Student $student): void
    {
        if ($student->isDirty('is_active') && !$student->is_active) {
            Log::info('Estudiante dado de baja', [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'school_id' => $student->school_id,
                'withdrawal_reason' => $student->withdrawal_reason,
            ]);
        }
    }

    public function deleted(Student $student): void
    {
        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }
    }
}