<?php

namespace App\View\Components\Ui\Forms;

use Illuminate\View\Component;

class Textarea extends Component
{
    public string $id;

    public function __construct(
        public string  $label       = '',
        public string  $name        = '',
        string         $id          = '',
        public string  $placeholder = '',
        public int     $rows        = 3,
        public ?string $error       = null,
        public ?string $hint        = null,
        public bool    $required    = false,
        public bool    $disabled    = false,
        public bool    $readonly    = false,
        public bool    $resize      = false,
    ) {
        $this->id = $id ?: $name;
    }

    public function textareaClasses(): string
    {
        $resize = $this->resize ? 'resize-y' : 'resize-none';

        $base  = "w-full border-0 border-b bg-transparent rounded-none px-0 py-3 text-sm "
                ."{$resize} transition-colors duration-200 focus:ring-0 focus:outline-none "
                ."placeholder-slate-400 dark:placeholder-slate-500 "
                ."disabled:opacity-50 disabled:cursor-not-allowed";

        if ($this->error) {
            return "{$base} border-state-error text-state-error focus:border-state-error";
        }

        return "{$base} border-slate-200 dark:border-dark-border text-slate-800 dark:text-white focus:border-orvian-orange";
    }

    public function render()
    {
        return view('components.ui.forms.textarea');
    }
}
