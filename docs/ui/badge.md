# Componente Badge (`x-ui.badge`)

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)

El componente `x-ui.badge` centraliza la representación visual de etiquetas de estado en ORVIAN. Soporta variantes de color semánticas, un indicador de punto opcional, dos tamaños y **colores hexadecimales personalizados** para casos de uso dinámicos como roles personalizados.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [API del Componente](#api-del-componente)
- [Variantes de Color](#variantes-de-color)
- [Colores Hexadecimales Personalizados](#colores-hexadecimales-personalizados)
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
| `hex` | `string\|null` | `null` | Cualquier código hexadecimal válido (ej. `#FF5733`, `3B82F6`) |
| `dot` | `bool` | `true` | — |
| `size` | `string` | `md` | `sm`, `md` |

> **IMPORTANTE:** Los props `variant` y `hex` son **mutuamente excluyentes**. Si se proporciona `hex`, se ignora `variant`. Usar `hex` cuando se necesiten colores arbitrarios no definidos en el sistema de diseño (ej. roles personalizados creados por usuarios).

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

## Colores Hexadecimales Personalizados

El prop `hex` permite asignar **cualquier color hexadecimal** al badge, aplicando automáticamente la misma lógica visual del sistema de diseño (fondo semitransparente, texto sólido, borde con opacidad).

### Cómo funciona

Cuando se proporciona `hex`, el componente:

1. **Ignora** el prop `variant` (no se aplican clases de Tailwind de color)
2. **Genera estilos CSS inline** con las siguientes opacidades:
   - **Fondo:** `{hex}1a` (10% de opacidad)
   - **Texto:** `{hex}` (100% sólido)
   - **Borde:** `{hex}33` (20% de opacidad)
3. **Punto indicador:** Si `dot="true"`, el círculo también usa el color hexadecimal sólido

### Formato aceptado

El prop `hex` acepta códigos hexadecimales con o sin el símbolo `#`:

- `#FF5733` ✅
- `FF5733` ✅
- `#3b82f6` ✅
- `3B82F6` ✅

### Ejemplo de uso

**Badge con color hexadecimal personalizado:**
```html
<x-ui.badge hex="#9333EA">Coordinador Regional</x-ui.badge>
```

**Roles personalizados desde base de datos:**
```html
@foreach($customRoles as $role)
    <x-ui.badge hex="{{ $role->color }}">
        {{ $role->name }}
    </x-ui.badge>
@endforeach
```

**Sin punto indicador:**
```html
<x-ui.badge hex="#EC4899" :dot="false">Marketing</x-ui.badge>
```

### Advertencias

- ⚠️ **Validación:** El componente NO valida si el hexadecimal es válido. Asegúrate de validar los valores antes de pasarlos al componente.
- ⚠️ **Accesibilidad:** Algunos colores hexadecimales pueden no cumplir con ratios de contraste WCAG AA/AAA. Considera validar el contraste si los usuarios finales eligen los colores.
- ⚠️ **Performance:** Usar `hex` implica estilos inline. Para badges estáticos con colores predefinidos, preferir `variant` (aprovecha Tailwind CSS y purging).

---

## Tamaños

| Size | Padding | Gap | Font size |
|---|---|---|---|
| `sm` | `px-2.5 py-0.5` | `gap-1.5` | `text-[9px]` |
| `md` | `px-4 py-1.5` | `gap-2` | `text-xs` |

El tamaño `sm` es adecuado para espacios reducidos como celdas de tabla o líneas de texto. El tamaño `md` es el valor por defecto y el recomendado para uso general.

---

## El Punto Indicador

El prop `dot` controla la visibilidad de un pequeño círculo sólido a la izquierda del texto. Su color coincide siempre con la variante activa o con el color hexadecimal proporcionado.

| Estado | Comportamiento |
|---|---|
| `dot="true"` *(default)* | Renderiza `<span class="w-2 h-2 rounded-full">` antes del texto |
| `dot="false"` | Solo se muestra el texto del slot, sin el indicador visual |

El punto es útil para badges que representan estados en tiempo real (activo/inactivo, conectado/desconectado). Para badges de categoría o etiqueta estática, se recomienda ocultarlo con `:dot="false"`.

---

## Ejemplos de Uso

### Con variantes del sistema

**Estado activo de un registro:**
```html
<x-ui.badge variant="success">Activo</x-ui.badge>
```

**Estado inactivo sin punto:**
```html
<x-ui.badge variant="slate" :dot="false">Inactivo</x-ui.badge>
```

**Badge pequeño en una celda de tabla:**
```html
<x-ui.badge variant="warning" size="sm">Pendiente</x-ui.badge>
```

**Estado de error con punto:**
```html
<x-ui.badge variant="error">Bloqueado</x-ui.badge>
```

**Nivel educativo como etiqueta de categoría:**
```html
<x-ui.badge variant="info" :dot="false">Secundaria</x-ui.badge>
```

**Badge primario para novedades:**
```html
<x-ui.badge variant="primary" size="sm">Nuevo</x-ui.badge>
```

### Con colores hexadecimales personalizados

**Rol personalizado con color desde base de datos:**
```html
<x-ui.badge hex="{{ $user->role->color }}">
    {{ $user->role->name }}
</x-ui.badge>
```

**Categorías de productos con paleta personalizada:**
```html
<x-ui.badge hex="#F59E0B" :dot="false" size="sm">Premium</x-ui.badge>
<x-ui.badge hex="#8B5CF6" :dot="false" size="sm">Exclusivo</x-ui.badge>
<x-ui.badge hex="#10B981" :dot="false" size="sm">Disponible</x-ui.badge>
```

**Estado de conexión con color dinámico:**
```html
<x-ui.badge hex="{{ $server->is_online ? '#22C55E' : '#EF4444' }}">
    {{ $server->is_online ? 'Online' : 'Offline' }}
</x-ui.badge>
```

### Uso dinámico con Livewire

**Estado desde propiedad (con variant):**
```html
<x-ui.badge :variant="$record->status_variant">
    {{ $record->status_label }}
</x-ui.badge>
```

**Rol con color personalizado:**
```html
<x-ui.badge hex="{{ $record->role->color }}">
    {{ $record->role->display_name }}
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

**En celdas de tabla con Livewire Tables:**
```html
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
- **Híbrido por diseño:** El componente mantiene compatibilidad total con el sistema de diseño mediante `variant`, mientras ofrece flexibilidad ilimitada mediante `hex` para casos de uso dinámicos como roles personalizados o integraciones con APIs externas.
