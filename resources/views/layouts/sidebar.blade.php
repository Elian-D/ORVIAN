<x-sidebar.layout>
    <x-sidebar.item href="{{ route('admin.hub') }}" icon="heroicon-s-squares-2x2" :active="request()->routeIs('admin.hub')">
        Dashboard General
    </x-sidebar.item>

    <x-sidebar.title>Operativa</x-sidebar.title>
    <x-sidebar.group>
        <x-sidebar.dropdown id="escuelas" icon="heroicon-s-academic-cap" label="Gestión Escolar" :activeRoutes="['admin.setup.*']">
            <x-sidebar.subitem href="{{ route('admin.setup') }}" :active="request()->routeIs('admin.setup')">Crear Escuela</x-sidebar.subitem>
            <x-sidebar.subitem href="#">Módulos Activos</x-sidebar.subitem>
        </x-sidebar.dropdown>
        
        <x-sidebar.item href="{{ route('admin.users.index') }}" icon="heroicon-s-user-group" :active="request()->routeIs('admin.users.*')">
            Usuarios Globales
        </x-sidebar.item>
    </x-sidebar.group>

    <x-sidebar.title>Seguridad y Acceso</x-sidebar.title>
    <x-sidebar.group>
        <x-sidebar.item href="{{ route('admin.roles.index') }}" icon="heroicon-s-shield-check" :active="request()->routeIs('admin.roles.*')">
            Roles del Sistema
        </x-sidebar.item>
    </x-sidebar.group>

    <x-sidebar.title>Observabilidad</x-sidebar.title>
    <x-sidebar.group>
        {{-- Laravel Pulse --}}
        <x-sidebar.item href="/admin/pulse" icon="heroicon-s-chart-bar-square" target="_blank">
            Métricas del Servidor
        </x-sidebar.item>

        {{-- Log Viewer --}}
        <x-sidebar.item href="/admin/logs" icon="heroicon-s-document-magnifying-glass" target="_blank">
            Logs del Sistema
        </x-sidebar.item>
    </x-sidebar.group>
</x-sidebar.layout>