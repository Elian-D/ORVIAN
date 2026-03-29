<?php

/**
 * config/modules.php
 * ──────────────────
 * Registro centralizado de módulos del panel de escuela (app/).
 *
 * FUENTE DE VERDAD ÚNICA para nombre, ícono SVG y sub-links de cada módulo.
 *
 * USO en componentes Livewire:
 *   ->layout('layouts.app-module', config('modules.configuracion'))
 *
 * Para agregar un módulo nuevo:
 *   1. Agregar la entrada aquí.
 *   2. Nada más — todos los componentes que usen esa clave lo heredan.
 *
 * Para agregar un sub-link a un módulo existente:
 *   1. Agregar el array ['label', 'route'] en moduleLinks.
 *   2. Nada más — aparece en el navbar de todos los componentes del módulo.
 *
 * NOTA: `moduleIcon` es el nombre del SVG en public/assets/icons/modules/,
 * NO un heroicon string. Ejemplo: 'administracion', no 'heroicon-o-cog'.
 */

return [

    'configuracion' => [
        'module'      => 'Configuración',
        'moduleIcon'  => 'administracion',
        'moduleLinks' => [
            ['label' => 'Usuarios',  'route' => 'app.users.index'],
            ['label' => 'Roles',     'route' => 'app.roles.index'],
            // ['label' => 'Permisos',  'route' => 'app.permissions.index'],  // fase futura
        ],
    ],

    // Descomenta cuando se construya el módulo:

    // 'asistencia' => [
    //     'module'      => 'Asistencia',
    //     'moduleIcon'  => 'asistencia',
    //     'moduleLinks' => [
    //         ['label' => 'Registro',  'route' => 'app.attendance.index'],
    //         ['label' => 'Reportes',  'route' => 'app.attendance.reports'],
    //     ],
    // ],

    // 'academico' => [
    //     'module'      => 'Académico',
    //     'moduleIcon'  => 'academico',
    //     'moduleLinks' => [
    //         ['label' => 'Secciones', 'route' => 'app.academic.sections'],
    //         ['label' => 'Materias',  'route' => 'app.academic.subjects'],
    //     ],
    // ],

    // 'notas' => [
    //     'module'      => 'Notas',
    //     'moduleIcon'  => 'notas',
    //     'moduleLinks' => [
    //         ['label' => 'Calificaciones', 'route' => 'app.grades.index'],
    //         ['label' => 'Períodos',       'route' => 'app.grades.periods'],
    //     ],
    // ],

];