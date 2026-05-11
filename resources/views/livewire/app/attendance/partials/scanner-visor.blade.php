<div class="bg-white dark:bg-dark-card border border-gray-100 dark:border-dark-border rounded-[2rem] overflow-hidden">

    {{-- Toggle de Modo (fuera de wire:ignore — Livewire puede actualizarlo) --}}
    <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-dark-border">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl {{ $mode === 'qr' ? 'bg-blue-500' : 'bg-purple-500' }} flex items-center justify-center transition-colors">
                @if($mode === 'qr')
                    <x-heroicon-s-qr-code class="w-5 h-5 text-white" />
                @else
                    <x-heroicon-s-camera class="w-5 h-5 text-white" />
                @endif
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $mode === 'qr' ? 'Modo Código QR' : 'Modo Reconocimiento Facial' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $mode === 'qr' ? 'Escaneo automático de códigos QR' : 'Detección automática de rostros' }}
                </p>
            </div>
        </div>

        {{-- Botones de Modo --}}
        <div class="flex items-center gap-2">
            <x-ui.button
                wire:click="setModeQr"
                :type="$mode === 'qr' ? 'solid' : 'outline'"
                hex="#3b82f6"
                iconLeft="heroicon-s-qr-code"
                size="sm">
                QR
            </x-ui.button>
            <x-ui.button
                wire:click="setModeFacial"
                :type="$mode === 'facial' ? 'solid' : 'outline'"
                hex="#a855f7"
                iconLeft="heroicon-s-camera"
                size="sm">
                Facial
            </x-ui.button>
        </div>
    </div>

    {{-- Viewport de cámara con wire:ignore --}}
    <div
        wire:ignore
        x-data="attendanceScanner(@entangle('mode'), @entangle('isProcessing'), '{{ $activeSession ? 'active' : 'inactive' }}')"
        x-init="init()"
        class="relative bg-gray-950"
        style="aspect-ratio: 4/3;">

        {{-- Visor QR --}}
        <div x-show="$wire.mode === 'qr'" x-cloak class="w-full h-full">
            <div id="qr-reader" class="w-full h-full"></div>
        </div>

        {{-- Visor Facial --}}
        <div x-show="$wire.mode === 'facial'" x-cloak class="relative w-full h-full overflow-hidden">
            {{-- La clase transform scale-x-[-1] hace el efecto espejo. Se aplica condicionalmente desde JS --}}
            <video id="facial-video" autoplay playsinline class="w-full h-full object-cover transition-transform duration-300"></video>
            <canvas id="facial-canvas" class="absolute inset-0 w-full h-full"></canvas>
        </div>

        {{-- Flash Overlay (Movido arriba: top-4) --}}
        <div
            x-show="showFlash"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="absolute top-4 inset-x-4 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl bg-gray-950/90 backdrop-blur-sm border border-white/10 shadow-lg"
            style="display: none;">

            <div x-show="flashType === 'success'" class="flex-shrink-0 text-emerald-400">
                <x-heroicon-s-check-circle class="w-5 h-5" />
            </div>
            <div x-show="flashType === 'error'" class="flex-shrink-0 text-red-400">
                <x-heroicon-s-x-circle class="w-5 h-5" />
            </div>
            <div x-show="flashType === 'warning'" class="flex-shrink-0 text-amber-400">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5" />
            </div>
            <p class="flex-1 text-sm text-white truncate" x-text="flashMessage"></p>
        </div>

        {{-- Loading Overlay --}}
        <div
            x-show="isProcessing"
            class="absolute inset-0 z-40 flex items-center justify-center bg-black/80 backdrop-blur-sm"
            style="display: none;">
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 text-white mx-auto mb-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-white font-bold">Procesando...</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
{{-- face-api.js: detección real de rostros en browser, ~2MB modelos --}}
<script defer src="{{ asset('vendor/face-api/face-api.min.js') }}"></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('attendanceScanner', (mode, isProcessing, sessionStatus) => ({
        mode: mode,
        isProcessing: isProcessing,
        sessionStatus: sessionStatus,

        // ── Control de concurrencia ──────────────────────────────
        isSwitching: false,

        // ── QR ───────────────────────────────────────────────────
        qrScanner: null,

        // ── Facial ───────────────────────────────────────────────
        videoStream: null,
        animationFrameId: null,
        faceApiReady: false,
        isUsingFrontCamera: false,

        // ── Dwell Time ───────────────────────────────────────────
        dwellStart: null,
        dwellRequired: 1200,
        cooldownActive: false,
        faceBox: null,

        // ── Flash ────────────────────────────────────────────────
        showFlash: false,
        flashType: '',
        flashMessage: '',
        flashTimer: null,

        init() {
            this.$wire.on('mode-changed', ({ mode }) => {
                if (this.mode !== mode) {
                    this.mode = mode;
                    this.switchMode(mode);
                }
            });
            this.$wire.on('flash-shown', ({ type, message }) => this.triggerFlash(type, message));
            
            this.$nextTick(() => {
                if (this.sessionStatus !== 'inactive') {
                    this.switchMode(this.mode);
                }
            });
        },

        triggerFlash(type, message) {
            this.flashType    = type;
            this.flashMessage = message;
            this.showFlash    = true;
            if (this.flashTimer) clearTimeout(this.flashTimer);
            this.flashTimer = setTimeout(() => { this.showFlash = false; }, 3500);
        },

        async switchMode(newMode) {
            // Semáforo para evitar que múltiples clicks crasheen la cámara
            if (this.isSwitching) return;
            this.isSwitching = true;

            try {
                await this.cleanup();
                if (this.sessionStatus === 'inactive') return;
                
                await this.$nextTick(); // Esperar que x-show renderice el DOM
                
                if (newMode === 'qr') {
                    await this.initQrScanner();
                } else {
                    await this.initFacialScanner();
                }
            } finally {
                this.isSwitching = false;
            }
        },

        async initQrScanner() {
            try {
                this.qrScanner = new Html5Qrcode("qr-reader");
                await this.qrScanner.start(
                    { facingMode: "environment" }, // Para QR siempre es mejor la trasera
                    { fps: 10, qrbox: { width: 300, height: 300 }, aspectRatio: 4/3 },
                    (decodedText) => {
                        if (this.isProcessing) return;
                        this.qrScanner.pause(true);
                        Livewire.dispatch('qrCodeScanned', { code: decodedText });
                        setTimeout(() => {
                            if (this.qrScanner && this.mode === 'qr') this.qrScanner.resume();
                        }, 2500);
                    },
                    () => {}
                );
            } catch (err) {
                console.warn("[QR] Error al iniciar cámara:", err);
                this.triggerFlash('error', 'No se pudo iniciar la cámara QR.');
            }
        },

        async initFacialScanner() {
            const video  = document.getElementById('facial-video');
            const canvas = document.getElementById('facial-canvas');
            if (!video || !canvas) return;

            try {
                // Primero intentamos explícitamente usar la cámara trasera
                let constraints = {
                    video: { 
                        facingMode: { ideal: 'environment' }, 
                        width: { ideal: 1280 }, 
                        height: { ideal: 720 } 
                    }
                };

                try {
                    this.videoStream = await navigator.mediaDevices.getUserMedia(constraints);
                } catch (fallbackErr) {
                    // Si falla (ej. laptop sin cámara trasera), caemos al default
                    console.warn("No se pudo usar cámara environment, probando default...", fallbackErr);
                    this.videoStream = await navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 1280 }, height: { ideal: 720 } }
                    });
                }

                video.srcObject = this.videoStream;

                // Determinar si estamos usando la cámara frontal para aplicar efecto espejo
                const track = this.videoStream.getVideoTracks()[0];
                const settings = track.getSettings();
                // Si reporta user o no reporta nada (suele ser webcam de PC), asumimos frontal
                this.isUsingFrontCamera = settings.facingMode === 'user' || !settings.facingMode;
                
                if (this.isUsingFrontCamera) {
                    video.classList.add('scale-x-[-1]');
                    canvas.classList.add('scale-x-[-1]'); // Espejar también los recuadros
                } else {
                    video.classList.remove('scale-x-[-1]');
                    canvas.classList.remove('scale-x-[-1]');
                }

                // Cargar modelos face-api.js
                if (!this.faceApiReady) {
                    const MODEL_URL = '/vendor/face-api/models';
                    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                    this.faceApiReady = true;
                }

                video.addEventListener('loadedmetadata', () => {
                    canvas.width  = video.videoWidth;
                    canvas.height = video.videoHeight;
                    this.runDetectionLoop(video, canvas);
                }, { once: true });

            } catch (err) {
                console.error(err);
                this.triggerFlash('error', 'No se pudo acceder a la cámara. Verifica los permisos.');
            }
        },

        runDetectionLoop(video, canvas) {
            const ctx     = canvas.getContext('2d');
            const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });

            const loop = async () => {
                if (!this.videoStream || this.mode !== 'facial') return;

                if (!this.cooldownActive && !this.isProcessing && video.readyState === 4) {
                    const detections = await faceapi.detectAllFaces(video, options);

                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (detections.length === 1) {
                        const box = detections[0].box;
                        this.faceBox = { x: box.x, y: box.y, w: box.width, h: box.height };

                        const now = Date.now();
                        if (!this.dwellStart) this.dwellStart = now;

                        const elapsed  = now - this.dwellStart;
                        const progress = Math.min(elapsed / this.dwellRequired, 1);

                        if (progress >= 1) {
                            // Captura completada
                            this.drawFaceBox(ctx, this.faceBox, '#10b981', 1);
                            this.dwellStart = null;
                            this.captureFace(canvas);
                        } else {
                            // En proceso (Amarillo)
                            this.drawFaceBox(ctx, this.faceBox, '#f59e0b', progress);
                        }

                    } else {
                        this.dwellStart = null;
                        this.faceBox    = null;

                        if (detections.length > 1) {
                            // Si el canvas está espejado, revertimos temporalmente para escribir el texto
                            if (this.isUsingFrontCamera) {
                                ctx.save();
                                ctx.scale(-1, 1);
                                ctx.translate(-canvas.width, 0);
                            }
                            
                            ctx.fillStyle = 'rgba(239,68,68,0.8)';
                            ctx.font      = 'bold 18px sans-serif';
                            ctx.textAlign = 'center';
                            ctx.fillText('Un solo rostro a la vez', canvas.width / 2, 40);
                            
                            if (this.isUsingFrontCamera) {
                                ctx.restore();
                            }
                        }
                    }
                }
                this.animationFrameId = requestAnimationFrame(loop);
            };

            this.animationFrameId = requestAnimationFrame(loop);
        },

        // ── DISEÑO ELEGANTE DEL CUADRO ─────────────────────────────────
        drawFaceBox(ctx, box, color, progress) {
            const { x, y, w, h } = box;
            const pad = 15;
            const lineLen = 25; // Largo de las esquinas
            
            const bx = x - pad;
            const by = y - pad;
            const bw = w + pad * 2;
            const bh = h + pad * 2;

            ctx.strokeStyle = color;
            ctx.lineWidth   = 4;
            ctx.lineCap     = 'round';
            ctx.lineJoin    = 'round';
            ctx.shadowColor = color;
            ctx.shadowBlur  = 10;

            // Dibujar las 4 esquinas (tipo Viewfinder)
            ctx.beginPath();
            // Arriba Izquierda
            ctx.moveTo(bx, by + lineLen); ctx.lineTo(bx, by); ctx.lineTo(bx + lineLen, by);
            // Arriba Derecha
            ctx.moveTo(bx + bw - lineLen, by); ctx.lineTo(bx + bw, by); ctx.lineTo(bx + bw, by + lineLen);
            // Abajo Derecha
            ctx.moveTo(bx + bw, by + bh - lineLen); ctx.lineTo(bx + bw, by + bh); ctx.lineTo(bx + bw - lineLen, by + bh);
            // Abajo Izquierda
            ctx.moveTo(bx + lineLen, by + bh); ctx.lineTo(bx, by + bh); ctx.lineTo(bx, by + bh - lineLen);
            ctx.stroke();

            ctx.shadowBlur = 0; // Quitar sombra para la barra de progreso

            // Barra de progreso delgada de alta tecnología flotando debajo
            const barY = by + bh + 15;
            const barW = bw;

            // Fondo de la barra
            ctx.fillStyle = 'rgba(255,255,255,0.2)';
            ctx.beginPath();
            ctx.roundRect(bx, barY, barW, 4, 2);
            ctx.fill();

            // Relleno de la barra
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.roundRect(bx, barY, barW * progress, 4, 2);
            ctx.fill();
        },

        captureFace(canvas) {
            this.cooldownActive = true;
            this.dwellStart     = null;

            const video = document.getElementById('facial-video');
            const captureCanvas = document.createElement('canvas');
            captureCanvas.width  = canvas.width;
            captureCanvas.height = canvas.height;
            const ctx = captureCanvas.getContext('2d');
            
            // Si la cámara estaba espejada, revertir el dibujo para guardar la foto correcta
            if (this.isUsingFrontCamera) {
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
            }
            
            ctx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);

            captureCanvas.toBlob((blob) => {
                const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
                @this.upload('capturedPhoto', file, () => {
                    Livewire.dispatch('facialCaptureReady');
                    setTimeout(() => { this.cooldownActive = false; }, 3500);
                });
            }, 'image/jpeg', 0.92);
        },

        async cleanup() {
            if (this.animationFrameId) {
                cancelAnimationFrame(this.animationFrameId);
                this.animationFrameId = null;
            }

            if (this.qrScanner) {
                try {
                    // Html5Qrcode state: 2 = SCANNING. Previene error de "not running"
                    if (this.qrScanner.getState() === 2) {
                        await this.qrScanner.stop();
                    }
                } catch (e) {
                    console.warn("[QR] Cleanup warning:", e);
                }
                this.qrScanner.clear();
                this.qrScanner = null;
            }

            if (this.videoStream) {
                this.videoStream.getTracks().forEach(t => t.stop());
                this.videoStream = null;
            }

            const video = document.getElementById('facial-video');
            if (video) video.srcObject = null;

            this.dwellStart     = null;
            this.cooldownActive = false;
            this.faceBox        = null;
        }
    }));
});
</script>
@endpush