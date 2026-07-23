<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\School;
use App\Models\Tenant\Plan;
use App\Models\Geo\Municipality;
use App\Models\Geo\Province;
use App\Models\Geo\RegionalEducation;
use App\Models\Geo\EducationalDistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $municipality = Municipality::inRandomOrder()->first() ?? $this->makeFallbackMunicipality();
        $educationalDistrict = EducationalDistrict::inRandomOrder()->first() ?? $this->makeFallbackEducationalDistrict();
        $plan = Plan::where('slug', 'basic')->first() ?? $this->makeFallbackPlan();

        return [
            'sigerd_code' => $this->faker->unique()->numerify('######'),
            'name' => $this->faker->company() . ' Academy',
            'modalidad' => School::MODALITY_ACADEMIC,
            'regimen_gestion' => School::REGIMEN_PRIVATE,
            'regional_education_id' => $educationalDistrict->regional_education_id,
            'educational_district_id' => $educationalDistrict->id,
            'municipality_id' => $municipality->id,
            'plan_id' => $plan->id,
            'is_active' => true,
            'is_configured' => true,
        ];
    }

    private function makeFallbackMunicipality(): Municipality
    {
        $province = Province::first() ?? Province::create(['name' => 'Distrito Nacional']);

        return Municipality::create([
            'name' => 'Municipio de Prueba',
            'province_id' => $province->id,
        ]);
    }

    private function makeFallbackEducationalDistrict(): EducationalDistrict
    {
        $regional = RegionalEducation::first() ?? RegionalEducation::create([
            'id' => '00',
            'name' => 'Regional de Prueba',
        ]);

        return EducationalDistrict::firstOrCreate(
            ['id' => '00-00'],
            ['regional_education_id' => $regional->id, 'name' => 'Distrito de Prueba'],
        );
    }

    private function makeFallbackPlan(): Plan
    {
        return Plan::firstOrCreate(
            ['slug' => 'basic'],
            ['name' => 'Basic', 'limit_students' => 1000, 'limit_users' => 50],
        );
    }
}