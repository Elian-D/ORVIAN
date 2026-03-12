<?php

namespace App\Console\Commands;

use App\Models\Tenant\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupStubSchools extends Command
{
    protected $signature = 'orvian:cleanup-stubs';
    protected $description = 'Elimina escuelas stub expiradas y sus usuarios asociados para evitar registros muertos.';

    public function handle()
    {
        $expiredSchools = School::where('is_configured', false)
            ->whereNotNull('stub_expires_at')
            ->where('stub_expires_at', '<', now())
            ->get();

        if ($expiredSchools->isEmpty()) {
            $this->info('No hay escuelas stub expiradas para limpiar.');
            return;
        }

        $count = $expiredSchools->count();

        DB::transaction(function () use ($expiredSchools) {
            foreach ($expiredSchools as $school) {
                // Eliminamos los usuarios vinculados a esta escuela stub
                // (Usuarios que se registraron pero nunca terminaron el wizard)
                User::where('school_id', $school->id)->delete();
                
                // Eliminamos la escuela
                $school->delete();
            }
        });

        $this->info("Éxito: Se han eliminado {$count} escuelas expiradas y sus usuarios asociados.");
    }
}