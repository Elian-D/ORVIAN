<?php

namespace Database\Seeders\Development;

use App\Models\Tenant\Teacher;
use App\Models\Tenant\School;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();

        if (!$school) {
            $this->command->warn('No hay escuelas registradas. Abortando TeacherSeeder.');
            return;
        }

        // 1. 12 maestros con User (60%)
        Teacher::factory()
            ->count(12)
            ->withUser()
            ->create(['school_id' => $school->id]);

        // 2. 8 maestros sin User (para completar los 20)
        // El factory ya maneja el 90% de is_active por defecto
        Teacher::factory()
            ->count(8)
            ->create(['school_id' => $school->id]);

        $this->command->info('20 maestros creados exitosamente.');
    }
}