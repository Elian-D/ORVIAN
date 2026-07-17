<x-public-layout :title="'Página no encontrada | ' . config('app.name')">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <div class="font-etna text-[120px] sm:text-[180px] leading-none text-slate-100 dark:text-white/5 select-none">
            404
        </div>
        <div class="mt-[-2rem] mb-6 w-16 h-16 rounded-2xl bg-orvian-orange/10 flex items-center justify-center">
            <x-heroicon-o-map class="w-8 h-8 text-orvian-orange" />
        </div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Página no encontrada</h1>
        <p class="text-slate-500 dark:text-slate-400 max-w-md leading-relaxed mb-8">
            La dirección que buscas no existe o fue movida. Verifica la URL o regresa al inicio.
        </p>
        <div class="flex items-center gap-3 flex-wrap justify-center">
            <x-ui.button onclick="if(history.length > 1) history.back();" variant="secondary" type="outline" iconLeft="heroicon-o-arrow-left">
                Volver
            </x-ui.button>
            <x-ui.button href="{{ Auth::check() ? route('app.dashboard') : route('landing') }}" variant="primary" iconLeft="heroicon-s-home">
                Ir al Inicio
            </x-ui.button>
        </div>
    </div>
</x-public-layout>
