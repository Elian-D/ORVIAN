<?php

namespace App\Tables\App;

use App\Tables\Contracts\TableConfig;
use App\Tables\Concerns\HasResponsiveColumns;

class StudentTableConfig implements TableConfig
{
    use HasResponsiveColumns;

    /**
     * Catálogo completo de columnas disponibles para el módulo de estudiantes.

     */
    public static function allColumns(): array
    {
        return [
            'full_name'         => 'Nombre Completo',
            'rnc'               => 'Documento',
            'section'           => 'Sección',
            'gender'            => 'Género',
            'age'               => 'Edad',
            'status'            => 'Estado',
            'has_face_encoding' => 'Biometría',
        ];
    }

    /**
     * Columnas visibles por defecto en resolución de escritorio.

     */
    public static function defaultDesktop(): array
    {
        return [
            'full_name', 
            'rnc', 
            'section', 
            'gender', 
            'age', 
            'status'
        ];
    }

    /**
     * Columnas esenciales visibles en dispositivos móviles.

     */
    public static function defaultMobile(): array
    {
        return [
            'full_name', 
            'section', 
            'status'
        ];
    }

    /**
     * Mapeo de los filtros del componente Livewire a labels legibles para los chips.
     *
     */
    public static function filterLabels(): array
    {
        return [
            'search'            => 'Búsqueda',
            'school_section_id' => 'Sección',
            'status'            => 'Estado',
            'gender'            => 'Género',
            'has_photo'         => 'Con Foto',
            'has_face_encoding' => 'Con Biometría',
        ];
    }
}