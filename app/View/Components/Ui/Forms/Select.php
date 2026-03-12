<?php

namespace App\View\Components\Ui\Forms;

use Illuminate\View\Component;

class Select extends Component
{
    public string $id;

    public function __construct(
        public string  $label       = '',
        public string  $name        = '',
        string         $id          = '',
        public string  $placeholder = 'Seleccionar...',
        public ?string $iconLeft    = null,
        public ?string $error       = null,
        public ?string $hint        = null,
        public bool    $required    = false,
        public bool    $disabled    = false,
    ) {
        $this->id = $id ?: $name;
    }

    public function selectClasses(): string
    {
        $base  = 'w-full border-0 border-b bg-transparent rounded-none px-0 py-3 text-sm '
                .'appearance-none cursor-pointer transition-colors duration-200 '
                .'focus:ring-0 focus:outline-none pr-7 '
                .'disabled:opacity-50 disabled:cursor-not-allowed';

        $pl = $this->iconLeft ? 'pl-7' : '';

        if ($this->error) {
            return trim("{$base} {$pl} border-state-error text-state-error focus:border-state-error");
        }

        return trim("{$base} {$pl} border-slate-200 dark:border-dark-border text-slate-800 dark:text-white focus:border-orvian-orange [&>option]:text-black");
    }

    public function iconWrapClasses(bool $right = false): string
    {
        $side = $right ? 'right-0' : 'left-0';
        return "absolute {$side} top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none transition-colors duration-200";
    }

    public function iconColorClasses(): string
    {
        return $this->error
            ? 'text-state-error'
            : 'text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange';
    }

    public function render()
    {
        return view('components.ui.forms.select');
    }
}
