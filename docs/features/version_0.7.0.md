# ORVIAN v0.7.0 — Presencia Pública Institucional

**RAMA PADRE:** `feature/public-presence`

**Objetivo:** Establecer la presencia pública de ORVIAN mediante una Landing Page institucional y una página "Sobre Nosotros" que comuniquen la propuesta de valor del sistema, el equipo fundador y los módulos disponibles. Esta versión también consolida la infraestructura de layout público (navbar y footer como componentes reutilizables), estandariza el uso de `x-ui.app-tile` como componente único de representación de módulos, e introduce mejoras de autenticación: redirección de usuarios no autenticados hacia `/` y toast de bienvenida post-login.

> **Estado de Fases:** Las Fases 1 y 2 están **finalizadas con PR mergeado**. La Fase 3 está **implementada en rama `feature/landing-sections`**, pendiente de PR. El desarrollo activo comienza en la Fase 4.

---

## Estado de la Base — v0.6.0 como Fundación

| Componente | Origen | Estado |
| :--- | :--- | :--- |
| `x-ui.theme-init` — script síncrono sin flash de tema | v0.3.0 | ✅ Completado |
| `x-ui.toasts` — sistema de notificaciones toast con eventos Alpine | v0.3.0 | ✅ Completado |
| `x-ui.badge` — badges semánticos con soporte `hex` | v0.3.0 | ✅ Completado |
| `x-ui.button` — botón polimórfico con variantes y soporte `href` | v0.3.0 | ✅ Completado |
| `x-ui.app-tile` — tile polimórfico de módulos con estados `active`, `comingSoon`, badge | v0.3.0 | ✅ Completado |
| `x-ui.module-icon` — componente de icono SVG por módulo | v0.3.0 | ✅ Completado |
| Iconos SVG de módulos en `public/assets/icons/modules/` | v0.3.0 | ✅ Completado |
| `View::share('appVersion')` desde archivo `VERSION` con caché | v0.4.1 | ✅ Completado |
| Redirección post-logout a `/` | v0.4.1 | ✅ Completado |
| Sistema de temas oscuro/claro en `tailwind.config.js` | v0.3.0 | ✅ Completado |
| `AuthenticatedSessionController` (Breeze) — login y logout | v0.2.0 | ✅ Completado |
| Fuente Etna configurada en `tailwind.config.js` con `font-etna` | v0.4.1 | ✅ Completado |

---

## Tabla de Requerimientos

| ID | Fase | Área | Descripción | Prioridad | Estado |
| :-- | :-- | :-- | :-- | :-- | :-- |
| REQ-01 | 1 | Routing | Renombrar `welcome.blade.php` → `landing.blade.php` y actualizar `web.php` | Alta | ✅ Mergeado |
| REQ-02 | 1 | Routing | `Authenticate.php` redirige a `route('landing')` en lugar de `/login` | Alta | ✅ Mergeado |
| REQ-03 | 1 | Routing | Rutas protegidas redirigen a `/` para usuarios no autenticados | Alta | ✅ Mergeado |
| REQ-04 | 1 | Auth | Toast de bienvenida post-login desde `AuthenticatedSessionController@store` | Alta | ✅ Mergeado |
| REQ-05 | 2 | Layout | `layouts/public.blade.php` con `x-ui.theme-init`, favicons por tema y toasts | Alta | ✅ Mergeado |
| REQ-06 | 2 | Layout | `layouts/navigation.blade.php` — Navbar pública como componente reutilizable | Alta | ✅ Mergeado |
| REQ-07 | 3 | UI | Sección Hero: headline, subheadline, CTAs y mockup visual del sistema | Alta | ✅ Implementado |
| REQ-08 | 3 | UI | Sección Stats: métricas institucionales estáticas (4 columnas) | Media | ✅ Implementado |
| REQ-09 | 3 | UI | Sección Módulos usando `x-ui.app-tile` exclusivamente — sin tarjetas nuevas | Alta | ✅ Implementado |
| REQ-10 | 3 | UI | Sección "¿Por qué ORVIAN?" con 6 diferenciadores usando Heroicons | Alta | ✅ Implementado |
| REQ-11 | 3 | UI | Sección CTA Final con botones de acción | Media | ✅ Implementado |
| REQ-12 | 3 | Layout | `layouts/footer.blade.php` — Footer público como componente reutilizable | Media | ✅ Implementado |
| REQ-13 | 4 | Página | Vista `about.blade.php` con layout público — ruta `/sobre-nosotros` | Alta | Pendiente |
| REQ-14 | 4 | UI | Sección de equipo fundador con fotos circulares, nombres y cargos | Alta | Pendiente |
| REQ-15 | 4 | UI | Sección de misión/visión institucional con estética de bloques Odoo | Media | Pendiente |

