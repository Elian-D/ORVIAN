<?php

namespace App\Actions\Tenant;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateSchoolPrincipalAction
{
    /**
     * Crea el usuario Director y le asigna el rol correspondiente en el scope de la escuela.
     */
    public function execute(array $data, int $schoolId): User
    {
        return DB::transaction(function () use ($data, $schoolId) {
            // 1. Crear el usuario vinculado físicamente a la escuela
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'school_id' => $schoolId,
            ]);

            // 2. Configurar el scope de Spatie para esta escuela específica
            setPermissionsTeamId($schoolId);

            // 3. Asignar el rol de 'School Principal' (Previamente creado en el seeder)
            $user->assignRole('School Principal');

            return $user;
        });
    }
}