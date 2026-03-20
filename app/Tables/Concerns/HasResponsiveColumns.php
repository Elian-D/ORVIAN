<?php

namespace App\Tables\Concerns;

trait HasResponsiveColumns
{
    /**
     * Retorna las clases CSS para una celda de la tabla.
     *
     * IMPORTANTE: Este método ya NO aplica `hidden md:table-cell`.
     * La visibilidad de columnas es responsabilidad exclusiva de $visibleColumns
     * (propiedad Livewire) + el @if(in_array($key, $visibleColumns)) en la vista.
     *
     * Antes se usaba `hidden md:table-cell` aquí para esconder columnas en mobile
     * mediante CSS. Eso funcionaba en desktop (md+ muestra el elemento) pero
     * rompía en mobile: aunque el usuario agregara la columna a $visibleColumns,
     * la clase `hidden` la ocultaba igualmente porque es CSS estático.
     *
     * La regla ahora es:
     *   - ¿Debe renderizarse la columna? → lo decide el @if en la vista
     *   - ¿Qué padding/estilos tiene la celda? → lo decide cellClass()
     */
    public static function cellClass(string $column, string $extra = 'px-4 py-3.5'): string
    {
        return $extra;
    }
}