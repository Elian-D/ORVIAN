<?php

namespace App\Actions\Tenant;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function execute(array $data, int $schoolId, string $role = 'Staff'): User
    {
        return DB::transaction(function () use ($data, $schoolId, $role) {
            // 1. Crear el usuario
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'school_id' => $schoolId,
            ]);

            // 2. Configurar el contexto de Spatie para este "Team/School"
            setPermissionsTeamId($schoolId);

            // 3. Asignar el rol (Spatie lo guardará vinculado al school_id)
            $user->assignRole($role);

            return $user;
        });
    }
}