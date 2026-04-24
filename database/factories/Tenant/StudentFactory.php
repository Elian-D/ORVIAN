<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory {

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['M', 'F']);
        
        return [
            'school_section_id' => SchoolSection::inRandomOrder()->first()?->id,
            'first_name' => $this->faker->firstName($gender === 'M' ? 'male' : 'female'),
            'last_name' => $this->faker->lastName(),
            'gender' => $gender,
            'date_of_birth' => $this->faker->dateTimeBetween('-18 years', '-5 years'),
            'place_of_birth' => $this->faker->city(),
            'rnc' => $this->faker->numerify('###-#######-#'),
            'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'is_active' => $this->faker->boolean(85),
            'enrollment_date' => $this->faker->dateTimeBetween('-3 years', 'now'),
        ];
    }

    public function withPhoto(): static
    {
        return $this->state(fn () => [
            'photo_path' => 'students/photos/' . $this->faker->uuid() . '.jpg',
        ]);
    }

    public function withUser(?int $schoolId = null): static
    {
        return $this->state(function (array $attributes) use ($schoolId) {
            return [
                'user_id' => \App\Models\User::factory()->create([
                    // Si schoolId es nulo, intentamos buscarlo en el array (como último recurso)
                    'school_id' => $schoolId ?? $attributes['school_id'] ?? null,
                    'name' => ($attributes['first_name'] ?? 'Student') . ' ' . ($attributes['last_name'] ?? 'User'),
                ])->id,
            ];
        });
    }
}