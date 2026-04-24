<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\AppInit\{
    EducationalGeoSeeder,
    GeoDataSeeder,
    GradeSeeder,
    LevelSeeder,
    RoleAcademicSeeder,
    RoleOwnerSeeder,
    PermissionGroupSeeder,
    PermissionSeeder,
    SubjectSeeder,
    TechnicalCatalogSeeder
};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\TenantInit\DevelopmentSeeder;
use Database\Seeders\TenantInit\PlanFeatureSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        /*User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */

        $this->call([
                GeoDataSeeder::class,
                EducationalGeoSeeder::class,
                LevelSeeder::class,
                GradeSeeder::class,
                TechnicalCatalogSeeder::class,
                SubjectSeeder::class,
                PermissionGroupSeeder::class,
                PermissionSeeder::class,
                RoleAcademicSeeder::class,
                //DevelopmentSeeder::class, 
                PlanFeatureSeeder::class, 
                RoleOwnerSeeder::class,
            ]);
          if (file_exists(database_path('data/modulos_formativos.csv'))) {
            $this->command->call('orvian:import-technical-modules');
  }

    }
}
