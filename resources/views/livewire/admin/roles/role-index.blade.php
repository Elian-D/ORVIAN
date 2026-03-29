<div class="space-y-6">
    {{-- Page Header --}}
    <x-ui.page-header
        title="Roles Globales"
        description="Plantillas de roles para todas las escuelas del sistema"
        :count="$roles->total()"
        countLabel="roles"
    >
        <x-slot:actions>
            <x-ui.button
                variant="primary"
                size="sm"
                iconLeft="heroicon-s-plus"
                href="{{ route('admin.roles.create') }}"
            >
                Crear Rol Global
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Data Table --}}
    <x-data-table.base-table
        :items="$roles"
        :definition="\App\Tables\Admin\RoleTableConfig::class"
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
                        'system' => 'Roles del Sistema',
                        'custom' => 'Roles Personalizados',
                    ]"
                    placeholder="Todos los tipos"
                />
            </x-data-table.filter-container>
        </x-slot:filterSlot>

        {{-- Filas --}}
        @forelse($roles as $role)
            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors duration-150">
                {{-- Columna: Nombre (con badge) --}}
                <x-data-table.cell column="name" :visible="$visibleColumns">
                    <div class="flex items-center gap-2">
                        {{-- Badge Principal del Rol --}}
                        <x-ui.badge hex="{{ $role->color }}" size="sm">
                            {{ $role->name }}
                        </x-ui.badge>

                        {{-- Feedback de Roles Base para Escuelas --}}
                        @php
                            $schoolRequiredRoles = [
                                'School Principal', 
                                'Teacher', 
                                'Secretary', 
                                'Student', 
                                'Staff'
                            ];
                        @endphp

                        @if(in_array($role->name, $schoolRequiredRoles))
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500" 
                                title="Este rol es una plantilla base necesaria para la creación de nuevas escuelas">
                                <x-heroicon-s-cube class="w-3 h-3" />
                                Base
                            </span>
                        @endif
                    </div>
                </x-data-table.cell>

                {{-- Columna: Usuarios --}}
                <x-data-table.cell column="users_count" :visible="$visibleColumns">
                    <div class="flex items-center">
                        @if($role->users_count > 0)
                            {{-- Grupo de Avatares en Stack --}}
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

                            {{-- Contador de Texto --}}
                            <span class="ml-3 text-xs font-medium text-slate-600 dark:text-slate-400">
                                {{ $role->users_count }} {{ $role->users_count === 1 ? 'Usuario' : 'Usuarios' }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400 italic">Sin usuarios</span>
                        @endif
                    </div>
                </x-data-table.cell>

                {{-- Columna: Tipo --}}
                <x-data-table.cell column="is_system" :visible="$visibleColumns">
                    @if($role->is_system)
                        <x-ui.badge variant="slate" :dot="false" size="sm">
                            Sistema
                        </x-ui.badge>
                    @else
                        <x-ui.badge variant="info" :dot="false" size="sm">
                            Personalizado
                        </x-ui.badge>
                    @endif
                </x-data-table.cell>

                {{-- Columna: Creado --}}
                <x-data-table.cell column="created_at" :visible="$visibleColumns">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $role->created_at->format('d/m/Y') }}
                    </span>
                </x-data-table.cell>

                {{-- Acciones --}}
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                        @php
                            $isOwner = strtolower($role->name) === 'owner';
                        @endphp
                        
                        {{-- Editar Identidad --}}
                        <x-ui.button 
                            variant="primary" 
                            type="ghost" 
                            size="sm"
                            icon="heroicon-o-pencil-square"
                            href="{{ route('admin.roles.edit', $role) }}" 
                            aria-label="Editar rol"
                        />

                        {{-- Configurar Permisos (Protección: No permitir en Owner) --}}
                        @if(!$isOwner)
                            <x-ui.button 
                                variant="success" 
                                type="ghost" 
                                size="sm"
                                icon="heroicon-o-key"
                                href="{{ route('admin.roles.permissions', $role) }}" 
                                aria-label="Configurar permisos"
                            />
                        @endif

                        {{-- Duplicar (Protección: No permitir en Owner) --}}
                        @if(!$isOwner)
                            <x-ui.button 
                                variant="info" 
                                type="ghost" 
                                size="sm"
                                icon="heroicon-o-document-duplicate"
                                wire:click="duplicate({{ $role->id }})" 
                                wire:loading.attr="disabled"
                                aria-label="Duplicar rol"
                            />
                        @endif

                        {{-- Eliminar o Candado (Protección: No sistema Y No Owner) --}}
                        @if(!$role->is_system && !$isOwner)
                            <x-ui.button 
                                variant="error" 
                                type="ghost" 
                                size="sm"
                                icon="heroicon-o-trash"
                                wire:click="confirmDelete({{ $role->id }})" 
                                x-on:click="$dispatch('open-modal', 'confirm-delete-role')"
                                aria-label="Eliminar rol"
                            />
                        @else
                            <div class="w-9 h-9 flex items-center justify-center opacity-25 cursor-not-allowed" 
                                title="Este rol está protegido y no puede gestionarse ni eliminarse">
                                <x-heroicon-o-lock-closed class="w-4 h-4 text-slate-500" />
                            </div>
                        @endif
                        
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($visibleColumns) + 1 }}" class="px-6 py-12">
                    <x-ui.empty-state
                        variant="simple"
                        title="Sin resultados"
                        description="No encontramos roles que coincidan con los filtros aplicados."
                    />
                </td>
            </tr>
        @endforelse
    </x-data-table.base-table>

    {{-- Modal de Confirmación de Eliminación --}}
    <x-modal name="confirm-delete-role" maxWidth="sm">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-red-100 dark:bg-red-500/10 flex items-center justify-center">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white mb-1">Eliminar Rol</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        ¿Estás seguro de que deseas eliminar este rol? Esta acción no se puede deshacer.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <x-ui.button 
                    variant="secondary" 
                    type="solid" 
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
                    wire:loading.attr="disabled"
                    wire:target="delete">
                    Eliminar
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>

