<?php

namespace Database\Factories\Tenant;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory {
    
    public function definition(): array
    {
        $gender = $this->faker->randomElement(['M', 'F']);
        
        return [
            'first_name' => $this->faker->firstName($gender === 'M' ? 'male' : 'female'),
            'last_name' => $this->faker->lastName(),
            'gender' => $gender,
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-25 years'),
            'rnc' => $this->faker->numerify('###-#######-#'),
            'specialization' => $this->faker->randomElement([
                'Matemáticas', 'Lengua Española', 'Ciencias Sociales',
                'Ciencias Naturales', 'Inglés', 'Educación Física',
            ]),
            'employment_type' => $this->faker->randomElement(['Full-Time', 'Part-Time']),
            'phone' => $this->faker->numerify('809-###-####'),
            'is_active' => $this->faker->boolean(90),
            'hire_date' => $this->faker->dateTimeBetween('-10 years', 'now'),
        ];
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