> **Estándar de calidad transversal:** Toda UI construida en las Fases 3 y 4 debe ser responsiva desde el primer commit. La responsividad no es una fase de validación posterior sino un criterio de aceptación de cada componente individual. Se considerará incompleta cualquier sección que no funcione correctamente en mobile (`sm`), tablet (`md`) y desktop (`lg`/`xl`).

---

## ⚠️ Normativa de Componentes — Cumplimiento Obligatorio

### Representación de Módulos: `x-ui.app-tile` es el único componente permitido

**Aplica a:** Landing Page (sección Módulos), Dashboard (`/app/dashboard`), y cualquier vista pública o privada que liste accesos a módulos del sistema.

**Está estrictamente prohibido:**
- Crear tarjetas (`<div>` custom) que dupliquen la funcionalidad de `app-tile`
- Copiar y pegar estilos del componente en vistas inline
- Usar `x-ui.badge` + `x-ui.module-icon` combinados manualmente para simular un tile

**Fundamento:** El componente `x-ui.app-tile` ya gestiona de forma centralizada los estados `active` (acceso por plan), `comingSoon` (desarrollo pendiente), badges de notificaciones, hover effects, modo oscuro/claro y accesibilidad. Duplicar esta lógica introduce deuda técnica y rompe la consistencia visual entre el dashboard interno y la cara pública del sistema.

**Ejemplo canónico de implementación:**

```blade
{{-- Módulo activo con URL real --}}
<x-ui.app-tile
    module="asistencia"
    title="Asistencia"
    subtitle="Control"
    url="{{ route('app.attendance.dashboard') }}"
    :active="true" />

{{-- Módulo en desarrollo --}}
<x-ui.app-tile
    module="notas"
    title="Calificaciones"
    subtitle="Notas"
    comingSoon="true" />

{{-- Módulo bloqueado por plan del tenant --}}
<x-ui.app-tile
    module="reportes"
    title="Reportes"
    subtitle="Analítica"
    :active="in_array('reports_advanced', $activeModules)" />
```

**En la Landing Page** (contexto público, sin `$activeModules`), todos los tiles se renderizan con `:active="true"` ya que la landing muestra el sistema en su estado ideal, no el estado del tenant:

```blade
{{-- En landing.blade.php — siempre activo, contexto informativo --}}
<x-ui.app-tile
    module="academico"
    title="Académico"
    subtitle="Gestión"
    url="#modulos"
    :active="true" />
```

> **Referencia del componente:** `resources/views/components/ui/app-tile.blade.php`

---

## Fase 1 — Infraestructura de Routing y Auth ✅ MERGEADA
**Rama:** `feature/landing-routing` — PR aprobado y mergeado a `main`

### Resumen de lo completado

| Subtarea | Archivo modificado | Resultado |
| :--- | :--- | :--- |
| 1.1 | `resources/views/welcome.blade.php` → `landing.blade.php` | Renombrado |
| 1.2 | `routes/web.php` | Ruta `/` retorna `landing` con nombre `landing` |
| 1.3 | `app/Http/Middleware/Authenticate.php` | `redirectTo` apunta a `route('landing')` |
| 1.4 | `AuthenticatedSessionController@destroy` | Validado: ya redirige a `/` |
| 1.5 | `AuthenticatedSessionController@store` | Flash `success` con nombre del usuario post-login |

