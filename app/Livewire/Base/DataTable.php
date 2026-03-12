<?php

namespace App\Livewire\Base;

use Livewire\Component;
use Livewire\WithPagination;

abstract class DataTable extends Component
{
    use WithPagination;

    // Estado de columnas visibles
    public array $visibleColumns = [];

    // Filtros (vinculados al Pipeline que ya hicimos)
    public array $filters = [];

    public function mount()
    {
        // Inicializamos con las columnas de escritorio por defecto
        $this->visibleColumns = $this->getTableDefinition()::defaultDesktop();
    }

    /**
     * Define qué clase de configuración usa esta tabla (ej: SchoolTable::class)
     */
    abstract protected function getTableDefinition(): string;

    /**
     * Alternar visibilidad de una columna
     */
    public function toggleColumn($column)
    {
        if (in_array($column, $this->visibleColumns)) {
            $this->visibleColumns = array_diff($this->visibleColumns, [$column]);
        } else {
            $this->visibleColumns[] = $column;
        }
    }

    // El render se encarga de aplicar los filtros usando el Pipeline
    abstract public function render();
}