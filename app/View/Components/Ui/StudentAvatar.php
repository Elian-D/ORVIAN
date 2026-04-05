<?php

namespace App\View\Components\Ui;

use App\Models\Tenant\Student; // Ajustar namespace según tu modelo
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StudentAvatar extends Component
{
    public string $initials;
    public string $bgColor;
    public string $sizeClass;
    public string $qrBadgeSize;

    public function __construct(
        public Student $student,
        public string $size = 'md',
        public bool $showQr = false
    ) {
        $this->initials = $this->generateInitials($student->full_name);
        $this->bgColor = $this->generateColor($student->full_name);
        $this->sizeClass = $this->getSizeClass($size);
        $this->qrBadgeSize = $this->getQrBadgeSize($size);
    }

    private function generateInitials(string $name): string
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    private function generateColor(string $name): string
    {
        // Genera un color consistente basado en el primer carácter del nombre
        $colors = [
            'A' => '#3B82F6', 'B' => '#10B981', 'C' => '#F59E0B', 'D' => '#EF4444',
            'E' => '#8B5CF6', 'F' => '#EC4899', 'G' => '#06B6D4', 'H' => '#F97316',
            'I' => '#6366F1', 'J' => '#14B8A6', 'K' => '#84CC16', 'L' => '#EAB308',
            'M' => '#D946EF', 'N' => '#0EA5E9', 'O' => '#22C55E', 'P' => '#F43F5E'
        ];

        $char = strtoupper(substr($name, 0, 1));
        return $colors[$char] ?? '#64748B'; // Slate-500 por defecto
    }

    private function getSizeClass(string $size): string
    {
        return match ($size) {
            'sm' => 'h-8 w-8 text-[10px]',
            'lg' => 'h-14 w-14 text-lg',
            'xl' => 'h-20 w-20 text-2xl',
            default => 'h-10 w-10 text-xs', // md
        };
    }

    private function getQrBadgeSize(string $size): string
    {
        return match ($size) {
            'sm' => 'h-3.5 w-3.5 p-0.5',
            'lg' => 'h-5 w-5 p-1',
            'xl' => 'h-6 w-6 p-1.5',
            default => 'h-4 w-4 p-1',
        };
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.student-avatar');
    }
}