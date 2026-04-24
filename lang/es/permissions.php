<?php

return [

    // --- TENANT (Dentro de la Escuela) ---

    'users' => [
        'view'        => ['label' => 'Ver usuarios',        'description' => 'Permite consultar la lista y perfiles de usuarios.'],
        'create'      => ['label' => 'Crear usuarios',      'description' => 'Permite registrar nuevos usuarios manualmente.'],
        'edit'        => ['label' => 'Editar usuarios',     'description' => 'Permite modificar datos de usuarios existentes.'],
        'delete'      => ['label' => 'Eliminar usuarios',   'description' => 'Permite dar de baja o eliminar registros de usuarios.'],
        'impersonate' => ['label' => 'Suplantar usuarios',   'description' => 'Permite loguearse como cualquier usuario para soporte.'],
    ],

    'students' => [
        'view'   => ['label' => 'Ver estudiantes',     'description' => 'Permite consultar la lista y fichas técnicas de los estudiantes.'],
        'create' => ['label' => 'Registrar estudiantes', 'description' => 'Permite el alta manual de nuevos estudiantes en el sistema.'],
        'edit'   => ['label' => 'Editar estudiantes',   'description' => 'Permite modificar información personal y académica de los alumnos.'],
        'delete' => ['label' => 'Eliminar estudiantes', 'description' => 'Permite dar de baja o remover registros de estudiantes.'],
        'import' => ['label' => 'Importar estudiantes', 'description' => 'Permite la carga masiva de estudiantes mediante archivos externos.'],
    ],

    'teachers' => [
        'view'            => ['label' => 'Ver maestros',      'description' => 'Permite consultar la lista del personal docente.'],
        'create'          => ['label' => 'Registrar maestros', 'description' => 'Permite el alta de nuevos docentes en la institución.'],
        'edit'            => ['label' => 'Editar maestros',    'description' => 'Permite actualizar los datos del perfil de los maestros.'],
        'delete'          => ['label' => 'Eliminar maestros',  'description' => 'Permite remover registros del personal docente.'],
        'assign_subjects' => ['label' => 'Asignar materias',   'description' => 'Permite vincular maestros con asignaturas, secciones y años escolares.'],
    ],

    'attendance_plantel' => [
        'view'          => ['label' => 'Ver asistencia plantel',  'description' => 'Consulta el historial de entradas y salidas del centro educativo.'],
        'open_session'  => ['label' => 'Abrir sesión del día',    'description' => 'Habilita el registro de asistencia para la jornada actual.'],
        'close_session' => ['label' => 'Cerrar sesión del día',   'description' => 'Finaliza formalmente el registro de asistencia diaria.'],
        'record'        => ['label' => 'Registrar asistencia',    'description' => 'Permite marcar entradas y salidas de forma manual.'],
        'qr'            => ['label' => 'Registro por QR',         'description' => 'Permite el uso de escáneres de códigos QR para la asistencia.'],
        'facial'        => ['label' => 'Reconocimiento facial',   'description' => 'Permite el uso de biometría facial para el control de acceso.'],
        'verify'        => ['label' => 'Verificar identidad',     'description' => 'Realiza validaciones de identidad en los puntos de acceso.'],
        'reports'       => ['label' => 'Reportes de plantel',     'description' => 'Genera estadísticas y listados de asistencia institucional.'],
    ],

    'attendance_classroom' => [
        'view'    => ['label' => 'Ver asistencia aula', 'description' => 'Consulta los registros de asistencia por asignatura y sección.'],
        'record'  => ['label' => 'Pasar lista',         'description' => 'Permite a los docentes registrar la presencia de los alumnos en el aula.'],
        'edit'    => ['label' => 'Editar asistencia',   'description' => 'Permite corregir registros de asistencia en clases ya impartidas.'],
        'reports' => ['label' => 'Reportes de aula',    'description' => 'Genera reportes de ausentismo por materia y alertas de pasilleo.'],
    ],

    'excuses' => [
        'view'    => ['label' => 'Ver excusas',      'description' => 'Consulta el historial de justificaciones médicas o personales.'],
        'submit'  => ['label' => 'Registrar excusas', 'description' => 'Permite someter nuevas solicitudes de justificación de inasistencia.'],
        'approve' => ['label' => 'Aprobar excusas',   'description' => 'Permite validar y autorizar las justificaciones presentadas.'],
        'reject'  => ['label' => 'Rechazar excusas',  'description' => 'Permite denegar justificaciones que no cumplan requisitos.'],
    ],

    'roles' => [
        'view'   => ['label' => 'Ver roles',           'description' => 'Permite ver la lista de roles y sus permisos.'],
        'create' => ['label' => 'Crear roles',         'description' => 'Permite crear nuevos roles personalizados para la escuela.'],
        'edit'   => ['label' => 'Editar roles',        'description' => 'Permite modificar permisos de roles existentes.'],
        'delete' => ['label' => 'Eliminar roles',      'description' => 'Permite eliminar roles que no sean del sistema.'],
        'manage' => ['label' => 'Gestionar roles globales', 'description' => 'Permite administrar roles del sistema (Admin Hub).'],
        'inspect'=> ['label' => 'Inspeccionar roles',  'description' => 'Permite ver detalles técnicos de roles globales.'],
    ],

    'settings' => [
        'view'   => ['label' => 'Ver configuración',   'description' => 'Permite ver los datos institucionales del centro.'],
        'update' => ['label' => 'Editar configuración', 'description' => 'Permite actualizar la información y preferencias del centro.'],
    ],

    // --- GLOBAL (Admin Hub / Orvian Team) ---

    'schools' => [
        'view'   => ['label' => 'Ver escuelas',      'description' => 'Lista completa de centros.'],
        'create' => ['label' => 'Registrar escuelas', 'description' => 'Alta de nuevos centros.'],
        'edit'   => ['label' => 'Editar escuelas',    'description' => 'Modificar estatus, planes o datos de centros.'],
        'delete' => ['label' => 'Eliminar escuelas',  'description' => 'Eliminación parcial de centros del ecosistema.'],
    ],

    'plans' => [
        'view'   => ['label' => 'Ver planes',      'description' => 'Consultar planes de suscripción disponibles.'],
        'manage' => ['label' => 'Gestionar planes', 'description' => 'Crear, editar y configurar límites de los planes.'],
    ],

    'global_users' => [
        'view'   => ['label' => 'Ver personal Orvian', 'description' => 'Ver lista de administradores globales.'],
        'manage' => ['label' => 'Gestionar personal',  'description' => 'Administrar accesos del equipo de soporte y admin.'],
    ],

    'admin' => [
        'access' => ['label' => 'Acceso al Admin Hub', 'description' => 'Permiso maestro para entrar al panel de control global.'],
    ],

    'logs' => [
        'activity' => ['label' => 'Ver logs de actividad', 'description' => 'Auditoría de acciones realizadas en el sistema.'],
        'auth'     => ['label' => 'Ver logs de acceso',    'description' => 'Historial de inicios de sesión y fallos de seguridad.'],
    ],

];