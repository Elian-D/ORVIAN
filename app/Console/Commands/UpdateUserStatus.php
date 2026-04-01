<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserStatus extends Command
{
    protected $signature = 'orvian:update-user-status';
    protected $description = 'Cambia el estado de usuarios online a away tras 15 minutos de inactividad';

    public function handle()
    {
        // Usuarios que están online pero su último login fue hace más de 15 min
        $affected = User::where('status', 'online')
            ->where('last_login_at', '<', now()->subMinutes(15))
            ->update(['status' => 'away']);

        $this->info("Se han actualizado {$affected} usuarios a estado 'away'.");
    }
}