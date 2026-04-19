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

        {{-- Switch Toggle --}}
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase">QR</span>
            <button
                wire:click="$set('mode', $mode === 'qr' ? 'facial' : 'qr')"
                type="button"
                class="relative inline-flex h-7 w-14 items-center rounded-full transition-colors
                       {{ $mode === 'facial' ? 'bg-purple-500' : 'bg-blue-500' }}">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform
                             {{ $mode === 'facial' ? 'translate-x-8' : 'translate-x-1' }}">
                </span>
            </button>
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase">Facial</span>
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

        {{-- Flash Overlay — pill minimalista en la parte superior del visor --}}
        <div
            x-show="showFlash"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute top-3 inset-x-3 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-white text-sm font-bold"
            :class="flashType === 'success' ? 'bg-green-500' : (flashType === 'error' ? 'bg-red-500' : 'bg-yellow-500')"
            style="display: none;">

            <div x-show="flashType === 'success'" class="flex-shrink-0">
                <x-heroicon-s-check-circle class="w-5 h-5 text-white" />
            </div>
            <div x-show="flashType === 'error'" class="flex-shrink-0">
                <x-heroicon-s-x-circle class="w-5 h-5 text-white" />
            </div>
            <div x-show="flashType === 'warning'" class="flex-shrink-0">
                <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-white" />
            </div>
            <p class="flex-1 truncate" x-text="flashMessage"></p>
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
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('attendanceScanner', (mode, isProcessing, sessionStatus) => ({
        mode: mode,
        isProcessing: isProcessing,
        sessionStatus: sessionStatus,
        qrScanner: null,
        videoStream: null,
        detectionInterval: null,
        lastDetectionTime: 0,
        cooldownActive: false,
        showFlash: false,
        flashType: '',
        flashMessage: '',
        flashTimer: null,

        init() {
            this.$watch('mode', (newMode) => {
                this.switchMode(newMode);
            });

            this.$wire.on('flash-shown', ({ type, message }) => {
                this.triggerFlash(type, message);
            });

            this.switchMode(this.mode);
        },

        triggerFlash(type, message) {
            this.flashType = type;
            this.flashMessage = message;
            this.showFlash = true;

            if (this.flashTimer) clearTimeout(this.flashTimer);
            this.flashTimer = setTimeout(() => { this.showFlash = false; }, 3000);
        },

        switchMode(newMode) {
            this.cleanup();

            if (this.sessionStatus === 'inactive') {
                console.warn('No hay sesión activa, scanner deshabilitado.');
                return;
            }

            if (newMode === 'qr') {
                this.initQrScanner();
            } else {
                this.initFacialScanner();
            }
        },

        initQrScanner() {
            this.qrScanner = new Html5Qrcode("qr-reader");

            this.qrScanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 300, height: 300 }, aspectRatio: 4/3 },
                (decodedText) => {
                    this.qrScanner.pause(true);
                    Livewire.dispatch('qrCodeScanned', { code: decodedText });
                    // El DOM no fue tocado gracias a wire:ignore → resume() funciona
                    setTimeout(() => {
                        if (this.qrScanner && this.mode === 'qr') {
                            this.qrScanner.resume();
                        }
                    }, 2500);
                },
                () => {}
            ).catch(err => {
                console.error('Error iniciando QR scanner:', err);
                this.triggerFlash('error', 'No se pudo iniciar la cámara.');
            });
        },

        async initFacialScanner() {
            const video = document.getElementById('facial-video');
            const canvas = document.getElementById('facial-canvas');
            const ctx = canvas.getContext('2d');

            try {
                this.videoStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } }
                });

                video.srcObject = this.videoStream;
                video.addEventListener('loadedmetadata', () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    this.startFaceDetection(video, canvas, ctx);
                });
            } catch (err) {
                console.error('Error accediendo a la cámara:', err);
                this.triggerFlash('error', 'No se pudo acceder a la cámara. Verifica los permisos.');
            }
        },

        startFaceDetection(video, canvas, ctx) {
            // TODO: Reemplazar simulación con face-api.js cuando el microservicio esté disponible
            this.detectionInterval = setInterval(() => {
                if (this.cooldownActive || this.isProcessing) return;

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const faceDetected = Math.random() > 0.3;

                if (faceDetected) {
                    ctx.strokeStyle = '#10b981';
                    ctx.lineWidth = 4;
                    ctx.strokeRect(canvas.width * 0.3, canvas.height * 0.2, canvas.width * 0.4, canvas.height * 0.5);

                    if (Date.now() - this.lastDetectionTime > 500) {
                        this.captureFace(canvas);
                    }
                    this.lastDetectionTime = Date.now();
                } else {
                    this.lastDetectionTime = 0;
                }
            }, 100);
        },

        captureFace(canvas) {
            this.cooldownActive = true;
            canvas.toBlob((blob) => {
                const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
                @this.upload('capturedPhoto', file, () => {
                    Livewire.dispatch('facialCaptureReady');
                    setTimeout(() => { this.cooldownActive = false; }, 3000);
                });
            }, 'image/jpeg', 0.9);
        },

        cleanup() {
            if (this.qrScanner) {
                this.qrScanner.stop().catch(() => {});
                this.qrScanner.clear();
                this.qrScanner = null;
            }
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(t => t.stop());
                this.videoStream = null;
            }
            if (this.detectionInterval) {
                clearInterval(this.detectionInterval);
                this.detectionInterval = null;
            }
        }
    }));
});
</script>
@endpush
