<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

class Badge extends Component
{
    public function __construct(
        public string $variant = 'info', // primary, success, warning, error, info, slate
        public bool $dot = true,         // Mostrar el punto indicador
        public string $size = 'md',      // sm, md
    ) {}

    public function getBadgeClasses(): string
    {
        $base = "inline-flex items-center font-bold rounded-full border transition-colors duration-200 uppercase tracking-wider";
        
        $sizes = [
            'sm' => 'px-2.5 py-0.5 text-[9px] gap-1.5',
            'md' => 'px-4 py-1.5 text-xs gap-2',
        ];

        $variants = [
            'primary' => 'bg-orvian-orange/10 dark:bg-orvian-orange/20 text-orvian-orange border-orvian-orange/20',
            'success' => 'bg-state-success/10 dark:bg-state-success/20 text-state-success border-state-success/20',
            'warning' => 'bg-state-warning/10 dark:bg-state-warning/20 text-state-warning border-state-warning/20',
            'error'   => 'bg-state-error/10 dark:bg-state-error/20 text-state-error border-state-error/20',
            'info'    => 'bg-state-info/10 dark:bg-state-info/20 text-state-info border-state-info/20',
            'slate'   => 'bg-slate-500/10 dark:bg-slate-500/20 text-slate-600 dark:text-slate-400 border-slate-500/20',
        ];

        return implode(' ', [$base, $sizes[$this->size], $variants[$this->variant] ?? $variants['info']]);
    }

    public function getDotClasses(): string
    {
        $dots = [
            'primary' => 'bg-orvian-orange',
            'success' => 'bg-state-success',
            'warning' => 'bg-state-warning',
            'error'   => 'bg-state-error',
            'info'    => 'bg-state-info',
            'slate'   => 'bg-slate-500',
        ];

        return "w-2 h-2 rounded-full " . ($dots[$this->variant] ?? $dots['info']);
    }

    public function render()
    {
        return view('components.ui.badge');
    }
}