<div class="max-w-2xl mx-auto space-y-6 p-4 md:p-6 flex flex-col gap-4">
    {{-- Header --}}
    <div>
        <h2 class="text-2xl font-bold text-orvian-navy dark:text-white">
            {{ $role ? 'Editar Rol' : 'Crear Rol' }}
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{ $isGlobal ? 'Rol global para todas las escuelas' : 'Rol específico de tu escuela' }}
        </p>
    </div>

    {{-- Alerta para roles del sistema --}}
    @if($role?->is_system)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <div class="flex gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-amber-900 dark:text-amber-200">
                        Rol Protegido del Sistema
                    </h4>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        Este es un rol del sistema. Solo puedes modificar su color, no su nombre.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Formulario --}}
    <form wire:submit="save" class="space-y-6">
        {{-- Tarjeta de Identidad Visual --}}
        <div class="bg-white dark:bg-dark-card rounded-xl border border-slate-200 dark:border-dark-border p-6 space-y-6">
            <h3 class="text-sm font-bold text-orvian-navy dark:text-white uppercase tracking-tight">
                Identidad Visual
            </h3>

            <div class="grid md:grid-cols-2 gap-6">
                {{-- Nombre --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Nombre del Rol
                    </label>
                    <input
                        type="text"
                        id="name"
                        wire:model.live="name"
                        @disabled($role?->is_system)
                        class="w-full px-3 py-2 border border-slate-200 dark:border-dark-border rounded-lg
                               bg-white dark:bg-dark-card text-slate-900 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-orvian-orange/50
                               disabled:bg-slate-50 dark:disabled:bg-slate-800 disabled:cursor-not-allowed"
                        placeholder="ej. Coordinador de Eventos"
                    />
                    @error('name')
                        <span class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Color --}}
                {{-- Color Identificador Personalizado --}}
                <div x-data="{ 
                    selectedColor: @entangle('color').live, 
                    presets: [
                        '#64748B', '#EF4444', '#F97316', '#F59E0B', '#10B981', 
                        '#06B6D4', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899'
                    ]
                }">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                        Color Identificador
                    </label>
                    
                    <div class="space-y-4">
                        {{-- Grid de Colores Predefinidos --}}
                        <div class="flex flex-wrap gap-3">
                            <template x-for="color in presets" :key="color">
                                <button 
                                    type="button"
                                    @click="selectedColor = color"
                                    :style="`background-color: ${color}`"
                                    class="w-8 h-8 rounded-full border-2 transition-all duration-200 transform hover:scale-110"
                                    :class="selectedColor === color 
                                        ? 'border-slate-900 dark:border-white ring-2 ring-offset-2 ring-slate-400 dark:ring-offset-dark-card' 
                                        : 'border-transparent dark:border-dark-border'"
                                    :title="color"
                                ></button>
                            </template>

                            {{-- Selector Personalizado (Círculo) --}}
                            <div class="relative w-8 h-8">
                                <input
                                    type="color"
                                    x-model="selectedColor"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                />
                                <div 
                                    class="w-full h-full rounded-full border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center text-slate-400"
                                    :style="!presets.includes(selectedColor.toUpperCase()) ? `background-color: ${selectedColor}; border-style: solid; border-color: white;` : ''"
                                >
                                    <template x-if="presets.includes(selectedColor.toUpperCase())">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Input Manual --}}
                        <div class="flex items-center gap-2 max-w-[180px]">
                            <span class="text-slate-400 font-mono text-sm">#</span>
                            <input
                                type="text"
                                x-model="selectedColor"
                                @input="if($event.target.value.charAt(0) === '#') $event.target.value = $event.target.value.substring(1)"
                                class="flex-1 px-3 py-1.5 border border-slate-200 dark:border-dark-border rounded-lg
                                    bg-white dark:bg-dark-card text-slate-900 dark:text-white font-mono text-sm
                                    focus:outline-none focus:ring-2 focus:ring-orvian-orange/50 uppercase"
                                placeholder="64748B"
                            />
                        </div>
                    </div>
                    
                    @error('color')
                        <span class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Preview en Vivo --}}
            <div class="pt-4 border-t border-slate-100 dark:border-dark-border">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Vista Previa en Vivo:</p>
                <div class="flex items-center gap-4 flex-wrap">
                    <x-ui.badge :hex="$color" size="sm">
                        {{ $name ?: 'Nombre del Rol' }}
                    </x-ui.badge>
                    <x-ui.badge :hex="$color">
                        {{ $name ?: 'Nombre del Rol' }}
                    </x-ui.badge>
                    <x-ui.badge :hex="$color" :dot="false">
                        {{ $name ?: 'Nombre del Rol' }}
                    </x-ui.badge>
                </div>
            </div>
        </div>

        {{-- Botones --}}
        <div class="flex justify-end gap-3">
            
            <a    href="{{ route($isGlobal ? 'admin.roles.index' : 'app.roles.index') }}"
                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300
                       hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="px-4 py-2 text-sm font-medium text-white bg-orvian-orange
                       hover:bg-orvian-orange/90 rounded-lg transition-colors"
            >
                {{ $role ? 'Guardar Cambios' : 'Crear y Continuar' }}
            </button>
        </div>
    </form>
</div>