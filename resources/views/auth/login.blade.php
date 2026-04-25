<x-guest-layout>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <div x-data="qrLogin()">

        {{-- ─── Estado de sesión (Breeze) ─── --}}
        <x-auth-session-status class="mb-6" :status="session('status')" />

        {{-- ─── Cabecera ─── --}}
        <div class="mb-10">
            <h2 class="font-black text-[1.85rem] tracking-tight leading-tight text-[#001b3d] dark:text-white">
                Acceso al Sistema
            </h2>
            <p class="text-[11px] uppercase tracking-widest mt-1.5 text-[#79747e] dark:text-white/30">
                Autentícate para establecer una sesión segura.
            </p>
        </div>

        {{-- ─── Formulario ─── --}}
        <form id="login-form"
              method="POST"
              action="{{ route('login') }}"
              x-data="{ showPassword: false }">
            @csrf
            <input type="hidden" name="qr_code" x-model="qrCode">

            {{-- ── Correo Electrónico ── --}}
            <div class="group flex flex-col gap-1 mb-8
                        border-b border-[#cac4cf] dark:border-white/10
                        focus-within:border-[#f78904] dark:focus-within:border-[#f78904]
                        transition-colors duration-200">

                <label for="email"
                       class="text-[10px] uppercase tracking-widest
                              text-[#79747e] dark:text-white/30
                              group-focus-within:text-[#f78904]
                              transition-colors duration-200">
                    Correo_Electrónico
                </label>

                <div class="flex items-center gap-3 py-0.5">
                    <span class="flex-shrink-0 pointer-events-none
                                 text-[#79747e] dark:text-white/25
                                 group-focus-within:text-[#f78904]
                                 transition-colors duration-200">
                        <x-heroicon-o-envelope class="w-[18px] h-[18px]" />
                    </span>
                    <input id="email"
                           type="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="usuario@institucional.org"
                           autofocus
                           autocomplete="email"
                           class="w-full bg-transparent border-none outline-none ring-0 focus:ring-0
                                  py-3 px-0 text-sm
                                  text-[#001b3d] dark:text-white
                                  placeholder:text-[#cac4cf] dark:placeholder:text-white/20
                                  transition-colors duration-200" />
                </div>

                @error('email')
                    <p class="flex items-center gap-1 mt-1 text-[10px] text-red-500">
                        <x-heroicon-s-exclamation-circle class="w-3 h-3 flex-shrink-0" />
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ── Contraseña ── --}}
            <div class="group flex flex-col gap-1 mb-10
                        border-b border-[#cac4cf] dark:border-white/10
                        focus-within:border-[#f78904] dark:focus-within:border-[#f78904]
                        transition-colors duration-200">

                <div class="flex items-end justify-between">
                    <label for="password"
                           class="text-[10px] uppercase tracking-widest
                                  text-[#79747e] dark:text-white/30
                                  group-focus-within:text-[#f78904]
                                  transition-colors duration-200">
                        Contraseña
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-[10px] uppercase tracking-tight
                                  text-[#79747e] dark:text-white/30
                                  hover:text-[#f78904] dark:hover:text-[#f78904]
                                  transition-colors duration-200">
                            ¿Olvidaste tu acceso?
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-3 py-0.5">
                    <span class="flex-shrink-0 pointer-events-none
                                 text-[#79747e] dark:text-white/25
                                 group-focus-within:text-[#f78904]
                                 transition-colors duration-200">
                        <x-heroicon-o-lock-closed class="w-[18px] h-[18px]" />
                    </span>
                    <input id="password"
                           :type="showPassword ? 'text' : 'password'"
                           name="password"
                           placeholder="••••••••••••"
                           autocomplete="current-password"
                           class="w-full bg-transparent border-none outline-none ring-0 focus:ring-0
                                  py-3 px-0 text-sm
                                  text-[#001b3d] dark:text-white
                                  placeholder:text-[#cac4cf] dark:placeholder:text-white/20
                                  transition-colors duration-200" />
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="flex-shrink-0
                                   text-[#79747e] dark:text-white/25
                                   hover:text-[#f78904] dark:hover:text-[#f78904]
                                   transition-colors duration-200">
                        <x-heroicon-o-eye       x-show="!showPassword" class="w-[18px] h-[18px]" />
                        <x-heroicon-o-eye-slash x-show="showPassword" x-cloak class="w-[18px] h-[18px]" />
                    </button>
                </div>

                @error('password')
                    <p class="flex items-center gap-1 mt-1 text-[10px] text-red-500">
                        <x-heroicon-s-exclamation-circle class="w-3 h-3 flex-shrink-0" />
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ── Botón Iniciar Sesión ── --}}
            <div class="mb-8">
                <button type="submit"
                        class="group relative w-full h-14 flex items-center justify-center gap-2
                               bg-orvian-orange text-white
                               font-black text-sm uppercase tracking-[0.15em]
                               rounded-orvian overflow-hidden
                               hover:bg-orvian-orange-hover
                               hover:scale-[1.015] hover:shadow-[0_10px_30px_rgba(247,137,4,0.35)]
                               active:scale-[0.98]
                               transition-all duration-300">
                    {{-- Shine en hover --}}
                    <span aria-hidden="true"
                          class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent
                                 -translate-x-full group-hover:translate-x-full
                                 transition-transform duration-700 pointer-events-none"></span>

                    <span class="relative z-10 flex items-center gap-2">
                        Iniciar Sesión
                        <x-heroicon-s-arrow-right-on-rectangle class="w-4 h-4 group-hover:translate-x-0.5 transition-transform duration-200" />
                    </span>
                </button>
            </div>

            {{-- ── Métodos Auxiliares ── --}}
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-px flex-grow bg-[#cac4cf]/30 dark:bg-white/[0.08]"></div>
                    <span class="text-[9px] uppercase tracking-widest text-[#79747e] dark:text-white/20">
                        Métodos_Auxiliares
                    </span>
                    <div class="h-px flex-grow bg-[#cac4cf]/30 dark:bg-white/[0.08]"></div>
                </div>

                <button type="button"
                        @click="startScanner()"
                        class="flex items-center justify-center gap-3 w-full py-3.5
                               border border-[#cac4cf]/40 dark:border-white/10 rounded-lg
                               text-[10px] uppercase tracking-widest
                               text-[#001b3d] dark:text-white/50
                               hover:border-orvian-orange/60 dark:hover:border-orvian-orange/40
                               hover:text-orvian-orange dark:hover:text-orvian-orange
                               transition-all duration-200">
                    <x-heroicon-o-qr-code class="w-4 h-4" />
                    Código_QR
                </button>
            </div>
        </form>

        {{-- ─── Modal escáner QR ─── --}}
        <div x-show="showScanner"
             x-cloak
             class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">

            <div class="w-full max-w-md rounded-[1.5rem] p-6 shadow-2xl
                        bg-white dark:bg-dark-card
                        border border-gray-200 dark:border-white/10">

                <div class="flex items-start justify-between mb-5">
                    <div>
                        <h3 class="font-bold text-[#001b3d] dark:text-white text-base">
                            Escanear código institucional
                        </h3>
                        <p class="text-[10px] uppercase tracking-widest mt-0.5 text-[#79747e] dark:text-white/30">
                            Apunta la cámara al QR de tu carnet
                        </p>
                    </div>
                    <button @click="stopScanner()"
                            class="w-8 h-8 rounded-lg flex items-center justify-center
                                   text-gray-400 hover:text-gray-700 hover:bg-gray-100
                                   dark:text-white/30 dark:hover:text-white dark:hover:bg-white/5
                                   transition-colors duration-200">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div id="reader"
                     class="overflow-hidden rounded-xl aspect-square bg-gray-300 dark:bg-dark-bg"></div>

                <div class="flex items-center justify-center gap-2 mt-4">
                    <span class="w-1.5 h-1.5 rounded-full bg-orvian-orange animate-pulse"></span>
                    <span class="text-[9px] uppercase tracking-widest text-[#79747e] dark:text-white/25">
                        Cámara activa — esperando lectura
                    </span>
                </div>
            </div>
        </div>

    </div>

    <script>
    function qrLogin() {
        return {
            showScanner: false,
            qrCode: '',
            scanner: null,

            init() {
                window.addEventListener('open-qr-scanner', () => this.startScanner());
            },

            async startScanner() {
                this.showScanner = true;
                await this.$nextTick();
                try {
                    this.scanner = new Html5Qrcode('reader');
                    await this.scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 240, height: 240 }, aspectRatio: 1.0 },
                        (decoded) => {
                            this.qrCode = decoded;
                            document.querySelector('[name="qr_code"]').value = decoded;
                            this.stopScanner();
                            setTimeout(() => document.getElementById('login-form').submit(), 120);
                        }
                    );
                } catch (err) {
                    console.error('Error al iniciar el escáner:', err);
                    alert('No se pudo acceder a la cámara. Verifica los permisos del navegador.');
                    this.showScanner = false;
                }
            },

            async stopScanner() {
                if (this.scanner && this.scanner.getState() === 2) {
                    try { await this.scanner.stop(); } catch {}
                    this.scanner = null;
                }
                this.showScanner = false;
            }
        };
    }
    </script>
</x-guest-layout>