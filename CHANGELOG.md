# Changelog

Todos los cambios importantes del proyecto **ORVIAN** se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)
y el proyecto sigue [Semantic Versioning](https://semver.org/lang/es/).

---

## [0.5.0] - 2026-04-25

### Added

#### Módulo de Comunicaciones
- `config/communications.php` — configuración centralizada para Chatwoot y Evolution API (base URL, tokens, umbrales de notificación).
- `ChatwootService` — cliente HTTP singleton hacia el VPS de Chatwoot: creación de agentes, generación de `identifier_hash` HMAC-SHA256 para SSO, conteo de conversaciones abiertas y método `syncUserAsAgent()`.
- Sincronización automática del Director con Chatwoot al completar el onboarding: `CompleteOnboardingAction` y `CompleteTenantOnboardingAction` invocan `ChatwootService::syncUserAsAgent()` mediante `DB::afterCommit`, garantizando que el agente se registra solo tras el commit exitoso de la transacción.
- `CreateSchoolPrincipalAction` refactorizado: eliminada la transacción interna propia; la sincronización con Chatwoot se delega a la Action padre para evitar anidamiento de transacciones.
- Acceso al Centro de Mensajes desde el panel app — redirección directa a `chat.orvian.com.do` con parámetros SSO generados server-side (email + `identifier_hash`). No se usa Iframe embebido.
- `WhatsAppService` — cliente HTTP singleton hacia Evolution API en VPS. ORVIAN actúa exclusivamente como emisor; no procesa respuestas entrantes.
- `WhatsAppTemplates` — plantillas estáticas para alertas de ausencia y tardanza con formato WhatsApp (`*negrita*`, `_cursiva_`).
- `AttendanceAlertEvaluator` — evalúa umbrales mensuales de ausencias y tardanzas por estudiante. Despacha `SendAttendanceAlertJob` con protección anti-spam por caché semanal (`alert_{tipo}_{student_id}_{weekOfYear}`, TTL 7 días).
- `SendAttendanceAlertJob` — Job asíncrono con 3 reintentos y backoff de 60 segundos. Llama a `WhatsAppService::sendTextMessage()` con la plantilla correspondiente.
- Comando `orvian:evaluate-attendance-alerts` con opción `--school` para evaluar un centro específico o todos. Programado diariamente a las 16:00 con `withoutOverlapping()` en `routes/console.php`.
- Migración `add_tutor_fields_to_students_table`: campo `tutor_phone` (string 20, nullable, formato E.164) en tabla `students`. `tutor_name` agregado con guardia `Schema::hasColumn` para compatibilidad con v0.4.0.
- `$fillable` del modelo `Student` actualizado con `tutor_phone`.
- Campo `tutor_phone` en formulario de edición de estudiante con validación E.164.
- Tile `conversaciones` activado con `visible: true` en `config/modules.php`.
- Singletons de `ChatwootService` y `WhatsAppService` registrados en `AppServiceProvider`.

### Changed

- `CompleteOnboardingAction` — inyecta `ChatwootService`; la sincronización Chatwoot se ejecuta vía `DB::afterCommit` en lugar de dentro del closure de transacción.
- `CompleteTenantOnboardingAction` — inyecta `ChatwootService`; mismo patrón `DB::afterCommit` para la sincronización.
- `CreateSchoolPrincipalAction` — eliminada transacción `DB::transaction` propia; ahora opera dentro de la transacción padre sin anidamiento.
- Acceso a conversaciones simplificado: eliminados `ConversationsController` (app y admin) basados en Iframe; el acceso se resuelve mediante redirección directa al dominio externo de Chatwoot con parámetros SSO en query string.

### Fixed

- `DB::afterCommit` en transacción anidada no disparaba el callback — resuelto eliminando la transacción interna de `CreateSchoolPrincipalAction` para que `afterCommit` opere sobre la transacción real (la de la Action padre).
- Email corrupto con sufijo numérico en agentes de prueba provocaba rechazo silencioso en Chatwoot sin log de error — agregado log explícito del status y body de la respuesta en `createAgent`.

---


## [0.4.1] - 2026-05-15

### Added

#### Identidad y Autenticación — Refactor Completo

**Generación Automática de Usuarios para Estudiantes:**
- Observer `StudentObserver@created` genera automáticamente un `User` al crear/importar un estudiante.
- Email derivado del RNC: patrón `{rnc_limpio}@orvian.com.do` (ej: `40212345678@orvian.com.do`).
- Usuario creado con estado `inactive` hasta habilitación por el Director.
- Rol `Student` asignado automáticamente en scope del tenant (`school_id`).
- Contraseña temporal: el RNC limpio (el estudiante debe cambiarla en primer login).
- Guard contra duplicados: no crea usuario si el email ya existe.

**Redirección Inteligente Post-Login:**
- Usuarios con `school_id = null` (Owner, TechnicalSupport, Administrative) → `admin.hub`.
- Usuarios con `school_id ≠ null` (Director, Teacher, Student) → `app.dashboard`.
- Lógica centralizada en `AuthenticatedSessionController@store`.

**Nueva Interfaz de Login — Arquitectónica:**
- Pantalla de dos columnas: panel izquierdo con branding oscuro, panel derecho con formulario Line UI.
- Componente `x-ui.toasts` integrado para notificaciones.
- Soporte de tema oscuro/claro vía `x-ui.theme-init` (lectura de preferencias desde BD, sin flash).
- Generador de frases aleatorias: 10 taglines distintos del sistema mostrados al azar en cada carga.
- Elementos decorativos arquitectónicos: grid blueprint, puntos (dots), figura isométrica, anillos orbitales, cruces de posicionamiento, líneas diagonales.
- Wordmark ORVIAN con fuente Etna (OTF cargada desde `public/fonts/etna-free-font.otf`).
- Botón QR nativo con escáner HTML5 para autenticación biométrica sin formulario clásico.
- Badge de versión global (`$appVersion`) desde variable compartida.

#### Utilidad de Versión Global

**Lectura y Caché de Versión:**
- Archivo `VERSION` en raíz del proyecto contiene número de versión en texto plano (`0.4.1`).
- `AppServiceProvider@boot()` lee el archivo con `Cache::rememberForever('orvian.app_version', ...)`.
- Fallback automático a `'dev'` si el archivo no existe.
- Variable global `$appVersion` compartida vía `View::share()` — disponible en todas las vistas.
- Invalidación de caché mediante comando `php artisan cache:forget orvian.app_version` en deploy.

#### Simplificación de UI

**Dashboard — Ocultar Módulos Incompletos:**
- Flag `visible: true/false` agregado a cada módulo en `config/modules.php`.
- Módulos activos para demo: Académico, Asistencia, Configuración (`visible: true`).
- Módulos pausados: Comunicaciones, Inventario/Facturación (`visible: false`).
- Rutas de módulos ocultos retornan 404 si se accede directamente.
- Filtro dinámico en controlador: `collect(config('modules'))->filter(fn($m) => $m['visible'] ?? false)`.

#### Coexistencia de Interfaces de Login (Versionado v1/v2)

**Separación Física de Vistas:**
- Versión clásica respaldada: `resources/views/layouts/guest-v1.blade.php`, `resources/views/auth/login-v1.blade.php`.
- Versión arquitectónica por defecto: `resources/views/layouts/guest.blade.php`, `resources/views/auth/login.blade.php`.
- Nombres por defecto para v2 mantienen claridad: v2 es el presente, v1 es legado.

**Enrutamiento por Cookie:**
- `AuthenticatedSessionController@create()` lee cookie `orvian_login_version` (default `'v2'`).
- Si `'v1'` → renderiza `auth.login-v1`, si `'v2'` → renderiza `auth.login`.
- Cookie no requiere autenticación previa — disponible para seleccionar versión antes de login.

**Sincronización de Preferencias (ProfileModal):**
- Nueva propiedad `$loginVersion` en `ProfileModal` Livewire.
- Cargada desde `User->preference('login_version', 'v2')` en `loadUserData()`.
- Guardada en JSON `preferences['login_version']` en BD y como Cookie por 1 año (`60*24*365` minutos).
- Cookie sincronizada vía `Cookie::queue()` en `savePreferences()`.

**Interfaz de Preferencias:**
- Nueva sección en modal de perfil (pestaña *Preferencias*): selector visual "Arquitectónico (Nuevo)" vs "Clásico (Legado)".
- Botones con feedback visual: borde naranja + fondo `orvian-orange/5` cuando activo.
- Check icon (Heroicons) cuando seleccionado.

**Flujo de Sincronización:**
1. Primera visita (sin cookie) → default v2.
2. Usuario autenticado → accede modal perfil → pestaña Preferencias → cambia versión.
3. Guardar → BD + Cookie (1 año).
4. Logout y relogin → cookie enviada al navegador → `AuthenticatedSessionController` lee y renderiza vista.
5. Cada dispositivo/navegador mantiene su preferencia independientemente.

### Changed

- `StudentObserver@created` ahora genera `User` automáticamente (delegado desde formularios).
- `StudentForm` simplificado: eliminado bloque `User::create()`; email calculado reactivamente desde RNC; email readonly.
- `StudentShow` — campo email ahora readonly en sección de credenciales.
- `AuthenticatedSessionController@store` con redirección diferenciada según `school_id`.
- `tailwind.config.js` con paleta `dark-bg`, `dark-card`, `dark-border` y `fontFamily.etna` para fuente institucional.
- Todos los layouts actualizados a usar `x-ui.theme-init` (sincrónico, sin flash).
- Dashboard: filtro dinámico de tiles visibles según `config/modules.php`.

### Fixed
- Módulos incompletos visibles en demo — ahora ocultos por defecto con flag `visible: false`.
- Redirección post-login ambigua — ahora diferenciada por tipo de usuario (global vs tenant).
- Falta de versión global disponible en vistas — `View::share('appVersion')` implementado con caché.

---

## [0.4.0] - 2026-04-24

### Added

#### Módulo de Asistencia — Arquitectura Dual (Plantel + Aula)

**Entidades Base:**
- Modelo `Student` con biometría completa: foto, QR único, encodings faciales (JSON), datos médicos (grupo sanguíneo, alergias, condiciones), RNC (cédula) único.
- Modelo `Teacher` con especialización, datos de contacto y asignaciones dinámicas a materias y secciones.
- Catálogo `Subject` (materias) con seeders MINERD.
- Tabla pivote `teacher_subject_sections` para asignación múltiple de maestros a secciones por materia.
- Observers `StudentObserver` y `TeacherObserver` para generación automática de QR codes y validaciones.

**Dominios de Asistencia:**
- `DailyAttendanceSession` — apertura/cierre manual del día por tanda (sin apertura = día feriado).
- `PlantelAttendanceRecord` — registro de entrada al centro educativo con validación de tardanza vs horario.
- `ClassroomAttendanceRecord` — registro por materia gestionado por maestro con validación cruzada.
- `Excuse` — modelo de justificaciones con tipos de licencia, evidencias adjuntas y flujo de aprobación.
- **Validación cruzada estricta:** Si estudiante está ausente en Plantel → no puede estar presente en Aula; si presente en Plantel pero ausente en Aula → alerta de "Pasilleo".

**Servicios de Negocio:**
- `StudentService` y `StudentPhotoService` para CRUD y captura de rostro.
- `TeacherService` y `TeacherAssignmentService` para gestión de maestros e impartición de materias.
- `PlantelAttendanceService` con validación de sesión abierta y cálculo de tardanzas.
- `ClassroomAttendanceService` con lógica de bloqueo cruzado y detección de pasilleo.
- `ExcuseService` con validación de licencias, cobertura y marque retroactivo.
- `DailySessionManager` para apertura/cierre centralizado de sesiones.
- `FaceEncodingManager` y `FacialApiClient` para enrolamiento y verificación de identidad.

**Métodos de Registro:**
- **Registro Manual:** interfaz de pase de lista clásica con búsqueda y marcado rápido.
- **Escáner QR Híbrido:** captura de código institucional con fallback a manual.
- **Reconocimiento Facial:** integración con microservicio Python `orvian-facial-recognition` con enrolamiento previo.
- **Validación Cruzada:** reglas de negocio que previenen inconsistencias entre dominios.

**Microservicio Python — Facial Recognition:**
- Repositorio independiente `orvian-facial-recognition` con FastAPI + `face_recognition` library.
- Endpoints `/health`, `/enroll` (captura + encoding), `/verify` (verificación 1:1).
- Dockerfile + docker-compose para deployment en el mismo host o remoto.
- Cliente HTTP `FacialApiClient` en Laravel para comunicación bidireccional con manejo de timeouts.
- Privacy-first: encodings almacenados en BD (128 floats), fotos nunca persistidas.

**Sincronización Offline (Fase Futura):**
- Columnas `synced_at`, `sync_status` en tablas de asistencia (preparadas pero lógica pendiente).
- `SyncManager` y comando `orvian:sync-attendance` para eventual consistency.
- Modo `APP_MODE=local` para Edge Nodes con DB ligera; `APP_MODE=cloud` para VPS central.
- Arquitectura lista para sincronización bidireccional cada 5 minutos.

#### Interfaz Web — Asistencia

**Gestión de Sesión:**
- Vista de apertura/cierre del día por tanda con histórico de sesiones.
- Indicador visual de estado (Abierta, Cerrada, En Revisión).
- Logs de auditoría con quién abrió/cerró y horarios.

**Registro de Asistencia — Plantel:**
- Interfaz de registro manual con buscador instantáneo y validación de QR.
- Soporte para modo "sustituto" (otro maestro registra en su ausencia).
- Indicadores en tiempo real de tardanza vs horario institucional.

**Pase de Lista — Aula:**
- Interfaz de maestro por materia/sección con bloqueo inteligente de estudiantes excusados.
- Detección automática de pasilleo con alerta visual.
- Justificación rápida de ausencias con razones predefinidas.

**Dashboard de Discrepancias:**
- Doble visión (Plantel vs Aula) con gráficos ApexCharts.
- Panel de anomalías: pasillos detectados, inconsistencias de horario, alumnos sin registrar en algún dominio.
- Timeline operativo del día con cambios en tiempo real.
- Metrics en vivo: presencia global, tardanzas, excusas sin revisar.

**Gestión de Excusas:**
- Portal de solicitud con adjunción de evidencias (foto/PDF).
- Flujo de revisión para coordinadores académicos.
- Notificaciones automáticas al aprobar/rechazar.
- Marque retroactivo de inasistencias al aprobar.

**Importación Masiva de Estudiantes:**
- Interfaz de dropzone para upload de Excel/CSV.
- Wizard de mapeo: usuario confirma equivalencia de columnas antes de procesar.
- Procesamiento en background con Jobs para archivos grandes.
- Barra de progreso en tiempo real y log de errores descargable.
- Validación de duplicados por RNC y nombre+fecha_nacimiento.
- Normalización automática de secciones: si Excel dice "4TO A" → búsqueda fuzzy en BD.

**Reportes y Exportación:**
- Reportes por rango de fechas, sección, maestro o alumno.
- Exportación a Excel (con formato) y PDF (con logo institucional).
- Filtros avanzados por Pipeline: estado de asistencia, tipo de registro, método (QR/Facial/Manual).
- Scoped Access: maestros ven solo sus secciones, coordinadores ven su dominio asignado.
- Plantillas de impresión personalizables.

#### Roles y Permisos — Asistencia

**Nuevos Roles:**
- `Academic Coordinator` — permiso intermedio entre Director y Maestro. Gestiona excusas, ve dashboard de discrepancias, asigna maestros a materias.

**Nuevos Permisos (PermissionGroup `attendance`):**
- `attendance.view_dashboard` — acceso al dashboard operativo.
- `attendance.manage_sessions` — abrir/cerrar sesiones diarias.
- `attendance.record_plantel` — registrar asistencia de plantel.
- `attendance.record_classroom` — registrar pase de lista de aula.
- `attendance.manage_excuses` — revisar y aprobar justificaciones.
- `attendance.export_reports` — exportar datos a Excel/PDF.
- `attendance.import_students` — importar masivamente desde archivos.
- `attendance.view_teacher_reports` — ver reportes por maestro (solo Coordinador+).

#### Configuración del Sistema

**Archivos de Configuración:**
- `config/modules.php` actualizado con módulo `asistencia` y `estudiantes` (iconos SVG, rutas, sub-links).
- `config/attendance.php` — configuración de límites de tardanza, rango de validación cruzada, timeouts del microservicio facial.

**Seeders Nuevos:**
- `SubjectSeeder` — importa catálogo MINERD de materias por modalidad.
- `StudentSeeder` y `TeacherSeeder` — factories masivos para testing.
- Actualización de `RoleAcademicSeeder` con nuevos permisos y rol `Academic Coordinator`.

**Middleware Nuevo:**
- Validación de sesión abierta antes de registrar asistencia.
- Bypass automático de permisos de asistencia para rutas `admin/*`.

#### Documentación

**Arquitectura:**
- `docs/architecture/attendance-domains.md` — explicación de doble dominio, validación cruzada y reglas de pasilleo.
- `docs/architecture/facial-recognition.md` — arquitectura del microservicio, flujo Laravel↔FastAPI, privacidad biométrica.
- `docs/architecture/offline-sync.md` — sincronización eventual, conflictos, modos local/cloud (documentado para v0.5+).

**Usuario Final:**
- `docs/modules/students.md` — gestión de estudiantes, captura de rostro, importación masiva.
- `docs/modules/teachers.md` — gestión de maestros, asignación de materias, vinculación con usuario.
- `docs/modules/attendance.md` — apertura del día, métodos de registro, pase de lista, gestión de excusas, interpretación del dashboard.

**API:**
- `docs/api/facial-recognition-api.md` — especificación completa de endpoints (`/enroll`, `/verify`, `/health`), schemas JSON, ejemplos curl, códigos de error.

#### Redis — Caché y Sesiones

- Integración de `predis/predis` para soporte de Redis.
- Configuración de driver en `.env`: `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`.
- Queues en Redis para procesamiento de importaciones masivas.
- Caché de encodings faciales con TTL para acelerar verificaciones repetidas.

#### Dashboard del Aplicativo

- Tiles de **Asistencia** y **Estudiantes** activados en `/app/dashboard` (antes en `comingSoon`).
- Enlaces a módulos de gestión agregados en sidebar/navbar.

### Changed

- **Estructura de rutas:** nuevo grupo `routes/app/attendance/*` para pase de lista, dashboard, reportes.
- **Estructura de rutas:** nuevo grupo `routes/app/academic/*` para estudiantes y maestros (reorganización desde `configuracion`).
- **Seeders:** orden de ejecución actualizado en `DatabaseSeeder` — entidades base (niveles, grados, materias) antes que roles y permisos.
- **Modelos relacionados:** `SchoolShift` renombrado a `SchoolShift` con nueva lógica de validación de horarios en `PlantelAttendanceService`.
- **Git:**  Rama parent `feature/attendance-module` merge desde `main` con conflictos resueltos en `bootstrap/app.php`.

### Fixed

- **SoftDeletes vs Foreign Keys:** Aclaración en documentación sobre comportamiento de cascada en arquitectura multitenant.
- **Timezone:** Configuración explícita de `America/Santo_Domingo` en `config/app.php` para evitar falsas tardanzas.
- **N+1 Queries:** Scopes `withIndexRelations()` agregados en controladores de asistencia para cargar relaciones críticas.
- **Índices de Base de Datos:** Índices nuevos en columnas `time`, `date` de `plantel_attendance_records` y `classroom_attendance_records` para queries de dashboard.

### Performance Considerations

- **Caché en Dashboard:** Livewire component pollea cada 10 segundos — implementar caché de corta duración (5s) en Redis para aliviar DB bajo concurrencia.
- **Biometría Separada:** Preparación para mover `face_encoding` a tabla `student_biometrics` en v0.5 para optimizar memoria en consultas masivas.
- **Índices Estratégicos:** Añadidos en `(school_id, is_active)`, `(section_id, date)`, `(teacher_id, subject_id)` para queries de filtrado.

### Known Limitations & Future Work

- **Sincronización Offline:** Lógica de `SyncManager` y comando preparados; procesamiento real planeado para v0.5.
- **Notificaciones Biométricas:** Sistema de WebSockets para feedback en tiempo real del microservicio facial pendiente.
- **Auditoría Completa:** Logs de cambios en asistencia listos; visualización en UI planeada para v0.5.

### Dependencies Added

- `phpoffice/phpexcel` — exportación a Excel con formato.
- `laravel-pdf/laravel-pdf` — generación de reportes PDF.
- `maatwebsite/excel` — importación y procesamiento de archivos Excel.
- `predis/predis` — cliente Redis para caché y sesiones.
- `apexcharts` (CDN) — gráficos en dashboard.

### Notes

- **Microservicio Separado:** El repositorio `orvian-facial-recognition` vive en su propio Git y Dockerfile; deployment independiente recomendado.
- **Privacy by Design:** Encodings faciales nunca viajan a logs o APIs externas; fotos procesadas en memoria del microservicio.
- **Escalabilidad:** Arquitectura dual (local/cloud) preparada para distribución geográfica de escuelas en futuras iteraciones.

---


## [0.3.0] - 2026-03-31

### Added

#### Identidad y Perfil de Usuario
- Campos extendidos en `users`: `avatar_path`, `avatar_color`, `phone`, `position`, `last_login_at`, `status`, `preferences` (JSON).
- `UserAvatarService` con generación de iniciales, color hex automático por paleta y resolución de URL de avatar.
- `UserObserver` para asignar `avatar_color` al crear un usuario.
- Sección de Preferencias en el perfil: tema (claro/oscuro/sistema) y sidebar colapsado (solo admins).
- Componente `x-ui.avatar` con soporte para foto, iniciales, 4 tamaños y prop `showStatus` (punto de presencia).
- Sistema de presencia de usuario: estados `online`, `away`, `busy`, `offline` con cambio manual desde el dropdown del navbar/sidebar y cambio automático por login/logout/inactividad.
- Job `orvian:update-user-status` programado en `routes/console.php` para marcar usuarios inactivos como `away`.

#### Sistema de Temas
- Componente `x-ui.theme-init` — script síncrono que aplica `.dark` desde DB antes del CSS, sin `localStorage`, eliminando el flash de tema incorrecto.
- Integrado en todos los layouts (`app`, `admin`, `install`, `wizard`).

#### Navbar y Shell de Aplicación
- Componente `x-app.navbar` extraído de `layouts/app.blade.php` con dos estados: **Hub** (transparente, compacto) y **Módulo** (sólido, con ícono SVG flip-a-‹, nombre, sub-links).
- Posicionamiento `fixed` en el navbar — los layouts compensan con `pt-14` (módulo) y `pt-[52px]` (hub).
- Drawer lateral en mobile para módulos: botón "Volver al Hub" explícito, ícono, nombre y sub-links con scroll.
- Componente `x-app.module-toolbar` — barra secundaria `sticky top-14` con slots para acciones, buscador y secundarias.
- Layout `layouts/app-module.blade.php` para vistas dentro de módulos.
- Sistema de iconos SVG para módulos: 9 SVGs en `public/assets/icons/modules/` y componente `x-ui.module-icon`.
- `config/modules.php` — fuente de verdad única para nombre, ícono y sub-links de cada módulo. Uso: `->layout('layouts.app-module', config('modules.configuracion'))`.
- Dashboard del panel app (`/app/dashboard`) estilo Odoo: grid de tiles responsivo, saludo dinámico en 7 rangos horarios, sección de accesos recientes.
- Componente `x-ui.app-tile` — tile polimórfico con soporte para SVG de módulo, badge de notificaciones, estado `comingSoon` y animación de entrada `tile-animate`.

#### Gestión de Usuarios
- CRUD completo de usuarios para el panel admin (`/admin/users`) y el panel escuela (`/app/users`).
- Soft delete y restauración en el panel admin.
- Suite de componentes `x-data-table.*`: search, per-page-selector, filter-container, filter-select, filter-toggle, filter-date-range, filter-range, filter-chips, column-selector, cell.
- Sistema de paginación ORVIAN: tres vistas (`orvian-compact`, `orvian-full`, `orvian-ledger`).
- Carga asíncrona con `#[Lazy]` y skeleton polimórfico (`x-ui.skeleton`) con variantes table, card, avatar-text, stats, form.
- Scope `withIndexRelations()` en modelo `User` para eliminar N+1 de Spatie.
- Componente `x-ui.page-header` con slots de acciones y conteo.

#### Roles y Permisos
- `SchoolRoleService` — clona roles globales (`school_id = null`) como roles propios de cada escuela al completar el wizard, heredando permisos, color e `is_system`.
- Tabla `permission_groups` con campo `context` (enum `global`/`tenant`) para separar permisos del Admin Hub de los del panel escuela.
- Seeders: `PermissionGroupSeeder`, `PermissionSeeder`, actualización de `RoleOwnerSeeder` y `RoleAcademicSeeder`.
- Modelo `PermissionGroup` con scopes `tenant()`, `global()`, `ordered()`.
- Modelos extendidos `Role` y `Permission` (sobre Spatie) con soporte de `color`, `is_system`, `school_id` y relación `belongsTo(PermissionGroup)`.
- `SchoolScope` en el modelo `Role` con bypass automático para rutas `admin/*`.
- CRUD de roles en `/admin/roles` y `/app/roles` con color picker, previsualización en vivo con badge dinámico y acción `duplicate()`.
- Matriz de permisos en `/admin/roles/{role}/permissions` y `/app/roles/{role}/permissions` con tabs por grupo, acciones masivas "Marcar todos / Desmarcar todos" y protección de roles de sistema.
- Helper `trans_permission()` y archivos `lang/es/permissions.php`, `lang/es/permission_groups.php`, `lang/es/roles.php`.
- `ProfileModal` — perfil como modal Livewire embebido en los layouts app, abierto desde el dropdown del navbar.

#### Componentes UI Extendidos
- `x-ui.badge`: nuevo prop `hex` para colores arbitrarios con estilos inline (fondo + borde semitransparente).
- `x-ui.button`: prop `hex` con contraste automático YIQ, prop `href` para tag dinámico `<a>`, tipo `ghost` para toolbars, `wire:loading.class` integrado nativo, `aria-label` inferido en modo icono.

#### Observabilidad
- Laravel Pulse en `/admin/pulse` protegido por gate `viewPulse` (solo Owner).
- Log Viewer en `/admin/logs` restringido a `Owner` y `TechnicalSupport`.

#### Administración Global (Owner)
- Dashboard SaaS con métricas de MRR, escuelas activas/inactivas, usuarios totales y gráficos ApexCharts (crecimiento 30 días + distribución por plan).
- Componente `x-admin.stat-card` con micro-interacciones y variantes semánticas.
- Gestión de centros (`/admin/schools`) con filtros avanzados geográficos, estados duales (`is_active` / `is_suspended`), columna de Director y diferenciación de usuarios (Staff vs Estudiantes).
- `EnsureSchoolIsActive` middleware con redirección a vistas de aviso (`suspended.notice`, `inactive.notice`).
- Integración Google Maps en ficha de escuela con fallback visual para coordenadas nulas.
- Gestión de planes (`/admin/plans`) con grid de cards, slide-over de creación/edición, previsualización en tiempo real y unicidad de `is_featured` garantizada por Observer.
- Matriz de asignación de features por plan con toggles agrupados por módulo.
- Componentes reutilizables `x-ui.plan-card` y `x-admin.stat-card`.

#### Configuración Institucional
- Vista `/app/settings/school` con gestión de logo (upload + limpieza de disco), datos institucionales, geografía en cascada y año escolar activo en solo lectura.
- Componentes `x-ui.school-logo` (con overlay de edición hover) y `x-ui.loading` (spinner versátil).
- Campo `logo_path` y `province_id` en tabla `schools`.

#### Wizard — Mejoras
- Agrupación visual de niveles por ciclos académicos (Primer / Segundo Ciclo) con componente `x-wizard.level-card`.
- Automatización del año escolar: calculado desde el mes actual, sin selector manual.
- Validaciones server-side de rango de fechas con mensajes descriptivos.
- Integración de `x-ui.plan-card` en el paso de selección de plan.
- Botón "Volver a Escuelas" visible para Owner en el paso intro del wizard administrativo.

### Changed

- `layouts/app.blade.php` simplificado — navbar extraído a componente, fondo actualizado a `bg-slate-100 dark:bg-[#080e1a]`.
- Sidebar admin adaptado a preferencia `sidebar_collapsed` desde DB, eliminando `localStorage`.
- Dropdown del avatar unificado en ambos contextos (hub y módulo) con selector de estado y enlace a perfil como modal.
- `tailwind.config.js` actualizado con paleta de tema oscuro en tonalidades grises slate.
- Rutas organizadas por dominio en `routes/admin/` y `routes/app/` con middleware `can:` en usuarios y roles.
- `AppServiceProvider` registra observer de `Plan` y paginator por defecto.

### Fixed

- ParseError en `dashboard.blade.php` con `match` dentro de `@php` en PHP 8.5 — lógica movida al controlador.
- Flash de tema incorrecto al navegar — `x-ui.theme-init` aplicado síncronamente en `<head>`.
- Cambio de tema al navegar hub → módulo — eliminado `wire:navigate` en tiles del dashboard (carga completa entre layouts distintos).
- Selector de roles vacío en `UserIndex` — resuelto con `SchoolRoleService::seedDefaultRoles()` al completar el wizard.
- Solapamiento de 4px entre navbar y `module-toolbar` — corregido con `sticky top-14` (navbar módulo es `h-14`, no `h-[52px]`).

### Removed

- `x-app.search` eliminado del navbar de módulo — el buscador contextual se define por módulo en su propio `module-toolbar`.
- Toggle de dark mode de `install.blade.php` y `wizard.blade.php` — reemplazado por el sistema de preferencias en perfil.
- Listener `AssignInitialRoles` — la asignación de rol al Director queda garantizada por el orden de ejecución en las Actions (roles clonados antes del evento `SchoolConfigured`).

---

## [0.2.0] - 2026-03-12

### Added

#### Geografía y Datos Maestros
- Jerarquía geográfica completa de República Dominicana: Provincias → Municipios → Distritos Municipales.
- Comando `orvian:import-geo` para importación masiva desde fuentes externas vía `Http::get()`.
- Geografía educativa MINERD: modelos `RegionalEducation` y `EducationalDistrict`.
- Comando `orvian:import-educational-geo` y seeder `EducationalGeoSeeder`.

#### Catálogos Académicos
- Infraestructura educativa completa: `Level`, `Grade`, `SchoolSection`, `SchoolShift`, `AcademicYear`.
- `Grade` con soporte de ciclos (`cycle`) y flag `allows_technical` para grados del Segundo Ciclo de Secundaria.
- `SchoolSection` refactorizado: campo `label` (letra del paralelo) y FK nullable `technical_title_id`.
- Catálogo técnico MINERD: `TechnicalFamily`, `TechnicalTitle`, tabla pivote `school_technical_titles`.
- Seeders: `LevelSeeder`, `GradeSeeder` (con ciclos), `TechnicalCatalogSeeder`, `RoleAcademicSeeder`.

#### Planes y Features
- Catálogo de planes (`plans`) con límites de estudiantes, usuarios, precio y flag `is_featured`.
- Sistema de Feature Flags basado en catálogo: tabla `features` + pivote `feature_plan`.
- Relación `Plan belongsToMany Feature` reemplazando el campo JSON anterior.
- Método `hasFeature()` en `School` y helper `canAccess()` en `Plan`.
- `PlanFeatureSeeder` unificado que reemplaza `PlanSeeder` y `FeatureSeeder` independientes.

#### Multi-Tenancy
- Aislamiento automático de consultas por `school_id` mediante `GlobalScope`.
- Middleware `IdentifyTenant` para resolución del tenant activo por sesión.
- Integración de `spatie/laravel-permission` con `teams = true` por `school_id`.
- Campo `users.school_id` nullable (null para roles globales: Owner, TechnicalSupport, Administrative).
- Índice único en `model_has_roles` con soporte de `school_id` nullable.

#### Arquitectura de Dominio
- Capa de aplicación con Actions de propósito único:
  - `CompleteOnboardingAction` — crea escuela + Director desde cero (Owner).
  - `CompleteTenantOnboardingAction` — actualiza escuela stub existente (Usuario registrado).
  - `CreateSchoolPrincipalAction` — crea Director y asigna rol en scope del tenant.
- Evento `SchoolConfigured` (`app/Events/Tenant/`).
- Listeners disparados por `SchoolConfigured`:
  - `SetupAcademicStructure` — crea secciones por nivel, grado y paralelo.
  - `CreateInitialAcademicYear` — crea el año escolar con las fechas del wizard.
  - `AssignInitialRoles` — confirma el rol `School Principal` en scope del tenant.
- Event Discovery activo; sin registros manuales en `EventServiceProvider`.

#### Onboarding y Seguridad
- Flujo de Self-Installation del Owner en `/register` con middleware `EnsureSystemIsInstalled`.
- Registro público en `/sign-up` con creación automática de escuela stub.
- Comando `orvian:cleanup-stubs` programado para eliminar stubs vencidos.
- Middlewares: `EnsureSystemIsInstalled`, `RedirectIfSystemNotInstalled`, `EnsureOnboardingIsComplete`, `EnsureGlobalAdminAccess`.

#### Wizard de Configuración de Escuela
- Arquitectura de herencia `BaseSchoolWizard` con `SchoolWizard` (Owner, 5 pasos) y `TenantSetupWizard` (Tenant, 4 pasos).
- Validación server-side por paso con regla custom para modalidades técnicas.
- Pantalla de progreso animada post-`finish()` con redirección automática.

#### Sistema UI
- Layouts: `components/admin.blade.php`, `components/install.blade.php`, `components/wizard.blade.php`, `components/app.blade.php`.
- Sidebar administrativo responsive y colapsable.
- Componentes core: `x-ui.button`, `x-ui.badge`, `x-ui.toast`, `x-ui.empty-state`.
- Suite `x-ui.forms.*`: Input, Select, Textarea, Checkbox, Radio, Toggle con dark mode completo.
- Query Filter Pipeline compatible con Livewire y componente `GeographicSelects`.

### Changed

- Modelo `School` refactorizado con nuevos campos y eliminación de `jornada`.
- Modelo `Grade` con `cycle` y `allows_technical`.
- Registro de usuario migrado de Breeze a Livewire.
- Features de Plan migradas de JSON a `belongsToMany`.

### Removed

- `FeatureSeeder` y `PlanSeeder` independientes — reemplazados por `PlanFeatureSeeder`.
- Vistas legacy `dashboard` y `auth/register` de Breeze.
- Trait `BelongsToSchool` y columna `jornada` de `schools`.

---

## [0.1.0] - 2026-01-15

Commit inicial del proyecto. Estructura base de Laravel con configuración de entorno y README.