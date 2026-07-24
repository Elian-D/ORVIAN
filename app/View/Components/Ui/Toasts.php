<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Toasts extends Component
{
    /**
     * Create a new component instance.
     *
     * @param bool $suppressValidationToast Omite el toast automático de $errors->any() —
     *   úsalo en vistas donde x-ui.forms.* ya muestra el error inline bajo cada campo.
     */
    public function __construct(
        public bool $suppressValidationToast = false,
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.toasts');
    }
}