### Referencia — Middleware de Autenticación

```php
// app/Http/Middleware/Authenticate.php
protected function redirectTo(Request $request): ?string
{
    return $request->expectsJson() ? null : route('landing');
}
```

### Referencia — Toast Post-Login

```php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();

    session()->flash('success', '¡Bienvenido de nuevo, ' . auth()->user()->name . '!');

    return redirect()->intended(
        auth()->user()->school_id
            ? route('app.dashboard')
            : route('admin.hub')
    );
}
```

---

## Fase 2 — Layout Público y Componentes de Estructura ✅ MERGEADA
**Rama:** `feature/landing-layout` — PR aprobado y mergeado a `main`

### Resumen de lo completado

| Subtarea | Archivo creado | Resultado |
| :--- | :--- | :--- |
| 2.1 | `resources/views/layouts/public.blade.php` | Layout base público con `x-ui.theme-init`, favicons y `x-ui.toasts` |
| 2.2 | `resources/views/layouts/navigation.blade.php` | Navbar pública modular con Alpine.js |

### Referencia — Estructura del Layout Público

```html
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ORVIAN — Sistema Integral de Gestión Educativa para instituciones dominicanas." />
    <title>ORVIAN — Gestión Educativa</title>

    <link rel="icon"
          href="{{ asset('img/logos/logo-icon-light.svg') }}"
          type="image/svg+xml"
          media="(prefers-color-scheme: light)">
    <link rel="icon"
          href="{{ asset('img/logos/logo-icon-dark.svg') }}"
          type="image/svg+xml"
          media="(prefers-color-scheme: dark)">

    <x-ui.theme-init />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-dark-bg text-slate-800 dark:text-slate-100 antialiased">
    <x-ui.toasts />
    @include('layouts.navigation')
    {{ $slot }}
    @include('layouts.footer')
</body>
</html>
```

### Referencia — Navbar con Scroll Effect (Alpine.js)

```html
{{-- resources/views/layouts/navigation.blade.php --}}
<nav
    x-data="{ scrolled: false, mobileOpen: false }"
    @scroll.window="scrolled = window.scrollY > 20"
    :class="scrolled
        ? 'bg-white/90 dark:bg-dark-bg/90 shadow-md backdrop-blur-sm border-b border-slate-200/50 dark:border-white/5'
        : 'bg-transparent'"
    class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
>
    ...
</nav>
```

> **Nota sobre el Footer en Fase 2:** El archivo `layouts/footer.blade.php` fue creado como stub vacío para no bloquear el layout público. Su implementación completa se realiza en la Fase 3 en conjunto con el contenido final de la landing.

---

## Fase 3 — Landing Page (`landing.blade.php`)
**Rama:** `feature/landing-sections`

### Objetivo

Construir el contenido completo de `landing.blade.php` usando el layout público y completar los componentes de estructura (`navigation.blade.php` con links finales y `footer.blade.php`). La página es una sola vista de scroll con 6 secciones.

**Criterio de aceptación de responsividad (aplica a cada sección individualmente):**

| Breakpoint | Ancho | Comportamiento esperado |
| :--- | :--- | :--- |
| base (mobile) | < 640px | Layout de 1 columna, hamburger menu, tiles en 3 columnas |
| `sm` | 640px | Grid de módulos pasa a 3-4 columnas |
| `md` | 768px | Navbar muestra links completos, hero en dos columnas |
| `lg` | 1024px | Grid de módulos en 6 columnas, secciones con `max-w-5xl` centrado |
| `xl` | 1280px | Espaciados y paddings finales |

**Paleta de referencia:**

