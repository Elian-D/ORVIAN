<?php

namespace App\Actions\Tenant;

use App\Models\User;
use App\Models\Role; // <-- Importar
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function execute(array $data, int $schoolId, string $roleName = 'Staff'): User
    {
        return DB::transaction(function () use ($data, $schoolId, $roleName) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'school_id' => $schoolId,
            ]);

            setPermissionsTeamId($schoolId);

            // EL FIX
            $tenantRole = Role::where('name', $roleName)
                ->where('school_id', $schoolId)
                ->firstOrFail();

            $user->assignRole($tenantRole);

            return $user;
        });
    }
}