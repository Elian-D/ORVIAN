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

    <x-sidebar.title>Configuración</x-sidebar.title>
    <x-sidebar.group>
        <x-sidebar.dropdown id="sistema" icon="heroicon-s-cog-8-tooth" label="Sistema" :activeRoutes="['config.*']">
            <x-sidebar.subitem href="#" icon="heroicon-s-key">Seguridad & API</x-sidebar.subitem>
            <x-sidebar.subitem href="#" icon="heroicon-s-document-text">Logs de Errores</x-sidebar.subitem>
        </x-sidebar.dropdown>
    </x-sidebar.group>
</x-sidebar.layout>

