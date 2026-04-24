<?php

namespace Database\Seeders\AppInit;

use App\Models\Tenant\Academic\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            // --- Básicas transversales ---
            [
                'code' => 'LEN',
                'name' => 'Lengua Española',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 3,
                'color' => '#3B82F6'
            ],
            [
                'code' => 'MAT',
                'name' => 'Matemática',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 3,
                'color' => '#10B981'
            ],
            [
                'code' => 'CSO',
                'name' => 'Ciencias Sociales',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 2,
                'color' => '#F59E0B'
            ],
            [
                'code' => 'CNA',
                'name' => 'Ciencias de la Naturaleza',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 3,
                'color' => '#84CC16'
            ],
            [
                'code' => 'FHR',
                'name' => 'Formación Integral Humana y Religiosa',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 1,
                'color' => '#8B5CF6'
            ],
            [
                'code' => 'EDF',
                'name' => 'Educación Física',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 1,
                'color' => '#EF4444'
            ],
            [
                'code' => 'EDA',
                'name' => 'Educación Artística',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 1,
                'color' => '#EC4899'
            ],
            [
                'code' => 'ING',
                'name' => 'Lenguas Extranjeras (Inglés)',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 4,
                'color' => '#06B6D4'
            ],
            [
                'code' => 'INT',
                'name' => 'Inglés Técnico',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 4,
                'color' => '#0EA5E9'
            ],

            // --- Módulos comunes ETP (Transversales a todos los títulos) ---
            [
                'code' => 'MF_002_3',
                'name' => 'Ofimática',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 3,
                'hours_total' => 135,
                'color' => '#6366F1'
            ],
            [
                'code' => 'MF_004_3',
                'name' => 'Emprendimiento',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 3,
                'hours_total' => 120,
                'color' => '#F97316'
            ],
            [
                'code' => 'MF_006_3',
                'name' => 'Formación y Orientación Laboral',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 2,
                'hours_total' => 90,
                'color' => '#A78BFA'
            ],
            [
                'code' => 'PST',
                'name' => 'Formación en centros de trabajo (Pasantía)',
                'type' => Subject::TYPE_BASIC,
                'hours_weekly' => 8,
                'hours_total' => 360,
                'color' => '#0f5ead'
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['code' => $subject['code']], // Buscamos por código
                $subject                     // Actualizamos o creamos con el resto de datos
            );
        }
    }
}