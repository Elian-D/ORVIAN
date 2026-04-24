# Gestión de Maestros

![Livewire](https://img.shields.io/badge/Livewire-Required-purple)
![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)

Guía completa para gestionar el personal docente en ORVIAN. Cubre el registro de maestros, la vinculación con cuentas de sistema y la asignación de materias y secciones.

---

## Tabla de Contenido

- [Acceso al Módulo](#acceso-al-módulo)
- [Listado de Maestros](#listado-de-maestros)
- [Registrar un Maestro](#registrar-un-maestro)
- [Editar un Maestro](#editar-un-maestro)
- [Ficha del Maestro](#ficha-del-maestro)
- [Vinculación con Cuenta de Sistema](#vinculación-con-cuenta-de-sistema)
- [Dar de Baja (Terminación)](#dar-de-baja-terminación)
- [Gestión de Asignaciones](#gestión-de-asignaciones)
- [Permisos Requeridos](#permisos-requeridos)

---

## Acceso al Módulo

El módulo de maestros es accesible desde:

- **Módulo Administración** → Sidebar → Maestros
- **Módulo Estudiantes** → Sidebar → Maestros

La ruta es `app.teachers.index`. Se requiere `teachers.view`.

---

## Listado de Maestros

La tabla principal muestra todos los maestros activos del centro. Las columnas por defecto son: Foto, Nombre, Código de empleado, Tipo de empleo, Materia de especialización, Estado de cuenta y Acciones.

### Filtros disponibles

| Filtro | Descripción |
|---|---|
| Búsqueda | Nombre o código de empleado |
| Tipo de empleo | Full-Time / Part-Time / Sustituto |
| Estado | Activos / Inactivos / Todos |
| Con cuenta | Filtra maestros que tienen acceso al sistema |

---

## Registrar un Maestro

Botón "Nuevo Maestro" en la parte superior derecha. Requiere `teachers.create`.

### Datos personales

- Nombre y apellidos
- Género (M/F)
- Fecha de nacimiento (opcional)
- Cédula/RNC (opcional, formato `402-1234567-8`)
- Teléfono (opcional)
- Contacto de emergencia — nombre y teléfono (opcional)

### Datos profesionales

- **Código de empleado** — se genera automáticamente en formato `EMP-{AÑO}-{NÚMERO}` (ej. `EMP-2025-0012`) si no se proporciona uno manualmente.
- **Especialización** — materia principal del docente (ej. "Matemáticas", "Lengua Española").
- **Tipo de empleo:** `Full-Time`, `Part-Time` o `Substitute`.
- **Fecha de contratación** — requerida.

### Código QR del maestro

Al igual que los estudiantes, cada maestro tiene un código QR único generado automáticamente (prefijo `TCH-`). Este código puede usarse en el futuro para el registro de asistencia del personal docente.

---

## Editar un Maestro

Desde el botón de edición en el listado o desde la ficha. Requiere `teachers.edit`.

Se pueden modificar todos los campos del perfil. El código QR y el código de empleado no son editables después de la creación.

---

## Ficha del Maestro

La vista de detalle (`app.teachers.show`) muestra:

- **Perfil** — datos personales y profesionales
- **Asignaciones activas** — materias y secciones del año en curso, agrupadas por sección
- **Cuenta de sistema** — si el maestro tiene acceso al sistema (vinculación con `User`)
- **Historial** — placeholder para fases futuras

Los botones de acción del header dan acceso a "Editar" y "Gestionar Asignaciones".

---

## Vinculación con Cuenta de Sistema

Un maestro puede operar en ORVIAN sin tener cuenta de usuario (para datos de plantilla o maestros que no usan la plataforma). Sin embargo, para que un maestro pueda **iniciar sesión y pasar lista** desde su dispositivo, necesita estar vinculado a un `User`.

### Cómo vincular

1. Abre la ficha del maestro
2. En la sección "Cuenta de Sistema", haz clic en "Vincular Cuenta"
3. Busca el usuario por nombre o email
4. Confirma la vinculación

El campo `user_id` en la tabla `teachers` establece esta relación. La relación es 1:1 — un usuario solo puede estar vinculado a un maestro.

### Por qué vincular es importante

Un maestro vinculado a una cuenta puede:
- Iniciar sesión con sus credenciales normales
- Ver únicamente las secciones que le están asignadas
- Acceder al módulo de pase de lista de aula
- Registrar y editar asistencia de sus clases

### Alternativa: Login por QR

También puede iniciarse sesión escaneando el código QR del carnet del maestro, sin necesidad de recordar contraseña. Útil para dispositivos compartidos en el plantel.

---

## Dar de Baja (Terminación)

Registra la salida del docente del centro. No elimina el registro — preserva el historial de asistencia y asignaciones.

Al dar de baja se registra:
- Fecha de terminación (`termination_date`)
- Motivo (`termination_reason`)
- `is_active` pasa a `false`

Las asignaciones activas del maestro se desactivan automáticamente (`is_active = false` en `teacher_subject_sections`). Si una asignación ya tiene registros de asistencia vinculados, se desactiva en lugar de eliminarse para preservar la integridad del historial.

**Requiere:** `teachers.delete`

---

## Gestión de Asignaciones

Las asignaciones definen **qué materia imparte cada maestro, en qué sección y en qué año escolar**. Este vínculo (`teacher_subject_sections`) es el que habilita el pase de lista — un maestro solo puede registrar asistencia en las clases que le están asignadas.

### Acceder a las asignaciones

Desde la ficha del maestro → botón "Gestionar Asignaciones", o desde la ruta `app.teachers.assignments`.

### Crear una asignación

1. En el panel izquierdo están las asignaciones actuales agrupadas por sección.
2. En el panel derecho, selecciona la sección destino del dropdown.
3. El sistema carga automáticamente las materias disponibles para esa sección (las que la escuela tiene habilitadas y que el maestro aún no tiene asignadas en esa sección).
4. Selecciona la materia y haz clic en "Asignar".

La asignación se vincula al año escolar activo (`AcademicYear.is_active = true`). Si no hay año activo, el sistema muestra un error.

### Eliminar una asignación

Desde el botón "×" en cada asignación del panel izquierdo. El comportamiento depende del historial:

- **Sin registros de asistencia:** Se elimina físicamente.
- **Con registros de asistencia:** Se desactiva (`is_active = false`). Los registros históricos de las clases de esa asignación se preservan intactos.

### Materias disponibles por sección

El sistema filtra las materias disponibles según el tipo de sección:

- **Secciones académicas:** Solo materias de tipo `basic` (Español, Matemáticas, Ciencias, etc.).
- **Secciones técnicas:** Materias `basic` más los módulos formativos técnicos del título que la sección tiene configurado.

Esto asegura que un maestro de "Fundamentos de Programación" no aparezca como opción en una sección de Primaria.

### Regla de unicidad

No puede existir más de una asignación de la misma materia para el mismo maestro en la misma sección en el mismo año escolar. Si se intenta crear un duplicado, el sistema lanza un error de restricción de base de datos.

---

## Permisos Requeridos

| Acción | Permiso |
|---|---|
| Ver listado y fichas | `teachers.view` |
| Registrar nuevo maestro | `teachers.create` |
| Editar datos del perfil | `teachers.edit` |
| Dar de baja | `teachers.delete` |
| Asignar / remover materias | `teachers.assign_subjects` |

Por defecto, estos permisos están asignados a los roles **School Principal** y **Academic Coordinator**. Los **Teachers** tienen `teachers.view` para poder consultar el directorio docente.

---

## Relación entre Maestro, Sección y Pase de Lista

El flujo completo desde el registro del maestro hasta el pase de lista es:

```
Teacher creado
    │
    ▼
Teacher vinculado a User (opcional, para login)
    │
    ▼
TeacherSubjectSection creado
(maestro + materia + sección + año)
    │
    ▼
Maestro inicia sesión y ve sus asignaciones activas
    │
    ▼
Accede a ClassroomAttendanceLive con su assignment_id
    │
    ▼
Registra el pase de lista para la clase del día
    │
    ▼
ClassroomAttendanceRecord creado por cada estudiante
```

Sin la asignación (`TeacherSubjectSection`), el maestro no puede acceder al pase de lista de esa clase. Esta es la barrera de seguridad que asegura que un maestro solo vea y modifique asistencia de sus propias clases.