<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\School;
use App\Models\Tenant\Plan;
use App\Models\Geo\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'sigerd_code' => $this->faker->unique()->numerify('######'),
            'name' => $this->faker->company() . ' Academy',
            'modalidad' => School::MODALITY_ACADEMIC,
            'sector' => School::SECTOR_PRIVATE,
            'jornada' => School::SHIFT_EXTENDED,
            'municipality_id' => Municipality::inRandomOrder()->first()->id ?? 1,
            'plan_id' => Plan::where('slug', 'basic')->first()->id ?? 1,
            'is_active' => true,
            'is_configured' => true,
        ];
    }
}