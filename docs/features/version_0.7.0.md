# ORVIAN v0.7.0 — Landing Page Institucional

**RAMA PADRE:** `feature/landing-page`

**Objetivo:** Crear la presencia pública de ORVIAN mediante una Landing Page institucional responsiva, con soporte de tema oscuro/claro, que comunique la propuesta de valor del sistema, los módulos disponibles y los diferenciadores frente a soluciones genéricas. Esta versión también introduce mejoras de infraestructura de autenticación: redirección de usuarios no autenticados hacia `/`, toast de bienvenida post-login y renombrado semántico de la vista de entrada pública.

---

## Estado de la Base — v0.6.0 como Fundación

| Componente | Origen | Estado |
| :--- | :--- | :--- |
| `x-ui.theme-init` — script síncrono sin flash de tema | v0.3.0 | ✅ Completado |
| `x-ui.toasts` — sistema de notificaciones toast con eventos Alpine | v0.3.0 | ✅ Completado |
| `x-ui.badge` — badges semánticos con soporte `hex` | v0.3.0 | ✅ Completado |
| `x-ui.button` — botón polimórfico con variantes y soporte `href` | v0.3.0 | ✅ Completado |
| `View::share('appVersion')` desde archivo `VERSION` con caché | v0.4.1 | ✅ Completado |
| Iconos SVG de módulos en `public/assets/icons/modules/` | v0.3.0 | ✅ Completado |
| `x-ui.module-icon` — componente de icono SVG por módulo | v0.3.0 | ✅ Completado |
| Redirección post-logout al login | v0.4.1 | ✅ Completado |
| Sistema de temas oscuro/claro en `tailwind.config.js` | v0.3.0 | ✅ Completado |
| `AuthenticatedSessionController` (Breeze) — login y logout | v0.2.0 | ✅ Completado |

---

## Tabla de Requerimientos

| ID | Fase | Área | Descripción | Prioridad |
| :-- | :-- | :-- | :-- | :-- |
| REQ-01 | 1 | Routing | Renombrar `welcome.blade.php` → `landing.blade.php` y actualizar `web.php` | Alta |
| REQ-02 | 1 | Routing | Middleware `RedirectIfAuthenticated` ajustado: `/` siempre disponible para no autenticados | Alta |
| REQ-03 | 1 | Routing | Rutas de auth (`/login`, `/dashboard`, etc.) redirigen a `/` cuando el usuario no está autenticado y accede a rutas protegidas | Alta |
| REQ-04 | 1 | Auth | Toast de bienvenida post-login disparado desde `AuthenticatedSessionController@store` | Alta |
| REQ-05 | 2 | UI | Layout `layouts/public.blade.php` con `x-ui.theme-init`, favicons por tema y meta tags SEO | Alta |
| REQ-06 | 2 | UI | Navbar pública responsiva: logo wordmark ORVIAN, links de navegación y botón "Iniciar Sesión" | Alta |
| REQ-07 | 3 | UI | Sección Hero: headline institucional, subheadline, CTAs primario y secundario | Alta |
| REQ-08 | 3 | UI | Sección de métricas institucionales (stats estáticos visuales) | Media |
| REQ-09 | 3 | UI | Sección de módulos en grid de cards usando iconos SVG reales de `public/assets/icons/modules/` | Alta |
| REQ-10 | 3 | UI | Sección "¿Por qué ORVIAN?" con diferenciadores del sistema frente a soluciones genéricas | Alta |
| REQ-11 | 3 | UI | Sección CTA final con llamada a acción institucional | Media |
| REQ-12 | 3 | UI | Footer con wordmark, descripción breve y columnas de navegación | Media |
| REQ-13 | 4 | UX | Modo oscuro/claro funcional en toda la landing mediante clases `dark:` de Tailwind | Alta |
| REQ-14 | 4 | UX | Landing completamente responsiva: mobile, tablet y desktop | Alta |

---

## Fase 1 — Infraestructura de Routing y Auth
**Rama:** `feature/landing-routing`

### Objetivo

Establecer la base de enrutamiento público de ORVIAN. La ruta `/` debe ser la cara visible del sistema para usuarios no autenticados. Cualquier acceso a rutas protegidas sin sesión activa debe redirigir a `/` en lugar de `/login`. El logout también redirige a `/`.

---

### 1.1 — Renombrado de la Vista de Entrada