| Elemento | Claro | Oscuro |
| :--- | :--- | :--- |
| Fondo body | `bg-white` | `dark:bg-dark-bg` |
| Fondo cards | `bg-slate-50` | `dark:bg-slate-800/50` |
| Bordes | `border-slate-200` | `dark:border-white/10` |
| Texto primario | `text-slate-900` | `dark:text-slate-100` |
| Texto secundario | `text-slate-500` | `dark:text-slate-400` |
| Acento naranja | `text-orvian-orange` | `text-orvian-orange` (idéntico en ambos temas) |

---

### 3.1 — Navbar Pública (completar `layouts/navigation.blade.php`)

**Elementos finales a agregar sobre el stub existente:**
- Wordmark "ORVIAN" con `font-etna`
- Links: `#modulos`, `#por-que`, `#contacto` y `route('about')` ("Sobre Nosotros")
- Botón "Iniciar Sesión": `<x-ui.button variant="secondary" type="outline" href="{{ route('login') }}">Iniciar Sesión</x-ui.button>`
- Botón "Solicitar Demo": `<x-ui.button variant="primary" href="{{ route('login') }}">Solicitar Demo</x-ui.button>`
- Mobile: hamburger con Alpine.js (`x-show` con transición), mismo menú en columna vertical

---

### 3.2 — Sección Hero

**ID de ancla:** `#inicio`

**Contenido:**
- Badge: `<x-ui.badge variant="primary" size="sm" :dot="false">Sistema de Gestión Educativa</x-ui.badge>`
- Headline: "Moderniza tu Centro con" + `<span class="text-orvian-orange font-black">ORVIAN</span>`
- Subheadline: "La plataforma modular diseñada para instituciones públicas dominicanas. Control total, desde la asistencia hasta las calificaciones, en un solo ecosistema."
- CTA primario: `<x-ui.button variant="primary" :hoverEffect="true" href="{{ route('login') }}">Empieza Ahora</x-ui.button>`
- CTA secundario: `<x-ui.button variant="secondary" type="outline">Ver Módulos</x-ui.button>` con Alpine `@click` para scroll suave a `#modulos`
- Mockup visual del dashboard con glow decorativo `shadow-[0_0_80px_rgba(theme(colors.orvian-orange/DEFAULT),0.15)]`

**Responsividad:**
- Mobile: hero centrado, una columna, mockup debajo de los CTAs
- `md`+: dos columnas, texto a la izquierda, mockup a la derecha

---

### 3.3 — Sección Stats

**ID de ancla:** `#stats`

Stats estáticos representativos. No conectados a la base de datos.

| Valor | Etiqueta |
| :--- | :--- |
| `500+` | Instituciones |
| `1M+` | Estudiantes |
| `99.9%` | Uptime |
| `4.9/5` | Satisfacción |

**Responsividad:** `grid-cols-2` en mobile, `md:grid-cols-4` en desktop.

---

### 3.4 — Sección Módulos

**ID de ancla:** `#modulos`

**⚠️ Normativa aplicada:** Esta sección usa **exclusivamente** `x-ui.app-tile`. Ver sección "Normativa de Componentes".

```blade
{{-- Sección Módulos en landing.blade.php --}}
<section id="modulos" class="py-24 px-4">
    <div class="max-w-5xl mx-auto">
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-orvian-orange/70 mb-3">
            Gestión Modular Sin Límites
        </p>
        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-2">
            Activa solo lo que necesitas
        </h2>
        <p class="text-slate-500 dark:text-slate-400 mb-12 max-w-xl">
            Nuestra arquitectura se adapta al crecimiento de tu institución.
        </p>

        <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-x-4 gap-y-8">
            <x-ui.app-tile module="administracion" title="Core"           subtitle="Sistema"     url="#" :active="true" />
            <x-ui.app-tile module="asistencia"     title="Asistencia"    subtitle="Control"      url="#" :active="true" />
            <x-ui.app-tile module="academico"      title="Académico"     subtitle="Gestión"      url="#" :active="true" />
            <x-ui.app-tile module="notas"          title="Calificaciones" subtitle="Notas"       url="#" :active="true" />
            <x-ui.app-tile module="conversaciones" title="Comunicaciones" subtitle="WhatsApp"    url="#" :active="true" />
            <x-ui.app-tile module="reportes"       title="Reportes"      subtitle="Analítica"    url="#" :active="true" />
        </div>
    </div>
</section>
```

