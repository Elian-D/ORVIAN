{{-- resources/views/layouts/app-module.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="audio-feedback" content="{{ auth()->user()?->preference('audio_feedback', true) ? 'true' : 'false' }}">
    <title>{{ $title ?? 'App' }} | {{ config('app.name') }}</title>

    <x-ui.theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">
</head>

<body class="font-sans antialiased min-h-screen flex flex-col bg-slate-100 dark:bg-dark-bg text-slate-900 dark:text-slate-100">

    {{--
        Navbar FIXED — ocupa h-14 (56px) en estado módulo.
        El <main> tiene pt-14 para que el contenido empiece debajo del navbar,
        no por detrás de él.

        module-toolbar usa sticky top-14 (default) para quedar justo debajo
        del navbar fijo — sin solapamiento.
    --}}
    <x-app.navbar
        :module="$module ?? null"
        :moduleIcon="$moduleIcon ?? null"
        :moduleLinks="$moduleLinks ?? []"
    />

    {{--
        pt-14 = 56px = h-14 del navbar de módulo.
        El module-toolbar se coloca dentro del $slot, al inicio de la vista.
    --}}
    <main class="flex-1 pt-14">
        {{ $slot }}
    </main>

    <x-ui.toasts />

    {{-- 1. Cambia el div: quitamos x-init y el () de x-data --}}
    {{-- Agregamos los listeners directamente aquí para que sean 100% confiables --}}
    <div
        x-data="audioFeedback"
        @attendance-recorded-success.window="play('success')"
        @attendance-facial-error.window="play('error')"
        style="display:none;"
        aria-hidden="true"
    ></div>

    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('audioFeedback', () => ({
            enabled: document.querySelector('meta[name="audio-feedback"]')?.content !== 'false',

            // 2. Usar las rutas correctas inyectadas por Laravel
            sounds: {
                success: new Audio("{{ asset('assets/sounds/success.wav') }}"),
                error:   new Audio("{{ asset('assets/sounds/error.wav') }}"),
            },

            init() {
                // Pre-cargar audios
                Object.values(this.sounds).forEach(s => {
                    s.volume = 0.5;
                    s.load();
                });
                
                // Ya no necesitamos Livewire.on aquí porque usamos los atributos @event.window arriba
            },

            play(type) {
                if (!this.enabled) return;
                const sound = this.sounds[type];
                if (!sound) return;

                sound.currentTime = 0;
                sound.play().catch(e => {
                    console.warn("[Audio] Bloqueado por el navegador:", e);
                });
            },
        }));
    });
    </script>
    @endpush

    @livewire('shared.profile-modal')
    @livewireScripts
    @stack('scripts')
</body>
</html>