<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Geo\RegionalEducation;
use App\Models\Geo\EducationalDistrict;

class ImportEducationalGeoData extends Command
{
    protected $signature = 'orvian:import-educational-geo';
    protected $description = 'Importa regionales y distritos educativos del MINERD';

    public function handle()
    {
        $this->info('Obteniendo datos educativos...');

        // 1. Importar Regionales
        $regionales = Http::get('https://raw.githubusercontent.com/Elian-D/minerd-territorial-data/main/json/regions.json')->json();
        foreach ($regionales as $item) {
            RegionalEducation::updateOrCreate(
                ['id' => $item['id']],
                ['name' => $item['name']]
            );
        }
        $this->line('Regionales importadas.');

        // 2. Importar Distritos
        $distritos = Http::get('https://raw.githubusercontent.com/Elian-D/minerd-territorial-data/main/json/school_districts.json')->json();
        foreach ($distritos as $item) {
            EducationalDistrict::updateOrCreate(
                ['id' => $item['id']],
                [
                    'regional_education_id' => $item['region_id'],
                    'name' => $item['name']
                ]
            );
        }
        $this->info('Estructura educativa completada con éxito.');
    }
}