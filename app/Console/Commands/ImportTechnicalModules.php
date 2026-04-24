<?php

namespace App\Console\Commands;

use App\Models\Tenant\Academic\Subject;
use App\Models\Tenant\Academic\TechnicalTitle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTechnicalModules extends Command
{
    protected $signature = 'orvian:import-technical-modules
                            {--file= : Ruta al CSV (default: database/data/modulos_formativos.csv)}
                            {--fresh : Elimina todos los módulos técnicos antes de importar}
                            {--dry-run : Simula la importación sin escribir en base de datos}';

    protected $description = 'Importa módulos formativos técnicos del MINERD desde el CSV generado por el script Python.';

    public function handle(): int
    {
        $file = $this->option('file') ?? database_path('data/modulos_formativos.csv');
        $isDryRun = $this->option('dry-run');
        $isFresh  = $this->option('fresh');

        if (! file_exists($file)) {
            $this->error("Archivo no encontrado: {$file}");
            $this->line("  Genera el CSV con: python extractor.py");
            $this->line("  Luego colócalo en: database/data/modulos_formativos.csv");
            return self::FAILURE;
        }

        // USO DE CONSTANTE PARA ELIMINACIÓN
        if ($isFresh && ! $isDryRun) {
            Subject::where('type', Subject::TYPE_TECHNICAL)->delete();
            $this->warn('🗑  Módulos técnicos anteriores eliminados (--fresh).');
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle); 

        $expected = ['codigo_titulo', 'titulo', 'familia_profesional', 'nivel', 'codigo_modulo', 'nombre_modulo'];
        if (array_diff($expected, $header)) {
            $this->error('El CSV no tiene las columnas esperadas. Revisa que sea el generado por extractor.py.');
            fclose($handle);
            return self::FAILURE;
        }

        // OPTIMIZACIÓN: Cargar todos los títulos en memoria (key => code, value => id)
        $titlesCache = TechnicalTitle::pluck('id', 'code');

        $bar      = $this->output->createProgressBar();
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Limpiar espacios en blanco de cada celda por inconsistencias en el CSV
                $row = array_map('trim', $row);
                $data = array_combine($header, $row);

                // FILTRO BÁSICO: Omitir filas que son errores de extracción del PDF
                if (str_starts_with(strtolower($data['nombre_modulo']), 'duración') || empty($data['codigo_modulo'])) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $titleId = $titlesCache->get($data['codigo_titulo']);

                if (! $titleId) {
                    $errors[] = "Título no encontrado: {$data['codigo_titulo']} (módulo: {$data['codigo_modulo']})";
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if (! $isDryRun) {
                    Subject::updateOrCreate(
                        ['code' => $data['codigo_modulo']],
                        [
                            'technical_title_id' => $titleId,
                            'name'               => $data['nombre_modulo'],
                            // USO DE CONSTANTE EN LA ASIGNACIÓN:
                            'type'               => Subject::TYPE_TECHNICAL, 
                            'hours_weekly'       => 0, 
                            'hours_total'        => 0, 
                            'is_active'          => true,
                        ]
                    );
                }

                $imported++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            if ($isDryRun) {
                DB::rollBack();
                $this->info("🔍 DRY RUN: Se importarían {$imported} módulos ({$skipped} omitidos/basura).");
            } else {
                DB::commit();
                $this->info("✅ {$imported} módulos importados correctamente ({$skipped} omitidos).");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error durante la importación: {$e->getMessage()}");
            return self::FAILURE;
        } finally {
            fclose($handle);
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->warn(count($errors) . ' advertencias:');
            foreach (array_slice($errors, 0, 10) as $err) {
                $this->line("  ⚠  {$err}");
            }
            if (count($errors) > 10) {
                $this->line('  ... y ' . (count($errors) - 10) . ' más.');
            }
        }

        return self::SUCCESS;
    }
}