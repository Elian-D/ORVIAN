<?php

namespace App\Tables\Admin;

use App\Tables\Concerns\HasResponsiveColumns;
use App\Tables\Contracts\TableConfig;

class SchoolTableConfig implements TableConfig
{
    use HasResponsiveColumns;

    /**
     * Definición de todas las columnas disponibles para el módulo de Escuelas.
     */
    public static function allColumns(): array
    {
        return [
            'name'          => 'Escuela / Centro',
            'principal'     => 'Director/a',
            'plan'          => 'Plan',
            'users_count'   => 'Usuarios',
            'health'        => 'Salud / Actividad',
            'is_active'     => 'Estado',
            'created_at'    => 'Fecha Registro',
        ];
    }

    /**
     * Columnas visibles por defecto en pantallas grandes.
     */
    public static function defaultDesktop(): array
    {
        return [
            'name', 
            'plan', 
            'users_count', 
            'health', 
            'is_active'
        ];
    }

    /**
     * Columnas críticas visibles en dispositivos móviles.
     */
    public static function defaultMobile(): array
    {
        return ['name', 'health'];
    }

    /**
     * Etiquetas para los filtros en la UI.
     */
    public static function filterLabels(): array
    {
        return [
            'search' => 'Búsqueda (Nombre, SIGERD)',
            'plan'   => 'Plan de Suscripción',
            'status' => 'Estado de Acceso',
            'suspended' => 'Estado de Pago',
            'regional'  => 'Regional Educativa',
            'district'  => 'Distrito Educativo',
        ];
    }
}