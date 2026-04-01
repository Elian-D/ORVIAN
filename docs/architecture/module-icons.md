# Sistema de Iconos de Módulos

![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)

ORVIAN usa un sistema de iconos SVG propios para identificar cada módulo del panel de escuela. Los iconos tienen una identidad visual consistente —azul navy (`#0d47a1`) y naranja (`#f78904`)— y se sirven como archivos estáticos individuales desde `public/assets/icons/modules/`.

---

## Tabla de Contenido

- [Filosofía del sistema](#filosofía-del-sistema)
- [Estructura de archivos](#estructura-de-archivos)
- [Especificación de los SVG](#especificación-de-los-svg)
- [Componentes disponibles](#componentes-disponibles)
  - [x-ui.module-icon](#x-uimodule-icon)
  - [x-ui.app-tile](#x-uiapp-tile)
- [Guía de uso por contexto](#guía-de-uso-por-contexto)
- [Cómo agregar un módulo nuevo](#cómo-agregar-un-módulo-nuevo)
- [Notas adicionales](#notas-adicionales)

---

## Filosofía del sistema

**Un archivo por módulo.** A diferencia de los iconos de UI (heroicons, que son monocromáticos y genéricos), los iconos de módulo son multicolor, tienen identidad de marca y no se reutilizan entre módulos. Tenerlos como archivos individuales permite reemplazar uno sin tocar los demás, y el navegador solo descarga los que el usuario tiene acceso.

**El icono no tiene contenedor propio.** `x-ui.module-icon` renderiza únicamente el `<img>`. El fondo, el padding y el `border-radius` los decide el componente que lo consume — el navbar, el `app-tile`, o cualquier otro contexto futuro. Esto evita tener que sobreescribir estilos del contenedor en cada contexto.

---

## Estructura de archivos

```plaintext
public/
└── assets/
    └── icons/
        └── modules/
            ├── administracion.svg
            ├── asistencia.svg
            ├── conversaciones.svg
            ├── academico.svg
            ├── notas.svg
            ├── classroom.svg
            ├── horarios.svg
            ├── reportes.svg
            └── web.svg

app/
└── View/
    └── Components/
        └── Ui/
            └── ModuleIcon.php

resources/
└── views/
    └── components/
        └── ui/
            ├── module-icon.blade.php
            └── app-tile.blade.php
```

---

## Especificación de los SVG

Para que los iconos escalen correctamente con Tailwind y no rompan el layout, cada archivo SVG debe cumplir estas tres reglas:

**1. Solo `viewBox`, sin `width` ni `height` fijos.**
```xml
<!-- ✓ Correcto -->
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1248 832">

<!-- ❌ Incorrecto — rompe el escalado con clases Tailwind -->
<svg xmlns="http://www.w3.org/2000/svg" width="1248" height="832" viewBox="0 0 1248 832">
```

**2. `fill` como atributo directo, no en `style="..."`.**
```xml
<!-- ✓ Correcto -->
<path fill="#0d47a1" fill-rule="evenodd" d="..." />

<!-- ❌ Incorrecto — algunos sanitizadores eliminan estilos inline -->
<path style="fill:#0d47a1;" d="..." />
```

**3. Sin `<style>` interno ni clases CSS.**
Los iconos tienen colores fijos (`#0d47a1` azul navy y `#f78904` naranja). No deben cambiar con el tema oscuro — son parte de la identidad de marca del módulo, no elementos de UI adaptativos.

**Ejemplo de SVG correcto:**
```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1248 832">
  <path fill="#0d47a1" fill-rule="evenodd" d="M606 251.948..." />
  <path fill="#f78904" fill-rule="evenodd" d="M617 337.886..." />
</svg>
```

---

## Componentes disponibles

### x-ui.module-icon

Renderiza el `<img>` del SVG. Sin contenedor, sin fondo.

**Clase PHP:** `app/View/Components/Ui/ModuleIcon.php`
**Vista:** `resources/views/components/ui/module-icon.blade.php`

#### Props

| Prop | Tipo | Requerido | Descripción |
|---|---|---|---|
| `name` | `string` | ✓ | Nombre del archivo SVG sin extensión |

Además acepta cualquier atributo HTML via `$attributes->merge()`. La clase más importante es el tamaño — Tailwind controla el `width` y `height` mediante `w-*` y `h-*`.

#### Ejemplos

```html
{{-- En el navbar: tamaño compacto --}}
<x-ui.module-icon name="administracion" class="w-5 h-5" />

{{-- En el app-tile: llena su contenedor --}}
<x-ui.module-icon name="asistencia" class="w-10 h-10" />

{{-- En un demo o hero: tamaño grande --}}
<x-ui.module-icon name="reportes" class="w-16 h-16" />
```

#### Módulos disponibles

| Nombre | Archivo |
|---|---|
| `administracion` | Usuarios, roles y configuración del centro |
| `asistencia` | Control de asistencia diaria |
| `conversaciones` | Chat y mensajería |
| `academico` | Secciones y materias |
| `notas` | Calificaciones y evaluaciones |
| `classroom` | Aula virtual |
| `horarios` | Planificación de horarios |
| `reportes` | Analítica e informes |
| `web` | Página web del centro |

---

### x-ui.app-tile

Tile de módulo estilo Odoo: icono cuadrado con hover + título + subtítulo debajo. Sin card envolvente.

**Tipo:** Componente anónimo (`@props`) — sin clase PHP.
**Vista:** `resources/views/components/ui/app-tile.blade.php`

#### Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `module` | `string\|null` | `null` | Nombre del icono SVG (ej: `'administracion'`) |
| `icon` | `string\|null` | `null` | Heroicon como fallback cuando no hay SVG |
| `title` | `string` | — | Nombre visible del módulo |
| `subtitle` | `string\|null` | `null` | Texto secundario bajo el título |
| `color` | `string` | `'bg-orvian-navy'` | Clase de fondo para el modo heroicon |
| `accent` | `string\|null` | `null` | Color hex para `box-shadow` en modo heroicon |
| `url` | `string` | `'#'` | Ruta de destino |
| `badge` | `int\|null` | `null` | Número de notificaciones sobre el icono |
| `comingSoon` | `bool` | `false` | Deshabilita navegación y muestra "Pronto" |

> [!NOTE]
> `module` y `icon` son mutuamente excluyentes. Si se pasan los dos, `module` tiene prioridad. Para módulos del sistema siempre usar `module`. `icon` es el fallback para casos temporales donde no existe aún el SVG.

#### Comportamiento de `comingSoon`

Cuando `comingSoon="true"`:
- La `<a>` no navega (`pointer-events-none`)
- Opacidad reducida a 50% (`opacity-50`)
- Badge "Pronto" en esquina superior derecha via `x-ui.badge variant="slate"`
- Sin `wire:navigate`

---

## Guía de uso por contexto

### Dashboard (grid de módulos)

```html
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">

    {{-- Módulo activo --}}
    <div class="tile-animate" style="animation-delay: 0.04s;">
        <x-ui.app-tile
            module="administracion"
            title="Administración"
            subtitle="Sistema"
            url="{{ route('app.users.index') }}" />
    </div>

    {{-- Módulo próximamente --}}
    <div class="tile-animate" style="animation-delay: 0.08s;">
        <x-ui.app-tile
            module="asistencia"
            title="Asistencia"
            subtitle="Control"
            comingSoon="true" />
    </div>

    {{-- Con badge de notificaciones --}}
    <div class="tile-animate" style="animation-delay: 0.12s;">
        <x-ui.app-tile
            module="conversaciones"
            title="Conversaciones"
            subtitle="Chat"
            :badge="5"
            url="{{ route('app.conversations.index') }}" />
    </div>

</div>
```

> [!TIP]
> La clase `tile-animate` con `animation-delay` en cascada viene definida en `layouts/app.blade.php`. Los tiles aparecen en secuencia con una animación de entrada suave al cargar la página.

### Navbar de módulo (icono compacto)

```html
{{-- El navbar usa x-ui.module-icon directamente, sin app-tile --}}
<x-ui.module-icon name="administracion" class="w-5 h-5 flex-shrink-0" />
```

El `app-tile` tiene estructura vertical (icono + texto debajo) y está diseñado para grids. En el navbar solo se necesita el icono sin texto, por eso se usa `x-ui.module-icon` directamente.

---

## Cómo agregar un módulo nuevo

**Paso 1.** Exportar el SVG cumpliendo la especificación: solo `viewBox`, `fill` como atributo directo, sin `width`/`height` fijos.

**Paso 2.** Colocarlo en `public/assets/icons/modules/{nombre-modulo}.svg`.

**Paso 3.** Usarlo en el dashboard:
```html
<x-ui.app-tile
    module="nuevo-modulo"
    title="Nuevo Módulo"
    subtitle="Descripción"
    url="{{ route('app.nuevo-modulo.index') }}" />
```

**Paso 4.** Agregar la entrada a la tabla de módulos disponibles en este documento.

No se requiere ningún cambio en las clases PHP ni en los componentes existentes.

---

## Notas adicionales

- Los SVG se sirven desde `public/` — no pasan por el compilador de Vite ni por Blade. Son archivos estáticos puros.
- Al ser `<img>`, el navegador los cachea automáticamente. No hay penalización de rendimiento por tener 9 archivos separados en lugar de un sprite.
- No agregar `width` y `height` fijos al `<img>` en `module-icon.blade.php` — ese control lo hace Tailwind desde el componente que lo consume.
- Los colores del SVG (`#0d47a1` y `#f78904`) son fijos por diseño. No deben variar con el modo oscuro. Si se necesita una variante monocromática para un contexto específico (ej: notificaciones), se crea un SVG separado.