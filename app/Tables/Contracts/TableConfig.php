<?php

namespace App\Tables\Contracts;

interface TableConfig
{
    /**
     * Catálogo completo de columnas disponibles: [clave => label].
     */
    public static function allColumns(): array;

    /**
     * Columnas visibles por defecto en escritorio.
     */
    public static function defaultDesktop(): array;

    /**
     * Columnas visibles por defecto en mobile.
     */
    public static function defaultMobile(): array;

    /**
     * Labels legibles para los chips de filtros activos.
     * Clave = nombre del campo en $filters del componente Livewire.
     * Solo necesita incluir los filtros que el módulo expone al usuario.
     */
    public static function filterLabels(): array;

    /**
     * Genera las clases CSS para una celda de la tabla según la columna.
     *
     * Esta función centraliza la lógica de responsividad para las celdas.
     * Una implementación de referencia sería:
     *
     * ```php
     * return in_array($column, static::defaultMobile())
     *     ? 'px-4 py-3.5' // Clases para columnas visibles en móvil y escritorio
     *     : 'hidden md:table-cell px-4 py-3.5'; // Clases para columnas solo de escritorio
     * ```
     *
     * @param string $column La clave de la columna (ej. 'name', 'email').
     * @return string Las clases de Tailwind CSS para el `<td>`.
     */
    public static function cellClass(string $column, string $extra = 'px-4 py-3.5'): string;
}