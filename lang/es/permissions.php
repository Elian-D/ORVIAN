<?php

return [

    // --- TENANT (Dentro de la Escuela) ---

    'users' => [
        'view'        => ['label' => 'Ver usuarios',        'description' => 'Permite consultar la lista y perfiles de usuarios.'],
        'create'      => ['label' => 'Crear usuarios',      'description' => 'Permite registrar nuevos usuarios manualmente.'],
        'edit'        => ['label' => 'Editar usuarios',     'description' => 'Permite modificar datos de usuarios existentes.'],
        'delete'      => ['label' => 'Eliminar usuarios',   'description' => 'Permite dar de baja o eliminar registros de usuarios.'],
        // Global
        'impersonate' => ['label' => 'Suplantar usuarios',   'description' => 'Permite loguearse como cualquier usuario para soporte.'],
    ],

    'roles' => [
        'view'   => ['label' => 'Ver roles',           'description' => 'Permite ver la lista de roles y sus permisos.'],
        'create' => ['label' => 'Crear roles',         'description' => 'Permite crear nuevos roles personalizados para la escuela.'],
        'edit'   => ['label' => 'Editar roles',        'description' => 'Permite modificar permisos de roles existentes.'],
        'delete' => ['label' => 'Eliminar roles',      'description' => 'Permite eliminar roles que no sean del sistema.'],
        // Global
        'manage' => ['label' => 'Gestionar roles globales', 'description' => 'Permite crear, editar y eliminar roles del sistema (Admin Hub).'],
        'inspect'=> ['label' => 'Inspeccionar roles', 'description' => 'Permite ver detalles técnicos de roles globales.'],
    ],

    'settings' => [
        'view'   => ['label' => 'Ver configuración',   'description' => 'Permite ver los datos institucionales del centro.'],
        'update' => ['label' => 'Editar configuración', 'description' => 'Permite actualizar la información y preferencias del centro.'],
    ],

    // --- GLOBAL (Admin Hub / Orvian Team) ---

    'schools' => [
        'view'   => ['label' => 'Ver escuelas',    'description' => 'Lista completa de centros.'],
        'create' => ['label' => 'Registrar escuelas', 'description' => 'Alta de nuevos centros.'],
        'edit'   => ['label' => 'Editar escuelas',     'description' => 'Modificar estatus, planes o datos de centros.'],
        'delete' => ['label' => 'Eliminar escuelas',   'description' => 'Eliminación parcial de centros del ecosistema.'],
    ],

    'plans' => [
        'view'   => ['label' => 'Ver planes',            'description' => 'Consultar planes de suscripción disponibles.'],
        'manage' => ['label' => 'Gestionar planes',      'description' => 'Crear, editar y configurar límites de los planes.'],
    ],

    'global_users' => [
        'view'   => ['label' => 'Ver personal Orvian', 'description' => 'Ver lista de administradores globales.'],
        'manage' => ['label' => 'Gestionar personal',  'description' => 'Administrar accesos del equipo de soporte y admin.'],
    ],

    'admin' => [
        'access' => ['label' => 'Acceso al Admin Hub',  'description' => 'Permiso maestro para entrar al panel de control global.'],
    ],

    'logs' => [
        'activity' => ['label' => 'Ver logs de actividad', 'description' => 'Auditoría de acciones realizadas en el sistema.'],
        'auth'     => ['label' => 'Ver logs de acceso',    'description' => 'Historial de inicios de sesión y fallos de seguridad.'],
    ],

];