<?php

return [
    // --- TENANT ---
    'usuarios'      => ['name' => 'Gestión de Usuarios', 'description' => 'Cuentas y accesos del personal escolar.'],
    'roles'         => ['name' => 'Roles y Seguridad',   'description' => 'Control de capacidades y niveles de acceso.'],
    'configuracion' => ['name' => 'Configuración del Sistema',       'description' => 'Ajustes institucionales del centro.'],
    'academico'     => ['name' => 'Gestión Académica',   'description' => 'Control de alumnos y registros.'],
    'asistencia'    => ['name' => 'Control de Asistencia', 'description' => 'Registro y seguimiento de presencia diaria.'],
    'reportes'      => ['name' => 'Reportes y Estadísticas', 'description' => 'Análisis de datos e informes institucionales.'],

    // --- GLOBAL ---
    'escuelas'      => ['name' => 'Gestión de Escuelas', 'description' => 'Administración de centros (Tenants).'],
    'planes'        => ['name' => 'Planes y Facturación','description' => 'Configuración de suscripciones SaaS.'],
    'usuarios_globales' => ['name' => 'Usuarios del Sistema', 'description' => 'Gestión del personal administrativo de Orvian.'],
    'sistema'       => ['name' => 'Sistema y Acceso',    'description' => 'Permisos críticos de infraestructura.'],
    'logs'          => ['name' => 'Logs y Observabilidad', 'description' => 'Auditoría y monitoreo técnico del sistema.'],
    'roles_globales'=> ['name' => 'Seguridad Global', 'description' => 'Control de roles y permisos a nivel de plataforma.'],
];
