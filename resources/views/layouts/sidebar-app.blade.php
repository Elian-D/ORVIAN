{{--
    resources/views/layouts/sidebar-app.blade.php
    -----------------------------------------------
    Sidebar de escuela — construido directamente desde config('modules'),
    reemplazo del navbar horizontal + Hub de tarjetas (Fase 7 / REQ-07.1).

    No recibe $module/$moduleIcon/$moduleLinks por props: a diferencia del
    navbar anterior, el Sidebar no depende de qué Livewire se está
    renderizando — siempre muestra todos los módulos, resaltando el activo
    vía request()->routeIs().

    Íconos: usa los SVG propios de config('modules.*.moduleIcon') vía
    x-ui.module-icon (REQ-07.10), no Heroicons genéricos.

    Permisos: cada moduleLink en config('modules') declara su 'permission'
    (el mismo que protege la ruta vía ->middleware('can:...')). Un link sin
    permiso para el usuario actual no se renderiza; un módulo sin ningún
    link visible no se renderiza tampoco (nada de dropdowns vacíos).
--}}

<x-sidebar.layout home-route="app.dashboard" profile-route="app.profile">
    <x-sidebar.item href="{{ route('app.dashboard') }}" icon="heroicon-s-squares-2x2" :active="request()->routeIs('app.dashboard')">
        Dashboard
    </x-sidebar.item>

    <x-sidebar.title>Módulos</x-sidebar.title>
    <x-sidebar.group>
        @foreach(config('modules') as $key => $mod)
            @php
                $visibleLinks = collect($mod['moduleLinks'])
                    ->filter(fn ($link) => empty($link['permission']) || auth()->user()->can($link['permission']))
                    ->values();
            @endphp

            @if($visibleLinks->isNotEmpty())
                <x-sidebar.dropdown
                    :id="$key"
                    :moduleIcon="$mod['moduleIcon']"
                    :label="$mod['module']"
                    :activeRoutes="$visibleLinks->pluck('route')->all()"
                >
                    @foreach($visibleLinks as $link)
                        <x-sidebar.subitem href="{{ route($link['route']) }}" :active="request()->routeIs($link['route'])">
                            {{ $link['label'] }}
                        </x-sidebar.subitem>
                    @endforeach
                </x-sidebar.dropdown>
            @endif
        @endforeach
    </x-sidebar.group>
</x-sidebar.layout>
