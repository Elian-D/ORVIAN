<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class EducationalGeoSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/sql/educational_geo_data.sql');
        
        if (!file_exists($path)) return;

        // Extraemos las variables para que el comando sea más limpio
        $host = config('database.connections.mysql.host');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $db   = config('database.connections.mysql.database');

        // Usamos MYSQL_PWD antes del comando para inyectar la clave de forma segura
        // El comando ya NO lleva el -p seguido de la clave
        $command = sprintf(
            'MYSQL_PWD=%s mysql -h %s -u %s %s < %s',
            escapeshellarg($pass),
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($db),
            escapeshellarg($path)
        );

        exec($command);
    }
}