<?php

namespace Database\Seeders\TenantInit;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basic',
                'limit_students' => 150,
                'limit_users' => 10,
                'price' => 0.00,
                'const_name' => 'BASIC',
                'bg_color' => '#F3F4F6', // gray-100
                'text_color' => '#4B5563', // gray-600
            ],
            [
                'name' => 'Profesional',
                'slug' => 'premium',
                'limit_students' => 500,
                'limit_users' => 30,
                'price' => 1500.00,
                'const_name' => 'PREMIUM',
                'bg_color' => '#DBEAFE', // blue-100
                'text_color' => '#1E40AF', // blue-800
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'limit_students' => 2000,
                'limit_users' => 100,
                'price' => 5000.00,
                'const_name' => 'ENTERPRISE',
                'bg_color' => '#FEF3C7', // amber-100
                'text_color' => '#92400E', // amber-800
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}