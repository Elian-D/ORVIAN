<?php

namespace App\View\Components\Ui;

use App\Models\Tenant\School;
use App\Services\Users\UserAvatarService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SchoolLogo extends Component
{
    // Solo dejamos las que NO están en el constructor
    public string $initials;
    public string $bgColor;
    public string $sizeClass;
    public ?string $logoPath = null;

    public function __construct(
        public ?School $school = null,
        public string $size = 'md',
        public ?string $name = null,
        public ?string $color = null,
        public ?string $uploadModel = null, // PHP 8 ya crea la propiedad automáticamente aquí
    ) {
        $avatarService = app(UserAvatarService::class);
        
        $displayName = $name ?? ($school ? $school->name : 'Centro Educativo');
        
        $this->initials = $avatarService->initials($displayName);
        
        $this->bgColor = $color ?? '#334155';
        $this->logoPath = $school ? $school->logo_path : null;
        
        $this->sizeClass = $this->getSizeClass($size);
    }

    private function getSizeClass(string $size): string
    {
        return match ($size) {
            'xs'    => 'h-6 w-6 text-[10px]',
            'sm'    => 'h-8 w-8 text-[12px]',
            'lg'    => 'h-16 w-16 text-xl',
            'xl'    => 'h-24 w-24 text-3xl',
            '2xl'   => 'h-32 w-32 text-4xl',
            default => 'h-12 w-12 text-base',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.school-logo');
    }
}