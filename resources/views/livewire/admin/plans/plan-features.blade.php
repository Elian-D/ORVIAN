<div class="max-w-[1600px] mx-auto p-4 md:p-10">
    
    {{-- Header --}}
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.plans.index') }}" class="p-2 bg-slate-100 dark:bg-white/5 rounded-lg hover:bg-orvian-orange/10 transition-colors group">
                    <x-heroicon-m-arrow-left class="w-5 h-5 text-slate-500 group-hover:text-orvian-orange" />
                </a>
                <span class="text-xs font-black uppercase tracking-[0.3em] text-orvian-orange">Configurador de Ecosistema</span>
            </div>
            <h1 class="text-4xl font-black text-slate-800 dark:text-white tracking-tighter">
                Asignar Funcionalidades
            </h1>
        </div>

        <div class="flex items-center gap-4 bg-white dark:bg-dark-card p-2 rounded-2xl border border-slate-200 dark:border-dark-border shadow-sm">
            <x-ui.button variant="secondary" type="ghost" href="{{ route('admin.plans.index') }}">
                Descartar
            </x-ui.button>
            <x-ui.button wire:click="save" variant="primary" class="px-8">
                Guardar Cambios Permanentemente
            </x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        {{-- Columna Izquierda: Matriz de Toggles --}}
        <div class="lg:col-span-7 xl:col-span-8 space-y-8">
            @foreach($groupedFeatures as $module => $features)
                <div class="bg-white dark:bg-dark-card rounded-[2rem] border border-slate-200 dark:border-dark-border overflow-hidden shadow-sm">
                    <div class="px-8 py-5 bg-slate-50/50 dark:bg-white/[0.02] border-b border-slate-100 dark:border-dark-border flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-dark-border flex items-center justify-center shadow-sm">
                                <x-dynamic-component :component="$features->first()->getIcon()" class="w-5 h-5 text-orvian-orange" />
                            </div>
                            <h3 class="text-lg font-black text-slate-700 dark:text-white tracking-tight">{{ $module }}</h3>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $features->count() }} Disponibles</span>
                    </div>

                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6">
                        @foreach($features as $feature)
                            <x-ui.forms.toggle 
                                wire:model.live="selectedFeatures.{{ $feature->id }}"
                                :name="'feat_'.$feature->id"
                                :id="'feat_'.$feature->id"
                                :label="$feature->name"
                                description="Módulo de {{ $module }}"
                            />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Columna Derecha: Preview Sticky --}}
        <div class="lg:col-span-5 xl:col-span-4">
            <div class="sticky top-10">
                <div class="mb-6 flex items-center justify-between px-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Vista Previa</h2>
                    <div class="flex gap-1 white px-3 py-1 rounded-full items-center">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-[10px] font-bold text-emerald-500 uppercase">En Vivo</span>
                    </div>
                </div>

                {{-- Inyectamos las features seleccionadas dinámicamente al componente plan-card --}}
                @php
                    // Clonamos el plan para no afectar el objeto original y le asignamos las features de la preview
                    $previewPlan = clone $plan;
                    $previewPlan->setRelation('features', $previewFeatures);
                @endphp

                <x-ui.plan-card :plan="$previewPlan" :showActions="false">
                    <div class="mt-4 p-4 rounded-2xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/30">
                        <p class="text-[11px] text-blue-600 dark:text-blue-400 font-medium text-center">
                            Esta es una representación visual de cómo aparecerá este plan en la landing page y el panel de suscripción.
                        </p>
                    </div>
                </x-ui.plan-card>
            </div>
        </div>
    </div>
</div>