La vista `welcome.blade.php` es el placeholder inicial de Laravel y no tiene significado semántico en el contexto de ORVIAN.

**Acción:**

```bash
# Renombrar el archivo
mv resources/views/welcome.blade.php resources/views/landing.blade.php
```

**Actualizar `routes/web.php`:**

```php
// Antes
Route::get('/', function () {
    return view('welcome');
});

// Después
Route::get('/', function () {
    return view('landing');
})->name('landing');
```

> **Nota:** La vista `landing.blade.php` no usa ningún layout de autenticación ni de aplicación. Usará un layout público propio (`layouts/public.blade.php`) creado en la Fase 2.

---

### 1.2 — Redirección de Usuarios No Autenticados

El middleware `authenticate` de Laravel, por defecto, redirige a `/login` cuando detecta que el usuario no está autenticado. En ORVIAN, la ruta pública de entrada es `/`, no `/login`. Se actualiza el método `redirectTo` del middleware.

**Archivo:** `app/Http/Middleware/Authenticate.php`

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('landing');
    }
}
```

> **Comportamiento resultante:** Un usuario no autenticado que intente acceder a `/app/dashboard` o cualquier ruta protegida será redirigido a `/` en lugar de `/login`. El botón "Iniciar Sesión" en la navbar pública lleva a `/login`.

---

### 1.3 — Redirección Post-Logout

Verificar que `AuthenticatedSessionController@destroy` (Breeze) redirija a `/` después de cerrar sesión. Breeze por defecto redirige a `/`, pero se valida explícitamente.

**Archivo:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

```php
public function destroy(Request $request): RedirectResponse
{
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
}
```

> **Si ya está así:** Sin cambios necesarios. Documentar como validación.

---

### 1.4 — Toast de Bienvenida Post-Login

El sistema ya tiene toast de logout (mensaje flash al cerrar sesión). Se agrega el equivalente para el login exitoso.

**Archivo:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

En el método `store`, después de `Auth::attempt()` exitoso y antes del `redirect()`, se despacha el toast mediante flash de sesión, siguiendo el mismo patrón que usa `x-ui.toasts` para detectar claves de sesión:

```php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate();

    // Toast de bienvenida — detectado automáticamente por x-ui.toasts
    session()->flash('success', '¡Bienvenido de nuevo, ' . auth()->user()->name . '!');

    // Redirección diferenciada por tipo de usuario (lógica existente v0.4.1)
    return redirect()->intended(
        auth()->user()->school_id
            ? route('app.dashboard')
            : route('admin.hub')
    );
}
```

> **Cómo funciona:** `x-ui.toasts` ya detecta la clave de sesión `success` y la renderiza automáticamente con título `¡Éxito!` y duración 5000ms. No requiere cambios en el componente toast.

> **Personalización del título:** Si se desea un título diferente a `¡Éxito!` para el login, se puede extender el componente `x-ui.toasts` para detectar la clave `login_success` con título personalizado `¡Bienvenido!`. Evaluar según tiempo disponible.

---

## Fase 2 — Layout Público
**Rama:** `feature/landing-layout`

### Objetivo

Crear un layout independiente para todas las páginas públicas de ORVIAN. Este layout no hereda nada del sistema de autenticación ni de la aplicación interna, garantizando que la experiencia pública sea completamente autónoma.

---

### 2.1 — Archivo `layouts/public.blade.php`

**Ubicación:** `resources/views/layouts/public.blade.php`

**Responsabilidades:**
- Cargar `x-ui.theme-init` de forma síncrona para evitar flash de tema incorrecto
- Definir favicons por tema (claro/oscuro) usando los SVGs institucionales
- Incluir meta tags básicos de SEO
- Cargar Tailwind CSS, Alpine.js y los scripts del sistema
- Exponer `$slot` para el contenido de cada página pública
- Incluir `x-ui.toasts` para notificaciones globales
- Compartir `$appVersion` desde `View::share` (ya disponible desde v0.4.1)

**Estructura base del layout:**

```html
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ORVIAN — Sistema Integral de Gestión Educativa para instituciones dominicanas." />

    <title>ORVIAN — Gestión Educativa</title>

    {{-- Favicon por tema --}}
    <link rel="icon"
          href="{{ asset('img/logos/logo-icon-light.svg') }}"
          type="image/svg+xml"
          media="(prefers-color-scheme: light)">
    <link rel="icon"
          href="{{ asset('img/logos/logo-icon-dark.svg') }}"
          type="image/svg+xml"
          media="(prefers-color-scheme: dark)">

    {{-- Tema: script síncrono antes del CSS para evitar flash --}}
    <x-ui.theme-init />

    {{-- Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-[#080e1a] text-slate-800 dark:text-slate-100 antialiased">

    {{-- Toasts globales --}}
    <x-ui.toasts />

    {{-- Contenido de la página --}}
    {{ $slot }}

</body>
</html>
```

> **Sobre `x-ui.theme-init`:** El componente lee la preferencia de tema desde la base de datos si hay sesión activa, o desde la preferencia del sistema operativo si no. En la landing (usuario no autenticado), leerá `prefers-color-scheme` del sistema. Esto es el comportamiento correcto para una página pública.

---

## Fase 3 — Landing Page
**Rama:** `feature/landing-sections`

### Objetivo

Construir la Landing Page completa de ORVIAN como una sola página (`landing.blade.php`) que use el layout público. El diseño sigue la identidad visual del sistema: paleta naranja institucional (`orvian-orange`) sobre fondo oscuro/claro, tipografía limpia y componentes del sistema de diseño donde aplique.

La landing se compone de 7 secciones en orden de scroll:

1. **Navbar Pública**
2. **Hero**
3. **Stats (Métricas)**
4. **Módulos**
5. **¿Por qué ORVIAN?**
6. **CTA Final**
7. **Footer**

---

### 3.1 — Navbar Pública

**Componente:** Sección `<nav>` dentro de `landing.blade.php` (no es un componente separado para mantener la simplicidad).

**Elementos:**
- Wordmark "ORVIAN" en fuente Etna (ya disponible desde `public/fonts/etna-free-font.otf` y configurada en `tailwind.config.js`)
- Links de navegación: `Módulos`, `Soluciones`, `Instituciones`, `Precios` — hacen scroll hacia las secciones correspondientes mediante anclas `#`
- Botón "Iniciar Sesión" usando `<x-ui.button>` con `variant="secondary"` y `type="outline"`, con `href="{{ route('login') }}"`
- Botón "Solicitar Demo" usando `<x-ui.button>` con `variant="primary"`, con `href="{{ route('login') }}"` (mismo destino por ahora)
- En mobile: hamburger menu que muestra/oculta los links usando Alpine.js (`x-data`, `x-show`, `@click`)
- Posición `fixed top-0` con `backdrop-blur` y transición de fondo al hacer scroll (Alpine.js escuchando `window.scroll`)

**Comportamiento de scroll:**

```html
<nav
    x-data="{ scrolled: false, mobileOpen: false }"
    @scroll.window="scrolled = window.scrollY > 20"
    :class="scrolled
        ? 'bg-white/90 dark:bg-[#080e1a]/90 shadow-md backdrop-blur-sm border-b border-slate-200/50 dark:border-white/5'
        : 'bg-transparent'"
    class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
>
    ...
</nav>
```

---

### 3.2 — Sección Hero

**ID de ancla:** `#inicio`

**Contenido:**
- Badge superior usando `<x-ui.badge variant="primary" size="sm" :dot="false">` con texto "Sistema de Gestión Educativa"
- Headline principal en dos líneas: "Moderniza tu Centro con" + "ORVIAN" en color `orvian-orange`
- Subheadline descriptivo institucional enfocado en escuelas públicas dominicanas
- Dos CTAs:
  - Primario: `<x-ui.button variant="primary" :hoverEffect="true">Empieza Ahora</x-ui.button>`
  - Secundario: `<x-ui.button variant="secondary" type="outline">Ver Módulos</x-ui.button>` con scroll a `#modulos`
- Mockup visual del sistema (screenshot o imagen representativa del dashboard) con glow decorativo en `orvian-orange`

**Nota sobre el mockup:** Si no se dispone de un screenshot limpio del dashboard, se puede usar una ilustración SVG simple del sistema o un placeholder oscuro con el logo centrado. La prioridad es que el hero se vea profesional, no que el mockup sea perfecto.

---

### 3.3 — Sección Stats

**ID de ancla:** `#stats`

Stats institucionales estáticos en una fila de 4 columnas (2x2 en mobile):

| Valor | Etiqueta |
| :--- | :--- |
| `500+` | Instituciones |
| `1M+` | Estudiantes |
| `99.9%` | Uptime |
| `4.9/5` | Satisfacción |

> **Nota:** Estos valores son representativos para la demo. No están conectados a la base de datos.

---

### 3.4 — Sección Módulos

**ID de ancla:** `#modulos`

**Estructura:** Grid de cards 4 columnas (2x2 en tablet, 1 columna en mobile).

**Módulos a mostrar** (usando iconos SVG reales desde `public/assets/icons/modules/`):

| Icono SVG | Nombre | Descripción |
| :--- | :--- | :--- |
| `core.svg` | Core | El corazón de tu base de datos centralizada y segura |
| `asistencia.svg` | Asistencia | Registro biométrico y digital en tiempo real para alumnos y staff |
| `academico.svg` | Académico | Gestión de currículos, horarios y planes de estudio avanzados |
| `calificaciones.svg` | Calificaciones | Boletines automáticos y analítica de rendimiento estudiantil |
| `comunicaciones.svg` | Comunicaciones | Alertas automáticas a tutores vía WhatsApp e integración con Chatwoot |
| `reportes.svg` | Reportes | Dashboards, exportaciones y resumen ejecutivo de todos los módulos |

**Cómo usar los iconos SVG en la landing:**

Los iconos ya existen en `public/assets/icons/modules/`. En la landing (que no es un componente Blade con acceso a `x-ui.module-icon` de la misma forma), se pueden incluir de dos maneras:

```html
{{-- Opción A: Usando el componente existente --}}
<x-ui.module-icon module="asistencia" class="w-8 h-8" />

{{-- Opción B: Inline directo (si el componente no aplica fuera del contexto app) --}}
<img src="{{ asset('assets/icons/modules/asistencia.svg') }}" class="w-8 h-8" alt="Módulo de Asistencia" />
```

> **Preferir Opción A** si el componente `x-ui.module-icon` no tiene dependencias de contexto de usuario autenticado. Validar durante implementación.

---

### 3.5 — Sección ¿Por Qué ORVIAN?

**ID de ancla:** `#por-que`

Esta sección reemplaza la sección de Testimonios del diseño de referencia. En lugar de citas de usuarios, muestra los diferenciadores reales del sistema frente a soluciones genéricas o procesos manuales.

**Estructura:** Grid de 3 columnas (1 columna en mobile) con cards de diferenciadores.

**Diferenciadores a comunicar:**

| Ícono | Título | Descripción |
| :--- | :--- | :--- |
| 🏛️ | Hecho para República Dominicana | Diseñado para el currículo MINERD, con geografía educativa dominicana integrada desde el núcleo |
| 🔒 | Multi-tenant y Seguro | Cada centro opera en su propio espacio aislado. Los datos de un centro nunca son accesibles por otro |
| 📶 | Sin Dependencia del Celular | El sistema funciona desde cualquier navegador en la red del plantel. No requiere que alumnos ni docentes tengan smartphones |
| 🧩 | Modular y Progresivo | Activa solo los módulos que necesitas hoy. Escala según el crecimiento de tu institución |
| 🤖 | Biometría Opcional | El reconocimiento facial nunca es obligatorio. Siempre hay una alternativa física (QR en brazalete) disponible |
| ⚡ | Tiempo Real | Dashboards operativos con datos en vivo. El Director sabe en todo momento qué está pasando en su plantel |

> **Nota de implementación:** Los íconos se pueden representar con emojis Unicode, SVGs simples inline, o Heroicons. Usar Heroicons es preferible para mantener consistencia con el sistema.

---

### 3.6 — Sección CTA Final

**ID de ancla:** `#contacto`

Sección de cierre con fondo diferenciado (gradiente oscuro o color de acento sutil).

**Contenido:**
- Headline: "¿Listo para modernizar tu centro?"
- Subheadline: Frase institucional de motivación
- Dos botones:
  - `<x-ui.button variant="primary" :hoverEffect="true">Solicitar Demo Gratuita</x-ui.button>`
  - `<x-ui.button variant="secondary" type="outline">Hablar con un Experto</x-ui.button>`

Ambos botones apuntan a `{{ route('login') }}` en esta versión, ya que no hay formulario de contacto ni calendario de demos implementado.

---

### 3.7 — Footer

**Estructura:** Fila con 4 columnas (colapsa a 2x2 en tablet y 1 columna en mobile).

**Columnas:**

| Columna | Contenido |
| :--- | :--- |
| Marca | Wordmark ORVIAN + descripción de una línea del sistema |
| Plataforma | Características, Seguridad, API |
| Empresa | Sobre Nosotros, Blog, Carreras |
| Soporte | Contacto, Documentación, Estado |

**Línea inferior:** Copyright `© {{ date('Y') }} ORVIAN. Todos los derechos reservados.` + badge de versión usando `$appVersion`.

> **Todos los links del footer** apuntan a `#` en esta versión. Son elementos visuales de completitud institucional, no páginas reales.

---

## Fase 4 — Responsividad y Modo Oscuro
**Rama:** `feature/landing-responsive`

### Objetivo

Validar que la landing se vea correctamente en todos los breakpoints de Tailwind y en ambos modos de tema, sin trabajo adicional de CSS personalizado.

### 4.1 — Breakpoints a Validar

| Breakpoint | Ancho | Cambios esperados |
| :--- | :--- | :--- |
| `sm` | 640px | Grid de módulos pasa a 2 columnas |
| `md` | 768px | Navbar muestra links. Grid de módulos a 2 columnas |
| `lg` | 1024px | Grid de módulos a 4 columnas. Hero con layout de dos columnas |
| `xl` | 1280px | Márgenes y padding finales |

### 4.2 — Modo Oscuro

Todas las secciones deben usar clases `dark:` de Tailwind. El componente `x-ui.theme-init` garantiza que la clase `dark` se aplique al `<html>` antes de que el CSS se renderice, eliminando el flash.

**Paleta de referencia:**

| Elemento | Claro | Oscuro |
| :--- | :--- | :--- |
| Fondo body | `bg-white` | `dark:bg-[#080e1a]` |
| Fondo cards | `bg-slate-50` | `dark:bg-slate-800/50` |
| Bordes | `border-slate-200` | `dark:border-white/10` |
| Texto primario | `text-slate-900` | `dark:text-slate-100` |
| Texto secundario | `text-slate-500` | `dark:text-slate-400` |
| Acento naranja | `text-orvian-orange` | `text-orvian-orange` (igual en ambos) |

---

## Archivos a Crear / Modificar

| Archivo | Acción | Fase |
| :--- | :--- | :--- |
| `resources/views/welcome.blade.php` | Renombrar a `landing.blade.php` | 1 |
| `routes/web.php` | Actualizar ruta `/` para retornar `landing` | 1 |
| `app/Http/Middleware/Authenticate.php` | Actualizar `redirectTo` a `route('landing')` | 1 |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Agregar flash `success` post-login y validar redirect post-logout | 1 |
| `resources/views/layouts/public.blade.php` | Crear layout público con `x-ui.theme-init`, favicons y toasts | 2 |
| `resources/views/landing.blade.php` | Crear Landing Page completa usando `layouts.public` | 3 |

---

## Notas de Implementación

- **Fuente Etna:** El wordmark ORVIAN en la navbar y el footer usa la fuente Etna (`font-etna`), ya configurada en `tailwind.config.js` desde v0.4.1. No requiere trabajo adicional.
- **Iconos de módulos:** Los SVGs en `public/assets/icons/modules/` pueden tener estilos de color fijos. Si no responden a `dark:` de Tailwind, usar `filter: invert()` en modo oscuro o reemplazar con Heroicons equivalentes para los íconos de la landing.
- **Sin dependencias externas nuevas:** Esta versión no agrega ningún paquete npm ni composer. Todo se construye con las herramientas ya presentes en el stack (Tailwind, Alpine.js, Blade, componentes `x-ui.*`).
- **Performance:** La landing no hace ninguna consulta a la base de datos (excepto la que hace `x-ui.theme-init` para leer preferencias, que ya está optimizada con caché en v0.3.0 para usuarios no autenticados retorna directamente `prefers-color-scheme`).
- **SEO básico:** El layout público incluye `<meta name="description">` y `<title>` institucionales. No se implementa Open Graph ni Schema.org en esta versión.
- **`$appVersion` en footer:** La variable ya está disponible en todas las vistas via `View::share` desde v0.4.1. El footer puede mostrarla como `v{{ $appVersion }}`.