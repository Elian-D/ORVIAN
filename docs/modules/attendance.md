# Módulo de Asistencia — Guía de Usuario

![Livewire](https://img.shields.io/badge/Livewire-Required-purple)
![Alpine.js](https://img.shields.io/badge/Alpine.js-Required-green)
![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)

Guía completa para operar el módulo de asistencia en ORVIAN. Cubre la apertura del día, los tres métodos de registro en plantel, el pase de lista del maestro, la gestión de excusas y la interpretación del dashboard.

---

## Tabla de Contenido

- [Apertura del Día (Sesión Diaria)](#apertura-del-día-sesión-diaria)
- [Registro de Asistencia de Plantel](#registro-de-asistencia-de-plantel)
  - [Registro Manual](#registro-manual)
  - [Escáner QR](#escáner-qr)
  - [Escáner Facial](#escáner-facial)
- [Cierre del Día y Marcado de Ausentes](#cierre-del-día-y-marcado-de-ausentes)
- [Pase de Lista del Maestro (Aula)](#pase-de-lista-del-maestro-aula)
- [Gestión de Excusas](#gestión-de-excusas)
- [Dashboard Operativo](#dashboard-operativo)
- [Hub de Gestión y Auditoría](#hub-de-gestión-y-auditoría)
- [Historial y Reportes](#historial-y-reportes)
- [Permisos por Rol](#permisos-por-rol)

---

## Apertura del Día (Sesión Diaria)

> **Regla fundamental:** Sin una sesión abierta, el sistema no acepta ningún registro de asistencia. Los días que no se abre sesión se consideran días sin clases (feriados, suspensiones).

### Quién puede abrir la sesión

Requiere el permiso `attendance_plantel.open_session`. Por defecto: **School Principal**, **Academic Coordinator** y **Secretary**.

### Cómo abrir la sesión

1. Accede al módulo de Asistencia → **Sesión del Día** (ruta `app.attendance.session`)
2. La pantalla muestra las tandas configuradas en el centro (Matutina, Vespertina, Nocturna)
3. Por cada tanda que tenga clases hoy, haz clic en **"Abrir Sesión"**
4. El sistema registra la hora de apertura, el usuario que abrió y calcula el total de estudiantes esperados

Si una tanda no tiene sesión abierta, sus estudiantes no podrán registrar asistencia por ningún método.

### El Hub de Gestión

Antes de abrir la sesión del día, puedes revisar el estado general desde **Asistencia → Hub** (`app.attendance.hub`). El Hub muestra un calendario mensual con indicadores de color:
- 🟢 Verde — sesión cerrada normalmente
- 🟡 Amarillo — sesión aún abierta
- 🔴 Rojo — ausentismo crítico (>20%)
- Sin color — no se abrió sesión (día libre)

---

## Registro de Asistencia de Plantel

Hay tres métodos para registrar la entrada de un estudiante al plantel. Pueden usarse en paralelo — la portería puede tener el escáner QR, la secretaría el registro manual y la cámara el escáner facial, todo simultáneamente.

### Registro Manual

**Ruta:** `app.attendance.session` → sección de lista manual. Requiere `attendance_plantel.record`.

1. Selecciona la tanda activa
2. La lista muestra todos los estudiantes con su estado actual (registrado / pendiente)
3. Usa la barra de búsqueda para encontrar al estudiante por nombre o cédula
4. Haz clic en "Presente", "Tardanza" o "Ausente" en la fila del estudiante
5. El estado se actualiza en tiempo real y el contador de la sesión sube

**Filtros de la lista manual:**
- Búsqueda por nombre o RNC
- Sección específica
- Estado (`pending` — sin registrar, `registered` — ya registrado)
- Ocultar excusados (activo por defecto — simplifica la lista)

El footer de la pantalla muestra estadísticas en tiempo real: Total / Presentes / Tardanzas / Ausentes / Excusas, con una barra de progreso del porcentaje completado.

### Escáner QR

**Ruta:** `app.attendance.qr`. Requiere `attendance_plantel.qr`.

Diseñado para dispositivos con cámara en la entrada del plantel. El estudiante presenta su carnet (o la pantalla de su teléfono con el QR desde la app) y el sistema registra automáticamente su entrada.

1. La pantalla muestra el visor de cámara activo
2. El estudiante acerca su QR
3. El sistema detecta el código, busca al estudiante y registra la entrada
4. Aparece confirmación visual: foto + nombre + estado asignado (Presente o Tardanza)

**Determinación automática del estado:**
- Si llega dentro de los primeros 15 minutos del inicio de la tanda → **Presente**
- Si llega después de ese margen → **Tardanza**

El horario de inicio de la tanda está configurado en `SchoolShift.start_time` desde la configuración del centro.

### Escáner Facial

**Ruta:** `app.attendance.facial`. Requiere `attendance_plantel.facial`.

El método más rápido — el estudiante solo mira la cámara al pasar. No requiere carnet ni acción del estudiante.

**Requisitos previos:**
- El microservicio de reconocimiento facial debe estar corriendo
- El estudiante debe tener un encoding facial registrado previamente (ver [Registro de Rostro](/docs/modules/students.md#registro-de-rostro-para-reconocimiento-facial))

**Funcionamiento:**
1. La cámara captura frames continuamente
2. Cada frame se envía al microservicio Python
3. El microservicio compara contra todos los encodings del plantel
4. Si hay match con confianza suficiente: registra la entrada automáticamente
5. El visor muestra el resultado: foto + nombre + porcentaje de confianza

Si el microservicio no está disponible, el botón del escáner facial aparece deshabilitado y se puede seguir usando QR o manual.

---

## Cierre del Día y Marcado de Ausentes

Al final de la jornada, el administrador debe cerrar la sesión. Esto congela el registro y genera las estadísticas definitivas.

### Pasos recomendados antes de cerrar

1. **Marcar ausentes faltantes:** Los estudiantes que no tienen ningún registro al momento del cierre no se consideran automáticamente ausentes hasta que se ejecute esta acción. Haz clic en "Marcar Ausentes Faltantes" antes de cerrar.

   El sistema busca todos los estudiantes activos de la tanda sin registro y:
   - Si tienen excusa aprobada para hoy → los marca como **Excusados** automáticamente
   - Si no tienen excusa → los marca como **Ausentes**

2. **Cerrar la sesión:** Haz clic en "Cerrar Sesión". Aparece un modal con el resumen final (totales por estado). Confirma para cerrar.

Al cerrar, la sesión registra `closed_at`, `closed_by` y recalcula todos los contadores definitivos.

> Una sesión cerrada puede auditarse desde el Hub → Auditoría. Los registros individuales pueden corregirse incluso después del cierre, siempre que el usuario tenga el permiso `attendance_plantel.verify`.

---

## Pase de Lista del Maestro (Aula)

El pase de lista es la responsabilidad de cada maestro en su clase. Opera de forma independiente al registro de plantel pero está validado contra él.

**Ruta:** `app.attendance.classroom.live/{assignmentId}`. Requiere `attendance_classroom.record`.

### Cómo hacer el pase de lista

1. El maestro inicia sesión en ORVIAN
2. En su dashboard o módulo de Asistencia, ve sus asignaciones del día
3. Hace clic en la asignación correspondiente (Materia + Sección)
4. La pantalla carga la lista de estudiantes de la sección

**Estado inicial de cada estudiante:**
- **Excusados:** Los estudiantes con excusa aprobada para hoy aparecen pre-marcados como "Excusado" y el botón "Presente" está deshabilitado — el sistema los protege.
- **Sin excusa:** Aparecen pre-marcados como "Presente" por defecto (el maestro ajusta los ausentes).

5. El maestro cambia el estado de los ausentes/tardanzas
6. Hace clic en "Guardar Pase de Lista"

### Regla de validación cruzada

Si un estudiante está marcado como **Ausente en plantel**, el sistema impide que el maestro lo marque como "Presente" en la clase. Aparece un error: *"El estudiante está marcado como 'Ausente' en el plantel."*

Esto previene inconsistencias lógicas (imposible estar en clase si no entró al centro) y detecta errores de registro.

### Estados disponibles en aula

| Estado | Cuándo usarlo |
|---|---|
| Presente | Asistió a la clase |
| Tardanza | Llegó tarde a la clase específica |
| Ausente | No asistió (detecta pasilleo si estuvo en plantel) |
| Excusado | Tiene justificación aprobada (pre-asignado automáticamente) |

---

## Gestión de Excusas

Las excusas justifican inasistencias de estudiantes y se integran automáticamente en el registro de asistencia.

**Ruta:** `app.attendance.excuses.index`. Requiere `excuses.view`.

### Flujo de una excusa

```
Padre/tutor o secretaria somete la excusa
          │
          ▼
Estado: PENDIENTE (excuse.submit)
          │
          ▼
Coordinador/Director revisa y aprueba o rechaza
    (excuses.approve / excuses.reject)
          │
     ┌────┴────┐
  APROBADA   RECHAZADA
     │
     ▼
Sistema aplica automáticamente:
  • En plantel: próximo markAbsences() marca al estudiante como EXCUSED
  • En aula: estudiante aparece pre-marcado como EXCUSED en pase de lista
```

### Tipos de excusa

Las excusas pueden ser por ausencia puntual (un día) o por licencia (rango de fechas). Una licencia activa cubre todos los días del período indicado.

### Excusa retroactiva

Si se aprueba una excusa para una fecha ya cerrada, el sistema actualiza retroactivamente los registros de asistencia de ese día. Los registros de plantel y aula que estaban como `absent` se actualizan a `excused` automáticamente, y se agrega una nota con la referencia a la excusa.

---

## Dashboard Operativo

**Ruta:** `app.attendance.dashboard`. Requiere `attendance_plantel.view`.

El dashboard central del módulo muestra el estado en tiempo real del día. Se actualiza automáticamente cada 10 segundos.

### Sección 1 — Estado del Día

Banner de alerta si no hay sesión abierta para hoy. Si hay sesión, muestra el estado por tanda.

### Sección 2 — Métricas Duales

Dos paneles en paralelo:

**Panel Plantel** (azul): Total esperado, Presentes, Tardanzas, Ausentes, Tasa de asistencia. Incluye barra de progreso visual.

**Panel Aula** (morado): Total de clases registradas hoy, Presentes, Ausentes, Tardanzas. Incluye barra de progreso.

Ver los dos paneles juntos revela la diferencia entre "llegó al centro" y "asistió a las clases".

### Sección 3 — Panel de Discrepancias (Pasilleo)

Solo visible cuando hay discrepancias detectadas. Muestra los estudiantes que:
- Están marcados como **Presente o Tardanza en plantel**, Y
- Tienen **una o más ausencias en aula ese día**

La tabla incluye: foto, nombre, estado en plantel, número de clases ausente en aula y botón "Ver Detalle". Esta información es para que coordinación investigue a los estudiantes que evaden clases estando dentro del centro.

### Sección 4 — Gráficos (ApexCharts)

- **Donut:** Distribución de estados de plantel (Presente / Tardanza / Ausente). Se actualiza con el polling.
- **Línea:** Tasa de asistencia de plantel de los últimos 7 días escolares. Permite identificar tendencias semanales.

### Sección 5 — Actividad Reciente

Lista de las últimas 15 entradas al plantel: avatar, nombre, hora de entrada, método de registro (badge QR / Facial / Manual) y estado (badge). Se actualiza automáticamente.

---

## Hub de Gestión y Auditoría

**Ruta:** `app.attendance.hub`. Requiere `attendance_plantel.view`.

Complementa al dashboard con navegación histórica por fecha.

### Calendario mensual

El lado izquierdo muestra un calendario con indicadores visuales por día. Hacer clic en un día carga el detalle de esa jornada a la derecha.

### Selector de tandas

Muestra el estado de cada tanda del día seleccionado. Permite alternar entre Matutina, Vespertina y Nocturna si el centro tiene múltiples jornadas.

### Acciones contextuales

El botón de acción del lado derecho cambia según el estado de la sesión seleccionada:
- **"Gestionar"** — sesión abierta → va a la gestión de sesión activa
- **"Auditar"** — sesión cerrada → va a la vista de auditoría

### Auditoría de sesiones cerradas

La auditoría permite corregir registros individuales después del cierre. Útil cuando un estudiante fue marcado incorrectamente. Las correcciones están protegidas por el permiso `attendance_plantel.verify` y cada cambio actualiza automáticamente los contadores de la sesión para mantener la consistencia de los reportes.

> La pantalla de auditoría muestra un banner de advertencia con animación de pulso para recordar que los cambios afectan los reportes oficiales.

---

## Historial y Reportes

### Historial de Plantel

**Ruta:** `app.attendance.history`. Requiere `attendance_plantel.view`.

Tabla de todos los registros de plantel con filtros: rango de fechas, sección, estado, método de registro y estado de verificación. Exportable a Excel.

### Historial de Aula

**Ruta:** `app.attendance.classroom.history`. Requiere `attendance_classroom.view`.

Tabla de todos los registros de aula. Los maestros solo ven sus propios registros. Coordinadores y Director ven todo. Exportable a Excel.

### Reportes

**Ruta:** `app.attendance.reports`. Requiere `attendance_plantel.reports` o `attendance_classroom.reports`.

Tipos de reporte disponibles:

| Tipo | Qué muestra |
|---|---|
| Resumen general del período | Tasa de asistencia por sección, comparativa mensual |
| Por estudiante | Historial completo, ausencias justificadas vs no justificadas |
| Discrepancias del período | Historial de eventos de pasilleo detectados |
| Por maestro | Cobertura de pase de lista (clases registradas vs clases dictadas) |

Los reportes pueden exportarse a **Excel** (datos en tabla) o **PDF** (documento formal con logo del centro, encabezado y pie de página con totales).

---

## Permisos por Rol

| Acción | School Principal | Academic Coordinator | Teacher | Secretary |
|---|---|---|---|---|
| Ver dashboard y historial | ✓ | ✓ | — | ✓ |
| Abrir / cerrar sesión | ✓ | ✓ | — | — |
| Registro manual plantel | ✓ | ✓ | — | ✓ |
| Escáner QR | ✓ | ✓ | — | ✓ |
| Escáner facial | ✓ | ✓ | — | — |
| Verificar / auditar registros | ✓ | ✓ | — | — |
| Reportes de plantel | ✓ | ✓ | — | ✓ |
| Pase de lista (aula) | ✓ | — | ✓ | — |
| Ver historial de aula | ✓ | ✓ | ✓ (propio) | — |
| Reportes de aula | ✓ | ✓ | ✓ | — |
| Registrar excusas | ✓ | ✓ | ✓ | ✓ |
| Aprobar excusas | ✓ | ✓ | — | — |
| Rechazar excusas | ✓ | ✓ | — | — |