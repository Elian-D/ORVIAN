<?php

namespace App\Livewire\Shared;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class UserStatus extends Component
{
    public string $status = 'offline';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->status = $user->status ?? 'offline';
    }

    public function setStatus(string $status): void
    {
        $allowed = ['online', 'away', 'busy', 'offline'];

        if (! in_array($status, $allowed)) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        $user->update(['status' => $status]);

        $this->status = $status;

        $labels = [
            'online'  => 'En línea',
            'away'    => 'Ausente',
            'busy'    => 'Ocupado',
            'offline' => 'Desconectado',
        ];

        $this->dispatch('notify-redirect',
            type:    'success',
            title:   'Estado actualizado',
            message: 'Tu estado ahora es: ' . $labels[$status],
        );

        $this->redirect(request()->header('Referer') ?? route('app.dashboard'));
    }

    public function render()
    {
        return view('livewire.shared.user-status');
    }
}