---

### 3.5 — Sección ¿Por Qué ORVIAN?

**ID de ancla:** `#por-que`

**Estructura:** `grid-cols-1` en mobile, `md:grid-cols-2`, `lg:grid-cols-3` en desktop. Heroicons como íconos.

| Heroicon | Título | Descripción |
| :--- | :--- | :--- |
| `heroicon-o-map-pin` | Hecho para República Dominicana | Diseñado para el currículo MINERD, con geografía educativa dominicana integrada desde el núcleo |
| `heroicon-o-lock-closed` | Multi-tenant y Seguro | Cada centro opera en su propio espacio aislado. Los datos de un centro nunca son accesibles por otro |
| `heroicon-o-device-phone-mobile` | Sin Dependencia del Celular | Funciona desde cualquier navegador. No requiere smartphones para alumnos ni docentes |
| `heroicon-o-puzzle-piece` | Modular y Progresivo | Activa solo los módulos que necesitas hoy. Escala según el crecimiento de tu institución |
| `heroicon-o-face-smile` | Biometría Opcional | El reconocimiento facial nunca es obligatorio. Siempre hay una alternativa física disponible |
| `heroicon-o-bolt` | Tiempo Real | Dashboards operativos con datos en vivo. El Director sabe en todo momento qué pasa en su plantel |

---

### 3.6 — Sección CTA Final

**ID de ancla:** `#contacto`

```blade
<section id="contacto" class="py-24 px-4 bg-slate-50 dark:bg-white/[0.02]">
    <div class="max-w-2xl mx-auto text-center">
        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-4">
            ¿Listo para modernizar tu centro?
        </h2>
        <p class="text-slate-500 dark:text-slate-400 mb-8">
            Únete a las instituciones que ya están transformando su gestión
            con tecnología de grado empresarial.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <x-ui.button variant="primary" :hoverEffect="true" href="{{ route('login') }}">
                Solicitar Demo Gratuita
            </x-ui.button>
            <x-ui.button variant="secondary" type="outline" href="{{ route('login') }}">
                Hablar con un Experto
            </x-ui.button>
        </div>
    </div>
</section>
```

---

### 3.7 — Footer (`layouts/footer.blade.php`)

Componente reutilizable incluido desde `layouts/public.blade.php`. Al ser un `@include`, todas las páginas públicas (landing, about) lo heredan automáticamente.

**Responsividad:** `grid-cols-1` en mobile, `md:grid-cols-2`, `lg:grid-cols-4` en desktop.

| Columna | Contenido |
| :--- | :--- |
| Marca | Wordmark ORVIAN (`font-etna`) + descripción + badge `v{{ $appVersion }}` |
| Plataforma | Características, Seguridad, API — `href="#"` |
| Empresa | Sobre Nosotros (`route('about')`), Blog, Carreras |
| Soporte | Contacto, Documentación, Estado — `href="#"` |

**Línea inferior:**
```blade
<p class="text-xs text-slate-400 dark:text-slate-600">
    © {{ date('Y') }} ORVIAN. Todos los derechos reservados.
</p>
<x-ui.badge variant="slate" size="sm" :dot="false">v{{ $appVersion }}</x-ui.badge>
```

---

## Fase 4 — Página "Sobre Nosotros" (`about.blade.php`)
**Rama:** `feature/landing-about`

### Objetivo

Crear la página institucional del equipo fundador. Usa `layouts/public.blade.php`. Sigue la estética de bloques tipo Odoo: secciones bien delimitadas con fondos alternados, tipografía jerárquica y espacio generoso. La página ya estará enlazada desde la navbar y el footer completados en la Fase 3.

**Ruta a registrar en `routes/web.php`:**

