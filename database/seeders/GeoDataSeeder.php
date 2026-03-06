<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/sql/geo_data_rd.sql');
        
        // Ejecutamos el comando directamente al binario de mysql de la base de datos
        // Usamos las variables de entorno para no hardcodear credenciales
        $command = sprintf(
            'mysql -h %s -u %s -p%s %s < %s',
            config('database.connections.mysql.host'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $path
        );

        exec($command);
    }
}