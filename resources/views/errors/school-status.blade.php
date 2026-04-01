<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $type === 'suspended' ? 'Servicio Suspendido' : 'Acceso Restringido' }} | {{ config('app.name') }}</title>

    <x-ui.theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <link rel="icon" href="{{ asset('img/logos/logo-icon-light.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: light)">
    <link rel="icon" href="{{ asset('img/logos/logo-icon-dark.svg') }}" type="image/svg+xml" media="(prefers-color-scheme: dark)">
</head>

<body class="font-sans antialiased min-h-screen flex flex-col bg-slate-100 dark:bg-dark-bg text-slate-900 dark:text-slate-100">

    <main class="flex-1 flex items-center justify-center relative overflow-hidden p-6">
        
        {{-- Efectos de fondo dinámicos --}}
        <div class="absolute -top-24 -left-24 w-96 h-96 {{ $type === 'suspended' ? 'bg-amber-500/5' : 'bg-slate-500/5' }} rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-orvian-blue/10 dark:bg-orvian-blue/20 rounded-full blur-[120px]"></div>

        <div class="relative z-10 max-w-xl w-full">
            <div class="bg-white dark:bg-dark-card border border-slate-200 dark:border-dark-border rounded-orvian shadow-2xl p-8 md:p-12 text-center">
                
                {{-- Icono Dinámico --}}
                <div class="mb-6 flex justify-center">
                    <div class="w-20 h-20 {{ $type === 'suspended' ? 'bg-amber-100 dark:bg-amber-500/10' : 'bg-slate-100 dark:bg-white/5' }} rounded-2xl flex items-center justify-center rotate-3">
                        @if($type === 'suspended')
                            <x-heroicon-s-credit-card class="w-10 h-10 text-amber-600 dark:text-amber-500" />
                        @else
                            <x-heroicon-s-no-symbol class="w-10 h-10 text-slate-600 dark:text-slate-400" />
                        @endif
                    </div>
                </div>

                {{-- Título Dinámico --}}
                <h1 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mb-4">
                    {{ $type === 'suspended' ? 'Servicio Temporalmente Suspendido' : 'Institución Inactiva' }}
                </h1>
                
                {{-- Mensaje Dinámico --}}
                <p class="text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">
                    @if($type === 'suspended')
                        Estimado administrador, el acceso para **{{ auth()->user()->school->name }}** ha sido pausado debido a un balance pendiente en su facturación mensual.
                    @else
                        Lo sentimos, el acceso para **{{ auth()->user()->school->name }}** ha sido desactivado por la administración central. Por favor, contacte con soporte técnico para más detalles.
                    @endif
                </p>

                {{-- Cuadro de Info --}}
                <div class="bg-slate-50 dark:bg-white/5 rounded-xl p-4 mb-8 border border-slate-100 dark:border-white/5 flex items-center gap-4 text-left">
                    <x-heroicon-s-information-circle class="w-5 h-5 {{ $type === 'suspended' ? 'text-amber-500' : 'text-slate-400' }} shrink-0" />
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        @if($type === 'suspended')
                            Sus datos permanecen seguros. Una vez regularizado el pago, el acceso se restaurará de forma inmediata.
                        @else
                            Esta es una medida administrativa. No podrá acceder a los módulos de gestión hasta que el estado del centro sea "Habilitado".
                        @endif
                    </p>
                </div>

                {{-- Acciones --}}
                <div class="flex flex-col gap-3">
                    @if($type === 'suspended')
                        <x-ui.button 
                            href="#" 
                            variant="primary" 
                            size="lg"
                            iconLeft="heroicon-s-banknotes"
                            :hoverEffect="true"
                            class="w-full bg-orvian-orange hover:bg-orvian-orange-hover border-none"
                        >
                            Regularizar Pago Ahora
                        </x-ui.button>
                    @else
                        <x-ui.button 
                            href="mailto:soporte@orvian.do" 
                            variant="secondary" 
                            size="lg"
                            iconLeft="heroicon-s-envelope"
                            class="w-full"
                        >
                            Contactar Soporte
                        </x-ui.button>
                    @endif

                    <x-ui.button 
                        href="{{ route('logout') }}" 
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        variant="secondary" 
                        type="ghost" 
                        size="sm"
                        iconLeft="heroicon-s-arrow-left-on-rectangle"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-white"
                    >
                        Cerrar Sesión
                    </x-ui.button>
                    
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </div>
            </div>

            <div class="mt-8 text-center">
                <p class="text-[10px] text-slate-400 uppercase tracking-[0.3em]">
                    Soporte Administrativo <span class="text-orvian-orange font-bold">ORVIAN</span>
                </p>
            </div>
        </div>
    </main>

    <x-ui.toasts />
    @livewireScripts
</body>
</html>