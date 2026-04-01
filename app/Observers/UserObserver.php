<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Users\UserAvatarService;

class UserObserver
{
    protected $avatarService;

    public function __construct(UserAvatarService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        if (!$user->avatar_color) {
            $user->avatar_color = $this->avatarService->generateColor();
        }
    }
}