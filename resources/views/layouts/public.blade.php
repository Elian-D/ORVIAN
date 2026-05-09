<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ORVIAN — Sistema Integral de Gestión Educativa para instituciones dominicanas." />

    <title>ORVIAN — Gestión Educativa</title>

    {{-- Favicon por tema --}}
    <link rel="icon"
          href="{{ asset('img/logos/logo-icon-light.svg') }}"
          type="image/svg+xml"
          media="(prefers-color-scheme: light)">
    <link rel="icon"
          href="{{ asset('img/logos/logo-icon-dark.svg') }}"
          type="image/svg+xml"
          media="(prefers-color-scheme: dark)">

    {{-- Tema: script síncrono antes del CSS para evitar flash visual --}}
    <x-ui.theme-init />

    {{-- Estilos --}}
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-dark-bg text-slate-800 dark:text-slate-100 antialiased 
             {{-- Configuración de selección de texto personalizada --}}
             selection:bg-state-info/30 selection:text-orvian-navy 
             dark:selection:bg-orvian-orange/20 dark:selection:text-orvian-orange">

    {{-- Toasts globales --}}
    <x-ui.toasts />

    <div class="flex min-h-screen flex-col">
        {{-- Aquí iría tu componente de Navbar si decides extraerlo --}}
        
        <main id="main-content" class="flex-grow">
            {{ $slot }}
        </main>

        {{-- Aquí iría tu componente de Footer --}}
    </div>
    
    {{-- Scripts --}}
    @livewireScripts
    @stack('scripts')
</body>
</html>