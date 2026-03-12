# Componentes de Formulario (`x-ui.forms.*`)

![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-Compatible-blue)
![Alpine.js](https://img.shields.io/badge/Alpine.js-Required_(Toggle)-green)

Colección de componentes de formulario para ORVIAN. Siguen el estilo **Line UI** — sin cajas contenedoras, solo el borde inferior — manteniendo coherencia visual con el sistema de diseño global. Todos soportan modo oscuro, estados de error y son compatibles con `wire:model` de Livewire.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Convenciones Compartidas](#convenciones-compartidas)
- [x-ui.forms.input](#x-uiformsinput)
- [x-ui.forms.select](#x-uiformsselect)
- [x-ui.forms.textarea](#x-uiformstextarea)
- [x-ui.forms.checkbox](#x-uiformscheckbox)
- [x-ui.forms.radio](#x-uiformsradio)
- [x-ui.forms.toggle](#x-uiformstoggle)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            └── Forms/
                ├── Input.php
                ├── Select.php
                ├── Textarea.php
                ├── Checkbox.php
                ├── Radio.php
                └── Toggle.php
resources/
└── views/
    └── components/
        └── ui/
            └── forms/
                ├── input.blade.php
                ├── select.blade.php
                ├── textarea.blade.php
                ├── checkbox.blade.php
                ├── radio.blade.php
                └── toggle.blade.php
```

---

## Convenciones Compartidas

### Sistema de Estados

Todos los componentes que reciben texto (Input, Select, Textarea) comparten tres estados visuales:

| Estado | Color del borde | Color del label | Icono |
|--------|----------------|-----------------|-------|
| **Default** | `slate-200` / `dark-border` | `slate-400` | `slate-400` |
| **Focus** | `orvian-orange` | `orvian-orange` | `orvian-orange` |
| **Error** | `state-error` | `state-error` | `exclamation-circle` (automático) |
| **Disabled** | Igual que default | — | `opacity-50`, `cursor-not-allowed` |

La transición al estado Focus está implementada con `group` + `group-focus-within:` en el wrapper. El label cambia de color automáticamente sin JavaScript.

### Props de Mensaje

| Prop | Tipo | Comportamiento |
|------|------|---------------|
| `error` | `string\|null` | Muestra mensaje en rojo debajo del campo; en Input/Select reemplaza el icono derecho con `exclamation-circle` |
| `hint` | `string\|null` | Muestra texto gris informativo. Se oculta si hay `error` activo |

### Integración con Livewire

Todos los componentes usan `$attributes->merge()` sobre el elemento nativo, lo que significa que cualquier atributo adicional se pasa directamente al `<input>`, `<select>` o `<textarea>`:

```blade
<x-ui.forms.input name="name" wire:model="name" />
<x-ui.forms.select name="plan_id" wire:model.live="plan_id" />
<x-ui.forms.checkbox name="terms" wire:model="terms" />
<x-ui.forms.toggle name="advanced" wire:model="advanced" />
```

### Iconos

Todos los props de icono aceptan el nombre del componente Heroicons de `blade-ui-kit/blade-heroicons`:

- Solid: `heroicon-s-{name}`
- Outline: `heroicon-o-{name}`
- Mini: `heroicon-m-{name}`

Se recomienda usar la variante **outline** (`heroicon-o-*`) en los campos de formulario para mantener un peso visual ligero, coherente con el estilo Line UI.

---

## x-ui.forms.input

Componente para entradas de texto de una sola línea. Soporta todos los tipos de `<input>` HTML.

### API

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `''` | Texto del label. Si está vacío, no se renderiza el label. |
| `name` | `string` | `''` | Atributo `name` del input |
| `id` | `string` | `→ name` | Atributo `id`. Si no se pasa, se usa el valor de `name` |
| `type` | `string` | `text` | Tipo HTML: `text`, `email`, `password`, `number`, `tel`, `url`, `search` |
| `placeholder` | `string` | `''` | Texto placeholder |
| `iconLeft` | `string\|null` | `null` | Heroicon en posición izquierda (añade `pl-7` al input) |
| `iconRight` | `string\|null` | `null` | Heroicon en posición derecha. **Ignorado** si hay `error` activo |
| `error` | `string\|null` | `null` | Mensaje de error. Activa estado visual de error en todo el componente |
| `hint` | `string\|null` | `null` | Texto auxiliar debajo del campo (solo visible si no hay `error`) |
| `required` | `bool` | `false` | Agrega `*` al label y atributo `required` al input |
| `disabled` | `bool` | `false` | Desactiva el campo |
| `readonly` | `bool` | `false` | Campo de solo lectura |

### Ejemplos

**Input básico con icono:**
```blade
<x-ui.forms.input
    label="Nombre del Centro"
    name="name"
    placeholder="Ej. Liceo Juan Pablo Duarte"
    icon-left="heroicon-o-building-library"
    hint="Nombre oficial según el MINERD"
/>
```

**Con Livewire y validación:**
```blade
<x-ui.forms.input
    label="Correo Electrónico"
    name="email"
    type="email"
    icon-left="heroicon-o-envelope"
    wire:model.live="email"
    :error="$errors->first('email')"
    required
/>
```

**Password con toggle manual (Alpine):**
```blade
<div x-data="{ show: false }" class="flex flex-col group">
    <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                  text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
        Contraseña <span class="text-state-error ml-0.5">*</span>
    </label>
    <div class="relative flex items-center">
        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none
                     text-slate-400 group-focus-within:text-orvian-orange transition-colors">
            <x-heroicon-o-lock-closed class="w-5 h-5" />
        </span>
        <input
            :type="show ? 'text' : 'password'"
            name="password"
            wire:model="password"
            class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                   rounded-none pl-7 pr-7 py-3 text-sm text-slate-800 dark:text-white
                   placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors"
        />
        <button type="button" @click="show = !show"
                class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5
                       text-slate-400 hover:text-orvian-orange transition-colors">
            <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
            <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" />
        </button>
    </div>
</div>
```

> [!NOTE]
> El campo password con toggle de visibilidad no está encapsulado en el componente `x-ui.forms.input` intencionalmente — el icono derecho necesita ser un botón interactivo, lo que rompe la semántica de los iconos decorativos. Se recomienda construirlo inline como en el ejemplo anterior o encapsularlo en un componente dedicado si se usa frecuentemente.

---

## x-ui.forms.select

Select nativo estilizado con borde inferior y chevron personalizado. Compatible con `$slot` para pasar `<option>` directamente.

### API

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `''` | Texto del label |
| `name` | `string` | `''` | Atributo `name` |
| `id` | `string` | `→ name` | Atributo `id` |
| `placeholder` | `string` | `'Seleccionar...'` | Opción vacía por defecto. Pasar `placeholder=""` para omitirla |
| `iconLeft` | `string\|null` | `null` | Heroicon izquierdo opcional |
| `error` | `string\|null` | `null` | Mensaje de error |
| `hint` | `string\|null` | `null` | Texto auxiliar |
| `required` | `bool` | `false` | — |
| `disabled` | `bool` | `false` | — |

### Ejemplos

**Select con opciones dinámicas desde Livewire:**
```blade
<x-ui.forms.select
    label="Regional Educativa"
    name="regional_education_id"
    icon-left="heroicon-o-map"
    wire:model.live="regional_education_id"
    :error="$errors->first('regional_education_id')"
    required
>
    @foreach ($this->regionalEducations as $regional)
        <option value="{{ $regional->id }}">{{ $regional->name }}</option>
    @endforeach
</x-ui.forms.select>
```

**Select dependiente (deshabilitado mientras no hay padre):**
```blade
<x-ui.forms.select
    label="Distrito Educativo"
    name="educational_district_id"
    icon-left="heroicon-o-map-pin"
    :disabled="!$regional_education_id"
    hint="{{ !$regional_education_id ? 'Selecciona primero la Regional' : '' }}"
    wire:model="educational_district_id"
>
    @foreach ($this->educationalDistricts as $district)
        <option value="{{ $district->id }}">{{ $district->name }}</option>
    @endforeach
</x-ui.forms.select>
```

---

## x-ui.forms.textarea

Área de texto con el mismo estilo Line UI.

### API

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `''` | Texto del label |
| `name` | `string` | `''` | Atributo `name` |
| `id` | `string` | `→ name` | Atributo `id` |
| `placeholder` | `string` | `''` | Placeholder |
| `rows` | `int` | `3` | Número de filas visibles |
| `resize` | `bool` | `false` | Permite redimensionar verticalmente (`resize-y`) |
| `error` | `string\|null` | `null` | Mensaje de error |
| `hint` | `string\|null` | `null` | Texto auxiliar |
| `required` | `bool` | `false` | — |
| `disabled` | `bool` | `false` | — |
| `readonly` | `bool` | `false` | — |

### Ejemplo

```blade
<x-ui.forms.textarea
    label="Referencias de Ubicación"
    name="address_reference"
    placeholder="Ej. Frente al parque central..."
    :rows="3"
    wire:model="address_reference"
    hint="Opcional — facilita la localización del centro"
/>
```

---

## x-ui.forms.checkbox

Checkbox con estilo ORVIAN. Usa `@tailwindcss/forms` para el check nativo y la clase `text-orvian-orange` para definir el color del tilde al seleccionar.

### API

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `''` | Texto principal del checkbox |
| `name` | `string` | `''` | Atributo `name` (usar `name="levels[]"` para arrays) |
| `id` | `string` | `→ name` | Atributo `id` |
| `value` | `string` | `'1'` | Valor del checkbox cuando está marcado |
| `checked` | `bool` | `false` | Estado inicial marcado |
| `description` | `string\|null` | `null` | Texto descriptivo pequeño bajo el label |
| `error` | `string\|null` | `null` | Mensaje de error (se muestra bajo el checkbox) |
| `disabled` | `bool` | `false` | — |

### Ejemplos

**Grupo de checkboxes para niveles:**
```blade
<div class="flex flex-col gap-4">
    <x-ui.forms.checkbox
        label="Primaria"
        name="levels[]"
        value="1"
        wire:model="selectedLevels"
        description="Primer ciclo educativo (1ro – 6to)"
    />
    <x-ui.forms.checkbox
        label="Secundaria Primer Ciclo"
        name="levels[]"
        value="2"
        wire:model="selectedLevels"
    />
    <x-ui.forms.checkbox
        label="Secundaria Segundo Ciclo"
        name="levels[]"
        value="3"
        wire:model="selectedLevels"
    />
</div>
```

> [!IMPORTANT]
> Para usar con `wire:model` en arrays Livewire, declara la propiedad como `public array $selectedLevels = []` en el componente. Livewire maneja correctamente los checkboxes con `name="levels[]"` y `wire:model="selectedLevels"`.

---

## x-ui.forms.radio

Radio button con estilo ORVIAN. El punto interior naranja se genera con `text-orvian-orange` via `@tailwindcss/forms`.

### API

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `''` | Texto del radio |
| `name` | `string` | `''` | Nombre del grupo (todos los radios del mismo grupo comparten el mismo `name`) |
| `id` | `string` | `→ name_value` | Atributo `id`. Por defecto se genera como `{name}_{value}` |
| `value` | `string` | `''` | Valor enviado al seleccionar |
| `checked` | `bool` | `false` | Estado inicial seleccionado |
| `description` | `string\|null` | `null` | Texto descriptivo bajo el label |
| `disabled` | `bool` | `false` | — |

### Ejemplo

```blade
<div class="flex flex-col gap-4">
    <x-ui.forms.radio
        label="Público"
        name="regimen_gestion"
        value="Público"
        description="Centro de gestión estatal"
        wire:model="regimen_gestion"
    />
    <x-ui.forms.radio
        label="Privado"
        name="regimen_gestion"
        value="Privado"
        description="Centro de gestión privada"
        wire:model="regimen_gestion"
    />
    <x-ui.forms.radio
        label="Semioficial"
        name="regimen_gestion"
        value="Semioficial"
        description="Gestión mixta público-privada"
        wire:model="regimen_gestion"
    />
</div>
```

---

## x-ui.forms.toggle

Interruptor visual implementado con Alpine.js. Internamente usa un `<input type="checkbox">` oculto (`sr-only`) para mantener el valor real compatible con `wire:model`.

### API

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `''` | Texto principal del toggle |
| `name` | `string` | `''` | Nombre del input oculto |
| `id` | `string` | `→ name` | Atributo `id` |
| `checked` | `bool` | `false` | Estado inicial activo |
| `description` | `string\|null` | `null` | Descripción pequeña bajo el label |
| `disabled` | `bool` | `false` | Desactiva el click sin cambiar el look |

### Comportamiento Visual

| Estado | Color de la pista | Bolita | Sombra |
|--------|------------------|--------|--------|
| **Off** | `slate-200` / `dark-border` | Izquierda | — |
| **On** | `orvian-orange` | Derecha | `shadow-orvian-orange/30` |
| **Disabled** | Sin cambio | — | `opacity-50`, sin click |

### Ejemplos

**Toggle simple:**
```blade
<x-ui.forms.toggle
    label="Notificaciones por correo"
    name="email_notifications"
    description="Recibe alertas de nuevas matrículas"
/>
```

**Con Livewire:**
```blade
<x-ui.forms.toggle
    label="Modo Avanzado"
    name="advanced_mode"
    wire:model="advancedMode"
    :checked="$advancedMode"
/>
```

> [!NOTE]
> Al usar `wire:model`, la sincronización ocurre a través del `x-model` de Alpine sobre el input oculto. Si el estado inicial del toggle debe reflejar el estado del componente Livewire al cargar, pasa siempre `:checked="$tuPropiedad"` para que el estado PHP inicialice correctamente el valor Alpine.

---

## Notas Adicionales

- **`@tailwindcss/forms`** es requerido para el estilo nativo de checkboxes y radios. Verifica que esté en `plugins` del `tailwind.config.js`.
- **Alpine.js** es requerido solo para `x-ui.forms.toggle`. Los demás componentes son puramente HTML/PHP.
- El `id` se genera automáticamente desde `name` si no se pasa. Para checkboxes en arrays (`name="levels[]"`), pasa siempre un `id` explícito y único para evitar conflictos en el `<label for="">`.
- La variante `link` del `x-ui.button` es la recomendada como acción secundaria dentro de formularios (ej. "¿Olvidaste tu contraseña?").
- Todos los tokens de color (`orvian-orange`, `orvian-navy`, `state-error`, `dark-border`, etc.) deben estar definidos en `tailwind.config.js` para que las clases sean generadas en el build de producción.
