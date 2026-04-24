# Gestión de Estudiantes

![Livewire](https://img.shields.io/badge/Livewire-Required-purple)
![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)

Guía completa para gestionar el padrón de estudiantes en ORVIAN. Cubre el registro individual, la importación masiva desde Excel, la captura de foto para el carnet digital y el registro biométrico para reconocimiento facial.

---

## Tabla de Contenido

- [Acceso al Módulo](#acceso-al-módulo)
- [Listado de Estudiantes](#listado-de-estudiantes)
- [Registrar un Estudiante](#registrar-un-estudiante)
- [Editar un Estudiante](#editar-un-estudiante)
- [Ficha del Estudiante](#ficha-del-estudiante)
- [Dar de Baja (Retiro)](#dar-de-baja-retiro)
- [Reactivar Estudiante](#reactivar-estudiante)
- [Transferir de Sección](#transferir-de-sección)
- [Importación Masiva desde Excel](#importación-masiva-desde-excel)
- [Captura de Foto (Carnet Digital)](#captura-de-foto-carnet-digital)
- [Registro de Rostro para Reconocimiento Facial](#registro-de-rostro-para-reconocimiento-facial)
- [Permisos Requeridos](#permisos-requeridos)

---

## Acceso al Módulo

El módulo de estudiantes es accesible desde dos puntos:

- **Dashboard principal** → Tile "Estudiantes"
- **Módulo de Asistencia** → Sidebar → Listado de Estudiantes

La ruta es `app.students.index`. Se requiere el permiso `students.view` para acceder.

---

## Listado de Estudiantes

La tabla principal muestra todos los estudiantes activos del centro por defecto. Incluye foto, nombre completo, sección, tanda, estado y los botones de acción.

### Filtros disponibles

| Filtro | Descripción |
|---|---|
| Búsqueda | Nombre o número de cédula (RNC) |
| Sección | Filtra por sección específica |
| Estado | Activos / Inactivos / Todos |
| Tiene foto | Filtra los que tienen foto registrada |
| Tiene biometría | Filtra los que tienen encoding facial |

### Columnas de la tabla

Las columnas son configurables via el selector de columnas. Las columnas por defecto son: Foto, Nombre, Sección, Tanda, Estado y Acciones.

---

## Registrar un Estudiante

Se accede desde el botón "Nuevo Estudiante" en la parte superior derecha del listado. Requiere el permiso `students.create`.

### Datos requeridos

**Datos personales:**
- Nombre y apellidos
- Género (M/F)
- Fecha de nacimiento
- Lugar de nacimiento (opcional)
- Cédula/RNC (opcional, formato `402-1234567-8`)
- Grupo sanguíneo (opcional)
- Alergias (opcional)
- Condiciones médicas (opcional)

**Datos académicos:**
- Sección (`school_section_id`) — determina grado, tanda y ciclo
- Fecha de inscripción

### Generación automática del código QR

Al crear un estudiante, el sistema genera automáticamente un código QR único de 32 caracteres (`qr_code`). Este código es el identificador del carnet digital y del escáner de asistencia. No puede modificarse manualmente.

---

## Editar un Estudiante

Disponible desde el botón de edición en la fila del listado o desde la ficha del estudiante. Requiere `students.edit`.

Se pueden editar todos los campos personales y académicos. El `qr_code` no es editable. El `face_encoding` se gestiona desde la sección de biometría.

---

## Ficha del Estudiante

La vista de detalle (`app.students.show`) centraliza toda la información del estudiante:

- **Datos personales** — información del perfil
- **Información académica** — sección, grado, tanda, año de inscripción
- **Carnet Digital** — vista previa del carnet con QR y botón de descarga
- **Biometría** — estado del encoding facial y botón para capturar/actualizar
- **Historial de asistencia** — registros recientes de plantel y aula

---

## Dar de Baja (Retiro)

Retira a un estudiante del sistema activo. No lo elimina de la base de datos — usa `soft delete` para preservar el historial de asistencia.

Al dar de baja se registra:
- Fecha del retiro (`withdrawal_date`)
- Motivo del retiro (`withdrawal_reason`)
- El campo `is_active` pasa a `false`

El estudiante deja de aparecer en los listados de asistencia y en los pases de lista de los maestros.

**Requiere:** `students.delete`

---

## Reactivar Estudiante

Un estudiante dado de baja puede reactivarse desde su ficha. El sistema limpia `withdrawal_date` y `withdrawal_reason`, y restaura `is_active = true`.

**Cuándo usarlo:** reingreso al centro después de un retiro temporal, corrección de un retiro accidental.

---

## Transferir de Sección

Mueve al estudiante a una sección diferente. El historial de la transferencia se guarda automáticamente en el campo `metadata['section_history']`:

```json
{
    "from_section_id": 5,
    "to_section_id": 8,
    "transferred_at": "2025-09-01T08:00:00Z",
    "transferred_by": 12
}
```

Esta información es útil para auditorías y para entender cambios en el historial de asistencia cuando un estudiante cambia de sección a mitad de año.

---

## Importación Masiva desde Excel

Permite registrar múltiples estudiantes desde un archivo `.xlsx`. Requiere `students.import`.

### Proceso

**Paso 1 — Preparar el archivo:**

Descarga la plantilla desde el módulo: botón "Descargar Plantilla" en la vista de importación. La plantilla incluye ejemplos y las columnas correctas:

| Columna | Requerido | Formato |
|---|---|---|
| `nombre` | Sí | Texto |
| `apellidos` | Sí | Texto |
| `genero` | Sí | `M` o `F` |
| `fecha_nacimiento` | Sí | `YYYY-MM-DD` o `DD/MM/YYYY` |
| `cedula` | No | `###-#######-#` (13 caracteres) |

**Paso 2 — Subir y configurar:**

1. Haz clic en "Importar Estudiantes"
2. Selecciona la sección destino de la lista
3. Sube el archivo `.xlsx`
4. Previsualiza las primeras 5 filas para verificar que el mapeo es correcto

**Paso 3 — Confirmar:**

Al confirmar, el sistema procesa fila por fila. Las filas con errores de validación se omiten y se reportan al final. El sistema informa:
- Filas importadas exitosamente
- Filas omitidas por error (con descripción del error)

**Errores comunes:**
- `nombre` vacío o mayor a 100 caracteres
- `genero` con valor diferente a `M` o `F`
- `fecha_nacimiento` en formato no reconocido
- `cedula` con longitud diferente a 13 caracteres

### Notas sobre la importación

- Los estudiantes importados no tienen foto ni encoding facial — deben capturarse manualmente después.
- El código QR se genera automáticamente para cada estudiante importado.
- La importación es idempotente por nombre completo: si un estudiante ya existe en la sección con el mismo nombre, se omite con una advertencia.

---

## Captura de Foto (Carnet Digital)

La foto del carnet se muestra en la interfaz de asistencia, en el perfil del estudiante y en el carnet digital descargable.

### Cómo capturar la foto

1. Abre la ficha del estudiante
2. Haz clic en el área de foto o en el botón "Actualizar Foto"
3. Se abre un modal con la cámara del dispositivo
4. Centra el rostro en el encuadre y captura
5. Previsualiza y confirma, o repite si no quedó bien

### Formatos aceptados

JPEG o PNG. Tamaño máximo 5MB. La foto se almacena en `storage/schools/{school_id}/students/` y se sirve desde el disco `public`.

### Carnet Digital

Desde la ficha del estudiante, sección "Carnet Digital", se puede ver la vista previa del carnet institucional (con logo del centro, foto, nombre, sección, tanda y código QR) y descargarlo como imagen. El carnet QR se usa en el escáner de asistencia de plantel.

---

## Registro de Rostro para Reconocimiento Facial

El encoding facial permite que el estudiante sea identificado automáticamente por la cámara al entrar al plantel, sin necesidad de presentar el carnet QR.

### Requisitos previos

- El microservicio de reconocimiento facial debe estar corriendo (`isServiceHealthy() = true`). Si no está disponible, el botón aparece deshabilitado con el mensaje "Servicio no disponible".
- El estudiante debe tener una foto capturada (aunque no es estrictamente necesario — la captura del encoding y la foto pueden hacerse en el mismo paso).

### Proceso de enrollment

1. Abre la ficha del estudiante → sección "Biometría Facial"
2. Haz clic en "Registrar Rostro"
3. Se abre el modal de cámara (similar al de foto)
4. El sistema captura la imagen, la envía al microservicio y guarda el encoding
5. El estado cambia a "Rostro registrado ✓" con la fecha del último enrollment

### Qué se almacena

Solo el vector de encoding (128 números decimales) en el campo `face_encoding` de la tabla `students`. La foto capturada para el enrollment **no se guarda** — se descarta después de generar el encoding.

### Errores comunes en el enrollment

- **"No se detectó ningún rostro":** La imagen no tiene suficiente resolución o la cara no está dentro del encuadre. Intenta con mejor iluminación y centra el rostro.
- **"Se detectaron múltiples rostros":** La foto incluye a más de una persona. El enrollment requiere exactamente un rostro visible.
- **"Servicio no disponible":** El microservicio Python no está corriendo. Contacta al administrador técnico.

### Actualizar el encoding

Si la apariencia del estudiante cambia significativamente (ej. después de años de uso), se puede actualizar el encoding repitiendo el proceso. El encoding anterior se sobreescribe.

---

## Permisos Requeridos

| Acción | Permiso |
|---|---|
| Ver listado y fichas | `students.view` |
| Crear nuevo estudiante | `students.create` |
| Editar datos | `students.edit` |
| Dar de baja / reactivar | `students.delete` |
| Importar desde Excel | `students.import` |
| Capturar foto | `students.edit` |
| Registrar encoding facial | `students.edit` |

Por defecto, estos permisos están asignados a los roles **School Principal** y **Secretary**. Los **Teachers** tienen `students.view` únicamente.