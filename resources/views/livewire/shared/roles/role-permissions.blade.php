<div class="max-w-7xl mx-auto p-4 md:p-6 flex flex-col gap-4">
    {{-- Header Sticky con Acciones --}}
    <div class="sticky rounded-xl top-0 z-20 bg-white dark:bg-dark-card border-b border-slate-200 dark:border-dark-border mb-6 -mx-4 px-4 py-4 sm:-mx-6 sm:px-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            {{-- Info del Rol --}}
            <div class="flex items-center gap-3 flex-wrap">
                <x-ui.badge hex="{{ $role->color }}">
                    {{ $role->name }}
                </x-ui.badge>
                
                @if($role->is_system)
                    <x-ui.badge variant="slate" :dot="false" size="sm">
                        Sistema
                    </x-ui.badge>
                @endif
                
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    Permisos
                </span>
            </div>
            
            {{-- Acciones --}}
            <div class="flex items-center gap-2">
                <x-ui.button
                    variant="secondary"
                    type="ghost"
                    size="sm"
                    href="{{ route($isGlobal ? 'admin.roles.index' : 'app.roles.index') }}"
                >
                    Cancelar
                </x-ui.button>
                
                @unless($role->is_system)
                    <x-ui.button
                        variant="primary"
                        size="sm"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <span wire:loading.remove wire:target="save">Guardar Cambios</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </x-ui.button>
                @endunless
            </div>
        </div>
    </div>
    
    {{-- Alerta de Solo Lectura --}}
    @if($role->is_system)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-xl p-4 mb-6 flex items-start gap-3">
            <x-heroicon-s-shield-check class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
            <div class="flex-1 text-sm text-amber-800 dark:text-amber-200">
                <strong>Rol Protegido del Sistema.</strong> Los permisos son de solo lectura para garantizar la estabilidad del módulo.
            </div>
        </div>
    @endif
    
    {{-- Tabs + Contenido --}}
    <div 
        x-data="{ 
            activeTab: '{{ $groupedPermissions->keys()->first() }}' 
        }"
        class="flex flex-col lg:flex-row gap-6"
    >
        {{-- Sidebar de Tabs (Izquierda en desktop, superior en mobile) --}}
        <div class="lg:w-64 flex-shrink-0">
            <nav class="space-y-1 lg:sticky lg:top-24">
                <div class="flex lg:flex-col gap-2 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0 custom-scroll">
                    @foreach($groupedPermissions as $groupSlug => $permissions)
                        @php
                            $changesCount = $this->getChangesCount($groupSlug);
                        @endphp
                        
                        <button
                            type="button"
                            @click="activeTab = '{{ $groupSlug }}'"
                            :class="activeTab === '{{ $groupSlug }}' 
                                ? 'bg-orvian-orange/10 dark:bg-orvian-orange/20 text-orvian-orange border-orvian-orange' 
                                : 'bg-white dark:bg-dark-card text-slate-700 dark:text-slate-300 border-slate-200 dark:border-dark-border hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                            class="flex-shrink-0 lg:w-full px-4 py-3 text-left text-sm font-medium rounded-lg border transition-all duration-150"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="whitespace-nowrap lg:whitespace-normal">
                                    {{ __("permission_groups.{$groupSlug}.name") }}
                                </span>
                                
                                @if($changesCount > 0)
                                    <span 
                                        class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold bg-orvian-orange text-white"
                                        wire:key="badge-{{ $groupSlug }}"
                                    >
                                        {{ $changesCount }}
                                    </span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </nav>
        </div>
        
        {{-- Contenido de Tabs --}}
        <div class="flex-1 min-w-0">
            @foreach($groupedPermissions as $groupSlug => $permissions)
                <div 
                    x-show="activeTab === '{{ $groupSlug }}'"
                    x-cloak
                    class="space-y-4"
                    wire:key="tab-content-{{ $groupSlug }}"
                >
                    {{-- Header del Grupo con Acciones Masivas --}}
                    <div class="bg-white dark:bg-dark-card rounded-xl border border-slate-200 dark:border-dark-border p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-orvian-navy dark:text-white">
                                    {{ __("permission_groups.{$groupSlug}.name") }}
                                </h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                    {{ __("permission_groups.{$groupSlug}.description") }}
                                </p>
                            </div>
                            
                            @unless($role->is_system)
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <x-ui.button
                                        variant="secondary"
                                        type="ghost"
                                        size="sm"
                                        wire:click="selectAll('{{ $groupSlug }}')"
                                    >
                                        Marcar todos
                                    </x-ui.button>
                                    
                                    <x-ui.button
                                        variant="secondary"
                                        type="ghost"
                                        size="sm"
                                        wire:click="deselectAll('{{ $groupSlug }}')"
                                    >
                                        Desmarcar todos
                                    </x-ui.button>
                                </div>
                            @endunless
                        </div>
                        
                        {{-- Lista de Permisos con Toggles --}}
                        <div class="space-y-4">
                            @foreach($permissions as $permission)
                                <div 
                                    class="pb-4 border-b border-slate-100 dark:border-dark-border last:border-0 last:pb-0"
                                    wire:key="permission-{{ $permission->id }}"
                                >
                                    <x-ui.forms.toggle
                                        wire:model.live="selectedPermissions.{{ $permission->id }}"
                                        :name="'permission_' . $permission->id"
                                        :checked="$selectedPermissions[$permission->id] ?? false"
                                        :label="trans_permission($permission->name, 'label')"
                                        :description="trans_permission($permission->name, 'description')"
                                        :disabled="$role->is_system"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>