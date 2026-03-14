<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UpdateUserStatusListener
{
    /**
     * Manejar el evento de Login.
     */
    public function handleLogin(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $user->update([
            'status' => 'online',
            'last_login_at' => now(),
        ]);
    }

    /**
     * Manejar el evento de Logout.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            /** @var User $user */
            $user = $event->user;

            $user->update([
                'status' => 'offline',
            ]);
        }
    }
}