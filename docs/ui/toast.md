# Componente Toast (`x-ui.toasts`)

![Alpine.js](https://img.shields.io/badge/Alpine.js-Required-green)
![Laravel](https://img.shields.io/badge/Laravel-Compatible-red)
![Livewire](https://img.shields.io/badge/Livewire-Compatible-purple)

Sistema de notificaciones toast para ORVIAN. Completamente reactivo vía eventos Alpine.js. Soporta cuatro tipos semánticos, duración configurable, pausa al hover, barra de progreso animada y persistencia entre redirecciones mediante `sessionStorage`.

---

## Tabla de Contenido

- [Estructura de Archivos](#estructura-de-archivos)
- [Instalación y Uso Global](#instalación-y-uso-global)
- [API de Eventos](#api-de-eventos)
- [Tipos y Apariencia](#tipos-y-apariencia)
- [Disparar desde JavaScript / Alpine](#disparar-desde-javascript--alpine)
- [Disparar desde Livewire](#disparar-desde-livewire)
- [Disparar desde PHP (Sesión Laravel)](#disparar-desde-php-sesión-laravel)
- [Persistencia en Redirecciones](#persistencia-en-redirecciones)
- [Comportamiento Visual](#comportamiento-visual)
- [Notas Adicionales](#notas-adicionales)

---

## Estructura de Archivos

```plaintext
app/
└── View/
    └── Components/
        └── Ui/
            └── Toasts.php
resources/
└── views/
    └── components/
        └── ui/
            └── toasts.blade.php
```

---

## Instalación y Uso Global

El componente debe colocarse **una sola vez** en el layout principal, fuera de cualquier contenedor con `overflow: hidden`. Posición recomendada: esquina superior derecha, fija, por encima de todo el contenido.

```html
{{-- En resources/views/components/app.blade.php o wizard.blade.php --}}
    <x-ui.toasts />
```

> [!IMPORTANT]
> El componente debe vivir dentro del scope de Alpine.js (dentro del `<body>` y después de que Alpine esté cargado). En layouts Livewire, asegúrate de que no esté dentro de un componente con `wire:key` o que pueda ser morfado.

---

## API de Eventos

El sistema escucha tres eventos globales de `window`:

| Evento | Payload | Descripción |
|--------|---------|-------------|
| `notify` | `{ type, title, message, duration? }` | Muestra un toast inmediatamente |
| `notify-redirect` | `{ type, title, message, duration? }` | Guarda el toast en `sessionStorage` para mostrarlo tras una redirección |
| `remove-toast` | `id` (number) | Elimina un toast específico por su ID interno |

### Estructura del Payload

| Campo | Tipo | Requerido | Default | Descripción |
|-------|------|-----------|---------|-------------|
| `type` | `string` | No | `info` | Tipo visual: `success`, `error`, `warning`, `info` |
| `title` | `string` | Sí | — | Texto del encabezado del toast |
| `message` | `string` | Sí | — | Texto descriptivo del cuerpo |
| `duration` | `number` | No | `5000` | Duración en milisegundos antes de auto-cerrar |

---

## Tipos y Apariencia

| Tipo | Color del borde | Fondo (light) | Fondo (dark) | Ícono |
|------|----------------|---------------|--------------|-------|
| `success` | `emerald-500` | `emerald-50` | `emerald-500/10` | `check-circle` |
| `error` | `red-500` | `red-50` | `red-500/10` | `x-circle` |
| `warning` | `amber-500` | `amber-50` | `amber-500/10` | `exclamation-triangle` |
| `info` | `blue-500` | `blue-50` | `blue-500/10` | `information-circle` |

Todos los tipos incluyen:
- Borde izquierdo de 4px con el color de la variante
- Ícono sólido de Heroicons en el color correspondiente
- Barra de progreso en la parte inferior que se vacía según la duración
- Botón de cierre manual (`x-mark`)

---

## Disparar desde JavaScript / Alpine

```javascript
// Desde cualquier evento o función JavaScript
window.dispatchEvent(new CustomEvent('notify', {
    detail: {
        type: 'success',
        title: '¡Guardado!',
        message: 'Los cambios han sido aplicados correctamente.',
    }
}));

// Con duración personalizada (8 segundos)
window.dispatchEvent(new CustomEvent('notify', {
    detail: {
        type: 'error',
        title: 'Error de conexión',
        message: 'No se pudo conectar con el servidor. Intenta de nuevo.',
        duration: 8000,
    }
}));
```

**Desde un elemento Alpine inline:**

```html
<button @click="$dispatch('notify', {
    type: 'success',
    title: '¡Copiado!',
    message: 'El código ha sido copiado al portapapeles.'
})">
    Copiar
</button>
```

---

## Disparar desde Livewire

Usa `$this->dispatch()` dentro de cualquier método del componente Livewire:

```php
// En un componente Livewire
public function save(): void
{
    // ... lógica de guardado

    $this->dispatch('notify',
        type: 'success',
        title: '¡Guardado!',
        message: 'El registro fue actualizado correctamente.',
    );
}

public function delete(): void
{
    // ... lógica de eliminación

    $this->dispatch('notify',
        type: 'warning',
        title: 'Registro eliminado',
        message: 'Esta acción no puede deshacerse.',
        duration: 8000,
    );
}
```

> [!NOTE]
> Livewire 3 usa `$this->dispatch('evento', payload)` en lugar del antiguo `$this->emit()`. El nombre del evento debe coincidir exactamente con el listener `@notify.window` del componente.

---

## Disparar desde PHP (Sesión Laravel)

Para redirecciones de controlador o después de acciones de formulario clásicas, usa los flash de sesión de Laravel. El componente los detecta automáticamente al renderizar:

```php
// En un Controller
return redirect()->route('schools.index')
    ->with('success', '¡Escuela registrada exitosamente!');

return redirect()->back()
    ->with('error', 'No tienes permiso para realizar esta acción.');

return redirect()->route('dashboard')
    ->with('info', 'Tu sesión fue restaurada.');

return redirect()->back()
    ->with('warning', 'El archivo supera el límite de tamaño permitido.');
```

**Claves de sesión reconocidas:**

| Clave | Tipo del toast | Título automático | Duración |
|-------|---------------|-------------------|----------|
| `success` | `success` | `¡Éxito!` | 5000ms |
| `error` | `error` | `Error` | 8000ms |
| `info` | `info` | `Información` | 5000ms |
| `warning` | `warning` | `Advertencia` | 8000ms |

**Errores de validación de Laravel:**

Si el bag `$errors` tiene contenido (tras un formulario con `validate()`), se muestra automáticamente el primer error con el título indicando cuántos errores adicionales hay:

```
// 1 error  → título: "Error de validación"
// 3 errores → título: "Error de validación (+2 más)"
```

---

## Persistencia en Redirecciones

Para mostrar un toast **después** de una redirección JavaScript (como `window.location.href`), usa el evento `notify-redirect`. El toast se guarda en `sessionStorage` y se muestra 300ms después de que la nueva página cargue:

```javascript
// Guardar toast para mostrar después de redirigir
window.dispatchEvent(new CustomEvent('notify-redirect', {
    detail: {
        type: 'success',
        title: '¡Configuración completada!',
        message: 'Tu escuela ha sido activada exitosamente.',
    }
}));

// Luego redirigir
window.location.href = '/app/dashboard';
```

Esto es especialmente útil en la pantalla de progreso del wizard, donde la redirección es via JavaScript y no por Livewire.

---

## Comportamiento Visual

### Animaciones

| Acción | Animación |
|--------|-----------|
| Entrada | `translate-x-full → translate-x-0` + `opacity-0 → opacity-100` (500ms ease-out) |
| Salida | `translate-x-0 → translate-x-full` + `opacity-100 → opacity-0` (400ms ease-in) |

### Barra de Progreso

La barra inferior se vacía linealmente desde el 100% hasta el 0% en el tiempo definido por `duration`. Se actualiza cada 10ms para una animación suave.

### Pausa al Hover

Al pasar el cursor sobre un toast, el temporizador se pausa. Al salir, el conteo continúa desde donde se detuvo. Esto evita que mensajes importantes desaparezcan mientras el usuario los lee.

### Cierre Manual

El botón `×` en la esquina superior derecha cierra el toast inmediatamente con la animación de salida. El intervalo del temporizador se limpia automáticamente.

### Múltiples Toasts

Los toasts se apilan verticalmente con `gap-3`. No hay límite de toasts simultáneos, aunque en la práctica raramente aparecen más de 2-3 a la vez.

---

## Notas Adicionales

- El componente usa `sessionStorage` (no `localStorage`) para la persistencia entre redirecciones. Los datos se eliminan automáticamente al leerlos — no persisten entre sesiones del navegador.
- El ID interno de cada toast se genera con `Date.now() + Math.random()` para garantizar unicidad incluso si se disparan múltiples toasts en el mismo milisegundo.
- El componente es `pointer-events-none` en su contenedor padre para no bloquear clics en el contenido subyacente. Cada toast individual restaura `pointer-events-auto`.
- Los toasts de `error` y `warning` tienen duración de 8000ms por defecto desde sesión; los de `success` e `info` usan 5000ms. Siempre puedes sobreescribir con el campo `duration`.
- El orden de aparición es LIFO (último en entrar, último visible arriba). Los toasts nuevos aparecen encima de los anteriores.