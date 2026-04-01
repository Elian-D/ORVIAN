@props([
    'column',               // clave de la columna, ej: 'name'
    'visible' => [],        // $visibleColumns del componente Livewire
    'class'   => 'px-4 py-3.5', // clases del <td>, sobreescribibles por slot
])

@if(in_array($column, $visible))
    <td {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </td>
@endif