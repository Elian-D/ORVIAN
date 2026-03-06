<?php

namespace Database\Seeders\TenantInit;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant\School;
use Illuminate\Support\Facades\Hash;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Escuela A y su Director
        $schoolA = School::factory()->create([
            'name' => 'Escuela Primaria Los Prados',
            'sigerd_code' => '100001'
        ]);

        User::create([
            'name' => 'Director Escuela A',
            'email' => 'admin_a@orvian.com',
            'password' => Hash::make('password'),
            'school_id' => $schoolA->id,
        ]);

        // 2. Crear Escuela B y su Director
        $schoolB = School::factory()->create([
            'name' => 'Colegio Santa Teresa',
            'sigerd_code' => '200002'
        ]);

        User::create([
            'name' => 'Director Escuela B',
            'email' => 'admin_b@orvian.com',
            'password' => Hash::make('password'),
            'school_id' => $schoolB->id,
        ]);

        $this->command->info('✅ Datos de desarrollo creados: admin_a@orvian.com y admin_b@orvian.com');
    }
}