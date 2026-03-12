<x-admin>
    <x-slot:title>Panel de Control Global</x-slot:title>

    <div class="max-w-7xl mx-auto">
        {{-- Header de Bienvenida --}}
        <div class="mb-8">
            <h1 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-tight">
                App Hub <span class="text-orvian-orange">Orvian</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Gestión centralizada de instituciones y configuración global.
            </p>
        </div>

        {{-- Grid de Acciones Rápidas (Placeholder) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {{-- Card: Gestión de Escuelas --}}
            <div class="bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-white/10 rounded-3xl p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-orvian-orange/10 rounded-2xl flex items-center justify-center mb-4">
                    <x-icon name="heroicon-s-academic-cap" class="w-6 h-6 text-orvian-orange" />
                </div>
                <h3 class="font-bold text-slate-800 dark:text-white">Instituciones</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Administra las escuelas registradas, sus licencias y estados de configuración.
                </p>
                <div class="mt-4">
                    <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-400 py-1 px-2 rounded-lg font-bold uppercase">Próximamente</span>
                </div>
            </div>

            {{-- Card: Soporte Técnico --}}
            <div class="bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-white/10 rounded-3xl p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-4">
                    <x-icon name="heroicon-s-wrench-screwdriver" class="w-6 h-6 text-blue-500" />
                </div>
                <h3 class="font-bold text-slate-800 dark:text-white">Sistema</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Logs de errores, configuración de e-NCF y variables de entorno globales.
                </p>
                <div class="mt-4">
                    <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-400 py-1 px-2 rounded-lg font-bold uppercase">Próximamente</span>
                </div>
            </div>

        </div>
    </div>
</x-admin>