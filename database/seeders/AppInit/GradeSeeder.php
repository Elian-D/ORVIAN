<?php

namespace Database\Seeders\AppInit;

use App\Models\Tenant\Academic\Grade;
use App\Models\Tenant\Academic\Level;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'primaria' => [
                'Primer Ciclo'  => ['1ro', '2do', '3ro'],
                'Segundo Ciclo' => ['4to', '5to', '6to'],
            ],
            'secundaria-primer-ciclo' => [
                'Primer Ciclo'  => ['1ro', '2do', '3ro'],
            ],
            'secundaria-segundo-ciclo' => [
                'Segundo Ciclo' => ['4to', '5to', '6to'],
            ],
        ];

        foreach ($structure as $levelSlug => $cycles) {
            $level = Level::where('slug', $levelSlug)->first();
            
            if (!$level) continue;

            $order = 1;
            foreach ($cycles as $cycleName => $names) {
                foreach ($names as $name) {
                    Grade::updateOrCreate(
                        [
                            'level_id' => $level->id, 
                            'name'     => $name . ' ' . explode(' ', $level->name)[0], // Ej: "1ro Primaria"
                        ],
                        [
                            'cycle'            => $cycleName,
                            'order'            => $order++,
                            'allows_technical' => ($levelSlug === 'secundaria-segundo-ciclo'),
                        ]
                    );
                }
            }
        }
    }
}