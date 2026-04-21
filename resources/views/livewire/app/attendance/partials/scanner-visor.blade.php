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

    {{--
        wire:ignore solo en el viewport de cámara:
        evita que Livewire destruya #qr-reader al re-renderizar (lo que invalida la instancia
        Html5Qrcode y deja el scanner muerto tras el primer escaneo).
        El toggle de arriba queda fuera y sigue actualizándose normalmente.
    --}}
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
        <div x-show="$wire.mode === 'facial'" x-cloak class="relative w-full h-full">
            <video id="facial-video" autoplay playsinline class="w-full h-full object-cover"></video>
            <canvas id="facial-canvas" class="absolute inset-0 w-full h-full"></canvas>
        </div>

        {{-- Flash Overlay --}}
        <div
            x-show="showFlash"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            class="absolute bottom-4 inset-x-4 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl bg-gray-950/90 backdrop-blur-sm border border-white/10 shadow-lg"
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

        {{-- Loading Overlay (isProcessing entangled, no wire:loading) --}}
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
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('attendanceScanner', (mode, isProcessing, sessionStatus) => ({
        mode: mode,
        isProcessing: isProcessing,
        sessionStatus: sessionStatus,

        // ── QR ───────────────────────────────────────────────────
        qrScanner: null,

        // ── Facial ───────────────────────────────────────────────
        videoStream: null,
        animationFrameId: null,   // requestAnimationFrame (no setInterval)
        faceApiReady: false,

        // ── Dwell Time ───────────────────────────────────────────
        dwellStart: null,         // timestamp cuando se detectó la cara
        dwellRequired: 1200,      // ms que debe permanecer (ajustable)
        cooldownActive: false,
        faceBox: null,            // { x, y, w, h } del último frame

        // ── Flash ────────────────────────────────────────────────
        showFlash: false,
        flashType: '',
        flashMessage: '',
        flashTimer: null,

        // ────────────────────────────────────────────────────────

        init() {
            this.$wire.on('mode-changed', ({ mode }) => this.switchMode(mode));
            this.$wire.on('flash-shown', ({ type, message }) => this.triggerFlash(type, message));
            this.$nextTick(() => this.switchMode(this.mode));
        },

        triggerFlash(type, message) {
            this.flashType    = type;
            this.flashMessage = message;
            this.showFlash    = true;
            if (this.flashTimer) clearTimeout(this.flashTimer);
            this.flashTimer = setTimeout(() => { this.showFlash = false; }, 3500);
        },

        async switchMode(newMode) {
            await this.cleanup();
            if (this.sessionStatus === 'inactive') return;
            await this.$nextTick();
            newMode === 'qr' ? this.initQrScanner() : await this.initFacialScanner();
        },

        // ── QR (sin cambios) ─────────────────────────────────────
        initQrScanner() {
            this.qrScanner = new Html5Qrcode("qr-reader");
            this.qrScanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 300, height: 300 }, aspectRatio: 4/3 },
                (decodedText) => {
                    this.qrScanner.pause(true);
                    Livewire.dispatch('qrCodeScanned', { code: decodedText });
                    setTimeout(() => {
                        if (this.qrScanner && this.mode === 'qr') this.qrScanner.resume();
                    }, 2500);
                },
                () => {}
            ).catch(() => this.triggerFlash('error', 'No se pudo iniciar la cámara.'));
        },

        // ── Facial ───────────────────────────────────────────────
        async initFacialScanner() {
            const video  = document.getElementById('facial-video');
            const canvas = document.getElementById('facial-canvas');

            try {
                this.videoStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                video.srcObject = this.videoStream;

                // Cargar modelos face-api.js (solo la primera vez)
                if (!this.faceApiReady) {
                    // Usamos CDN de los modelos — tiny_face_detector es el más ligero (~190KB)
                    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
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

        // Loop con requestAnimationFrame — mucho más eficiente que setInterval
        runDetectionLoop(video, canvas) {
            const ctx     = canvas.getContext('2d');
            const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });

            const loop = async () => {
                // Si nos limpiaron, salir
                if (!this.videoStream) return;

                // No procesar si ya estamos enviando
                if (!this.cooldownActive && !this.isProcessing && video.readyState === 4) {
                    const detections = await faceapi.detectAllFaces(video, options);

                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (detections.length === 1) {
                        // ── Hay exactamente UNA cara ──────────────────────
                        const box = detections[0].box;
                        this.faceBox = { x: box.x, y: box.y, w: box.width, h: box.height };

                        const now = Date.now();

                        if (!this.dwellStart) {
                            // Primera detección — empezar dwell
                            this.dwellStart = now;
                        }

                        const elapsed  = now - this.dwellStart;
                        const progress = Math.min(elapsed / this.dwellRequired, 1); // 0..1

                        if (progress >= 1) {
                            // ✅ Dwell completo — capturar y enviar
                            this.drawFaceBox(ctx, this.faceBox, '#10b981', 1); // verde sólido
                            this.dwellStart = null;
                            this.captureFace(canvas);
                        } else {
                            // ⏳ Acumulando dwell — bounding box amarillo + barra de progreso
                            this.drawFaceBox(ctx, this.faceBox, '#f59e0b', progress);
                        }

                    } else {
                        // Sin cara o múltiples — resetear dwell silenciosamente
                        this.dwellStart = null;
                        this.faceBox    = null;

                        if (detections.length > 1) {
                            // Feedback sutil: texto en canvas
                            ctx.fillStyle = 'rgba(239,68,68,0.7)';
                            ctx.font      = 'bold 18px sans-serif';
                            ctx.textAlign = 'center';
                            ctx.fillText('Un solo rostro a la vez', canvas.width / 2, 30);
                        }
                    }
                }

                this.animationFrameId = requestAnimationFrame(loop);
            };

            this.animationFrameId = requestAnimationFrame(loop);
        },

        // Dibuja bounding box + barra de progreso en el canvas
        drawFaceBox(ctx, box, color, progress) {
            const { x, y, w, h } = box;
            const pad = 12; // padding alrededor de la cara

            // Box
            ctx.strokeStyle = color;
            ctx.lineWidth   = 3;
            ctx.shadowColor = color;
            ctx.shadowBlur  = 8;
            ctx.strokeRect(x - pad, y - pad, w + pad * 2, h + pad * 2);
            ctx.shadowBlur  = 0;

            // Barra de progreso debajo del box
            const barY = y + h + pad + 10;
            const barW = w + pad * 2;
            const barX = x - pad;

            // Fondo
            ctx.fillStyle = 'rgba(0,0,0,0.4)';
            ctx.beginPath();
            ctx.roundRect(barX, barY, barW, 6, 3);
            ctx.fill();

            // Progreso
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.roundRect(barX, barY, barW * progress, 6, 3);
            ctx.fill();
        },

        captureFace(canvas) {
            this.cooldownActive = true;
            this.dwellStart     = null;

            // Capturar el frame real del video, no el canvas de overlays
            const video = document.getElementById('facial-video');
            const captureCanvas = document.createElement('canvas');
            captureCanvas.width  = canvas.width;
            captureCanvas.height = canvas.height;
            captureCanvas.getContext('2d').drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);

            captureCanvas.toBlob((blob) => {
                const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
                @this.upload('capturedPhoto', file, () => {
                    Livewire.dispatch('facialCaptureReady');
                    // Cooldown: no volver a detectar hasta que Livewire responda + margen
                    setTimeout(() => { this.cooldownActive = false; }, 3500);
                });
            }, 'image/jpeg', 0.92);
        },

        async cleanup() {
            if (this.qrScanner) {
                await this.qrScanner.stop().catch(() => {});
                this.qrScanner.clear();
                this.qrScanner = null;
            }
            if (this.animationFrameId) {
                cancelAnimationFrame(this.animationFrameId);
                this.animationFrameId = null;
            }
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(t => t.stop());
                this.videoStream = null;
            }
            // Limpiar estado dwell
            this.dwellStart     = null;
            this.cooldownActive = false;
            this.faceBox        = null;
        }
    }));
});
</script>
@endpush