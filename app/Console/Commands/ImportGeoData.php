<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Geo\Province;
use App\Models\Geo\Municipality;
use App\Models\Geo\District;
use App\Models\Geo\Neighborhood;
use App\Models\Geo\Section;

class ImportGeoData extends Command
{
    protected $signature = 'orvian:import-geo';
    protected $description = 'Importa provincias, municipios y distritos de RD desde GitHub';

    public function handle()
    {
        $this->info('🛰️ Conectando con el repositorio de datos de RD...');

        // 1. Provincias
        $this->importProvinces();
        
        // 2. Municipios
        $this->importMunicipalities();

        // 3. Distritos
        $this->importDistricts();

        // 4. Secciones 
        $this->importSections();
        
        // 5. Barrios
        $this->importNeighborhoods();

        $this->info('✅ ¡Infraestructura geográfica completada!');
    }

    private function importProvinces() {
        $data = Http::get('https://raw.githubusercontent.com/DannyFeliz/Datos-Rep-Dom/master/JSON/provincias.json')->json();
        foreach ($data as $item) {
            // En provincias la llave es 'id' y 'nombre'
            Province::updateOrCreate(
                ['id' => $item['id']], 
                ['name' => $item['nombre']]
            );
        }
        $this->line('  - Provincias importadas.');
    }

    private function importMunicipalities() {
        $data = Http::get('https://raw.githubusercontent.com/DannyFeliz/Datos-Rep-Dom/master/JSON/municipios.json')->json();
        
        foreach ($data as $index => $item) {
            // El JSON de municipios NO tiene llave 'id'. 
            // Usamos el índice + 1 para crear un ID consistente.
            Municipality::updateOrCreate(
                ['id' => $index + 1], 
                [
                    'name' => $item['nombre'], 
                    'province_id' => $item['provinciaId'] // Cambiado de id_provincia a provinciaId
                ]
            );
        }
        $this->line('  - Municipios importados.');
    }

    private function importDistricts() {
        $data = Http::get('https://raw.githubusercontent.com/DannyFeliz/Datos-Rep-Dom/master/JSON/distritos.json')->json();
        foreach ($data as $item) {
            // En distritos la llave es 'id', 'nombre' y 'municipioId'
            District::updateOrCreate(
                ['id' => $item['id']], 
                [
                    'name' => $item['nombre'], 
                    'municipality_id' => $item['municipioId'] // Cambiado de id_municipio a municipioId
                ]
            );
        }
        $this->line('  - Distritos importados.');
    }

    private function importSections() {
        $data = Http::get('https://raw.githubusercontent.com/DannyFeliz/Datos-Rep-Dom/master/JSON/secciones.json')->json();
        
        foreach ($data as $item) {
            Section::updateOrCreate(
                ['id' => $item['id']], 
                [
                    'name' => $item['nombre'], 
                    // Si distritoId es null o 0, guardamos null
                    'district_id' => ($item['distritoId'] && $item['distritoId'] > 0) ? $item['distritoId'] : null,
                    'municipality_id' => $item['municipioId']
                ]
            );
        }
        $this->line('  - Secciones importadas.');
    }

    private function importNeighborhoods() {
        $data = Http::get('https://raw.githubusercontent.com/DannyFeliz/Datos-Rep-Dom/master/JSON/barrios.json')->json();
        foreach ($data as $item) {
            Neighborhood::updateOrCreate(
                ['id' => $item['id']], 
                [
                    'name' => $item['nombre'], 
                    'section_id' => $item['seccionId']
                ]
            );
        }
        $this->line('  - Barrios importados.');
    }
}