```php
Route::get('/sobre-nosotros', function () {
    return view('about');
})->name('about');
```

**Criterio de aceptación de responsividad:** Idéntico al de la Fase 3. Cada sección debe funcionar correctamente en mobile desde el primer commit.

---

### 4.1 — Sección Hero de la Página

Encabezado sobrio e institucional. Fondo consistente con el body.

**Contenido:**
- Badge: `<x-ui.badge variant="primary" size="sm" :dot="false">El Equipo</x-ui.badge>`
- Headline: "Las personas detrás de ORVIAN"
- Subheadline: "Somos un equipo joven de República Dominicana con una convicción: la tecnología bien aplicada puede transformar la educación pública de nuestro país."

---

### 4.2 — Sección Misión y Visión

**Estructura:** Bloques tipo Odoo — 2 columnas en `md`+, 1 columna en mobile. Fondo alternado (`bg-slate-50 dark:bg-white/[0.02]`). Cada bloque tiene un Heroicon grande, título y párrafo.

| Bloque | Heroicon | Título | Contenido |
| :--- | :--- | :--- | :--- |
| Misión | `heroicon-o-academic-cap` | Nuestra Misión | Modernizar los procesos académicos y administrativos de los centros educativos públicos dominicanos mediante tecnología accesible, segura y construida para su realidad. |
| Visión | `heroicon-o-eye` | Nuestra Visión | Ser la plataforma de referencia en gestión educativa de República Dominicana, reconocida por reducir la carga administrativa de los docentes y mejorar la trazabilidad del aprendizaje. |

---

### 4.3 — Sección del Equipo Fundador

**Estructura:** `grid-cols-1` en mobile, `sm:grid-cols-2`, `lg:grid-cols-3` en desktop.

**Miembros del equipo:**

| Nombre | Cargo | Descripción de rol |
| :--- | :--- | :--- |
| Elian David | Fundador / Director General | Visión del producto, arquitectura del sistema y desarrollo principal de ORVIAN |
| Kimberly Marte | Gerente Administrativa | Coordinación operativa, planificación estratégica y gestión de recursos del proyecto |
| Meredyth Ferreira | Coordinadora de Relaciones Institucionales | Comunicación institucional, alianzas con centros educativos y relaciones con el MINERD |
| Jhostin Morales | Líder de Tecnología e Infraestructura | Arquitectura de servidores, DevOps, Docker y estabilidad de la plataforma en producción |
| Justin Francisco | Desarrollador de Software | Desarrollo de módulos, integraciones y aseguramiento de calidad del código |
| Jeremía Meléndez | Diseño y Marketing | Identidad visual de ORVIAN, experiencia de usuario y estrategia de comunicación digital |

**Implementación del card de miembro:**

```blade
<div class="flex flex-col items-center text-center p-6 rounded-2xl
            bg-slate-50 dark:bg-white/[0.03]
            border border-slate-200 dark:border-white/[0.06]">

    {{-- Foto circular con fallback automático --}}
    <div class="w-24 h-24 rounded-full overflow-hidden mb-4 ring-2 ring-orvian-orange/20">
        <img
            src="{{ asset('img/team/' . Str::slug($nombre) . '.jpg') }}"
            alt="{{ $nombre }}"
            class="w-full h-full object-cover"
            onerror="this.src='{{ asset('img/team/placeholder.svg') }}'"
        />
    </div>

    <h3 class="font-bold text-slate-900 dark:text-white text-base leading-tight">
        {{ $nombre }}
    </h3>

    <div class="mt-2">
        <x-ui.badge variant="primary" size="sm" :dot="false">{{ $cargo }}</x-ui.badge>
    </div>

    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
        {{ $descripcion }}
    </p>
</div>
```

**Convención de nombres de archivo para fotos:**

```
public/img/team/elian-david.jpg
public/img/team/kimberly-marte.jpg
public/img/team/meredyth-ferreira.jpg
public/img/team/jhostin-morales.jpg
public/img/team/justin-francisco.jpg
public/img/team/jeremias-melendez.jpg
public/img/team/placeholder.svg        ← fallback si la foto no existe
```

