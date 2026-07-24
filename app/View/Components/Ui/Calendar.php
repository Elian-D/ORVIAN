<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

/**
 * Calendario mensual con estado por día, agnóstico del origen de los datos:
 * recibe cada día ya resuelto (status success/warning/error/null) y solo
 * se encarga de la cuadrícula, la navegación de mes y la selección de fecha.
 */
class Calendar extends Component
{
    public function __construct(
        public iterable $days,
        public string $month,
        public string $selectMethod = 'selectDate',
        public string $previousMethod = 'previousMonth',
        public string $nextMethod = 'nextMonth',
    ) {}

    public function getStatusDotClasses(?string $status): string
    {
        return match ($status) {
            'success' => 'bg-emerald-500',
            'warning' => 'bg-amber-500',
            'error'   => 'bg-red-500',
            default   => '',
        };
    }

    public function render()
    {
        return view('components.ui.calendar');
    }
}
