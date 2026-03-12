# Componente Badge (`x-ui.badge`)

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)

El componente `x-ui.badge` centraliza la representación visual de etiquetas de estado en ORVIAN. Soporta variantes de color semánticas, un indicador de punto opcional y dos tamaños. Diseñado para mostrar estados, categorías o clasificaciones de forma compacta y consistente con el sistema de diseño global.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [API del Componente](#api-del-componente)
- [Variantes de Color](#variantes-de-color)
- [Tamaños](#tamaños)
- [El Punto Indicador](#el-punto-indicador)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            └── Badge.php                     # Lógica y generación de clases CSS
resources/
└── views/
    └── components/
        └── ui/
            └── badge.blade.php               # Plantilla del componente
```

---

## API del Componente

| Prop | Tipo | Default | Opciones |
|---|---|---|---|
| `variant` | `string` | `info` | `primary`, `success`, `warning`, `error`, `info`, `slate` |
| `dot` | `bool` | `true` | — |
| `size` | `string` | `md` | `sm`, `md` |

---

## Variantes de Color

Cada variante usa el mismo token de color del sistema de diseño ORVIAN, aplicado en tres partes del badge: fondo semitransparente, texto y borde.

| Variante | Token | Uso recomendado |
|---|---|---|
| `primary` | `orvian-orange` | Destacado, novedad, acción requerida |
| `success` | `state-success` | Activo, aprobado, completado |
| `warning` | `state-warning` | Pendiente, en revisión, precaución |
| `error` | `state-error` | Inactivo, rechazado, bloqueado |
| `info` | `state-info` | Informativo, en proceso, neutral |
| `slate` | `slate-500` | Deshabilitado, archivado, sin clasificar |

Todas las variantes aplican un fondo con opacidad `/10` en modo claro y `/20` en modo oscuro, con el color sólido en texto y borde `/20`. Esto garantiza legibilidad en ambos modos sin necesidad de configuración adicional.

---

## Tamaños

| Size | Padding | Gap | Font size |
|---|---|---|---|
| `sm` | `px-2.5 py-0.5` | `gap-1.5` | `text-[9px]` |
| `md` | `px-4 py-1.5` | `gap-2` | `text-xs` |

El tamaño `sm` es adecuado para espacios reducidos como celdas de tabla o líneas de texto. El tamaño `md` es el valor por defecto y el recomendado para uso general.

---

## El Punto Indicador

El prop `dot` controla la visibilidad de un pequeño círculo sólido a la izquierda del texto. Su color coincide siempre con la variante activa.

| Estado | Comportamiento |
|---|---|
| `dot="true"` *(default)* | Renderiza `<span class="w-2 h-2 rounded-full bg-{color}">` antes del texto |
| `dot="false"` | Solo se muestra el texto del slot, sin el indicador visual |

El punto es útil para badges que representan estados en tiempo real (activo/inactivo, conectado/desconectado). Para badges de categoría o etiqueta estática, se recomienda ocultarlo con `:dot="false"`.

---

## Ejemplos de Uso

**Estado activo de un registro:**
```blade
<x-ui.badge variant="success">Activo</x-ui.badge>
```

**Estado inactivo sin punto:**
```blade
<x-ui.badge variant="slate" :dot="false">Inactivo</x-ui.badge>
```

**Badge pequeño en una celda de tabla:**
```blade
<x-ui.badge variant="warning" size="sm">Pendiente</x-ui.badge>
```

**Estado de error con punto:**
```blade
<x-ui.badge variant="error">Bloqueado</x-ui.badge>
```

**Nivel educativo como etiqueta de categoría:**
```blade
<x-ui.badge variant="info" :dot="false">Secundaria</x-ui.badge>
```

**Badge primario para novedades:**
```blade
<x-ui.badge variant="primary" size="sm">Nuevo</x-ui.badge>
```

**Uso dinámico con Livewire (estado desde propiedad):**
```blade
<x-ui.badge :variant="$record->status_variant">
    {{ $record->status_label }}
</x-ui.badge>
```

> [!TIP]
> Para mapear estados de Eloquent a variantes del badge, define un accessor en el modelo o un método en el componente Livewire que devuelva el nombre de la variante como string. Por ejemplo:
> ```php
> public function getStatusVariantAttribute(): string
> {
>     return match($this->status) {
>         'active'   => 'success',
>         'pending'  => 'warning',
>         'rejected' => 'error',
>         'inactive' => 'slate',
>         default    => 'info',
>     };
> }
> ```

**En celdas de tabla con Livewire Tables (u otro sistema):**
```blade
<x-ui.badge variant="{{ $row->status_variant }}" size="sm">
    {{ $row->status_label }}
</x-ui.badge>
```

---

## Notas Adicionales

- El componente usa `$attributes->merge()` sobre el elemento raíz (`<div>`), lo que permite añadir clases adicionales o atributos HTML directamente desde el sitio de uso sin necesidad de modificar el componente.
- El texto del badge proviene del `$slot`, lo que permite pasar contenido dinámico o traducciones directamente: `<x-ui.badge>{{ __('status.active') }}</x-ui.badge>`.
- El badge no es interactivo por diseño — no incluye estados hover ni focus. Si se necesita un badge clickeable, envuélvelo en un `<button>` o `<a>` externo.
- Todos los tokens de color (`orvian-orange`, `state-success`, `state-warning`, `state-error`, `state-info`) deben estar definidos en `tailwind.config.js` para que las clases sean generadas correctamente y no sean eliminadas en el build de producción.