> El atributo `onerror` garantiza que la página no se rompa si alguna foto no fue subida antes del deploy. El `placeholder.svg` cubre cualquier imagen faltante.

---

### 4.4 — Sección de Valores

**Estructura:** Bloques tipo Odoo — `grid-cols-1` en mobile, `md:grid-cols-3` en desktop. Fondo alternado.

| Heroicon | Valor | Descripción |
| :--- | :--- | :--- |
| `heroicon-o-heart` | Compromiso con la educación pública | Construimos ORVIAN pensando primero en las escuelas que más lo necesitan |
| `heroicon-o-shield-check` | Privacidad por diseño | Los datos biométricos nunca salen del sistema. La confianza de los centros es nuestra responsabilidad |
| `heroicon-o-light-bulb` | Tecnología accesible | Un sistema de grado empresarial no debería requerir un presupuesto empresarial |

---

## Archivos a Crear / Modificar

| Archivo | Acción | Fase |
| :--- | :--- | :--- |
| `resources/views/welcome.blade.php` | Renombrado a `landing.blade.php` | 1 ✅ |
| `routes/web.php` | Ruta `/` → `landing`; ruta `/sobre-nosotros` → `about` | 1 ✅ / 4 |
| `app/Http/Middleware/Authenticate.php` | `redirectTo` apunta a `route('landing')` | 1 ✅ |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Flash `success` post-login | 1 ✅ |
| `resources/views/layouts/public.blade.php` | Layout base público | 2 ✅ |
| `resources/views/layouts/navigation.blade.php` | Navbar pública modular (stub en F2, completar en F3) | 2 ✅ stub / 3 |
| `resources/views/layouts/footer.blade.php` | Footer público modular (stub en F2, completar en F3) | 2 ✅ stub / 3 |
| `resources/views/landing.blade.php` | Landing Page completa | 3 |
| `resources/views/about.blade.php` | Página Sobre Nosotros | 4 |
| `public/img/team/*.jpg` | Fotos de los 6 miembros del equipo | 4 |
| `public/img/team/placeholder.svg` | Avatar placeholder para fotos faltantes | 4 |

---

## Notas de Implementación

- **Fuente Etna:** Configurada en `tailwind.config.js` con `font-etna` desde v0.4.1. El wordmark en navbar y footer la usa sin trabajo adicional.
- **`x-ui.app-tile` en contexto público:** El componente no tiene dependencias de usuario autenticado. Usar siempre `:active="true"` en la landing — representa el sistema en su estado ideal, no el estado del plan del tenant.
- **`x-ui.module-icon` en la landing:** Accesible desde cualquier vista Blade sin contexto de usuario. El componente `x-ui.app-tile` lo usa internamente; no se necesita invocarlo por separado.
- **Sin dependencias externas nuevas:** Esta versión no agrega paquetes npm ni composer. Todo se construye con Tailwind, Alpine.js, Blade y los componentes `x-ui.*` ya existentes.
- **Performance de la landing:** Sin consultas a la base de datos. `x-ui.theme-init` para usuarios no autenticados lee `prefers-color-scheme` del sistema operativo directamente.
- **Fotos del equipo y fallback:** El atributo `onerror` en los `<img>` de los cards de equipo carga el `placeholder.svg` automáticamente si la foto no existe, garantizando que la página `about` no se rompa en ningún escenario de deploy.
- **Links a páginas no implementadas:** Footer y navbar incluyen links a Blog, Carreras, API y Documentación que apuntan a `href="#"` en esta versión. Solo "Sobre Nosotros" tiene ruta real (`route('about')`).
- **`$appVersion` en footer:** Disponible en todas las vistas vía `View::share` desde v0.4.1. El footer lo muestra como `<x-ui.badge>v{{ $appVersion }}</x-ui.badge>`.