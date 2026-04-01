<?php

namespace App\Actions\Tenant;

use App\Models\User;
use App\Models\Role; // <-- Asegúrate de importar tu modelo Role extendido
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateSchoolPrincipalAction
{
    public function execute(array $data, int $schoolId): User
    {
        setPermissionsTeamId($schoolId);

        return DB::transaction(function () use ($data, $schoolId) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'school_id' => $schoolId,
            ]);

            // EL FIX: Buscamos manualmente el rol EXACTO de este tenant
            $tenantRole = Role::where('name', 'School Principal')
                ->where('school_id', $schoolId)
                ->firstOrFail();

            // Le pasamos el objeto a Spatie, no el string
            $user->assignRole($tenantRole);

            return $user;
        });
    }
}