<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    <x-ui.theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">
</head>
<body class="font-sans antialiased min-h-screen bg-white dark:bg-dark-bg text-slate-900 dark:text-slate-100">
    {{ $slot }}

    @livewireScripts
</body>
</html>
