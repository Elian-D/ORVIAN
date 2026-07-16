<?php

/**
 * config/modules.php
 * ──────────────────
 * Registro centralizado de módulos del panel de escuela (app/).
 *
 * FUENTE DE VERDAD ÚNICA para nombre, ícono SVG, sub-links y permiso
 * requerido de cada módulo. Consumido por
 * resources/views/layouts/sidebar-app.blade.php (navegación) — cada
 * moduleLink se filtra con @can('permission') antes de mostrarse, y un
 * módulo entero se oculta si ninguno de sus links es visible para el
 * usuario actual.
 *
 * NO alimenta el buscador global (REQ-07.4) — ese índice sale de metadatos
 * ->defaults('search', [...]) puestos directamente en routes/app/*.php, vía
 * App\Services\Navigation\GlobalSearchService. Son fuentes separadas a
 * propósito: el Sidebar necesita ESTRUCTURA ("Académico > Estudiantes"), el
 * buscador necesita ACCIONES ("Crear Estudiante") — cosas que no siempre
 * coinciden 1:1 con un ítem del Sidebar.
 *
 * Para agregar un módulo nuevo:
 *   1. Agregar la entrada aquí.
 *   2. Nada más — el Sidebar lo recoge automáticamente.
 *
 * Para agregar un sub-link a un módulo existente:
 *   1. Agregar el array ['label', 'route', 'permission'] en moduleLinks.
 *   2. `permission` debe ser EXACTAMENTE el mismo permiso que protege esa
 *      ruta vía ->middleware('can:...') en routes/app/*.php — si difieren,
 *      el Sidebar puede mostrar un link que la ruta rechaza (403), o
 *      esconder uno al que el usuario sí tiene acceso. Si la ruta cambia de
 *      permiso, actualiza aquí también (no hay verificación automática).
 *   3. Nada más — aparece en el Sidebar solo para quien tenga ese permiso.
 *   4. Si esa página debe ser buscable, agrega el ->defaults('search', [...])
 *      correspondiente en el archivo de rutas — ver routes/app/*.php.
 *
 * NOTA: `moduleIcon` es el nombre del SVG en public/assets/icons/modules/,
 * NO un heroicon string. Ejemplo: 'administracion', no 'heroicon-o-cog'.
 */

return [

    'configuracion' => [
        'module'      => 'Administración',
        'moduleIcon'  => 'administracion',
        'moduleLinks' => [
            ['label' => 'Centro',    'route' => 'app.school.settings', 'permission' => 'settings.view'],
            ['label' => 'Usuarios',  'route' => 'app.users.index',     'permission' => 'users.view'],
            ['label' => 'Roles',     'route' => 'app.roles.index',     'permission' => 'roles.view'],
        ],
    ],

    'asistencia' => [
        'module'      => 'Asistencia',
        'moduleIcon'  => 'asistencia',
        'moduleLinks' => [
            // Fase 5 (piloto): solo el Pase de Lista se oculta a nivel de ruta
            // (es 100% aula). Dashboard y Reportes se quedan — mezclan datos
            // de Plantel con paneles de aula/pasilleo, que se comentan dentro
            // de cada blade en vez de tumbar la vista completa (REQ-05.13).
            ['label' => 'Dashboard',              'route' => 'app.attendance.dashboard',       'permission' => 'attendance_plantel.reports'],
            ['label' => 'Plantel',                'route' => 'app.attendance.plantel.index',   'permission' => 'attendance_plantel.view'],
            ['label' => 'Sesión del Día',         'route' => 'app.attendance.session',         'permission' => 'attendance_plantel.open_session'],
            ['label' => 'Control diario',         'route' => 'app.attendance.hub',             'permission' => 'attendance_plantel.view'],
            ['label' => 'Excusas',                'route' => 'app.attendance.excuses.index',   'permission' => 'excuses.view'],
            // ['label' => 'Pase de Lista',      'route' => 'app.attendance.classroom.live'],
            ['label' => 'Reportes',               'route' => 'app.attendance.reports',         'permission' => 'attendance_plantel.reports'],
            ['label' => 'Configuración Horaria',  'route' => 'app.attendance.shift-windows',   'permission' => 'settings.update'],
        ],
    ],

    'academico' => [
        'module'      => 'Académico',
        'moduleIcon'  => 'academico',
        'moduleLinks' => [
            ['label' => 'Estructura Académica', 'route' => 'app.academic.courses.index',    'permission' => 'settings.view'],
            ['label' => 'Estudiantes',          'route' => 'app.academic.students.index',   'permission' => 'students.view'],
            ['label' => 'Maestros',             'route' => 'app.academic.teachers.index',   'permission' => 'teachers.view'],
            ['label' => 'Matriculación',        'route' => 'app.academic.enrollment-hub',   'permission' => 'students.edit'],
            ['label' => 'Registro Facial',      'route' => 'app.academic.biometric-kiosk',  'permission' => 'students.edit'],
            // ['label' => 'Gestión de Carnets', 'route' => 'app.academic.students.print-manager'],
        ],
    ],

    // Descomenta cuando se construya el módulo:


    // 'notas' => [
    //     'module'      => 'Notas',
    //     'moduleIcon'  => 'notas',
    //     'moduleLinks' => [
    //         ['label' => 'Calificaciones', 'route' => 'app.grades.index', 'permission' => 'grades.view'],
    //         ['label' => 'Períodos',       'route' => 'app.grades.periods', 'permission' => 'grades.view'],
    //     ],
    // ],

];
