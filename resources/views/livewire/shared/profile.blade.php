<div class="min-h-screen bg-slate-50 dark:bg-dark-bg">
    <div class="max-w-4xl mx-auto px-4 py-10">

        {{-- ── Header ──────────────────────────────────────────── --}}
        <div class="mb-8">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white">Mi Perfil</h1>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5">
                Administra tu información personal y seguridad de la cuenta.
            </p>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">

            {{-- ── Columna izquierda: avatar + nav ─────────────── --}}
            <aside class="w-full lg:w-60 flex-shrink-0 flex flex-col gap-4">

                {{-- Tarjeta de avatar --}}
                <div class="bg-white dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border p-6 flex flex-col items-center gap-4">

                    <div class="relative group">
                        <x-ui.avatar :user="auth()->user()" size="xl" showStatus />

                        @if(auth()->user()->avatar_path)
                            <button
                                wire:click="removePhoto"
                                wire:confirm="¿Eliminar foto de perfil?"
                                class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover:opacity-100
                                       transition-opacity flex items-center justify-center cursor-pointer">
                                <x-heroicon-o-trash class="w-5 h-5 text-white" />
                            </button>
                        @endif
                    </div>

                    {{-- Info básica --}}
                    <div class="text-center">
                        <p class="text-sm font-semibold text-slate-800 dark:text-white leading-tight">
                            {{ auth()->user()->name }}
                        </p>
                        @if(auth()->user()->position)
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                {{ auth()->user()->position }}
                            </p>
                        @endif
                        <div class="mt-2">
                            <x-ui.badge variant="info" size="sm">
                                {{ auth()->user()->getRoleNames()->first() ?? 'Sin rol' }}
                            </x-ui.badge>
                        </div>
                    </div>

                    {{-- Upload foto --}}
                    <div class="w-full">
                        <label class="flex items-center justify-center gap-2 w-full cursor-pointer
                                      text-[11px] font-bold uppercase tracking-wider text-slate-400
                                      hover:text-orvian-orange dark:text-slate-500 dark:hover:text-orvian-orange
                                      border border-dashed border-slate-200 dark:border-dark-border
                                      rounded-xl py-2.5 transition-colors">
                            <x-heroicon-o-camera class="w-4 h-4" />
                            <span>Cambiar foto</span>
                            <input type="file" class="sr-only"
                                   wire:model="photo"
                                   accept="image/jpg,image/jpeg,image/png,image/webp" />
                        </label>

                        <div wire:loading wire:target="photo" class="mt-2 text-center text-[11px] text-state-info font-medium">
                            Subiendo...
                        </div>

                        @if($photo)
                            <div class="mt-3 flex flex-col items-center gap-2">
                                <img src="{{ $photo->temporaryUrl() }}"
                                     class="w-16 h-16 rounded-full object-cover ring-2 ring-orvian-orange/30"
                                     alt="Preview">
                                <x-ui.button wire:click="savePhoto" size="sm" variant="primary">
                                    Guardar foto
                                </x-ui.button>
                            </div>
                        @endif

                        @error('photo')
                            <p class="mt-1.5 text-[11px] text-state-error text-center">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Navegación --}}
                <nav class="bg-white dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border overflow-hidden">
                    @foreach([
                        'personal' => ['icon' => 'heroicon-o-user',       'label' => 'Información personal'],
                        'security' => ['icon' => 'heroicon-o-lock-closed', 'label' => 'Seguridad'],
                        'preferences' => ['icon' => 'heroicon-o-paint-brush',    'label' => 'Preferencias visuales'],
                    ] as $tab => $item)
                        <button
                            wire:click="$set('activeTab', '{{ $tab }}')"
                            @class([
                                'w-full flex items-center gap-3 px-4 py-3 text-left text-sm transition-colors border-l-2',
                                'border-orvian-orange text-orvian-orange bg-orvian-orange/5 font-semibold' => $activeTab === $tab,
                                'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-white/5' => $activeTab !== $tab,
                            ])>
                            <x-dynamic-component :component="$item['icon']" class="w-4 h-4 flex-shrink-0" />
                            {{ $item['label'] }}
                        </button>
                    @endforeach
                </nav>

            </aside>

            {{-- ── Columna derecha: formularios ────────────────── --}}
            <main class="flex-1 min-w-0">

                {{-- ── Tab: Información Personal ──────────────── --}}
                @if($activeTab === 'personal')
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border">

                        <div class="px-6 py-5 border-b border-slate-100 dark:border-dark-border">
                            <h2 class="text-sm font-bold text-slate-800 dark:text-white">Información personal</h2>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                Tu nombre y datos de contacto dentro de la plataforma.
                            </p>
                        </div>

                        <div class="px-6 py-6 flex flex-col gap-6">

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

                            {{-- Correo: editable en admin, solo lectura en app --}}
                            @if($isAdmin)
                                <x-ui.forms.input
                                    label="Correo electrónico"
                                    name="email"
                                    type="email"
                                    wire:model="email"
                                    icon-left="heroicon-o-envelope"
                                    :error="$errors->first('email')"
                                    placeholder="correo@dominio.com"
                                    hint="Cambia con cuidado — se usa para iniciar sesión"
                                    required
                                />
                            @else
                                <div class="flex flex-col">
                                    <p class="text-[11px] font-bold uppercase tracking-wider mb-2
                                              text-slate-400 dark:text-slate-500">
                                        Correo electrónico
                                    </p>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-slate-300 dark:text-slate-600">
                                            <x-heroicon-o-envelope class="w-5 h-5" />
                                        </span>
                                        <input
                                            type="email"
                                            value="{{ auth()->user()->email }}"
                                            readonly
                                            class="w-full border-0 border-b border-slate-100 dark:border-dark-border bg-transparent
                                                   rounded-none pl-7 py-3 text-sm text-slate-400 dark:text-slate-600
                                                   cursor-not-allowed focus:ring-0 focus:outline-none"
                                        />
                                        <span class="absolute right-0">
                                            <x-ui.badge variant="default" size="sm">No editable</x-ui.badge>
                                        </span>
                                    </div>
                                    <p class="mt-1.5 text-[11px] text-slate-400 dark:text-slate-600">
                                        Para cambiar el correo, contacta al soporte técnico.
                                    </p>
                                </div>
                            @endif

                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 dark:border-dark-border flex justify-end">
                            <x-ui.button
                                wire:click="savePersonal"
                                variant="primary"
                                wire:loading.attr="disabled"
                                wire:target="savePersonal">
                                <span wire:loading.remove wire:target="savePersonal">Guardar cambios</span>
                                <span wire:loading wire:target="savePersonal">Guardando...</span>
                            </x-ui.button>
                        </div>

                    </div>
                @endif

                {{-- ── Tab: Seguridad ──────────────────────────── --}}
                @if($activeTab === 'security')
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border">

                        <div class="px-6 py-5 border-b border-slate-100 dark:border-dark-border">
                            <h2 class="text-sm font-bold text-slate-800 dark:text-white">Cambiar contraseña</h2>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                Usa una contraseña de al menos 8 caracteres con letras y números.
                            </p>
                        </div>

                        <div class="px-6 py-6 flex flex-col gap-6">

                            <div x-data="{ show: false }" class="flex flex-col group">
                                <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                                              text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                                    Contraseña actual <span class="text-state-error ml-0.5">*</span>
                                </label>
                                <div class="relative flex items-center">
                                    <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none
                                                 text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                        <x-heroicon-o-lock-closed class="w-5 h-5" />
                                    </span>
                                    <input
                                        :type="show ? 'text' : 'password'"
                                        wire:model="current_password"
                                        placeholder="Tu contraseña actual"
                                        class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                               rounded-none pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white
                                               placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors"
                                    />
                                    <button type="button" @click="show = !show"
                                            class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5
                                                   text-slate-400 hover:text-orvian-orange transition-colors">
                                        <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                        <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" style="display:none;" />
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="mt-1.5 text-xs text-state-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="border-t border-slate-100 dark:border-dark-border"></div>

                            <div x-data="{ show: false }" class="flex flex-col group">
                                <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                                              text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                                    Nueva contraseña <span class="text-state-error ml-0.5">*</span>
                                </label>
                                <div class="relative flex items-center">
                                    <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none
                                                 text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                        <x-heroicon-o-key class="w-5 h-5" />
                                    </span>
                                    <input
                                        :type="show ? 'text' : 'password'"
                                        wire:model="password"
                                        placeholder="Mínimo 8 caracteres"
                                        class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                               rounded-none pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white
                                               placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors"
                                    />
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

                            <div x-data="{ show: false }" class="flex flex-col group">
                                <label class="text-[11px] font-bold uppercase tracking-wider mb-2
                                              text-slate-400 dark:text-slate-500 group-focus-within:text-orvian-orange transition-colors">
                                    Confirmar contraseña <span class="text-state-error ml-0.5">*</span>
                                </label>
                                <div class="relative flex items-center">
                                    <span class="absolute left-0 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none
                                                 text-slate-400 group-focus-within:text-orvian-orange transition-colors">
                                        <x-heroicon-o-key class="w-5 h-5" />
                                    </span>
                                    <input
                                        :type="show ? 'text' : 'password'"
                                        wire:model="password_confirmation"
                                        placeholder="Repite la nueva contraseña"
                                        class="w-full border-0 border-b border-slate-200 dark:border-dark-border bg-transparent
                                               rounded-none pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white
                                               placeholder-slate-400 focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors"
                                    />
                                    <button type="button" @click="show = !show"
                                            class="absolute right-0 top-1/2 -translate-y-1/2 w-5 h-5
                                                   text-slate-400 hover:text-orvian-orange transition-colors">
                                        <x-heroicon-o-eye x-show="!show" class="w-5 h-5" />
                                        <x-heroicon-o-eye-slash x-show="show" class="w-5 h-5" style="display:none;" />
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <p class="mt-1.5 text-xs text-state-error">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 dark:border-dark-border flex justify-end">
                            <x-ui.button
                                wire:click="savePassword"
                                variant="primary"
                                wire:loading.attr="disabled"
                                wire:target="savePassword">
                                <span wire:loading.remove wire:target="savePassword">Actualizar contraseña</span>
                                <span wire:loading wire:target="savePassword">Actualizando...</span>
                            </x-ui.button>
                        </div>

                    </div>
                @endif

                {{-- ── Tab: Preferencias ───────────────────────────── --}}
                @if($activeTab === 'preferences')
                    <div class="bg-white dark:bg-dark-card rounded-2xl border border-slate-100 dark:border-dark-border">

                        <div class="px-6 py-5 border-b border-slate-100 dark:border-dark-border">
                            <h2 class="text-sm font-bold text-slate-800 dark:text-white">Preferencias visuales</h2>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                Personaliza cómo se ve y se comporta la interfaz de ORVIAN.
                            </p>
                        </div>

                        <div class="px-6 py-6 flex flex-col gap-8">

                            {{-- Selector de Tema --}}
                            <div>
                                <label class="text-[11px] font-bold uppercase tracking-wider mb-4 block text-slate-400 dark:text-slate-500">
                                    Tema de la aplicación
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    @foreach([
                                        'light'  => ['label' => 'Claro',   'icon' => 'heroicon-o-sun'],
                                        'dark'   => ['label' => 'Oscuro',  'icon' => 'heroicon-o-moon'],
                                        'system' => ['label' => 'Sistema', 'icon' => 'heroicon-o-computer-desktop'],
                                    ] as $val => $data)
                                        <label @class([
                                            'relative flex flex-col items-center justify-center p-4 border rounded-xl cursor-pointer transition-all duration-200',
                                            'border-orvian-orange bg-orvian-orange/5'                             => $theme === $val,
                                            'border-slate-200 dark:border-dark-border hover:border-orvian-orange/30' => $theme !== $val,
                                        ])>
                                            {{-- wire:model.live → actualiza $theme en PHP en cada click sin esperar submit --}}
                                            <input type="radio" wire:model.live="theme" value="{{ $val }}" class="sr-only">

                                            <x-dynamic-component
                                                :component="$data['icon']"
                                                @class(['w-6 h-6 mb-2', 'text-orvian-orange' => $theme === $val, 'text-slate-400 dark:text-slate-500' => $theme !== $val])
                                            />

                                            <span @class(['text-sm font-semibold', 'text-orvian-orange' => $theme === $val, 'text-slate-700 dark:text-slate-300' => $theme !== $val])>
                                                {{ $data['label'] }}
                                            </span>

                                            @if($theme === $val)
                                                <div class="absolute -top-2 -right-2 bg-orvian-orange text-white rounded-full p-0.5 border-2 border-white dark:border-dark-card">
                                                    <x-heroicon-s-check class="w-3 h-3" />
                                                </div>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Sidebar colapsado — solo para usuarios sin school_id (admin/tenant) --}}
                            @if($isAdmin)
                                <div class="border-t border-slate-100 dark:border-dark-border pt-6">
                                    <label class="relative flex items-start gap-3 cursor-pointer group">
                                        <div class="flex items-center h-5 mt-0.5">
                                            <input type="checkbox" wire:model="sidebar_collapsed"
                                                class="w-4 h-4 text-orvian-orange bg-slate-50 border-slate-300 rounded
                                                    focus:ring-orvian-orange dark:focus:ring-orvian-orange dark:ring-offset-dark-card
                                                    focus:ring-2 dark:bg-slate-800 dark:border-dark-border transition-all">
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-slate-800 dark:text-white group-hover:text-orvian-orange transition-colors">
                                                Colapsar menú lateral por defecto
                                            </span>
                                            <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                El menú principal se mostrará minimizado al iniciar sesión.
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            @endif

                            <div class="mt-8 pt-8 border-t border-slate-100 dark:border-white/5">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-5">
                                    Selecciona tu Interfaz de Acceso
                                </label>
                                
                                {{-- Contenedor en Columna con GAP mayor --}}
                                <div class="flex flex-col gap-6">
                                    @foreach(['v2' => 'Arquitectónico (Nuevo)', 'v1' => 'Clásico (Legado)'] as $val => $label)
                                        <button wire:click="$set('loginVersion', '{{ $val }}')"
                                            @class([
                                                "group relative flex flex-col p-3 rounded-2xl border transition-all duration-300 text-left overflow-hidden",
                                                "border-orvian-orange bg-orvian-orange/[0.02] ring-2 ring-orvian-orange shadow-lg shadow-orvian-orange/10" => $loginVersion === $val,
                                                "border-slate-100 dark:border-white/10 bg-white dark:bg-dark-card hover:border-orvian-orange/30 hover:shadow-md" => $loginVersion !== $val
                                            ])>
                                            
                                            {{-- Contenedor de Imagen GRANDE (Proporción Panorámica) --}}
                                            {{-- Usamos aspect-video (16:9) para que las capturas de pantalla de la web encajen perfecto --}}
                                            <div class="aspect-video w-full rounded-xl overflow-hidden border border-slate-200 dark:border-white/5 relative bg-slate-50 dark:bg-black/20">
                                                <img src="{{ asset('img/auth-preview/' . $val . '.png') }}" 
                                                    alt="{{ $label }}"
                                                    @class([
                                                        "w-full h-full object-cover transition-transform duration-700 group-hover:scale-105",
                                                        "grayscale-0" => $loginVersion === $val,
                                                        "grayscale group-hover:grayscale-0" => $loginVersion !== $val
                                                    ])>
                                                
                                                {{-- Overlay de selección sobre la imagen --}}
                                                @if($loginVersion === $val)
                                                    <div class="absolute inset-0 bg-orvian-orange/5 flex items-center justify-center">
                                                        {{-- Check gigante en el centro de la imagen --}}
                                                        <div class="bg-orvian-orange text-white rounded-full p-2 shadow-2xl scale-110">
                                                            <x-heroicon-s-check class="w-6 h-6" />
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Pie de Tarjeta (Label) --}}
                                            <div class="mt-4 px-1 flex items-center justify-between">
                                                <div>
                                                    <span @class([
                                                        "text-[11px] font-black uppercase tracking-wider",
                                                        "text-orvian-orange" => $loginVersion === $val,
                                                        "text-slate-500 dark:text-slate-400" => $loginVersion !== $val
                                                    ])>
                                                        {{ $val === 'v2' ? 'Versión 2.0' : 'Versión 1.0' }}
                                                    </span>
                                                    <p class="text-[13px] font-semibold text-slate-800 dark:text-white mt-0.5">
                                                        {{ $label }}
                                                    </p>
                                                </div>

                                                {{-- Radio button simulado lateral --}}
                                                <div @class([
                                                    "w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all flex-shrink-0",
                                                    "border-orvian-orange" => $loginVersion === $val,
                                                    "border-slate-300 dark:border-white/20" => $loginVersion !== $val
                                                ])>
                                                    <div @class([
                                                        "w-2.5 h-2.5 rounded-full transition-all",
                                                        "bg-orvian-orange scale-100" => $loginVersion === $val,
                                                        "bg-transparent scale-0" => $loginVersion !== $val
                                                    ])></div>
                                                </div>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Nota Aclaratoria Profesional --}}
                                <div class="mt-6 flex items-start gap-2.5 bg-slate-50 dark:bg-white/[0.03] p-4 rounded-xl border border-slate-100 dark:border-white/5">
                                    <x-heroicon-s-information-circle class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" />
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                                        <span class="font-bold dark:text-white">Nota de sesión:</span> Esta preferencia se guarda en tu navegador y es específica para este dispositivo. Al cerrar sesión, el portal recordará automáticamente qué interfaz mostrarte la próxima vez que intentes acceder.
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 dark:border-dark-border flex justify-end">
                            <x-ui.button
                                wire:click="savePreferences"
                                variant="primary"
                                wire:loading.attr="disabled"
                                wire:target="savePreferences">
                                <span wire:loading.remove wire:target="savePreferences">Guardar preferencias</span>
                                <span wire:loading wire:target="savePreferences">Guardando...</span>
                            </x-ui.button>
                        </div>

                    </div>
                @endif

            </main>

        </div>
    </div>
</div>