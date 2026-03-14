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
    public string $statusColor;
    public string $statusSize;
    public ?string $avatarPath = null;

    public function __construct(
        public ?User $user = null,
        public string $size = 'md',
        public ?string $name = null,
        public ?string $color = null,
        public bool $showStatus = false // Nueva prop
    ) {
        $avatarService = app(UserAvatarService::class);
        
        $displayName = $name ?? ($user ? $user->name : 'Orvian');
        
        $this->initials = $avatarService->initials($displayName);
        $this->bgColor = $color ?? ($user ? $user->avatar_color : '#FF8A65');
        $this->avatarPath = $user ? $user->avatar_path : null;
        $this->sizeClass = $this->getSizeClass($size);
        
        // Lógica de status
        $this->statusColor = $this->getStatusColor($user?->status ?? 'offline');
        $this->statusSize = $this->getStatusSize($size);
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

    private function getStatusSize(string $size): string
    {
        return match ($size) {
            'sm' => 'h-2.5 w-2.5 border-2',
            'lg' => 'h-3.5 w-3.5 border-2',
            'xl' => 'h-4 w-4 border-2',
            default => 'h-3 w-3 border-2',
        };
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'online'  => 'bg-green-500',
            'away'    => 'bg-amber-400',
            'busy'    => 'bg-red-500',
            default   => 'bg-slate-400', // offline
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.avatar');
    }
}