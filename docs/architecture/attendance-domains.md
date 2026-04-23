# Arquitectura de Dominios de Asistencia

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Livewire](https://img.shields.io/badge/Livewire-Compatible-purple)

ORVIAN implementa un **sistema dual de asistencia** que distingue con precisión dos contextos operativos diferentes dentro del mismo centro educativo. Esta separación refleja una realidad pedagógica concreta: estar en el plantel no equivale a estar en el aula.

---

## Tabla de Contenido

- [Los Dos Dominios](#los-dos-dominios)
- [Por qué son modelos separados](#por-qué-son-modelos-separados)
- [Validación Cruzada (Regla de Pasilleo)](#validación-cruzada-regla-de-pasilleo)
- [Flujo Completo del Día](#flujo-completo-del-día)
- [La Sesión Diaria como Gatekeeper](#la-sesión-diaria-como-gatekeeper)
- [Tablas involucradas](#tablas-involucradas)
- [Diagrama de Estados por Dominio](#diagrama-de-estados-por-dominio)

---

## Los Dos Dominios

### Dominio 1 — Asistencia de Plantel ("La Puerta")

Registra la **presencia física en el centro educativo**. Es el control de acceso perimetral: ¿el estudiante cruzó la entrada hoy?

- **Quién lo opera:** Personal de portería, secretaría o coordinación.
- **Cuándo ocurre:** Al inicio de la jornada, al cruzar la entrada.
- **Métodos de registro:** Manual (lista), código QR, reconocimiento facial.
- **Regida por:** Tanda (`SchoolShift`) y su horario institucional (`start_time`).
- **Detecta:** Llegadas tardías. Si el estudiante llega pasados 15 minutos del inicio de la tanda, se registra automáticamente como `late` en lugar de `present`.
- **Modelo:** `PlantelAttendanceRecord`
- **Sesión contenedora:** `DailyAttendanceSession` (debe estar abierta para aceptar registros)

### Dominio 2 — Asistencia de Aula ("El Registro Anecdótico Digital")

Registra la **presencia en cada clase específica**. Es el pase de lista que históricamente llevaba el maestro en papel.

- **Quién lo opera:** Cada maestro en su asignatura.
- **Cuándo ocurre:** Al inicio de cada período de clase.
- **Métodos de registro:** Solo manual (interfaz de pase de lista).
- **Regida por:** Asignación de maestro (`TeacherSubjectSection`) — la combinación maestro + materia + sección.
- **Detecta:** "Pasilleo" — el estudiante está en el plantel pero no aparece en la clase.
- **Modelo:** `ClassroomAttendanceRecord`
- **Sin sesión contenedora propia:** Cada registro se vincula directamente a la asignación del maestro.

---

## Por qué son modelos separados

La tentación de usar una única tabla con un campo `type` sería un error de diseño por varias razones:

**Cardinalidad diferente.** Un estudiante tiene exactamente un registro de plantel por tanda por día. Pero puede tener N registros de aula ese mismo día — uno por cada materia que curse. Una tabla unificada violaría el principio de unicidad del índice de plantel.

**Metadata diferente.** El registro de plantel captura `method` (qr/facial/manual), `temperature`, y coordina con `DailyAttendanceSession`. El registro de aula captura `teacher_notes`, vincula a `TeacherSubjectSection`, y requiere validación cruzada hacia plantel. Estos datos no se superponen.

**Actores diferentes.** El portero y la secretaria operan plantel. El maestro opera aula. Sus vistas, permisos y flujos de trabajo son distintos. Un modelo compartido complicaría la separación de responsabilidades.

**Reglas de negocio distintas.** La tardanza la determina el plantel comparando la hora de llegada con `SchoolShift.start_time`. La excusa retroactiva afecta a plantel de manera diferente a cómo afecta a aula. Separar los modelos permite que cada uno tenga sus propias reglas sin condicionales entrelazados.

---

## Validación Cruzada (Regla de Pasilleo)

La validación cruzada es la regla de negocio más importante del sistema. Existe para prevenir inconsistencias lógicas y detectar estudiantes que evaden clases.

### Regla 1 — Bloqueo estricto

> Si un estudiante está marcado como `absent` o `excused` en **plantel**, el sistema **impide** registrarlo como `present` en **aula**.

Esta validación ocurre en `ClassroomAttendanceService::validateCrossAttendance()` antes de crear cualquier registro de aula. Si la regla se viola, se lanza una excepción que el componente Livewire captura y muestra como error.

```
Estudiante marcado como "Ausente" en plantel
        │
        ▼
Maestro intenta marcarlo "Presente" en clase
        │
        ▼
ClassroomAttendanceService::validateCrossAttendance()
        │
        ▼
PlantelAttendanceRecord encontrado con status = 'absent'
        │
        ▼
Lanza \Exception: "El estudiante está marcado como 'Ausente' en el plantel.
                   No puede registrarse como presente en aula."
        │
        ▼
El registro de aula NO se crea
```

### Regla 2 — Excepción por excusa

Si el estudiante no tiene registro de plantel **pero sí tiene una excusa aprobada** para esa fecha (validada por `ExcuseService::hasApprovedExcuseForDate()`), el sistema **permite** el registro de aula. La lógica reconoce que el maestro puede querer dejar constancia de la justificación aunque el estudiante no haya pasado por la puerta.

### Regla 3 — Alerta de pasilleo

> Si un estudiante está marcado como `present` o `late` en **plantel** pero como `absent` en una o más clases de **aula**, se genera una alerta de "pasilleo".

Esta detección ocurre en `ClassroomAttendanceService::detectDiscrepancies()`. No bloquea nada — es informativa. El dashboard operativo muestra estos casos para que coordinación pueda investigar.

```
Estudiante presente en plantel
        │
        ▼
Ausente en 2 clases de aula ese día
        │
        ▼
detectDiscrepancies() lo agrega a la colección
        │
        ▼
Dashboard muestra alerta:
  Foto + Nombre | Estado Plantel: "Presente" | Clases ausente: 2 | [Ver Detalle]
```

### Diagrama de la validación cruzada

```
                    ┌─────────────────────────────────────────┐
                    │          REGISTRO EN PLANTEL             │
                    └──────────────────┬──────────────────────┘
                                       │
              ┌────────────────────────┼──────────────────────┐
              │                        │                       │
         ABSENT / EXCUSED           PRESENT / LATE       SIN REGISTRO
              │                        │                       │
              ▼                        ▼                       ▼
    ┌─────────────────┐     ┌─────────────────┐     ┌──────────────────┐
    │  AULA BLOQUEADA  │     │  AULA PERMITIDA │     │  ¿Tiene excusa?  │
    │                 │     │                 │     └────────┬─────────┘
    │ No puede estar  │     │ Registro normal │          Sí  │  No
    │ presente en     │     │                 │          │   │
    │ ninguna clase   │     │ Si luego está   │          ▼   ▼
    └─────────────────┘     │ ausente en aula │  PERMITIDO  BLOQUEADO
                            │ → PASILLEO alert│
                            └─────────────────┘
```

---

## Flujo Completo del Día

Un día escolar en ORVIAN sigue este flujo estricto:

```
┌─────────────────────────────────────────────────────────────────┐
│  FASE 1 — APERTURA (requerida, sin esto nada funciona)          │
│                                                                  │
│  Administrador abre DailyAttendanceSession                       │
│  para cada Tanda (Matutina, Vespertina, etc.)                    │
│                                                                  │
│  Se registra:                                                    │
│    • school_id, school_shift_id, date                           │
│    • opened_at = now(), opened_by = auth()->id()                 │
│    • total_expected = Student::active()->count()                 │
└──────────────────────────────┬──────────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────────┐
│  FASE 2 — REGISTRO DE ENTRADAS (plantel)                        │
│                                                                  │
│  Métodos en paralelo:                                           │
│                                                                  │
│  QR Scanner                 Facial Scanner         Manual       │
│  └─ Estudiante escanea      └─ Cámara identifica   └─ Secretaria│
│     su carnet QR               al estudiante          marca     │
│                                                                  │
│  → PlantelAttendanceService::recordAttendance()                  │
│  → determineStatus(): ¿llegó antes de start_time + 15min?       │
│    • Sí → STATUS_PRESENT                                         │
│    • No → STATUS_LATE                                            │
│  → session->incrementRegistered()                                │
└──────────────────────────────┬──────────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────────┐
│  FASE 3 — PASE DE LISTA (aula, durante el día)                  │
│                                                                  │
│  Por cada clase, el maestro abre ClassroomAttendanceLive        │
│                                                                  │
│  Por cada estudiante:                                            │
│    1. ¿Tiene excusa aprobada? → Pre-seleccionado como EXCUSED   │
│    2. No → Pre-seleccionado como PRESENT (maestro puede cambiar)│
│                                                                  │
│  Al guardar: ClassroomAttendanceService::takeClassAttendance()   │
│    → validateCrossAttendance() para cada estudiante              │
│    → Registra o lanza excepción por regla de plantel             │
└──────────────────────────────┬──────────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────────┐
│  FASE 4 — CIERRE DEL DÍA                                        │
│                                                                  │
│  Administrador cierra la DailyAttendanceSession                  │
│                                                                  │
│  Acción opcional antes de cerrar:                               │
│  "Marcar Ausentes Faltantes"                                     │
│   → PlantelAttendanceService::markAbsences()                    │
│   → Busca estudiantes SIN registro en la sesión                  │
│   → Por cada uno: ¿tiene excusa? → EXCUSED : ABSENT             │
│                                                                  │
│  Al cerrar: session->update({ closed_at, stats finales })        │
└──────────────────────────────┬──────────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────────┐
│  POST-CIERRE — REPORTES Y DISCREPANCIAS                         │
│                                                                  │
│  AttendanceDashboard muestra:                                    │
│    • Métricas duales (plantel vs aula)                          │
│    • Panel de pasilleo detectado                                 │
│    • Timeline de actividad reciente                              │
│    • Gráficos (ApexCharts): donut de estados + línea 7 días     │
└─────────────────────────────────────────────────────────────────┘
```

---

## La Sesión Diaria como Gatekeeper

El modelo `DailyAttendanceSession` es el control de flujo central del dominio de plantel. Sin una sesión abierta, `PlantelAttendanceService::recordAttendance()` lanza una excepción y no crea ningún registro.

**Esto es intencional y crítico.** El sistema no asume que hay clases todos los días. Los días feriados, vacaciones o suspensiones no requieren ninguna acción — simplemente no se abre la sesión y los microservicios no procesan alertas.

**Unicidad de sesión:** La tabla `daily_attendance_sessions` tiene un índice único sobre `(school_id, date, school_shift_id)`. No pueden existir dos sesiones abiertas para la misma escuela, la misma fecha y la misma tanda.

**Estadísticas en tiempo real:** La sesión mantiene contadores actualizados (`total_registered`, `total_present`, `total_late`, `total_absent`, `total_excused`) que se incrementan con cada registro. Al cierre, estos contadores se recalculan definitivamente desde los registros reales.

---

## Tablas involucradas

```
daily_attendance_sessions
    id, school_id, school_shift_id, date
    opened_at, closed_at, opened_by, closed_by
    total_expected, total_registered, total_present, total_late,
    total_absent, total_excused
    UNIQUE(school_id, date, school_shift_id)

plantel_attendance_records
    id, school_id, student_id, daily_attendance_session_id, school_shift_id
    date, time, status [present|late|absent|excused], method [manual|qr|facial]
    registered_by, temperature, notes, verified_at, verified_by
    UNIQUE(student_id, date, school_shift_id)
    INDEX(school_id, date), INDEX(status, date)

classroom_attendance_records
    id, school_id, student_id, teacher_subject_section_id, teacher_id
    date, class_time, status [present|late|absent|excused]
    teacher_notes
    UNIQUE(student_id, teacher_subject_section_id, date)
```

---

## Diagrama de Estados por Dominio

### Estados de `PlantelAttendanceRecord`

```
                    ┌─────────┐
                    │ PRESENT │  Llegó a tiempo (≤ 15 min del inicio de tanda)
                    └─────────┘

                    ┌─────────┐
                    │  LATE   │  Llegó tarde (> 15 min del inicio de tanda)
                    └─────────┘

                    ┌─────────┐
                    │ ABSENT  │  No se presentó. Marcado por markAbsences()
                    └─────────┘   o manualmente al final del día.

                    ┌─────────┐
                    │ EXCUSED │  Ausente con excusa aprobada. Automático si
                    └─────────┘   ExcuseService detecta justificación vigente.
```

### Estados de `ClassroomAttendanceRecord`

```
                    ┌─────────┐
                    │ PRESENT │  Presente en la clase. Estado por defecto.
                    └─────────┘

                    ┌─────────┐
                    │  LATE   │  Llegó tarde a la clase específica.
                    └─────────┘   (Distinto a la tardanza de plantel)

                    ┌─────────┐
                    │ ABSENT  │  No asistió a la clase. Genera alerta de
                    └─────────┘   pasilleo si estaba presente en plantel.

                    ┌─────────┐
                    │ EXCUSED │  Pre-asignado si ExcuseService detecta excusa
                    └─────────┘   vigente. Botón "Presente" deshabilitado en UI.
```

> **Nota importante sobre `late` en aula vs plantel:** La tardanza de plantel la determina el algoritmo comparando la hora de llegada con `SchoolShift.start_time`. La tardanza de aula la determina el maestro manualmente en el pase de lista. Son conceptos diferentes que coexisten en el mismo estudiante.