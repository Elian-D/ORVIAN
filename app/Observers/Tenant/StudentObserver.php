<?php

namespace App\Observers\Tenant;

use App\Models\Role;
use App\Models\Tenant\Student;
use App\Models\User;
use App\Services\Academic\Students\StudentService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

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
            'password'   => Hash::make('12345678'),
            'school_id'  => $student->school_id,
            'status'     => 'inactive',
        ]);

        // Buscar el rol Student del tenant explícitamente por school_id,
        // sin depender del estado global de setPermissionsTeamId() que puede
        // estar corrupto en contextos de queue worker (jobs anteriores lo resetean).
        $tenantRole = Role::withoutGlobalScopes()
            ->where('name', 'Student')
            ->where('guard_name', 'web')
            ->where('school_id', $student->school_id)
            ->first();

        if ($tenantRole) {
            // Insertar directamente en el pivot con el school_id correcto,
            // evitando por completo la dependencia en getPermissionsTeamId().
            $user->roles()->attach($tenantRole->id, [
                config('permission.column_names.team_foreign_key') => $student->school_id,
            ]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } else {
            Log::warning("Rol 'Student' no encontrado para school_id={$student->school_id}. El usuario {$user->id} no tiene rol asignado.");
        }

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