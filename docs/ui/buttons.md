# Componente Button (`x-ui.button`)

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)
![Livewire](https://img.shields.io/badge/Livewire-Compatible-orange)


El componente `x-ui.button` es el elemento de acción central de ORVIAN. Centraliza toda la lógica visual y es **polimórfico**: detecta automáticamente si debe renderizarse como `<button>` o como `<a>` según el contexto. Soporta variantes de color del sistema, colores hexadecimales con cálculo de contraste automático, integración nativa con estados de carga de Livewire y un modo de icono exclusivo altamente accesible.

-----

## Tabla de Contenido

  - [Estructura de Archivos](https://www.google.com/search?q=%23estructura-de-archivos)
  - [API del Componente](https://www.google.com/search?q=%23api-del-componente)
  - [Variantes y Colores Hexadecimales](https://www.google.com/search?q=%23variantes-y-colores-hexadecimales)
  - [Tipos de Estilo](https://www.google.com/search?q=%23tipos-de-estilo)
  - [Polimorfismo (Tag Dinámico)](https://www.google.com/search?q=%23polimorfismo-tag-din%C3%A1mico)
  - [Tamaños](https://www.google.com/search?q=%23tama%C3%B1os)
  - [Uso de Iconos y Accesibilidad](https://www.google.com/search?q=%23uso-de-iconos-y-accesibilidad)
  - [Estados de Carga (Livewire)](https://www.google.com/search?q=%23estados-de-carga-livewire)
  - [Props Adicionales](https://www.google.com/search?q=%23props-adicionales)
  - [Ejemplos de Uso](https://www.google.com/search?q=%23ejemplos-de-uso)
  - [Notas Adicionales](https://www.google.com/search?q=%23notas-adicionales)

-----

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            └── Button.php                    # Lógica, contraste YIQ y polimorfismo
resources/
└── views/
    └── components/
        └── ui/
            └── button.blade.php              # Plantilla del componente
```

-----

## API del Componente

| Prop | Tipo | Default | Opciones / Descripción |
|---|---|---|---|
| `variant` | `string` | `primary` | `primary`, `secondary`, `success`, `warning`, `info`, `error`, `link` |
| `type` | `string` | `solid` | `solid`, `outline`, `ghost` |
| `size` | `string` | `md` | `sm`, `md`, `lg`, `xl` |
| `iconLeft` | `string\|null` | `null` | Nombre de Heroicon (ej: `heroicon-s-plus`) |
| `iconRight` | `string\|null` | `null` | Nombre de Heroicon |
| `icon` | `string\|null` | `null` | Heroicon para modo icono exclusivo |
| `hex` | `string\|null` | `null` | Color hexadecimal arbitrario (ej: `#7C3AED`) |
| `href` | `string\|null` | `null` | Si está presente, renderiza un `<a>` en lugar de `<button>` |
| `disabled` | `bool` | `false` | Deshabilita el elemento |
| `fullWidth` | `bool` | `false` | Agrega `w-full` |
| `hoverEffect` | `bool` | `false` | Activa micro-interacción de escala en hover/active |

-----

## Variantes y Colores Hexadecimales

El componente puede usar los tokens del sistema mediante el prop `variant`, o colores dinámicos mediante el prop `hex`.

### Variantes del Sistema (`variant`)

| Variante | Token | Uso recomendado |
|---|---|---|
| `primary` | `orvian-orange` | Acción principal, CTA |
| `secondary` | `orvian-navy` | Acción secundaria o neutral |
| `success` | `state-success` | Confirmación, guardado |
| `warning` | `state-warning` | Advertencia, precaución |
| `info` | `state-info` | Información contextual |
| `error` | `state-error` | Eliminación, acción destructiva |
| `link` | `orvian-navy` | Acción inline sin peso visual |

### Soporte Hexadecimal (`hex`)

Para colores arbitrarios (roles personalizados, categorías, etc.). El componente calcula automáticamente la luminancia perceptual (YIQ del W3C) del color de fondo para asegurar la legibilidad del texto.

  * **YIQ ≥ 128 (Claro):** El texto será oscuro (`#1e293b`).
  * **YIQ \< 128 (Oscuro):** El texto será blanco (`#ffffff`).

<!-- end list -->

```blade
{{-- Fondo violeta oscuro (YIQ 67) → Texto blanco --}}
<x-ui.button hex="#7C3AED">Director/a</x-ui.button>

{{-- Fondo blanco verdoso (YIQ 242) → Texto oscuro --}}
<x-ui.button hex="#F0FDF4">Activo</x-ui.button>
```

-----

## Tipos de Estilo

El prop `type` controla la apariencia visual del botón independientemente del color.

  * **`solid` (Default):** Fondo relleno. Mayor peso visual. Usa `hover:opacity-90` para consistencia.
  * **`outline`:** Borde visible con fondo semitransparente. Parten de una opacidad base `/10` que se intensifica en hover `/20`.
  * **`ghost` (Nuevo):** Sin fondo ni borde base. El fondo aparece solo en hover con baja opacidad. Ideal para barras de herramientas, filas de tabla y acciones secundarias para evitar competir con el contenido principal.

> [\!NOTE]
> La variante `link` ignora parcialmente los estilos. En `solid` se renderiza como texto plano; en `outline` agrega un borde inferior (subrayado). El prop `size` no afecta su padding.

-----

## Polimorfismo (Tag Dinámico)

Al pasar el prop `href`, el componente renderiza automáticamente una etiqueta `<a>` manteniendo exactamente el mismo aspecto visual y comportamiento. Útil para navegación semántica.

```blade
{{-- <button> (default, acciona un método) --}}
<x-ui.button variant="primary" wire:click="save">Guardar</x-ui.button>

{{-- <a> (con href, navegación) --}}
<x-ui.button href="{{ route('app.dashboard') }}" variant="secondary">
    Volver al Hub
</x-ui.button>
```

-----

## Tamaños

| Size | Padding (con texto) | Dimensión (icono exclusivo) | Font size | Icono |
|---|---|---|---|---|
| `sm` | `px-4 py-2` | `w-8 h-8` | `text-xs` | `w-4 h-4` |
| `md` | `px-6 py-3` | `w-11 h-11` | `text-sm` | `w-5 h-5` |
| `lg` | `px-8 py-4` | `w-14 h-14` | `text-base` | `w-5 h-5` |
| `xl` | `px-10 py-5` | `w-16 h-16` | `text-lg` | `w-7 h-7` |

-----

## Uso de Iconos y Accesibilidad

El componente distingue dos modos según si el `$slot` tiene contenido:

**1. Botón con texto e icono:**
Usa `iconLeft` o `iconRight`. El icono acompaña al texto.

**2. Modo "Solo Icono" (Exclusivo):**
Usa `icon` (o deja el slot vacío con `iconLeft`/`iconRight`). El componente asume dimensiones cuadradas perfectas automáticamente.

> [\!IMPORTANT]
> **Accesibilidad (a11y):** Cuando el componente detecta el modo "solo icono", intentará inferir un `aria-label` básico del nombre del icono como fallback (ej: "trash" para `heroicon-s-trash`). Sin embargo, **se recomienda pasar un `aria-label` explícito** para lectores de pantalla.

```blade
{{-- aria-label inferido: "trash" --}}
<x-ui.button variant="error" icon="heroicon-s-trash" size="sm" />

{{-- aria-label explícito (Recomendado ✔️) --}}
<x-ui.button variant="error" icon="heroicon-s-trash" size="sm" aria-label="Eliminar usuario" />
```

-----

## Estados de Carga (Livewire)

El componente añade de forma nativa soporte para Livewire. Al disparar una acción, agrega automáticamente `wire:loading.class="opacity-60 pointer-events-none"`.

Solo necesitas definir el `wire:target` y gestionar el reemplazo del texto si lo deseas:

```blade
<x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
    <span wire:loading.remove wire:target="save">Guardar cambios</span>
    <span wire:loading wire:target="save">Guardando...</span>
</x-ui.button>
```

-----

## Props Adicionales

  * **`disabled`:** Agrega el atributo HTML `disabled`, aplica `opacity-60 cursor-not-allowed` y desactiva el `hoverEffect`.
  * **`fullWidth`:** Agrega `w-full` (ignorado en modo solo icono).
  * **`hoverEffect`:** Activa una micro-interacción de escala (`hover:scale-[1.03]`, `active:scale-[0.98]`) y sombra sutil. Recomendado para CTAs principales.

-----

## Ejemplos de Uso

**Ghost para barras de herramientas:**

```blade
<x-ui.button type="ghost" variant="secondary" iconLeft="heroicon-o-pencil-square">
    Editar
</x-ui.button>
```

**CTA con micro-interacción:**

```blade
<x-ui.button variant="primary" :hoverEffect="true" iconLeft="heroicon-s-plus">
    Nuevo Registro
</x-ui.button>
```

**Botón de icono exclusivo Outline:**

```blade
<x-ui.button variant="secondary" type="outline" size="lg" icon="heroicon-s-cog-6-tooth" aria-label="Ajustes" />
```

**Enlace tipo botón (Polimorfismo):**

```blade
<x-ui.button href="/usuarios/crear" variant="primary" iconLeft="heroicon-s-user-plus">
    Crear Usuario
</x-ui.button>
```

-----

## Notas Adicionales

  * El atributo `type="button"` HTML se renderiza por defecto cuando no es un enlace. Para formularios, pasa explícitamente `type="submit"`. Laravel gestionará correctamente la diferencia entre tu prop `$type` (solid/outline/ghost) y el atributo HTML mediante `$attributes->merge()`.
  * La variante `link` no debe combinarse con `fullWidth` ni `hoverEffect`.
  * Los tokens del sistema de diseño (`orvian-orange`, `state-success`, etc.) deben estar declarados en tu `tailwind.config.js` (`safelist` o usados en la base de código) para evitar que PurgeCSS los elimine en producción.