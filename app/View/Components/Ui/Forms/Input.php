<?php

namespace App\View\Components\Ui\Forms;

use Illuminate\View\Component;

class Input extends Component
{
    public string $id;

    public function __construct(
        public string  $label       = '',
        public string  $name        = '',
        string         $id          = '',
        public string  $type        = 'text',
        public string  $placeholder = '',
        public ?string $iconLeft    = null,
        public ?string $iconRight   = null,
        public ?string $error       = null,
        public ?string $hint        = null,
        public bool    $required    = false,
        public bool    $disabled    = false,
        public bool    $readonly    = false,
    ) {
        $this->id = $id ?: $name;
    }

    /**
     * Clases del <input> según estado.
     */
    public function inputClasses(): string
    {
        $base  = 'w-full border-0 border-b bg-transparent rounded-none px-0 py-3 text-sm '
                .'transition-colors duration-200 focus:ring-0 focus:outline-none '
                .'placeholder-slate-400 dark:placeholder-slate-500 '
                .'disabled:opacity-50 disabled:cursor-not-allowed';

        $pl = $this->iconLeft  ? 'pl-7'  : '';
        $pr = ($this->iconRight || $this->error) ? 'pr-7' : '';

        if ($this->error) {
            return trim("{$base} {$pl} {$pr} border-state-error text-state-error dark:text-state-error focus:border-state-error");
        }

        return trim("{$base} {$pl} {$pr} border-slate-200 dark:border-dark-border text-slate-800 dark:text-white focus:border-orvian-orange");
    }

    /**
     * Clases base para los wrappers de iconos.
     */
    public function iconWrapClasses(bool $right = false): string
    {
        $side = $right ? 'right-0' : 'left-0';
        return "absolute {$side} top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none transition-colors duration-200";
    }

    /**
     * Clases de color para los iconos según estado.
     */
    public function iconColorClasses(): string
    {
        return $this->error
            ? 'text-state-error'
            : 'text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange';
    }

    public function render()
    {
        return view('components.ui.forms.input');
    }
}
