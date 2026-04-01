<?php

namespace App\Tables\Admin;

use App\Tables\Contracts\TableConfig;
use App\Tables\Concerns\HasResponsiveColumns;

class RoleTableConfig implements TableConfig
{
    use HasResponsiveColumns;

    /**
     * Catálogo completo de columnas disponibles.
     */
    public static function allColumns(): array
    {
        return [
            'name'        => 'Rol',
            'users_count' => 'Usuarios',
            'is_system'   => 'Tipo',
            'created_at'  => 'Creado',
        ];
    }

    /**
     * Columnas visibles por defecto en escritorio.
     */
    public static function defaultDesktop(): array
    {
        return ['name', 'users_count', 'is_system'];
    }

    /**
     * Columnas visibles por defecto en móvil.
     */
    public static function defaultMobile(): array
    {
        return ['name', 'users_count'];
    }

    /**
     * Labels para chips de filtros activos.
     */
    public static function filterLabels(): array
    {
        return [
            'search'    => 'Búsqueda',
            'is_system' => 'Tipo de rol',
        ];
    }
}