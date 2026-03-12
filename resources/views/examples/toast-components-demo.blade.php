{{--
    resources/views/examples/toast-components-demo.blade.php
    -------------------------------------------------------
    Showcase interactivo del sistema x-ui.toasts.
    Todos los toasts se disparan mediante botones.
    Acceder vía: Route::view('/demo/toasts', 'examples.toast-components-demo');
--}}

<x-app-layout title="Demo — Toasts (x-ui.toasts)">

<div class="max-w-4xl mx-auto py-12 px-4 space-y-16">

    {{-- ── Encabezado ─────────────────────────────────────────────── --}}
    <div class="space-y-1">
        <p class="text-[11px] font-bold uppercase tracking-widest text-orvian-orange">
            UI Kit · System
        </p>
        <h1 class="text-2xl font-bold text-orvian-navy dark:text-white">
            Sistema de Toasts
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Notificaciones reactivas vía eventos Alpine.js. Haz clic en cualquier botón para ver el toast correspondiente.
        </p>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         01 · TIPOS BÁSICOS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">01 · Tipos Básicos</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Success --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Success</p>
                </div>
                <p class="text-[11px] text-slate-400">Confirmaciones, guardados, acciones completadas.</p>
                <button
                    @click="$dispatch('notify', {
                        type: 'success',
                        title: '¡Guardado exitosamente!',
                        message: 'Los cambios del centro educativo han sido aplicados correctamente.'
                    })"
                    class="w-full px-4 py-2.5 rounded-xl text-xs font-bold border-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 bg-emerald-500/5 hover:bg-emerald-500/10 transition-all"
                >
                    Disparar Success →
                </button>
            </div>

            {{-- Error --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-red-500"></div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Error</p>
                </div>
                <p class="text-[11px] text-slate-400">Fallos críticos, errores de servidor, acciones bloqueadas.</p>
                <button
                    @click="$dispatch('notify', {
                        type: 'error',
                        title: 'Error al procesar',
                        message: 'No se pudo guardar el registro. Verifica tu conexión e intenta nuevamente.',
                        duration: 8000
                    })"
                    class="w-full px-4 py-2.5 rounded-xl text-xs font-bold border-2 border-red-500 text-red-600 dark:text-red-400 bg-red-500/5 hover:bg-red-500/10 transition-all"
                >
                    Disparar Error →
                </button>
            </div>

            {{-- Warning --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Warning</p>
                </div>
                <p class="text-[11px] text-slate-400">Advertencias, acciones irreversibles, precauciones.</p>
                <button
                    @click="$dispatch('notify', {
                        type: 'warning',
                        title: 'Atención requerida',
                        message: 'Este registro será eliminado permanentemente. Esta acción no puede deshacerse.',
                        duration: 8000
                    })"
                    class="w-full px-4 py-2.5 rounded-xl text-xs font-bold border-2 border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-500/5 hover:bg-amber-500/10 transition-all"
                >
                    Disparar Warning →
                </button>
            </div>

            {{-- Info --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Info</p>
                </div>
                <p class="text-[11px] text-slate-400">Mensajes informativos, estados del sistema, contexto.</p>
                <button
                    @click="$dispatch('notify', {
                        type: 'info',
                        title: 'Sincronización en curso',
                        message: 'ORVIAN está sincronizando los datos con los servidores SIGERD.'
                    })"
                    class="w-full px-4 py-2.5 rounded-xl text-xs font-bold border-2 border-blue-500 text-blue-600 dark:text-blue-400 bg-blue-500/5 hover:bg-blue-500/10 transition-all"
                >
                    Disparar Info →
                </button>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         02 · DURACIÓN PERSONALIZADA
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">02 · Duración Personalizada</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-4">
            <p class="text-xs text-slate-500">La barra de progreso refleja el tiempo restante. Pasa el cursor sobre un toast para pausar el temporizador.</p>
            <div class="flex flex-wrap gap-3">

                <button
                    @click="$dispatch('notify', {
                        type: 'info',
                        title: 'Toast rápido (2s)',
                        message: 'Este toast desaparecerá en 2 segundos.',
                        duration: 2000
                    })"
                    class="px-4 py-2 rounded-xl text-xs font-bold border-2 border-slate-200 dark:border-dark-border text-slate-600 dark:text-slate-300 hover:border-slate-300 dark:hover:border-white/20 transition-all"
                >
                    2 segundos
                </button>

                <button
                    @click="$dispatch('notify', {
                        type: 'success',
                        title: 'Duración estándar (5s)',
                        message: 'Este es el tiempo por defecto para toasts de éxito.',
                        duration: 5000
                    })"
                    class="px-4 py-2 rounded-xl text-xs font-bold border-2 border-slate-200 dark:border-dark-border text-slate-600 dark:text-slate-300 hover:border-slate-300 dark:hover:border-white/20 transition-all"
                >
                    5 segundos (default)
                </button>

                <button
                    @click="$dispatch('notify', {
                        type: 'warning',
                        title: 'Mensaje persistente (10s)',
                        message: 'Los toasts de error y advertencia importantes pueden tener mayor duración.',
                        duration: 10000
                    })"
                    class="px-4 py-2 rounded-xl text-xs font-bold border-2 border-slate-200 dark:border-dark-border text-slate-600 dark:text-slate-300 hover:border-slate-300 dark:hover:border-white/20 transition-all"
                >
                    10 segundos
                </button>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         03 · MÚLTIPLES SIMULTÁNEOS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">03 · Múltiples Simultáneos</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-4">
            <p class="text-xs text-slate-500">Los toasts se apilan verticalmente en el orden de aparición. Cada uno tiene su propio temporizador independiente.</p>
            <div class="flex flex-wrap gap-3">

                <button
                    @click="
                        $dispatch('notify', { type: 'info',    title: 'Paso 1 de 3', message: 'Verificando credenciales de acceso...' });
                        setTimeout(() => $dispatch('notify', { type: 'warning', title: 'Paso 2 de 3', message: 'Se detectó una sesión activa anterior.' }), 600);
                        setTimeout(() => $dispatch('notify', { type: 'success', title: 'Paso 3 de 3', message: '¡Acceso concedido! Bienvenido a ORVIAN.' }), 1200);
                    "
                    class="px-5 py-2.5 rounded-xl text-xs font-bold bg-orvian-orange text-white hover:opacity-90 transition-all"
                >
                    Disparar secuencia (3 toasts)
                </button>

                <button
                    @click="
                        ['success','error','warning','info'].forEach((type, i) => {
                            setTimeout(() => $dispatch('notify', {
                                type,
                                title: type.charAt(0).toUpperCase() + type.slice(1),
                                message: 'Todos los tipos activos simultáneamente.'
                            }), i * 300)
                        })
                    "
                    class="px-5 py-2.5 rounded-xl text-xs font-bold border-2 border-orvian-orange text-orvian-orange bg-orvian-orange/5 hover:bg-orvian-orange/10 transition-all"
                >
                    Disparar todos los tipos
                </button>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         04 · CASOS DE USO EN ORVIAN
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">04 · Casos de Uso Reales</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Wizard --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Wizard de Configuración</p>
                <div class="flex flex-col gap-2">
                    <button
                        @click="$dispatch('notify', {
                            type: 'success',
                            title: '¡Escuela configurada!',
                            message: 'Liceo Juan Pablo Duarte ha sido activada exitosamente en ORVIAN.'
                        })"
                        class="w-full px-4 py-2 rounded-xl text-xs font-bold border-2 border-emerald-500/40 text-emerald-600 dark:text-emerald-400 bg-emerald-500/5 hover:bg-emerald-500/10 transition-all text-left"
                    >
                        → Onboarding completado
                    </button>
                    <button
                        @click="$dispatch('notify', {
                            type: 'info',
                            title: 'Configuración incompleta',
                            message: 'Debes completar la configuración de tu escuela antes de acceder al sistema.',
                            duration: 7000
                        })"
                        class="w-full px-4 py-2 rounded-xl text-xs font-bold border-2 border-blue-500/40 text-blue-600 dark:text-blue-400 bg-blue-500/5 hover:bg-blue-500/10 transition-all text-left"
                    >
                        → Acceso bloqueado por onboarding
                    </button>
                    <button
                        @click="$dispatch('notify', {
                            type: 'warning',
                            title: 'Tiempo límite próximo',
                            message: 'Tu cuenta de configuración expira en menos de 2 horas.',
                            duration: 8000
                        })"
                        class="w-full px-4 py-2 rounded-xl text-xs font-bold border-2 border-amber-500/40 text-amber-600 dark:text-amber-400 bg-amber-500/5 hover:bg-amber-500/10 transition-all text-left"
                    >
                        → TTL del stub por vencer
                    </button>
                </div>
            </div>

            {{-- CRUD --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Operaciones CRUD</p>
                <div class="flex flex-col gap-2">
                    <button
                        @click="$dispatch('notify', {
                            type: 'success',
                            title: 'Estudiante inscrito',
                            message: 'María García fue inscrita en 2do de Secundaria · Matutina.'
                        })"
                        class="w-full px-4 py-2 rounded-xl text-xs font-bold border-2 border-emerald-500/40 text-emerald-600 dark:text-emerald-400 bg-emerald-500/5 hover:bg-emerald-500/10 transition-all text-left"
                    >
                        → Registro creado
                    </button>
                    <button
                        @click="$dispatch('notify', {
                            type: 'success',
                            title: 'Cambios guardados',
                            message: 'El horario del docente fue actualizado correctamente.'
                        })"
                        class="w-full px-4 py-2 rounded-xl text-xs font-bold border-2 border-emerald-500/40 text-emerald-600 dark:text-emerald-400 bg-emerald-500/5 hover:bg-emerald-500/10 transition-all text-left"
                    >
                        → Registro actualizado
                    </button>
                    <button
                        @click="$dispatch('notify', {
                            type: 'warning',
                            title: 'Registro eliminado',
                            message: 'El año escolar 2024-2025 fue removido. No es posible recuperarlo.',
                            duration: 8000
                        })"
                        class="w-full px-4 py-2 rounded-xl text-xs font-bold border-2 border-amber-500/40 text-amber-600 dark:text-amber-400 bg-amber-500/5 hover:bg-amber-500/10 transition-all text-left"
                    >
                        → Registro eliminado
                    </button>
                    <button
                        @click="$dispatch('notify', {
                            type: 'error',
                            title: 'Error de validación',
                            message: 'El código SIGERD ingresado ya existe en el sistema.',
                            duration: 8000
                        })"
                        class="w-full px-4 py-2 rounded-xl text-xs font-bold border-2 border-red-500/40 text-red-600 dark:text-red-400 bg-red-500/5 hover:bg-red-500/10 transition-all text-left"
                    >
                        → Error de validación
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         05 · MÉTODOS DE DISPARO
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">05 · Métodos de Disparo</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- Alpine $dispatch --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <div>
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Alpine <code class="text-orvian-orange text-[11px]">$dispatch</code></p>
                    <p class="text-[11px] text-slate-400 mt-1">Desde cualquier elemento Alpine en el DOM.</p>
                </div>
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-3 font-mono text-[10px] text-slate-500 leading-relaxed">
                    @click="$dispatch('notify', {<br>
                    &nbsp;&nbsp;type: 'success',<br>
                    &nbsp;&nbsp;title: 'Título',<br>
                    &nbsp;&nbsp;message: 'Mensaje'<br>
                    })"
                </div>
                <button
                    @click="$dispatch('notify', {
                        type: 'success',
                        title: 'Vía Alpine $dispatch',
                        message: 'Disparado con @click y $dispatch desde el template.'
                    })"
                    class="w-full px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 dark:bg-white/10 text-white hover:opacity-90 transition-all"
                >
                    Probar →
                </button>
            </div>

            {{-- window.dispatchEvent --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <div>
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">JS <code class="text-orvian-orange text-[11px]">window.dispatchEvent</code></p>
                    <p class="text-[11px] text-slate-400 mt-1">Desde JavaScript puro, callbacks o promesas.</p>
                </div>
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-3 font-mono text-[10px] text-slate-500 leading-relaxed">
                    window.dispatchEvent(<br>
                    &nbsp;&nbsp;new CustomEvent('notify', {<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;detail: { ... }<br>
                    &nbsp;&nbsp;})<br>
                    );
                </div>
                <button
                    onclick="window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'info', title: 'Vía window.dispatchEvent', message: 'Disparado con JavaScript puro desde un onclick handler.' } }))"
                    class="w-full px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 dark:bg-white/10 text-white hover:opacity-90 transition-all"
                >
                    Probar →
                </button>
            </div>

            {{-- notify-redirect --}}
            <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-3">
                <div>
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Evento <code class="text-orvian-orange text-[11px]">notify-redirect</code></p>
                    <p class="text-[11px] text-slate-400 mt-1">Guarda en <code class="text-[10px]">sessionStorage</code> y muestra tras recargar.</p>
                </div>
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-3 font-mono text-[10px] text-slate-500 leading-relaxed">
                    $dispatch('notify-redirect', {<br>
                    &nbsp;&nbsp;type: 'success',<br>
                    &nbsp;&nbsp;title: 'Para redirect',<br>
                    &nbsp;&nbsp;message: '...'<br>
                    })
                </div>
                <button
                    @click="
                        $dispatch('notify-redirect', {
                            type: 'success',
                            title: 'Toast guardado',
                            message: 'Este toast aparecerá después de recargar la página.'
                        });
                        setTimeout(() => window.location.reload(), 800);
                    "
                    class="w-full px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 dark:bg-white/10 text-white hover:opacity-90 transition-all"
                >
                    Guardar y recargar →
                </button>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         06 · INTEGRACIÓN CON SESIÓN LARAVEL
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">06 · Sesión Laravel (referencia)</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-dark-border"></div>
        </div>

        <div class="p-5 rounded-2xl border border-slate-100 dark:border-dark-border bg-white dark:bg-dark-card space-y-4">
            <p class="text-[11px] text-slate-400">Los toasts de sesión se disparan automáticamente al renderizar la página. No requieren acción del usuario. Referencia de uso desde PHP:</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-4 space-y-1.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Session success</p>
                    <p class="font-mono text-[11px] text-slate-500">return redirect()->back()<br>&nbsp;&nbsp;&nbsp;&nbsp;->with('success', 'Mensaje');</p>
                </div>
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-4 space-y-1.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-red-500">Session error</p>
                    <p class="font-mono text-[11px] text-slate-500">return redirect()->back()<br>&nbsp;&nbsp;&nbsp;&nbsp;->with('error', 'Mensaje');</p>
                </div>
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-4 space-y-1.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Session warning</p>
                    <p class="font-mono text-[11px] text-slate-500">return redirect()->back()<br>&nbsp;&nbsp;&nbsp;&nbsp;->with('warning', 'Mensaje');</p>
                </div>
                <div class="bg-slate-50 dark:bg-white/[0.03] rounded-xl p-4 space-y-1.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-blue-500">Session info</p>
                    <p class="font-mono text-[11px] text-slate-500">return redirect()->back()<br>&nbsp;&nbsp;&nbsp;&nbsp;->with('info', 'Mensaje');</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-500/5 border border-amber-500/15">
                <svg class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p class="text-[11px] text-amber-500/80">Los errores de validación de Laravel (<code>$errors</code>) también se muestran automáticamente. Si hay múltiples errores, el título indica la cantidad: <strong>"Error de validación (+2 más)"</strong>.</p>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="pt-8 text-center border-t border-slate-100 dark:border-dark-border">
        <p class="text-[10px] text-slate-400 uppercase tracking-widest">
            ORVIAN Design System · v2026.1
        </p>
    </div>

</div>

</x-app-layout>