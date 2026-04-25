<?php

namespace App\Actions\Tenant;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateSchoolPrincipalAction
{
    public function execute(array $data, int $schoolId): User
    {
        setPermissionsTeamId($schoolId);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'school_id' => $schoolId,
        ]);

        $tenantRole = Role::where('name', 'School Principal')
            ->where('school_id', $schoolId)
            ->firstOrFail();

        $user->assignRole($tenantRole);

        return $user;
    }
}