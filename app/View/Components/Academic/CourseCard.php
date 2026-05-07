<?php

namespace App\View\Components\Academic;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Collection;

class CourseCard extends Component
{
    /**
     * @param array $grade Datos del grado (nombre, id, etc.)
     * @param Collection $sections Colección de SchoolSection
     * @param string $type Tipo de card ('academic' o 'technical')
     * @param array|null $techGroup Datos de la familia técnica (solo si $type == 'technical')
     */
    public function __construct(
        public array $grade,
        public Collection $sections,
        public string $type = 'academic',
        public ?array $techGroup = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.academic.course-card');
    }
}