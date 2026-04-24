<?php

namespace Database\Seeders\Development;

use App\Models\Tenant\Student;
use App\Models\Tenant\School;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        // Obtenemos la primera escuela para asignar los estudiantes
        $school = School::first();

        if (!$school) {
            $this->command->warn('No hay escuelas registradas. Abortando StudentSeeder.');
            return;
        }

        // 1. 20 estudiantes con User (Classroom Virtual) y Foto (20%)
        Student::factory()
            ->count(20)
            ->withUser($school->id)
            ->withPhoto()
            ->create(['school_id' => $school->id]);

        // 2. 20 estudiantes solo con Foto (para completar el 40% con foto)
        Student::factory()
            ->count(20)
            ->withPhoto()
            ->create(['school_id' => $school->id]);

        // 3. Los 60 restantes (sin user, sin foto)
        // El factory ya maneja el 85% de is_active por defecto
        Student::factory()
            ->count(60)
            ->create(['school_id' => $school->id]);

        $this->command->info('100 estudiantes creados exitosamente.');
    }
}