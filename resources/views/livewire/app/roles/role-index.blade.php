<div class="p-4 md:p-6 flex flex-col gap-4">
    {{-- ── Grid de Cards de Información (Actualizado) ── --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    {{-- Card: Privilegiados --}}
    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl border border-slate-200 dark:border-white/5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
            <x-heroicon-o-shield-check class="w-6 h-6 text-amber-500" />
        </div>
        <div>
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acceso Total</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $this->stats['privileged_users'] }}
                <span class="text-xs font-normal text-slate-400 ml-1 italic">usuarios</span>
            </p>
        </div>
    </div>

    {{-- Card: Roles Propios --}}
    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl border border-slate-200 dark:border-white/5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center flex-shrink-0">
            <x-heroicon-o-swatch class="w-6 h-6 text-indigo-500" />
        </div>
        <div>
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Roles Propios</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">
                {{ $this->stats['custom_roles'] }}
                <span class="text-xs font-normal text-slate-400 ml-1 italic">creados</span>
            </p>
        </div>
    </div>

    {{-- Card: Último Cambio --}}
    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl border border-slate-200 dark:border-white/5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center flex-shrink-0">
            <x-heroicon-o-arrow-path class="w-6 h-6 text-blue-500" />
        </div>
        <div class="overflow-hidden">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Último cambio</p>
            <p class="text-lg font-bold text-slate-800 dark:text-white truncate leading-tight">
                {{ $this->stats['last_name'] }}
            </p>
            <p class="text-[10px] text-slate-400">{{ $this->stats['last_time'] }}</p>
        </div>
    </div>
</div>
    
    {{-- Page Header --}}
    <x-ui.page-header
        title="Roles y Permisos"
        description="Gestiona quién puede hacer qué dentro de la institución"
        :count="$roles->total()"
        countLabel="roles"
    >
        <x-slot:actions>
            <x-ui.button
                variant="primary"
                size="sm"
                iconLeft="heroicon-s-plus"
                href="{{ route('app.roles.create') }}"
            >
                Crear Nuevo Rol
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Data Table --}}
    <x-data-table.base-table
        :items="$roles"
        :definition="\App\Tables\App\RoleTableConfig::class"
        :visibleColumns="$visibleColumns"
        :activeChips="$this->getActiveChips()"
        :hasFilters="count(array_filter($filters)) > 0"
    >
        {{-- Slot de Filtros --}}
        <x-slot:filterSlot>
            <x-data-table.filter-container
                :activeCount="count(array_filter($filters))"
            >
                <x-data-table.filter-select
                    label="Tipo de Rol"
                    filterKey="is_system"
                    :options="[
                        'system' => 'Roles del Sistema (Base)',
                        'custom' => 'Roles Personalizados',
                    ]"
                    placeholder="Todos los tipos"
                />
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        {{-- Filas --}}
        @forelse($roles as $role)
            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors duration-150">
                
                {{-- Columna: Nombre --}}
                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <div class="flex items-center gap-2">
                        <x-ui.badge hex="{{ $role->color }}" size="sm">
                            {{ $role->name }}
                        </x-ui.badge>
                    </div>
                </x-data-table.cell>

                {{-- Columna: Usuarios (Avatar Stack) --}}
                <x-data-table.cell column="users_count" :visible="$visibleColumns">
                    <div class="flex items-center">
                        @if($role->users_count > 0)
                            <div class="flex items-center -space-x-3 overflow-hidden">
                                @foreach($role->users as $user)
                                    <x-ui.avatar 
                                        :user="$user" 
                                        size="sm" 
                                        class="ring-2 ring-white dark:ring-dark-card" 
                                        title="{{ $user->name }}"
                                    />
                                @endforeach
                            </div>
                            <span class="ml-3 text-xs font-medium text-slate-600 dark:text-slate-400">
                                {{ $role->users_count }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400 italic">Sin asignar</span>
                        @endif
                    </div>
                </x-data-table.cell>

                {{-- Columna: Tipo --}}
                <x-data-table.cell column="is_system" :visible="$visibleColumns">
                    @if($role->is_system)
                        <x-ui.badge variant="slate" :dot="false" size="sm">Sistema</x-ui.badge>
                    @else
                        <x-ui.badge variant="info" :dot="false" size="sm">Personalizado</x-ui.badge>
                    @endif
                </x-data-table.cell>

                {{-- Columna: Creado --}}
                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $role->created_at->format('d/m/Y') }}
                    </span>
                </x-data-table.cell>

                {{-- Acciones (Estilo Unificado) --}}
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        
                        {{-- Editar --}}
                        <x-ui.button 
                            variant="primary" 
                            type="ghost" 
                            size="sm"
                            icon="heroicon-o-pencil-square"
                            href="{{ route('app.roles.edit', $role) }}" 
                            title="Editar rol"
                        />

                        {{-- Gestionar Permisos --}}
                        <x-ui.button 
                            variant="success" 
                            type="ghost" 
                            size="sm"
                            icon="heroicon-o-key"
                            href="{{ route('app.roles.permissions', $role) }}" 
                            title="Matriz de permisos"
                        />

                        {{-- Duplicar --}}
                        <x-ui.button 
                            variant="info" 
                            type="ghost" 
                            size="sm"
                            icon="heroicon-o-document-duplicate"
                            wire:click="duplicate({{ $role->id }})" 
                            wire:loading.attr="disabled"
                            title="Duplicar como nuevo"
                        />

                        {{-- Eliminar o Candado --}}
                        @if(!$role->is_system)
                            <x-ui.button 
                                variant="error" 
                                type="ghost" 
                                size="sm"
                                icon="heroicon-o-trash"
                                wire:click="confirmDelete({{ $role->id }})" 
                                title="Eliminar rol"
                            />
                        @else
                            <div class="w-9 h-9 flex items-center justify-center opacity-25 cursor-not-allowed" 
                                title="Los roles de sistema están protegidos">
                                <x-heroicon-o-lock-closed class="w-4 h-4 text-slate-500" />
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="100%" class="px-6 py-12">
                    <x-ui.empty-state
                        variant="simple"
                        title="No hay roles"
                        description="Crea roles personalizados para organizar los permisos de tu escuela."
                    />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>

    {{-- Modal de Confirmación Estandarizado --}}
    <x-modal name="confirm-delete-role" maxWidth="sm">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-red-100 dark:bg-red-500/10 flex items-center justify-center">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white mb-1">¿Eliminar este rol?</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Esta acción es irreversible. Asegúrate de que no haya usuarios activos con este rol antes de proceder.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <x-ui.button 
                    variant="secondary" 
                    size="sm"
                    x-on:click="$dispatch('close-modal', 'confirm-delete-role')"
                    wire:click="$set('roleToDelete', null)">
                    Cancelar
                </x-ui.button>
                <x-ui.button 
                    variant="error" 
                    size="sm"
                    wire:click="delete"
                    x-on:click="$dispatch('close-modal', 'confirm-delete-role')"
                    wire:loading.attr="disabled">
                    Confirmar Eliminación
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>