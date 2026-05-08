<div>
    <x-app.module-toolbar>
        <x-slot:title>Registro Biométrico</x-slot:title>
    </x-app.module-toolbar>

        {{-- NUEVO: Barra de Controles Sticky --}}
        {{-- z-20 y backdrop-blur crean el efecto de cristal flotante al hacer scroll --}}
        <div class="sticky top-[7rem] z-20 bg-slate-50/90 dark:bg-dark-bg/90 backdrop-blur-md px-4 md:px-6 py-4 border-b border-slate-200/60 dark:border-white/5 shadow-sm">
            
            <div class="flex flex-wrap items-center gap-3">
                
                {{-- Búsqueda --}}
                <div class="min-w-[250px] flex-1 lg:flex-none">
                    <x-ui.forms.input wire:model.live.debounce.300ms="search"
                        placeholder="Buscar estudiante..." iconLeft="heroicon-o-magnifying-glass" size="sm" />
                </div>

                {{-- Selector de sección --}}
                <x-ui.forms.select
                    name="selectedSectionId"
                    wire:model.live="selectedSectionId"
                    class="w-48 lg:w-56"
                    placeholder="Todas las secciones">
                    @foreach($this->sections as $section)
                        <option value="{{ $section->id }}">{{ $section->full_label }}</option>
                    @endforeach
                </x-ui.forms.select>

                

                {{-- Filtro por estado biométrico (Segmented Control) --}}
                <div class="flex rounded-xl border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm">
                    @foreach(['' => 'Todos', 'with' => 'Con Biometría', 'without' => 'Sin Biometría'] as $val => $label)
                        <button wire:click="$set('filterBiometric', '{{ $val }}')"
                                class="px-3 py-2 text-xs font-bold transition-all
                                    {{ $filterBiometric === $val
                                        ? 'bg-orvian-orange text-white'
                                        : 'bg-white dark:bg-dark-card text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Stats Rápidas Mejoradas (Mini Dashboard Widget) --}}
                <div class="ml-auto hidden sm:flex items-center bg-white dark:bg-dark-card rounded-xl border border-slate-200 dark:border-white/10 shadow-sm py-1.5">
                    
                    <div class="px-4 text-center border-r border-slate-100 dark:border-white/5">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Total</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-white leading-tight">{{ $this->stats['total'] }}</p>
                    </div>
                    
                    <div class="px-4 text-center border-r border-slate-100 dark:border-white/5">
                        <p class="text-[9px] font-black uppercase tracking-widest text-green-500">Listos</p>
                        <div class="flex items-center justify-center gap-1">
                            <x-heroicon-s-check-circle class="w-3 h-3 text-green-500" />
                            <p class="text-sm font-bold text-green-600 dark:text-green-400 leading-tight">{{ $this->stats['enrolled'] }}</p>
                        </div>
                    </div>
                    
                    <div class="px-4 text-center">
                        <p class="text-[9px] font-black uppercase tracking-widest text-amber-500">Faltan</p>
                        <div class="flex items-center justify-center gap-1">
                            <x-heroicon-s-camera class="w-3 h-3 text-amber-500" />
                            <p class="text-sm font-bold text-amber-600 dark:text-amber-400 leading-tight">{{ $this->stats['pending'] }}</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    {{-- NUEVO: Contenedor del Grid --}}
    {{-- Aquí envolvemos el grid con el padding que antes tenía toda la página superior --}}
    <div class="p-4 md:p-6 pt-6">
        {{-- Grid de estudiantes --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            @forelse($this->students as $student)
                @php $hasBiometric = !empty($student->face_encoding); @endphp
                <div class="relative bg-white dark:bg-dark-card rounded-2xl p-3 border-2
                            transition-all cursor-pointer group
                            {{ $hasBiometric
                                ? 'border-green-200 dark:border-green-900/50'
                                : 'border-slate-200 dark:border-white/10 hover:border-orvian-orange/50' }}"
                    wire:click="{{ !$hasBiometric ? 'openEnrollModal(' . $student->id . ')' : '' }}">

                    {{-- Indicador de estado --}}
                    <div class="absolute top-2 right-2">
                        @if($hasBiometric)
                            <div class="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center">
                                <x-heroicon-s-check class="w-3 h-3 text-white" />
                            </div>
                        @else
                            <div class="w-5 h-5 rounded-full bg-amber-400 flex items-center justify-center
                                        opacity-0 group-hover:opacity-100 transition-opacity">
                                <x-heroicon-s-camera class="w-3 h-3 text-white" />
                            </div>
                        @endif
                    </div>

                    {{-- Avatar --}}
                    <div class="flex justify-center mb-2">
                        <x-ui.student-avatar :student="$student" size="lg" />
                    </div>

                    {{-- Nombre --}}
                    <p class="text-xs font-semibold text-slate-700 dark:text-white text-center
                            leading-tight truncate">
                        {{ $student->first_name }}
                    </p>
                    <p class="text-xs text-slate-400 text-center truncate">
                        {{ $student->last_name }}
                    </p>

                    {{-- Hover: botón de captura --}}
                    @if(!$hasBiometric)
                        <div class="absolute inset-0 bg-orvian-orange/5 rounded-2xl flex items-center
                                    justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="bg-orvian-orange text-white rounded-xl px-3 py-1.5 text-xs font-bold
                                        shadow-lg">
                                Capturar
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-20 text-center">
                    <x-heroicon-o-user-group class="w-12 h-12 mx-auto text-slate-300 mb-3" />
                    <p class="text-slate-400">No se encontraron estudiantes con los filtros actuales.</p>
                </div>
            @endforelse
        </div>
    </div>


    {{-- Modal: Captura Biométrica --}}
    <x-modal name="biometric-kiosk-modal" maxWidth="2xl">
        <div x-data="biometricKioskModal()" x-init="init()">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-orvian-orange/10 text-orvian-orange rounded-xl">
                        <x-heroicon-s-finger-print class="w-5 h-5" />
                    </div>
                    <div class="text-left">
                        @if($this->enrollingStudent)
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">
                                {{ $this->enrollingStudent->full_name }}
                            </h3>
                        @else
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">Captura Biométrica</h3>
                        @endif
                        <p class="text-xs text-gray-400">Foto para reconocimiento facial automático</p>
                    </div>
                </div>
                <button @click="cancel()" type="button"
                    class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors">
                    <x-heroicon-s-x-mark class="w-5 h-5" />
                </button>
            </div>

            {{-- Resultado de enrolamiento --}}
            @if(!empty($enrollResult))
                <div class="mx-6 mt-4 p-4 rounded-xl text-center
                            {{ $enrollResult['success']
                                ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
                                : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' }}">
                    <p class="text-sm font-semibold">{{ $enrollResult['message'] }}</p>
                </div>
            @endif

            {{-- Body --}}
            <div class="flex flex-col md:flex-row gap-5 p-5 md:p-6">

                {{-- Visor de cámara --}}
                <div class="relative flex-1 bg-gray-950 rounded-2xl overflow-hidden" style="aspect-ratio: 4/3;">
                    <video x-ref="video" autoplay playsinline
                        x-show="!captured"
                        class="w-full h-full object-cover"
                        :style="facingMode === 'user' ? 'transform: scaleX(-1)' : ''"></video>

                    <canvas x-ref="canvas" x-show="captured"
                        class="w-full h-full object-cover"></canvas>

                    {{-- Grid 3×3 --}}
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute top-0 bottom-0 border-l border-white/15" style="left: 33.333%"></div>
                        <div class="absolute top-0 bottom-0 border-l border-white/15" style="left: 66.666%"></div>
                        <div class="absolute left-0 right-0 border-t border-white/15" style="top: 33.333%"></div>
                        <div class="absolute left-0 right-0 border-t border-white/15" style="top: 66.666%"></div>
                    </div>

                    {{-- Óvalo guía de rostro --}}
                    <div x-show="!captured" class="absolute inset-0 pointer-events-none">
                        <svg viewBox="0 0 400 300" class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                            <ellipse cx="200" cy="150" rx="88" ry="116"
                                fill="none"
                                stroke="rgba(255,255,255,0.5)"
                                stroke-width="1.5"
                                stroke-dasharray="7,4"/>
                            <circle cx="200" cy="126" r="10" fill="none" stroke="rgba(255,255,255,0.25)" stroke-width="1"/>
                            <path d="M183 172 Q200 185 217 172" fill="none" stroke="rgba(255,255,255,0.25)" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>

                    {{-- Overlay: sin cámara --}}
                    <div x-show="!stream && !captured" class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-gray-500">
                        <x-heroicon-o-camera class="w-10 h-10 opacity-30" />
                        <p class="text-xs opacity-60">Iniciando cámara...</p>
                    </div>

                    {{-- Botón de cambio de cámara --}}
                    <button x-show="stream && !captured" @click="toggleFacingMode()" type="button"
                        class="absolute bottom-3 right-3 p-2 bg-black/40 text-white rounded-xl hover:bg-black/60 transition-colors backdrop-blur-sm"
                        title="Cambiar cámara">
                        <x-heroicon-s-arrow-path class="w-4 h-4" />
                    </button>
                </div>

                {{-- Panel de consejos --}}
                <div class="md:w-44 flex flex-col gap-3 flex-shrink-0">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Consejos</p>

                    <div class="space-y-3">
                        <div class="flex items-start gap-2.5">
                            <div class="flex-shrink-0 p-1.5 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-500">
                                <x-heroicon-s-sun class="w-4 h-4" />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-snug">Buena iluminación frontal, sin contraluz</p>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <div class="flex-shrink-0 p-1.5 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-500">
                                <x-heroicon-s-viewfinder-circle class="w-4 h-4" />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-snug">Centra el rostro dentro del óvalo</p>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <div class="flex-shrink-0 p-1.5 rounded-lg bg-slate-100 dark:bg-white/10 text-slate-500 dark:text-slate-300">
                                <x-heroicon-s-arrows-pointing-in class="w-4 h-4" />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-snug">Mantén distancia cómoda del dispositivo</p>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <div class="flex-shrink-0 p-1.5 rounded-lg bg-red-50 dark:bg-red-500/10 text-red-400">
                                <x-heroicon-s-no-symbol class="w-4 h-4" />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-snug">Sin gafas de sol, gorras ni accesorios</p>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <div class="flex-shrink-0 p-1.5 rounded-lg bg-slate-100 dark:bg-white/10 text-slate-500 dark:text-slate-300">
                                <x-heroicon-s-square-2-stack class="w-4 h-4" />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-snug">Fondo neutro y sin distracciones</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 dark:border-dark-border">
                <button @click="cancel()" type="button"
                    class="px-4 py-2 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                    Cancelar
                </button>

                <div class="flex items-center gap-2">
                    <button x-show="captured" @click="retake()" type="button"
                        class="px-4 py-2 text-sm text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-white/10 rounded-xl hover:opacity-80 transition-opacity">
                        Reintentar
                    </button>

                    <button x-show="!captured" @click="capture()" type="button" :disabled="!stream"
                        class="flex items-center gap-2 px-5 py-2.5 bg-orvian-orange text-white text-sm font-bold rounded-xl hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition-opacity shadow-sm">
                        <x-heroicon-s-camera class="w-4 h-4" />
                        Capturar Foto
                    </button>

                    <button x-show="captured" @click="register()" type="button" :disabled="uploading"
                        class="flex items-center gap-2 px-5 py-2.5 bg-green-500 text-white text-sm font-bold rounded-xl hover:opacity-90 disabled:opacity-50 transition-opacity shadow-sm">
                        <span x-show="!uploading" class="flex items-center gap-2">
                            <x-heroicon-s-check class="w-4 h-4" />
                            Registrar
                        </span>
                        <span x-show="uploading">Procesando...</span>
                    </button>
                </div>
            </div>
        </div>
    </x-modal>
</div>

@script
<script>
    Alpine.data('biometricKioskModal', () => ({
        stream:     null,
        captured:   false,
        uploading:  false,
        facingMode: 'user',

        init() {
            this.facingMode = ('ontouchstart' in window || navigator.maxTouchPoints > 0)
                ? 'environment'
                : 'user';

            // $wire.on() escucha eventos emitidos por $this->dispatch() de Livewire
            // window.addEventListener escucharía CustomEvents del DOM — canal diferente
            this.$wire.on('open-biometric-modal', () => {
                this.captured  = false;
                this.uploading = false;
                // Abrir el x-modal disparando el CustomEvent del DOM que él espera
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'biometric-kiosk-modal' }));
                this.$nextTick(() => this.startCamera());
            });

            this.$wire.on('close-biometric-modal', () => {
                // Quitar this.stopCamera() de aquí — el listener de abajo lo cubre
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'biometric-kiosk-modal' }));
            });

            window.addEventListener('close-modal', (e) => {
                if (e.detail === 'biometric-kiosk-modal') this.stopCamera(); // ← único punto de parada
            });

            window.addEventListener('enroll-success', () => {
                setTimeout(() => this.$wire.closeEnrollModal(), 1500);
            });
        },

        async startCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: this.facingMode }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false,
                });
                this.$refs.video.srcObject = this.stream;
            } catch {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { type: 'error', title: 'Cámara no disponible', message: 'Verifica los permisos del navegador.' }
                }));
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            if (this.$refs.video) this.$refs.video.srcObject = null;
        },

        async toggleFacingMode() {
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            this.stopCamera();
            await this.startCamera();
        },

        capture() {
            const video  = this.$refs.video;
            const canvas = this.$refs.canvas;
            const ctx    = canvas.getContext('2d');
            const size   = Math.min(video.videoWidth, video.videoHeight);
            canvas.width  = size;
            canvas.height = size;
            if (this.facingMode === 'user') {
                ctx.save();
                ctx.translate(size, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(
                video,
                (video.videoWidth - size) / 2, (video.videoHeight - size) / 2,
                size, size,
                0, 0, size, size
            );
            if (this.facingMode === 'user') ctx.restore();
            this.captured = true;
        },

        retake() {
            this.captured = false;
            const canvas = this.$refs.canvas;
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        },

        register() {
            this.uploading = true;
            this.$refs.canvas.toBlob((blob) => {
                if (!blob) { this.uploading = false; return; }
                $wire.upload(
                    'capturedPhoto',
                    new File([blob], 'biometric.jpg', { type: 'image/jpeg' }),
                    () => {
                        this.uploading = false;
                        $wire.enroll();
                    },
                    () => {
                        this.uploading = false;
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: { type: 'error', title: 'Error de subida', message: 'No se pudo procesar la imagen.' }
                        }));
                    }
                );
            }, 'image/jpeg', 0.95);
        },

        cancel() {
            this.stopCamera();
            this.captured = false;
            $wire.closeEnrollModal();
        },
    }));
</script>
@endscript