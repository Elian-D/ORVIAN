<x-modal name="profile-modal" maxWidth="2xl" focusable>
    <div class="flex flex-col md:flex-row h-full md:h-[600px] overflow-hidden bg-white dark:bg-dark-card relative">
        
        {{-- Botón de cierre explícito para móviles --}}
        <button x-on:click="$dispatch('close-modal', 'profile-modal')" 
            class="absolute top-4 right-4 z-50 p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors md:hidden">
            <x-heroicon-s-x-mark class="w-6 h-6" />
        </button>

        {{-- ── Sidebar Lateral ── --}}
        <aside class="w-full md:w-64 bg-slate-50 dark:bg-dark-bg/40 border-b md:border-b-0 md:border-r border-slate-100 dark:border-white/5 flex flex-col min-h-0">
            
            {{-- Header del Perfil --}}
            <div class="p-6 flex-shrink-0">
                <div class="relative w-24 h-24 mx-auto mb-4 flex items-center justify-center">
                    <div class="relative group">
                        <x-ui.avatar :user="auth()->user()" size="xl" showStatus />
                        
                        <label class="absolute inset-0 z-10 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 cursor-pointer">
                            <x-heroicon-s-camera class="w-6 h-6 text-white" />
                            <input type="file" wire:model="photo" class="hidden" accept="image/*">
                        </label>

                        @if(auth()->user()->avatar_path)
                            <button 
                                wire:click="removePhoto" 
                                wire:confirm="¿Estás seguro de que deseas eliminar tu foto de perfil?"
                                class="absolute -top-1 -right-1 z-30 p-1.5 bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 rounded-full text-red-500 shadow-sm hover:bg-red-50 transition-colors"
                                title="Eliminar foto">
                                <x-heroicon-s-trash class="w-3.5 h-3.5" />
                            </button>
                        @endif

                        <div wire:loading wire:target="photo" class="absolute inset-0 z-20 rounded-full bg-black/60 flex items-center justify-center">
                            <svg class="animate-spin h-6 w-6 text-white" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-white truncate">{{ $name }}</h2>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate mb-2">{{ $email }}</p>
                    
                    @if($roleName)
                        <x-ui.badge :hex="$roleColor" size="sm" class="mx-auto">
                            {{ $roleName }}
                        </x-ui.badge>
                    @endif
                </div>
            </div>

            {{-- Navegación Pestañas --}}
            <nav class="flex-1 px-3 py-3 md:py-0 space-x-2 md:space-x-0 md:space-y-1 flex flex-row md:flex-col overflow-x-auto md:overflow-y-auto custom-scroll items-center md:items-stretch">
                <button wire:click="$set('activeTab', 'personal')" 
                    class="flex-shrink-0 flex items-center gap-3 px-4 md:px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $activeTab === 'personal' ? 'bg-white dark:bg-white/5 text-orvian-orange shadow-sm border border-slate-100 dark:border-white/10' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-user class="w-4 h-4" />
                    <span class="whitespace-nowrap">Información Personal</span>
                </button>

                <button wire:click="$set('activeTab', 'security')" 
                    class="flex-shrink-0 flex items-center gap-3 px-4 md:px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $activeTab === 'security' ? 'bg-white dark:bg-white/5 text-orvian-orange shadow-sm border border-slate-100 dark:border-white/10' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-shield-check class="w-4 h-4" />
                    <span class="whitespace-nowrap">Seguridad</span>
                </button>

                <button wire:click="$set('activeTab', 'preferences')" 
                    class="flex-shrink-0 flex items-center gap-3 px-4 md:px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $activeTab === 'preferences' ? 'bg-white dark:bg-white/5 text-orvian-orange shadow-sm border border-slate-100 dark:border-white/10' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5' }}">
                    <x-heroicon-s-adjustments-vertical class="w-4 h-4" />
                    <span class="whitespace-nowrap">Preferencias</span>
                </button>
            </nav>

            <div class="p-4 border-t border-slate-100 dark:border-white/5 flex-shrink-0">
                <x-ui.button variant="secondary" size="sm" class="w-full justify-center" x-on:click="$dispatch('close-modal', 'profile-modal')">
                    Cerrar Ajustes
                </x-ui.button>
            </div>
        </aside>

        {{-- ── Contenido Principal ── --}}
        <main class="flex-1 flex flex-col min-w-0 bg-white dark:bg-dark-card">
            
            <div class="flex-1 overflow-y-auto custom-scroll p-6 md:p-8">
                
                {{-- TAB: PERSONAL --}}
                @if($activeTab === 'personal')
                    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Información Personal</h3>
                            <p class="text-xs text-slate-500">Actualiza tus datos de contacto y posición.</p>
                        </div>
                        
                        <div class="grid gap-5">
                            {{-- Email: Solo lectura --}}
                            <x-ui.forms.input
                                label="Correo electrónico"
                                value="{{ $email }}"
                                icon-left="heroicon-o-envelope"
                                readonly
                                hint="El correo no puede ser modificado por el usuario"
                            />

                            <x-ui.forms.input
                                label="Nombre completo"
                                name="name"
                                wire:model="name"
                                icon-left="heroicon-o-user"
                                :error="$errors->first('name')"
                                placeholder="Ej. María González"
                                required
                            />

                            <x-ui.forms.input
                                label="Teléfono"
                                name="phone"
                                type="tel"
                                wire:model="phone"
                                icon-left="heroicon-o-phone"
                                :error="$errors->first('phone')"
                                placeholder="Ej. 809-555-0000"
                                hint="Opcional — número de contacto interno"
                            />

                            <x-ui.forms.input
                                label="Cargo / Posición"
                                name="position"
                                wire:model="position"
                                icon-left="heroicon-o-briefcase"
                                :error="$errors->first('position')"
                                placeholder="Ej. Coordinador académico"
                                hint="Visible solo dentro del centro"
                            />
                        </div>
                    </div>
                @endif

                {{-- TAB: SEGURIDAD --}}
                @if($activeTab === 'security')
                    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Seguridad</h3>
                            <p class="text-xs text-slate-500">Cambia tu contraseña de acceso periódicamente.</p>
                        </div>
                        <div class="grid gap-5">
                            <x-ui.forms.input 
                                type="password" 
                                label="Contraseña Actual" 
                                wire:model="current_password" 
                                icon-left="heroicon-o-lock-closed"
                                :error="$errors->first('current_password')"
                                placeholder="••••••••"
                            />
                            
                            <x-ui.forms.input 
                                type="password" 
                                label="Nueva Contraseña" 
                                wire:model="password" 
                                icon-left="heroicon-o-key"
                                :error="$errors->first('password')"
                                placeholder="Mínimo 8 caracteres"
                            />
                            
                            <x-ui.forms.input 
                                type="password" 
                                label="Confirmar Nueva" 
                                wire:model="password_confirmation" 
                                icon-left="heroicon-o-check-badge"
                                placeholder="Repite la nueva contraseña"
                            />
                        </div>
                    </div>
                @endif

                {{-- TAB: PREFERENCIAS --}}
                @if($activeTab === 'preferences')
                    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Preferencias</h3>
                            <p class="text-xs text-slate-500">Configura la apariencia de la plataforma.</p>
                        </div>
                        <div class="space-y-4">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Esquema de Color</label>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach(['light' => 'Modo Claro', 'dark' => 'Modo Oscuro', 'system' => 'Seguir Sistema'] as $val => $label)
                                    <button wire:click="$set('theme', '{{ $val }}')" 
                                        @class([
                                            "flex items-center justify-between px-4 py-3 rounded-xl border text-sm transition-all text-left",
                                            "border-orvian-orange bg-orvian-orange/5 text-orvian-orange font-bold" => $theme === $val,
                                            "border-slate-100 dark:border-white/5 text-slate-600 dark:text-slate-400 hover:border-slate-200" => $theme !== $val
                                        ])>
                                        {{ $label }}
                                        @if($theme === $val) <x-heroicon-s-check-circle class="w-5 h-5" /> @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Botonera de Acción --}}
            <div class="p-6 bg-slate-50/50 dark:bg-white/[0.02] border-t border-slate-100 dark:border-white/5 flex-shrink-0 flex justify-end gap-3">
                @php
                    $action = match($activeTab) {
                        'personal' => 'savePersonal',
                        'security' => 'savePassword',
                        'preferences' => 'savePreferences'
                    };
                    $label = match($activeTab) {
                        'personal' => 'Guardar Cambios',
                        'security' => 'Actualizar',
                        'preferences' => 'Aplicar Cambios'
                    };
                @endphp
                
                <x-ui.button 
                    wire:click="{{ $action }}" 
                    variant="primary" 
                    size="md" 
                    :hoverEffect="true" 
                    wire:loading.attr="disabled" 
                    wire:target="{{ $action }}"
                    class="w-full md:w-auto">
                    <span wire:loading.remove wire:target="{{ $action }}">{{ $label }}</span>
                    <span wire:loading wire:target="{{ $action }}">Procesando...</span>
                </x-ui.button>
            </div>
        </main>
    </div>
</x-modal>