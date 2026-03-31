<?php

namespace App\Livewire\Base;

use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy]
abstract class DataTable extends Component
{
    use WithPagination;

    public array $visibleColumns = [];
    public array $filters        = [];
    public int   $perPage        = 15;

    public function mount(): void
    {
        // El servidor siempre arranca con defaultDesktop().
        // Si el cliente está en mobile, column-selector corrige en init() via JS.
        $this->visibleColumns = $this->getTableDefinition()::defaultDesktop();
    }

    // ── Hooks ──────────────────────────────────────────────────────────────

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // ── TableConfig ────────────────────────────────────────────────────────

    /** @return class-string<\App\Tables\Contracts\TableConfig> */
    abstract protected function getTableDefinition(): string;

    // ── Columnas ───────────────────────────────────────────────────────────

    /**
     * Alterna la visibilidad de una columna.
     * Guard: nunca deja la tabla sin columnas.
     */
    public function toggleColumn(string $column): void
    {
        if (in_array($column, $this->visibleColumns)) {
            $remaining = array_values(array_diff($this->visibleColumns, [$column]));

            if (count($remaining) === 0) {
                $this->visibleColumns = $this->getTableDefinition()::defaultDesktop();
                return;
            }

            $this->visibleColumns = $remaining;
        } else {
            $this->visibleColumns[] = $column;
        }
    }

    /**
     * Restaura las columnas según el dispositivo.
     *
     * @param bool $mobile  true → defaultMobile(), false → defaultDesktop()
     *
     * Alpine llama: $wire.resetColumns(isMobile)
     * El botón Restablecer pasa el contexto correcto desde el cliente.
     */
    public function resetColumns(bool $mobile = false): void
    {
        $this->visibleColumns = $mobile
            ? $this->getTableDefinition()::defaultMobile()
            : $this->getTableDefinition()::defaultDesktop();
    }

    // ── Filtros ────────────────────────────────────────────────────────────

    public function getActiveChips(): array
    {
        $labels = $this->getTableDefinition()::filterLabels();
        $chips  = [];

        foreach ($this->filters as $key => $value) {
            if ($value === '' || $value === null || $value === false || $value === 0) {
                continue;
            }

            $chips[] = [
                'key'   => $key,
                'label' => $labels[$key] ?? $key,
                // Cambiamos esta línea para usar el nuevo método:
                'value' => $this->formatFilterValue($key, $value), 
            ];
        }

        return $chips;
    }

    /**
     * Formatea el valor del filtro para mostrarlo en el Chip.
     * Los componentes hijos pueden sobreescribir este método para traducir IDs a Nombres.
     */
    protected function formatFilterValue(string $key, mixed $value): string
    {
        return is_bool($value) ? 'Sí' : (string) $value;
    }

    public function clearFilter(string $key): void
    {
        if (array_key_exists($key, $this->filters)) {
            $current = $this->filters[$key];
            $this->filters[$key] = match (true) {
                is_bool($current) => false,
                is_int($current)  => 0,
                default           => '',
            };
        }

        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->filters = array_map(function ($value) {
            return match (true) {
                is_bool($value) => false,
                is_int($value)  => 0,
                default         => '',
            };
        }, $this->filters);

        $this->resetPage();
    }

    public function placeholder(): \Illuminate\Contracts\View\View
    {
        return view('components.ui.skeleton', [
            'type' => 'table',
            'rows' => $this->perPage,
        ]);
    }


    public function paginationView(): string
    {
        return 'pagination.orvian-compact';
    }

    public function paginationSimpleView(): string
    {
        return 'pagination.orvian-compact';
    }

    abstract public function render();
}