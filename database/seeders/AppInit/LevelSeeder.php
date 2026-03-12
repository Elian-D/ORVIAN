<?php

namespace Database\Seeders\AppInit;

use App\Models\Tenant\Academic\Level;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            'Primaria Primer Ciclo',
            'Primaria Segundo Ciclo',
            'Secundaria Primer Ciclo',
            'Secundaria Segundo Ciclo',
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate(
                ['slug' => Str::slug($level)],
                ['name' => $level]
            );
        }
    }
}