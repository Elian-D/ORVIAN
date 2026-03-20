<div>

    {{-- ══════════════════════════════════════════════════
         BARRA DE MÓDULO — sticky bajo el navbar principal
         Título, límite del plan y acción primaria.
         No hay page-header dentro del contenido.
    ═══════════════════════════════════════════════════ --}}
    <x-app.module-toolbar
        title="Usuarios"
        :subtitle="$users->total() . ' en total'">

        <x-slot:actions>
            {{-- Badge de límite del plan --}}
            @if($limit > 0)
                <x-ui.badge
                    :variant="$pct >= 100 ? 'error' : ($pct >= 80 ? 'warning' : 'slate')"
                    size="sm"
                    :dot="false">
                    {{ $total }} / {{ $limit }} usuarios
                </x-ui.badge>
            @endif

            {{-- Botón desactivado si se alcanzó el límite --}}
            <x-ui.button
                variant="primary"
                size="sm"
                iconLeft="heroicon-s-plus"
                wire:click="create"
                :disabled="$atLimit">
                Nuevo Usuario
            </x-ui.button>
        </x-slot:actions>

    </x-app.module-toolbar>

    {{-- ══════════════════════════════════════════════════
         CONTENIDO — padding para separarse del toolbar
    ═══════════════════════════════════════════════════ --}}
    <div class="p-4 md:p-6 flex flex-col gap-4">

        {{-- Aviso si se alcanzó el límite --}}
        @if($atLimit)
            <div class="flex items-start gap-3 px-4 py-3 rounded-xl
                        bg-amber-500/5 border border-amber-500/20">
                <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" />
                <p class="text-xs text-amber-500/90">
                    Has alcanzado el límite de
                    <strong>{{ $limit }} usuarios</strong> de tu plan.
                    Para agregar más, actualiza tu plan desde Configuración.
                </p>
            </div>
        @endif

        {{-- ── DataTable ──────────────────────────────── --}}
        <x-data-table.base-table
            :items="$users"
            :definition="\App\Tables\App\TenantUserTableConfig::class"
            :visibleColumns="$visibleColumns"
            :activeChips="$this->getActiveChips()"
            :hasFilters="count(array_filter($filters)) > 0">

            <x-slot:filterSlot>
                <x-data-table.filter-container :activeCount="count(array_filter($filters))">

                    <x-data-table.filter-select
                        label="Rol"
                        filterKey="role"
                        :options="$roleOptions"
                        placeholder="Todos los roles" />

                    <x-data-table.filter-select
                        label="Estado"
                        filterKey="status"
                        :options="[
                            'online'  => 'En línea',
                            'away'    => 'Ausente',
                            'busy'    => 'Ocupado',
                            'offline' => 'Desconectado',
                        ]"
                        placeholder="Todos los estados" />

                </x-data-table.filter-container>
            </x-slot:filterSlot>

            {{-- Filas --}}
            @forelse($users as $user)
                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.03] transition-colors duration-150">

                    <x-data-table.cell column="name" :visible="$visibleColumns">
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :user="$user" size="sm" :showStatus="true" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                        <span class="text-[10px] font-normal text-slate-400 dark:text-slate-500 ml-1">
                                            (tú)
                                        </span>
                                    @endif
                                </p>
                                @if($user->position)
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate">
                                        {{ $user->position }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </x-data-table.cell>

                    <x-data-table.cell column="email" :visible="$visibleColumns">
                        <span class="text-sm text-slate-600 dark:text-slate-300 truncate max-w-[200px] block"
                              title="{{ $user->email }}">
                            {{ $user->email }}
                        </span>
                    </x-data-table.cell>

                    <x-data-table.cell column="role" :visible="$visibleColumns">
                        @php $roleName = $user->getRoleNames()->first() ?? '—' @endphp
                        @if($roleName !== '—')
                            <x-ui.badge
                                :variant="\App\Livewire\App\Users\UserIndex::roleBadgeVariant($roleName)"
                                size="sm" :dot="false">
                                {{ $roleName }}
                            </x-ui.badge>
                        @else
                            <span class="text-slate-400 dark:text-slate-600 text-sm">—</span>
                        @endif
                    </x-data-table.cell>

                    <x-data-table.cell column="status" :visible="$visibleColumns">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full flex-shrink-0
                                {{ \App\Livewire\App\Users\UserIndex::statusColor($user->status ?? 'offline') }}">
                            </span>
                            <span class="text-sm text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ \App\Livewire\App\Users\UserIndex::statusLabel($user->status ?? 'offline') }}
                            </span>
                        </div>
                    </x-data-table.cell>

                    <x-data-table.cell column="last_login_at" :visible="$visibleColumns">
                        <span class="text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '—' }}
                        </span>
                    </x-data-table.cell>

                    <x-data-table.cell column="position" :visible="$visibleColumns">
                        <span class="text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">
                            {{ $user->position ?? '—' }}
                        </span>
                    </x-data-table.cell>

                    <td class="px-4 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <x-ui.button
                                variant="secondary"
                                type="solid"
                                size="sm"
                                icon="heroicon-o-pencil-square"
                                wire:click="edit({{ $user->id }})" />

                            @if($user->id !== auth()->id())
                                <x-ui.button
                                    variant="error"
                                    type="solid"
                                    size="sm"
                                    icon="heroicon-o-trash"
                                    wire:click="confirmDelete({{ $user->id }})" />
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
                            description="No encontramos usuarios que coincidan con los filtros aplicados." />
                    </td>
                </tr>
            @endforelse

        </x-data-table.base-table>
    </div>


    {{-- ── Modal: Formulario ──────────────────────────────────── --}}
    <x-modal name="user-form" maxWidth="lg" focusable>
        <div class="px-6 py-5 bg-white dark:bg-dark-card">

            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-bold text-slate-800 dark:text-white">
                    {{ $isEditing ? 'Editar usuario' : 'Nuevo usuario' }}
                </h2>
                <button x-on:click="$dispatch('close-modal', 'user-form')"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300
                               hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                    <x-heroicon-s-x-mark class="w-4 h-4" />
                </button>
            </div>

            <div class="space-y-5">

                <x-ui.forms.input label="Nombre completo" name="name" wire:model="name"
                    icon-left="heroicon-o-user" :error="$errors->first('name')"
                    placeholder="Ej. María González" required />

                <x-ui.forms.input label="Correo electrónico" name="email" type="email"
                    wire:model="email" icon-left="heroicon-o-envelope"
                    :error="$errors->first('email')" placeholder="usuario@centro.edu.do" required />

                <div x-data="{ show: false }" class="flex flex-col group">
                    <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                                  text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                        Contraseña
                        @if(!$isEditing)
                            <span class="text-state-error ml-0.5">*</span>
                        @else
                            <span class="font-normal normal-case tracking-normal ml-1 text-slate-400 dark:text-slate-600">
                                — dejar vacío para no cambiar
                            </span>
                        @endif
                    </label>
                    <div class="relative flex items-center">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none
                                     text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                            <x-heroicon-o-lock-closed class="w-5 h-5" />
                        </span>
                        <input :type="show ? 'text' : 'password'" wire:model="password"
                               placeholder="{{ $isEditing ? 'Nueva contraseña (opcional)' : 'Mínimo 8 caracteres' }}"
                               class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                      rounded-none pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white
                                      placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors" />
                        <button type="button" @click="show = !show"
                                class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5
                                       text-slate-400 hover:text-orvian-orange transition-colors">
                            <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                            <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" style="display:none;" />
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-state-error">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.forms.select label="Rol" name="role" wire:model="role"
                    icon-left="heroicon-o-shield-check" :error="$errors->first('role')" required>
                    <option value="">Seleccionar rol...</option>
                    @foreach($globalRoles as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                </x-ui.forms.select>

                <x-ui.forms.input label="Cargo / Posición" name="position" wire:model="position"
                    icon-left="heroicon-o-briefcase" :error="$errors->first('position')"
                    placeholder="Ej. Docente de Matemáticas" />

            </div>

            <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-slate-100 dark:border-dark-border">
                <x-ui.button variant="secondary" type="outline" size="sm"
                    x-on:click="$dispatch('close-modal', 'user-form')">
                    Cancelar
                </x-ui.button>
                <x-ui.button variant="primary" size="sm"
                    wire:click="save" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        {{ $isEditing ? 'Guardar cambios' : 'Crear usuario' }}
                    </span>
                    <span wire:loading wire:target="save">Guardando...</span>
                </x-ui.button>
            </div>

        </div>
    </x-modal>

    {{-- ── Modal: Confirmar eliminación ──────────────────────── --}}
    <x-modal name="confirm-delete" maxWidth="sm">
        <div class="px-6 py-5 bg-white dark:bg-dark-card">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-red-100 dark:bg-red-500/10
                            flex items-center justify-center">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white mb-1">Eliminar usuario</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Esta acción eliminará la cuenta del usuario del centro. No puede deshacerse.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <x-ui.button variant="secondary" type="outline" size="sm"
                    x-on:click="$dispatch('close-modal', 'confirm-delete')">
                    Cancelar
                </x-ui.button>
                <x-ui.button variant="error" size="sm"
                    wire:click="delete($wire.deletingId)"
                    x-on:click="$dispatch('close-modal', 'confirm-delete')"
                    wire:loading.attr="disabled">
                    Eliminar
                </x-ui.button>
            </div>
        </div>
    </x-modal>

</div>