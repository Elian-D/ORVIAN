<?php

namespace App\View\Components\Ui;

use App\Models\User;
use App\Services\Users\UserAvatarService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Avatar extends Component
{
    public string $initials;
    public string $bgColor;
    public string $sizeClass;
    public ?string $avatarPath = null;

    public function __construct(
        public ?User $user = null,
        public string $size = 'md',
        public ?string $name = null,
        public ?string $color = null,
    ) {
        $avatarService = app(UserAvatarService::class);

        $displayName = $name ?? ($user ? $user->name : 'Orvian');

        $this->initials = $avatarService->initials($displayName);
        $this->bgColor = $color ?? ($user ? $user->avatar_color : '#FF8A65');
        $this->avatarPath = $user ? $user->avatar_path : null;
        $this->sizeClass = $this->getSizeClass($size);
    }

    private function getSizeClass(string $size): string
    {
        return match ($size) {
            'sm' => 'h-8 w-8 text-[12px]',
            'lg' => 'h-12 w-12 text-base',
            'xl' => 'h-16 w-16 text-xl',
            default => 'h-10 w-10 text-sm',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.avatar');
    }
}