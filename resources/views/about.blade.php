<x-layouts::public>
    <div class="min-h-screen flex items-center justify-center px-4 pt-16">
        <div class="text-center">
            <x-ui.badge variant="primary" size="sm" :dot="false">Próximamente</x-ui.badge>
            <h1 class="mt-6 text-3xl font-black text-slate-900 dark:text-white">
                Sobre Nosotros
            </h1>
            <p class="mt-4 text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                Esta página está en construcción. Pronto encontrarás aquí al equipo detrás de ORVIAN.
            </p>
            <div class="mt-8">
                <x-ui.button variant="primary" href="{{ route('landing') }}">
                    Volver al inicio
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layouts::public>
