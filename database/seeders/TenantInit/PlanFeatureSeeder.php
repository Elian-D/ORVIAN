<?php

namespace Database\Seeders\TenantInit;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Plan;
use App\Models\Tenant\Feature;

class PlanFeatureSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Definir Características (Features)
        $featuresData = [
            ['name' => 'Código QR', 'slug' => 'attendance_qr', 'module' => 'Asistencia'],
            ['name' => 'Reconocimiento Facial', 'slug' => 'attendance_facial', 'module' => 'Asistencia'],
            ['name' => 'Gestión de Notas', 'slug' => 'academic_grades', 'module' => 'Académico'],
            ['name' => 'Importación Excel', 'slug' => 'academic_excel_import', 'module' => 'Académico'],
            ['name' => 'Classroom Local', 'slug' => 'classroom_internal', 'module' => 'Classroom'],
            ['name' => 'Reportes Avanzados', 'slug' => 'reports_advanced', 'module' => 'Reportes'],
        ];

        $features = [];
        foreach ($featuresData as $f) {
            $features[$f['slug']] = Feature::updateOrCreate(['slug' => $f['slug']], $f);
        }

        // 2. Definir 7 Planes (5 Reales + 2 Test)
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basic',
                'limit_students' => 150,
                'limit_users' => 10,
                'price' => 0.00,
                'is_featured' => false,
                'is_active' => true,
                'const_name' => 'BASIC',
                'bg_color' => '#F3F4F6', // gray-100
                'text_color' => '#4B5563', // gray-600
                'features' => ['attendance_qr', 'academic_grades']
            ],
            [
                'name' => 'Profesional',
                'slug' => 'premium',
                'limit_students' => 500,
                'limit_users' => 30,
                'price' => 30.00,
                'is_featured' => true, // <-- RECOMENDADO
                'is_active' => true,
                'const_name' => 'PREMIUM',
                'bg_color' => '#DBEAFE', // blue-100
                'text_color' => '#1E40AF', // blue-800
                'features' => ['attendance_qr', 'attendance_facial', 'academic_grades', 'academic_excel_import', 'classroom_internal']
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'limit_students' => 2000,
                'limit_users' => 100,
                'price' => 90.00,
                'is_featured' => false,
                'is_active' => true,
                'const_name' => 'ENTERPRISE',
                'bg_color' => '#FEF3C7', // amber-100
                'text_color' => '#92400E', // amber-800
                'features' => ['attendance_qr', 'attendance_facial', 'academic_grades', 'academic_excel_import', 'classroom_internal', 'reports_advanced']
            ],
        ];

        // 3. Crear Planes y Sincronizar Features
        foreach ($plans as $planData) {
            $featureSlugs = $planData['features'];
            unset($planData['features']);

            $plan = Plan::updateOrCreate(['slug' => $planData['slug']], $planData);

            $featureIds = collect($featureSlugs)->map(fn($slug) => $features[$slug]->id);
            $plan->features()->sync($featureIds);
        }
    }
}