# Componente Button (`x-ui.button`)

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)

El componente `x-ui.button` centraliza toda la lógica visual de los botones en ORVIAN. Soporta variantes de color, estilos visuales, tamaños, iconos en ambas posiciones y un modo de icono exclusivo (sin texto) con dimensiones cuadradas automáticas.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [API del Componente](#api-del-componente)
- [Variantes de Color](#variantes-de-color)
- [Tipos de Estilo](#tipos-de-estilo)
- [Tamaños](#tamaños)
- [Uso de Iconos](#uso-de-iconos)
- [Props Adicionales](#props-adicionales)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            └── Button.php                    # Lógica y generación de clases CSS
resources/
└── views/
    └── components/
        └── ui/
            └── button.blade.php              # Plantilla del componente
```

---

## API del Componente

| Prop | Tipo | Default | Opciones |
|---|---|---|---|
| `variant` | `string` | `primary` | `primary`, `secondary`, `success`, `warning`, `info`, `error`, `link` |
| `type` | `string` | `solid` | `solid`, `outline` |
| `size` | `string` | `md` | `sm`, `md`, `lg`, `xl` |
| `iconLeft` | `string\|null` | `null` | Nombre de Heroicon (ej: `heroicon-s-plus`) |
| `iconRight` | `string\|null` | `null` | Nombre de Heroicon |
| `icon` | `string\|null` | `null` | Heroicon para modo icono exclusivo |
| `disabled` | `bool` | `false` | — |
| `fullWidth` | `bool` | `false` | — |
| `hoverEffect` | `bool` | `false` | — |

---

## Variantes de Color

Cada variante mapea a un token de color del sistema de diseño ORVIAN.

| Variante | Token | Uso recomendado |
|---|---|---|
| `primary` | `orvian-orange` | Acción principal, CTA |
| `secondary` | `orvian-navy` | Acción secundaria o neutral |
| `success` | `state-success` | Confirmación, guardado |
| `warning` | `state-warning` | Advertencia, precaución |
| `info` | `state-info` | Información contextual |
| `error` | `state-error` | Eliminación, acción destructiva |
| `link` | `orvian-navy` | Acción inline sin peso visual |

---

## Tipos de Estilo

El prop `type` controla la apariencia visual del botón independientemente del color.

**`solid`** — Fondo relleno con el color de la variante. Es el estilo por defecto y el de mayor peso visual. El hover usa `hover:opacity-90` en lugar de definir un tono específico, lo que garantiza consistencia entre todas las variantes.

**`outline`** — Borde visible con fondo semitransparente. Para los estados (`success`, `warning`, `info`, `error`) el fondo incluye una opacidad base (`/10`) que se intensifica en hover (`/20`). Las variantes `primary` y `secondary` parten desde fondo completamente transparente.

> [!NOTE]
> La variante `link` ignora parcialmente `solid` vs `outline`. En `solid` se renderiza como texto plano sin padding ni fondo. En `outline` agrega un borde inferior a modo de subrayado y elimina el `border-radius`. En ambos casos, el prop `size` no tiene efecto sobre el padding.

---

## Tamaños

| Size | Padding (con texto) | Dimensión (icono exclusivo) | Font size |
|---|---|---|---|
| `sm` | `px-4 py-2` | `w-8 h-8` | `text-xs` |
| `md` | `px-6 py-3` | `w-11 h-11` | `text-sm` |
| `lg` | `px-8 py-4` | `w-14 h-14` | `text-base` |
| `xl` | `px-10 py-5` | `w-16 h-16` | `text-lg` |

Cuando el componente detecta que no hay texto en el `$slot` y existe algún prop de icono (`icon`, `iconLeft`, o `iconRight`), cambia automáticamente a dimensiones cuadradas. No es necesario declarar ningún modo especial — el cambio es transparente.

---

## Uso de Iconos

El componente distingue dos modos de icono según si el slot tiene contenido o no.

**Botón con texto e icono** — Se usan `iconLeft` o `iconRight` junto con contenido en el slot. El icono se renderiza al lado del texto con el tamaño apropiado al `size` del botón.

**Botón de icono exclusivo** — Se usa el prop `icon` (o `iconLeft`/`iconRight`) con el slot vacío. El componente detecta que no hay texto y aplica dimensiones cuadradas en lugar del padding rectangular.

> [!IMPORTANT]
> La detección de modo icono se basa en `empty(trim($slot->toHtml()))`. Si el slot contiene espacios en blanco o caracteres invisibles, el componente no activará el modo cuadrado. Asegúrate de que el tag esté cerrado sin contenido al usar `icon`.

Los tamaños de los iconos escalan con `size`:

| Size | Icono con texto | Icono exclusivo |
|---|---|---|
| `sm` | `w-4 h-4` | `w-4 h-4` |
| `md` | `w-5 h-5` | `w-5 h-5` |
| `lg` | `w-5 h-5` | `w-5 h-5` |
| `xl` | `w-5 h-5` | `w-7 h-7` |

---

## Props Adicionales

**`disabled`** — Agrega el atributo `disabled` al `<button>` y aplica `opacity-60 cursor-not-allowed`. También desactiva el `hoverEffect` automáticamente para evitar micro-interacciones en estado inactivo.

**`fullWidth`** — Agrega `w-full` para que el botón ocupe el ancho completo de su contenedor. No tiene efecto en modo icono exclusivo.

**`hoverEffect`** — Activa una micro-interacción de escala al hacer hover (`hover:scale-[1.03]`) y al hacer clic (`active:scale-[0.98]`), junto con una sombra `shadow-lg shadow-orvian-orange/20`. Recomendado para CTAs prominentes o el botón de submit de un formulario.

---

## Ejemplos de Uso

**CTA principal con micro-interacción:**
```blade
<x-ui.button variant="primary" :hoverEffect="true" iconLeft="heroicon-s-plus">
    Nuevo Registro
</x-ui.button>
```

**Acción destructiva outline pequeña:**
```blade
<x-ui.button variant="error" type="outline" size="sm" iconRight="heroicon-s-trash">
    Eliminar
</x-ui.button>
```

**Confirmación a ancho completo:**
```blade
<x-ui.button variant="success" :fullWidth="true" :hoverEffect="true">
    Guardar Cambios
</x-ui.button>
```

**Acción secundaria con flecha:**
```blade
<x-ui.button variant="secondary" iconRight="heroicon-s-arrow-right">
    Continuar
</x-ui.button>
```

**Enlace inline con subrayado:**
```blade
<x-ui.button variant="link" type="outline">
    Ver detalles del proceso
</x-ui.button>
```

**Botón de icono exclusivo (dimensiones cuadradas automáticas):**
```blade
{{-- md por defecto → w-11 h-11 --}}
<x-ui.button variant="primary" icon="heroicon-s-pencil" />

{{-- sm → w-8 h-8 --}}
<x-ui.button variant="error" type="outline" size="sm" icon="heroicon-s-trash" />

{{-- lg outline --}}
<x-ui.button variant="secondary" type="outline" size="lg" icon="heroicon-s-cog-6-tooth" />
```

**Botón deshabilitado:**
```blade
<x-ui.button variant="primary" :disabled="true">
    Procesando...
</x-ui.button>
```

**Con atributos Livewire** — el componente usa `$attributes->merge()`, cualquier atributo adicional se pasa directamente al `<button>`:
```blade
<x-ui.button variant="primary" wire:click="guardar" wire:loading.attr="disabled">
    Guardar
</x-ui.button>
```

---

## Notas Adicionales

- El `type` HTML del `<button>` es `button` por defecto. Para usarlo como submit dentro de un formulario pásalo explícitamente con `type="submit"`. Laravel resuelve correctamente la precedencia entre el prop de estilo (`type`) y el atributo HTML a través de `$attributes->merge()`.
- La variante `link` no debe combinarse con `fullWidth` ni `hoverEffect`, ya que carece de padding y dimensiones fijas.
- Todos los tokens de color (`orvian-orange`, `orvian-navy`, `state-success`, etc.) deben estar definidos en `tailwind.config.js` para que las clases sean generadas correctamente y no sean eliminadas en el build de producción.