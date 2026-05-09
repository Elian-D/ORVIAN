{{-- resources/views/livewire/app/teachers/teacher-form.blade.php --}}
<div class="w-full max-w-7xl mx-auto p-4 md:p-8">
    
    {{-- Header --}}
    <x-ui.page-header
        title="{{ $isEdit ? 'Editar Maestro' : 'Nuevo Maestro' }}"
        description="Gestión de expediente docente y credenciales de acceso."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" type="ghost" href="{{ route('app.academic.teachers.index') }}">
                Cancelar
            </x-ui.button>
            <x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $isEdit ? 'Actualizar Maestro' : 'Guardar Maestro' }}
                </span>
                <span wire:loading wire:target="save">Procesando...</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Columna Izquierda: Formularios --}}
        <div class="lg:col-span-8 space-y-8">
            
            {{-- Sección 1: Datos Personales --}}
            <section class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3 mb-8 border-b border-slate-50 dark:border-dark-border pb-4">
                    <div class="p-2 bg-orvian-orange/10 text-orvian-orange rounded-lg">
                        <x-heroicon-s-user class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Datos Personales</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <x-ui.forms.input 
                        label="Nombres" 
                        wire:model="first_name" 
                        placeholder="Ej. Roberto" 
                        :error="$errors->first('first_name')" 
                        required 
                    />
                    
                    <x-ui.forms.input 
                        label="Apellidos" 
                        wire:model="last_name" 
                        placeholder="Ej. Gómez" 
                        :error="$errors->first('last_name')" 
                        required 
                    />

                    <x-ui.forms.input 
                        label="RNC / Cédula" 
                        wire:model="rnc" 
                        placeholder="000-0000000-0" 
                        :error="$errors->first('rnc')" 
                    />

                    <div class="flex flex-col gap-3">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Género</label>
                        <div class="flex gap-6 h-[45px] items-center">
                            <x-ui.forms.radio label="Masculino" name="gender" value="M" wire:model="gender" />
                            <x-ui.forms.radio label="Femenino" name="gender" value="F" wire:model="gender" />
                        </div>
                    </div>

                    <x-ui.forms.input 
                        label="Fecha de Nacimiento" 
                        type="date"
                        wire:model="date_of_birth" 
                        :error="$errors->first('date_of_birth')" 
                    />

                    <x-ui.forms.input 
                        label="Teléfono" 
                        wire:model="phone" 
                        placeholder="809-000-0000" 
                        :error="$errors->first('phone')" 
                    />

                    <div class="md:col-span-2">
                        <x-ui.forms.textarea 
                            label="Dirección de Residencia" 
                            wire:model="address" 
                            placeholder="Calle, Número, Sector..." 
                            :rows="2"
                        />
                    </div>
                </div>
            </section>

            {{-- Sección 2: Datos Laborales --}}
            <section class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3 mb-8 border-b border-slate-50 dark:border-dark-border pb-4 text-blue-500">
                    <div class="p-2 bg-blue-50 dark:bg-blue-500/10 rounded-lg">
                        <x-heroicon-s-briefcase class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold uppercase text-sm tracking-widest">Información Laboral</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <x-ui.forms.input 
                        label="Especialidad / Área" 
                        wire:model="specialization" 
                        placeholder="Ej. Matemáticas, Ciencias..." 
                        :error="$errors->first('specialization')" 
                    />

                    <x-ui.forms.select label="Tipo de Contrato" wire:model="employment_type" required>
                        <option value="Full-Time">Tiempo Completo</option>
                        <option value="Part-Time">Tiempo Parcial</option>
                        <option value="Substitute">Suplente</option>
                    </x-ui.forms.select>

                    <x-ui.forms.input 
                        label="Fecha de Contratación" 
                        type="date"
                        wire:model="hire_date" 
                        :error="$errors->first('hire_date')" 
                        required
                    />
                </div>
            </section>

            
            {{-- Sección 3: Acceso al Sistema (Breeze compatible) --}}
            <section x-data="{ open: @entangle('create_user_account') }" class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border overflow-hidden">
                <div class="flex items-center justify-between mb-8 border-b border-slate-50 dark:border-dark-border pb-4">
                    <div class="flex items-center gap-3 text-purple-500">
                        <div class="p-2 bg-purple-50 dark:bg-purple-500/10 rounded-lg">
                            <x-heroicon-s-key class="w-6 h-6" />
                        </div>
                        <h3 class="font-bold uppercase text-sm tracking-widest">Acceso al Sistema</h3>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="create_user_account" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                        <span class="ml-3 text-xs font-bold text-slate-500 uppercase tracking-tighter">Habilitar Cuenta</span>
                    </label>
                </div>

                <div x-show="open" x-collapse x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 bg-purple-50/30 dark:bg-purple-900/10 p-6 rounded-2xl border border-purple-100 dark:border-purple-900/30">
                        <x-ui.forms.input 
                            label="Correo Electrónico (Usuario)" 
                            type="email"
                            wire:model="email" 
                            placeholder="maestro@orvian.com" 
                            :error="$errors->first('email')" 
                        />

                        <div class="space-y-1">
                            <x-ui.forms.input 
                                label="Contraseña" 
                                type="password"
                                wire:model="password" 
                                placeholder="••••••••" 
                                :error="$errors->first('password')" 
                            />
                            @if($isEdit)
                                <span class="text-[10px] text-slate-400 italic">Dejar vacío para mantener la contraseña actual</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div x-show="!open" class="text-center py-4 text-slate-400 text-sm italic">
                    Este maestro no tendrá acceso para iniciar sesión en la plataforma.
                </div>
            </section>
        </div>

        {{-- Columna Derecha: Sidebar --}}
        <div class="lg:col-span-4 space-y-8">
            
            {{-- Card: Foto (Cámara & Upload) --}}
            <section x-data="webcamHandler()" class="bg-white dark:bg-dark-card rounded-3xl p-8 shadow-sm border border-slate-100 dark:border-dark-border text-center">
                <div class="relative w-32 h-32 mx-auto mb-6">
                    {{-- Spinner --}}
                    <div wire:loading wire:target="photo" 
                        class="absolute inset-0 z-50 flex items-center justify-center bg-white/60 dark:bg-dark-bg/60 rounded-2xl backdrop-blur-[1px]">
                        <x-ui.loading size="lg" class="text-orvian-orange" />
                    </div>

                    {{-- Video --}}
                    <video x-ref="video" autoplay playsinline :class="streaming ? '' : 'hidden'" 
                        class="w-full h-full rounded-2xl object-cover ring-4 ring-orvian-orange shadow-lg">
                    </video>
                    
                    {{-- Preview --}}
                    <template x-if="!streaming">
                        <div class="w-full h-full" wire:loading.remove wire:target="photo">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full rounded-2xl object-cover ring-4 ring-orvian-orange/10 shadow-lg">
                            @elseif($teacher && $teacher->photo_path)
                                <img src="{{ Storage::url($teacher->photo_path) }}" class="w-full h-full rounded-2xl object-cover ring-4 ring-slate-100">
                            @else
                                <div class="w-full h-full rounded-2xl bg-slate-50 dark:bg-dark-bg flex items-center justify-center border-2 border-dashed border-slate-200 dark:border-dark-border">
                                    <x-heroicon-o-camera class="w-10 h-10 text-slate-300" />
                                </div>
                            @endif
                        </div>
                    </template>

                    {{-- Botones Cámara --}}
                    <div class="absolute -bottom-2 -right-2 flex gap-1 z-10">
                        <button type="button" x-show="!streaming" @click="startCamera()" 
                                class="p-2 bg-orvian-orange text-white rounded-full shadow-lg hover:scale-110 transition-transform">
                            <x-heroicon-s-camera class="w-4 h-4" />
                        </button>
                        <button type="button" x-show="streaming" @click="takePhoto()" 
                                class="p-2 bg-green-500 text-white rounded-full shadow-lg hover:scale-110 transition-transform">
                            <x-heroicon-s-check class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div x-show="!streaming">
                    <x-ui.forms.file-input 
                        label="Foto de Perfil" 
                        wire:model="photo" 
                        accept="image/*"
                        hint="JPG/PNG. Máx 2MB"
                    />
                </div>
                
                <div x-show="streaming" class="text-xs text-orvian-orange font-bold animate-pulse uppercase tracking-widest">
                    Cámara Activa
                </div>
                <canvas x-ref="canvas" class="hidden"></canvas>
            </section>

            {{-- Card: Identificación QR (Solo modo edición) --}}
            @if($isEdit)
            <section class="bg-white dark:bg-dark-card rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3 mb-6 text-slate-700 dark:text-slate-300">
                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
                        <x-heroicon-s-qr-code class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold uppercase text-[11px] tracking-widest">Identificación QR</h3>
                </div>

                <div class="flex flex-col items-center gap-4">
                    <div class="p-4 bg-white rounded-2xl shadow-inner">
                        {!! QrCode::size(120)->generate($teacher->qr_code) !!}
                    </div>
                    <p class="text-[10px] text-slate-400 font-mono text-center">{{ $teacher->qr_code }}</p>
                    <x-ui.button iconLeft="heroicon-o-printer" variant="secondary" size="sm" type="ghost" class="w-full">
                        Imprimir Carnet
                    </x-ui.button>
                </div>
            </section>
            @endif
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('webcamHandler', () => ({
        streaming: false,
        stream: null,

        async startCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: "user", width: { ideal: 600 }, height: { ideal: 600 } }, 
                    audio: false 
                });
                this.$refs.video.srcObject = this.stream;
                this.streaming = true;
            } catch (err) {
                console.error(err);
                alert("No se pudo acceder a la cámara.");
            }
        },

        takePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            const context = canvas.getContext('2d');
            const size = Math.min(video.videoWidth, video.videoHeight);
            
            canvas.width = size;
            canvas.height = size;
            context.drawImage(video, (video.videoWidth - size) / 2, (video.videoHeight - size) / 2, size, size, 0, 0, size, size);

            canvas.toBlob((blob) => {
                if (!blob) return;
                const file = new File([blob], "teacher_photo.jpg", { type: "image/jpeg" });
                this.streaming = false; 

                $wire.upload('photo', file, (uploadedFilename) => {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { type: 'success', title: 'Foto Capturada', message: 'Imagen procesada.' }
                    }));
                    this.stopCamera();
                });
            }, 'image/jpeg', 0.95);
        },

        stopCamera() {
            if (this.stream) this.stream.getTracks().forEach(track => track.stop());
            this.streaming = false;
        }
    }));
</script>
@endscript