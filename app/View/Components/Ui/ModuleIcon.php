<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Componente de icono de módulo.
 * Renderiza el SVG del módulo desde public/assets/icons/modules/{name}.svg
 * Sin contenedor propio — solo el <img>. El contexto (navbar, tarjeta, etc.)
 * decide el tamaño y el fondo.
 *
 * Uso:
 *   <x-ui.module-icon name="administracion" class="w-8 h-8" />
 *   <x-ui.module-icon name="asistencia" class="w-5 h-5" />
 */
class ModuleIcon extends Component
{
    public function __construct(
        public string $name,
    ) {}

    public function render(): View
    {
        return view('components.ui.module-icon');
    }
}