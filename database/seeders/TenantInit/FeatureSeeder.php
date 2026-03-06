<?php

namespace Database\Seeders\TenantInit;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Feature;

class FeatureSeeder extends Seeder
{
    // database/seeders/FeatureSeeder.php
    public function run(): void
    {
        $features = [
            // Módulo Asistencia
            ['name' => 'Reconocimiento Facial', 'slug' => 'attendance_facial', 'module' => 'Asistencia'],
            ['name' => 'Código QR', 'slug' => 'attendance_qr', 'module' => 'Asistencia'],
            
            // Módulo Académico
            ['name' => 'Gestión de Notas', 'slug' => 'academic_grades', 'module' => 'Académico'],
            ['name' => 'Importación Excel', 'slug' => 'academic_excel_import', 'module' => 'Académico'],
            
            // LMS
            ['name' => 'Classroom Local', 'slug' => 'classroom_internal', 'module' => 'Classroom'],
            
            // Reportes
            ['name' => 'Reportes Avanzados', 'slug' => 'reports_advanced', 'module' => 'Reportes'],
        ];

        foreach ($features as $f) {
            Feature::updateOrCreate(['slug' => $f['slug']], $f);
        }
    }
}