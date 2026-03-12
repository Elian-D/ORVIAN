<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

class Button extends Component
{
    public function __construct(
        public string $variant = 'primary', 
        public string $type = 'solid',    
        public string $size = 'md',       
        public ?string $iconLeft = null,
        public ?string $iconRight = null,
        public ?string $icon = null, // Nueva propiedad simplificada para solo icono
        public bool $disabled = false,
        public bool $fullWidth = false,
        public bool $hoverEffect = false,
    ) {}

    public function getButtonClasses(bool $isIconOnly): string
    {
        $base = "inline-flex items-center justify-center font-semibold rounded-orvian transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed";
        
        // Dimensiones rectangulares vs cuadradas
        $sizes = $isIconOnly ? [
            'sm' => 'w-8 h-8 text-xs',
            'md' => 'w-11 h-11 text-sm',
            'lg' => 'w-14 h-14 text-base',
            'xl' => 'w-16 h-16 text-lg',
        ] : [
            'sm' => 'px-4 py-2 text-xs gap-1.5',
            'md' => 'px-6 py-3 text-sm gap-2',
            'lg' => 'px-8 py-4 text-base gap-2.5',
            'xl' => 'px-10 py-5 text-lg gap-3',
        ];

        $variants = [
            'primary'   => [
                'solid'   => 'bg-orvian-orange text-white hover:opacity-90 focus:ring-orvian-orange/50', 
                'outline' => 'border-2 border-orvian-orange text-orvian-orange hover:bg-orvian-orange/5'
            ],
            'secondary' => [
                'solid'   => 'bg-orvian-navy text-white hover:opacity-90 focus:ring-orvian-navy/50', 
                'outline' => 'border-2 border-orvian-navy text-orvian-navy hover:bg-orvian-navy/5'
            ],
            'success'   => [
                'solid'   => 'bg-state-success text-white hover:opacity-90 focus:ring-state-success/50', 
                'outline' => 'border-2 border-state-success text-state-success bg-state-success/10 hover:bg-state-success/20'
            ],
            'warning'   => [
                'solid'   => 'bg-state-warning text-white hover:opacity-90 focus:ring-state-warning/50', 
                'outline' => 'border-2 border-state-warning text-state-warning bg-state-warning/10 hover:bg-state-warning/20'
            ],
            'info'      => [
                'solid'   => 'bg-state-info text-white hover:opacity-90 focus:ring-state-info/50', 
                'outline' => 'border-2 border-state-info text-state-info bg-state-info/10 hover:bg-state-info/20'
            ],
            'error'     => [
                'solid'   => 'bg-state-error text-white hover:opacity-90 focus:ring-state-error/50', 
                'outline' => 'border-2 border-state-error text-state-error bg-state-error/10 hover:bg-state-error/20'
            ],
            'link'      => [
                'solid'   => 'text-orvian-navy hover:text-orvian-orange p-0', 
                'outline' => 'text-orvian-navy border-b border-orvian-navy/30 hover:border-orvian-orange hover:text-orvian-orange p-0 rounded-none bg-transparent'
            ],
        ];

        $classes = [$base, $sizes[$this->size]];
        $classes[] = $variants[$this->variant][$this->type] ?? $variants['primary']['solid'];
        
        if ($this->fullWidth && !$isIconOnly) $classes[] = 'w-full';
        
        if ($this->hoverEffect && !$this->disabled) {
            $classes[] = 'hover:scale-[1.03] active:scale-[0.98] shadow-lg shadow-orvian-orange/20';
        }

        return implode(' ', $classes);
    }

    public function render()
    {
        return view('components.ui.button');
    }
}