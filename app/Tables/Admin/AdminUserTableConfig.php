<?php

namespace App\Tables\Admin;

use App\Tables\Concerns\HasResponsiveColumns;
use App\Tables\Contracts\TableConfig;

class AdminUserTableConfig implements TableConfig
{
    use HasResponsiveColumns;

    public static function allColumns(): array
    {
        return [
            'name'          => 'Nombre',
            'email'         => 'Correo Electrónico',
            'role'          => 'Rol',
            'status'        => 'Estado',
            'last_login_at' => 'Último Acceso',
            'position'      => 'Cargo',
        ];
    }

    public static function defaultDesktop(): array
    {
        return ['name', 'email', 'role', 'status', 'last_login_at'];
    }

    public static function defaultMobile(): array
    {
        return ['name', 'role'];
    }

    public static function filterLabels(): array
    {
        return [
            'search' => 'Búsqueda',
            'role'   => 'Rol',
            'status' => 'Estado',
            'trashed' => 'Eliminados',
        ];
    }
    
}