        <div x-show="$wire.showIntro" class="flex-1 flex flex-col">

            <div class="px-8 pt-8 pb-6 border-b border-slate-200 dark:border-white/5">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-10 h-10 rounded-xl bg-orvian-orange/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-orvian-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-orvian-orange">ORVIAN · Configuración</p>
                        <h1 class="text-lg font-black text-slate-800 dark:text-white leading-tight">
                            @if($totalSteps === 5)
                                Asistente de Configuración de Escuela
                            @else
                                Configura tu Centro Educativo
                            @endif
                        </h1>
                    </div>
                </div>
            </div>

            <div class="flex-1 px-8 py-7 space-y-6">
                @if($totalSteps === 5)
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                        Este asistente te guiará para registrar y configurar completamente un nuevo centro educativo en ORVIAN.
                        Al finalizar, la escuela quedará activa y su director tendrá acceso inmediato al sistema.
                    </p>
                @else
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                        Bienvenido a ORVIAN. Para acceder al sistema necesitas configurar los datos de tu centro educativo.
                        El proceso toma aproximadamente <strong class="text-slate-700 dark:text-slate-200">5 minutos</strong> y solo debes realizarlo una vez.
                    </p>
                @endif

                <div class="space-y-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Proceso en {{ $totalSteps }} pasos
                    </p>
                    <div class="space-y-2">
                        @foreach(array_slice($stepNames, 0, $totalSteps, true) as $idx => $s)
                            <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-white/6 bg-slate-50/50 dark:bg-white/[0.02]">
                                <div class="w-7 h-7 rounded-lg bg-orvian-orange/10 border border-orvian-orange/20 flex items-center justify-center flex-shrink-0">
                                    <span class="text-[11px] font-black text-orvian-orange">{{ $idx }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $s['name'] }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $s['sub'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($totalSteps === 4)
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-500/5 border border-amber-500/15">
                        <svg class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                        <div>
                            <p class="text-xs font-semibold text-amber-400">Tienes 24 horas para completar la configuración.</p>
                            <p class="text-[11px] text-amber-400/70 mt-0.5">Si no terminas en ese tiempo, tu cuenta será eliminada automáticamente por seguridad.</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="px-8 py-5 border-t border-slate-200 dark:border-white/5 bg-slate-50/30 dark:bg-white/[0.01] space-y-3">
                {{-- Botón Principal --}}
                <x-ui.button
                    variant="primary"
                    :fullWidth="true"
                    :hoverEffect="true"
                    iconRight="heroicon-s-arrow-right"
                    wire:click="startWizard"
                >
                    Comenzar Configuración
                </x-ui.button>

                {{-- Botón de Retorno: Solo para el Owner (Administrador Global sin escuela asignada) --}}
                @if(auth()->user()->school_id === null)
                    <div class="flex justify-center">
                        <x-ui.button
                            variant="info"
                            :fullWidth="true"
                            type="ghost"
                            href="{{ route('admin.schools.index') }}"
                            iconLeft="heroicon-s-arrow-left"
                        >
                            Volver al listado de escuelas
                        </x-ui.button>
                    </div>
                @endif
            </div>

        </div>{{-- /intro --}}