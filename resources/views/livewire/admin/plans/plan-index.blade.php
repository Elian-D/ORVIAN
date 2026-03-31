<div>
    <x-ui.page-header 
        title="Planes y Membresías" 
        description="Define la oferta comercial y límites de uso del SaaS." 
        :count="$plans->count()" 
        countLabel="planes"
    >
        <x-slot:actions>
            <x-ui.button 
                variant="primary" 
                size="sm" 
                iconLeft="heroicon-s-plus" 
                wire:click="openCreate"
            >
                Nuevo Plan
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- GRID DE PLANES --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($plans as $plan)
            <div @class([
                'relative flex flex-col p-6 rounded-3xl transition-all duration-300 border-2',
                'bg-white dark:bg-dark-card border-slate-100 dark:border-white/5 shadow-sm hover:shadow-xl' => !$plan->is_featured,
                'bg-white dark:bg-dark-card border-orvian-orange shadow-orvian-orange/10 shadow-2xl scale-[1.02] z-10' => $plan->is_featured,
            ])>
                
                {{-- Badge de Estado: Posicionado en el medio abajo --}}
                <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 bg-white dark:bg-dark-card px-3 py-1 rounded-xl border border-slate-200 dark:border-dark-border shadow-md">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Estado</span>
                    <x-ui.badge 
                        variant="{{ $plan->is_active ? 'success' : 'error' }}" 
                        size="sm" 
                        dot
                        class="font-black text-[10px] py-0 px-1"
                    >
                        {{ $plan->is_active ? 'Activo' : 'Inactivo' }}
                    </x-ui.badge>
                </div>

                @if($plan->is_featured)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-orvian-orange text-white text-[10px] font-bold uppercase tracking-widest px-4 py-1 rounded-full shadow-lg">
                        Más Popular
                    </div>
                @endif

                {{-- HEADER DEL PLAN (Sin cambios) --}}
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 rounded-2xl" style="background-color: {{ $plan->bg_color }}20">
                        <x-heroicon-o-rocket-launch class="w-6 h-6" style="color: {{ $plan->bg_color }}" />
                    </div>
                    <x-ui.badge hex="{{ $plan->text_color }}" size="sm" :dot="false">
                        {{ $plan->const_name }}
                    </x-ui.badge>
                </div>

                <h3 class="text-xl font-black text-slate-800 dark:text-white">{{ $plan->name }}</h3>
                <div class="flex items-baseline gap-1 mt-2">
                    <span class="text-3xl font-black text-slate-900 dark:text-white">
                        USD$ {{ number_format($plan->price, 0) }}
                    </span>
                    <span class="text-sm text-slate-400 font-medium">/ mes</span>
                </div>

                {{-- LÍMITES --}}
                <div class="mt-6 space-y-3 py-6 border-y border-slate-50 dark:border-white/5">
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-5 h-5 rounded-full bg-green-500/10 flex items-center justify-center">
                            <x-heroicon-s-check class="w-3 h-3 text-green-500" />
                        </div>
                        <span class="text-slate-600 dark:text-slate-300">
                            <b>{{ $plan->limit_students }}</b> Estudiantes máx.
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-5 h-5 rounded-full bg-green-500/10 flex items-center justify-center">
                            <x-heroicon-s-check class="w-3 h-3 text-green-500" />
                        </div>
                        <span class="text-slate-600 dark:text-slate-300">
                            <b>{{ $plan->limit_users }}</b> Usuarios Staff
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-5 h-5 rounded-full bg-blue-500/10 flex items-center justify-center">
                            <x-heroicon-s-academic-cap class="w-3 h-3 text-blue-500" />
                        </div>
                        <span class="text-slate-600 dark:text-slate-300 font-bold">
                            {{ $plan->schools_count }} Escuelas activas
                        </span>
                    </div>
                </div>

                {{-- ACCIONES DE LA CARD --}}
                <div class="mt-6 flex items-center gap-2">
                    <x-ui.button 
                        variant="secondary" 
                        type="ghost" 
                        size="sm" 
                        class="flex-1" 
                        wire:click="edit({{ $plan->id }})"
                    >
                        Configurar
                    </x-ui.button>
                    
                    @if($plan->schools_count === 0)
                        <x-ui.button 
                            variant="error" 
                            type="ghost"
                            size="sm" 
                            wire:click="deletePlan({{ $plan->id }})"
                            wire:confirm="¿Estás seguro de eliminar este plan? Esta acción no se puede deshacer."
                        >
                            <x-heroicon-s-trash class="w-4 h-4" />
                        </x-ui.button>
                    @endif

                    <x-ui.button 
                        variant="info" 
                        size="sm" 
                        iconLeft="heroicon-s-puzzle-piece"
                        href="{{ route('admin.plans.features', $plan->id) }}"
                    >
                        Módulos
                    </x-ui.button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- SLIDE-OVER PANEL (Crear/Editar) --}}
    <div 
        x-data="{ open: @entangle('showPanel') }" 
        x-show="open" 
        class="fixed inset-0 z-[60] overflow-hidden" 
        style="display: none;"
    >
        <div 
            class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
            x-show="open" 
            x-transition:enter="ease-in-out duration-500" 
            x-transition:enter-start="opacity-0" 
            x-transition:enter-end="opacity-100" 
            x-transition:leave="ease-in-out duration-500" 
            x-transition:leave-start="opacity-100" 
            x-transition:leave-end="opacity-0"
            wire:click="$set('showPanel', false)"
        ></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div 
                class="pointer-events-auto w-screen max-w-md transform transition duration-500 ease-in-out sm:duration-700"
                x-show="open" 
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" 
                x-transition:enter-start="translate-x-full" 
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" 
                x-transition:leave-start="translate-x-0" 
                x-transition:leave-end="translate-x-full"
            >
                <div class="flex h-full flex-col bg-white dark:bg-dark-card shadow-2xl border-l border-slate-100 dark:border-white/5">
                    <div class="px-6 py-6 border-b border-slate-100 dark:border-white/5">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                {{ $isEditing ? 'Editar Plan' : 'Crear Nuevo Plan' }}
                            </h2>
                            <button 
                                wire:click="$set('showPanel', false)" 
                                class="text-slate-400 hover:text-slate-600"
                            >
                                <x-heroicon-s-x-mark class="w-6 h-6" />
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                        {{-- PREVISUALIZACIÓN DINÁMICA --}}
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-white/5 space-y-3">
                            <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                Previsualización de Badge
                            </span>
                            <div>
                                <x-ui.badge hex="{{ $bg_color }}" :dot="false" size="md">
                                    <span style="color: {{ $text_color }}">
                                        {{ $name ?: 'Nombre del Plan' }}
                                    </span>
                                </x-ui.badge>
                            </div>
                        </div>

                        <div class="grid gap-6">
                            <x-ui.forms.input 
                                label="Nombre Comercial" 
                                wire:model.live="name" 
                                placeholder="Ej: Enterprise" 
                                :error="$errors->first('name')"
                                required 
                            />
                            
                            <div class="grid grid-cols-2 gap-4">
                                <x-ui.forms.input 
                                    label="Slug" 
                                    wire:model="slug" 
                                    readonly 
                                    disabled 
                                    hint="Automático" 
                                />
                                <x-ui.forms.input 
                                    label="Constant" 
                                    wire:model="const_name" 
                                    readonly 
                                    disabled 
                                    hint="Uso interno" 
                                />
                            </div>

                            <x-ui.forms.input 
                                label="Precio Mensual (USD$)" 
                                type="number" 
                                wire:model="price" 
                                iconLeft="heroicon-o-currency-dollar"
                                :error="$errors->first('price')"
                                required
                            />

                            <div class="grid grid-cols-2 gap-4">
                                <x-ui.forms.input 
                                    label="Máx. Estudiantes" 
                                    type="number" 
                                    wire:model="limit_students" 
                                    :error="$errors->first('limit_students')" 
                                />
                                <x-ui.forms.input 
                                    label="Máx. Usuarios" 
                                    type="number" 
                                    wire:model="limit_users" 
                                    :error="$errors->first('limit_users')" 
                                />
                            </div>

                            <div class="p-4 border border-slate-100 dark:border-white/5 rounded-2xl space-y-4">
                                <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                    Identidad Visual
                                </span>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2">
                                            Fondo del Badge
                                        </label>
                                        <input 
                                            type="color" 
                                            wire:model.live="bg_color" 
                                            class="w-full h-10 rounded-lg cursor-pointer bg-transparent border-0"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-400 uppercase mb-2">
                                            Color del Texto
                                        </label>
                                        <input 
                                            type="color" 
                                            wire:model.live="text_color" 
                                            class="w-full h-10 rounded-lg cursor-pointer bg-transparent border-0"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 pt-2">
                                {{-- CAMBIO: Agregar .live para sincronización inmediata --}}
                                <x-ui.forms.toggle 
                                    label="Plan Destacado" 
                                    wire:model.live="is_featured"
                                    :checked="$is_featured"
                                    description="Se mostrará con diseño especial y tag de 'Recomendado'" 
                                />
                                
                                <x-ui.forms.toggle 
                                    label="Plan Activo" 
                                    wire:model.live="is_active"
                                    :checked="$is_active"
                                    description="Si se desactiva, no aparecerá para nuevas escuelas" 
                                />
                                
                                @error('is_active')
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-6 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/[0.02]">
                        <x-ui.button 
                            variant="primary" 
                            size="md" 
                            class="w-full" 
                            wire:click="save" 
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $isEditing ? 'Guardar Cambios' : 'Crear Plan' }}
                            </span>
                            <span wire:loading wire:target="save">Procesando...</span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>