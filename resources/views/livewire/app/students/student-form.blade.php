{{-- Agregamos w-full para evitar que el flex-1 del layout colapse el ancho --}}
<div class="w-full max-w-7xl mx-auto p-4 md:p-8">
    
    {{-- Header: Se mantiene igual pero asegurando alineación --}}
    <x-ui.page-header
        title="{{ $isEdit ? 'Editar Perfil' : 'Nuevo Estudiante' }}"
        description="Expediente académico y ficha médica biometrizada."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" type="ghost" href="{{ route('app.academic.students.index') }}">
                Cancelar
            </x-ui.button>
            <x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $isEdit ? 'Actualizar Cambios' : 'Registrar Estudiante' }}
                </span>
                <span wire:loading wire:target="save">Guardando...</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Error General --}}
    @if($errors->has('general'))
        <div class="mb-8 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 text-sm rounded-r-xl">
            <div class="flex items-center gap-2">
                <x-heroicon-s-x-circle class="w-5 h-5" />
                {{ $errors->first('general') }}
            </div>
        </div>
    @endif

    {{-- Grid Principal: lg:grid-cols-12 --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Columna Izquierda: Formularios (8 de 12 columnas) --}}
        <div class="lg:col-span-8 space-y-8">
            
            {{-- Card: Información Personal --}}
            <section class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3 mb-8 border-b border-slate-50 dark:border-dark-border pb-4">
                    <div class="p-2 bg-orvian-orange/10 text-orvian-orange rounded-lg">
                        <x-heroicon-s-user-circle class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold uppercase text-sm tracking-widest text-slate-700 dark:text-slate-300">Información Personal</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <x-ui.forms.input 
                        label="Nombres" 
                        wire:model="first_name" 
                        placeholder="Ej. Juan Gabriel" 
                        :error="$errors->first('first_name')" 
                        required 
                    />
                    
                    <x-ui.forms.input 
                        label="Apellidos" 
                        wire:model="last_name" 
                        placeholder="Ej. Perez Rosario" 
                        :error="$errors->first('last_name')" 
                        required 
                    />

                    <x-ui.forms.input 
                        label="Correo Electrónico" 
                        type="email"
                        wire:model="email" 
                        placeholder="estudiante@orvian.com" 
                        :error="$errors->first('email')" 
                        required 
                    />

                    <x-ui.forms.input 
                        label="Documento" 
                        wire:model="rnc" 
                        placeholder="000-0000000-0" 
                        :error="$errors->first('rnc')" 
                        hint="Identificación oficial"
                        required 
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
                </div>
            </section>

            {{-- Card: Información Médica --}}
            <section class="bg-white dark:bg-dark-card rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3 mb-8 border-b border-slate-50 dark:border-dark-border pb-4 text-red-500">
                    <div class="p-2 bg-red-50 dark:bg-red-500/10 rounded-lg">
                        <x-heroicon-s-heart class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold uppercase text-sm tracking-widest">Ficha Médica</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <x-ui.forms.select label="Tipo de Sangre" wire:model="blood_type">
                        <option value="">Desconocido</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </x-ui.forms.select>

                    <div class="md:col-span-2">
                        <x-ui.forms.input 
                            label="Alergias Conocidas" 
                            wire:model="allergies" 
                            placeholder="Medicamentos, alimentos, etc." 
                        />
                    </div>
                </div>

                <div class="mt-6">
                    <x-ui.forms.textarea 
                        label="Notas de Salud Especiales" 
                        wire:model="medical_notes" 
                        placeholder="Cualquier información relevante para el personal de enfermería..." 
                        :rows="2"
                    />
                </div>
            </section>
        </div>

        {{-- Columna Derecha: Sidebar (4 de 12 columnas) --}}
        <div class="lg:col-span-4 space-y-8">
            
            {{-- Card: Foto --}}
            <section x-data="webcamHandler()" class="bg-white dark:bg-dark-card rounded-3xl p-8 shadow-sm border border-slate-100 dark:border-dark-border text-center">
                <div class="relative w-32 h-32 mx-auto mb-6">
                    {{-- Spinner de carga de Livewire (Usando tu nuevo componente) --}}
                    <div wire:loading wire:target="photo" 
                        class="absolute inset-0 z-50 flex items-center justify-center bg-white/60 dark:bg-dark-bg/60 rounded-2xl backdrop-blur-[1px]">
                        <x-ui.loading size="lg" class="text-orvian-orange" />
                    </div>

                    {{-- Video Stream (Cámara activa) --}}
                    <video x-ref="video" 
                        autoplay 
                        playsinline 
                        :class="streaming ? '' : 'hidden'" 
                        class="w-full h-full rounded-2xl object-cover ring-4 ring-orvian-orange shadow-lg">
                    </video>
                    
                    {{-- Previsualización (Cámara inactiva) --}}
                    <template x-if="!streaming">
                        <div class="w-full h-full" wire:loading.remove wire:target="photo">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" 
                                    class="w-full h-full rounded-2xl object-cover ring-4 ring-orvian-orange/10 shadow-lg">
                            @elseif($student && $student->photo_path)
                                <img src="{{ Storage::url($student->photo_path) }}" 
                                    class="w-full h-full rounded-2xl object-cover ring-4 ring-slate-100">
                            @else
                                <div class="w-full h-full rounded-2xl bg-slate-50 dark:bg-dark-bg flex items-center justify-center border-2 border-dashed border-slate-200 dark:border-dark-border">
                                    <x-heroicon-o-camera class="w-10 h-10 text-slate-300" />
                                </div>
                            @endif
                        </div>
                    </template>

                    {{-- Botones Flotantes --}}
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

                {{-- Canvas oculto para procesar la captura --}}
                <canvas x-ref="canvas" class="hidden"></canvas>
            </section>
            {{-- Card: Académico --}}
            <section class="bg-white dark:bg-dark-card rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-dark-border">
                <div class="flex items-center gap-3 mb-6 text-blue-500">
                    <div class="p-2 bg-blue-50 dark:bg-blue-500/10 rounded-lg">
                        <x-heroicon-s-academic-cap class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold uppercase text-[11px] tracking-widest">Asignación Académica</h3>
                </div>

                <div class="space-y-6">
                    <x-ui.forms.select 
                        label="Sección / Curso" 
                        wire:model="school_section_id" 
                        iconLeft="heroicon-o-rectangle-group"
                        :error="$errors->first('school_section_id')"
                        required
                    >
                        @foreach($sections as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.forms.select>
                </div>
            </section>

            {{-- Card: Biometría --}}
            <section class="relative overflow-hidden p-6 transition-all duration-300 rounded-3xl bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-white shadow-xl shadow-slate-200 dark:shadow-none border border-slate-200 dark:border-slate-800">
                
                <div class="relative z-10 flex items-center gap-4">
                    {{-- Contenedor del Icono --}}
                    <div @class([
                        'p-3 rounded-2xl transition-colors duration-300',
                        // Estado: Verificado (Verde)
                        'bg-green-500 text-white shadow-lg shadow-green-200 dark:shadow-none' => $student?->has_face_encoding,
                        // Estado: Pendiente (Gris neutro adaptable)
                        'bg-slate-200 dark:bg-white/10 text-slate-400 dark:text-white/40' => !$student?->has_face_encoding,
                    ])>
                        <x-heroicon-s-finger-print class="w-8 h-8" />
                    </div>

                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-tight leading-none">Estatus Biométrico</h4>
                        <p class="text-[11px] mt-1 font-medium text-slate-500 dark:text-white/60">
                            {{ $student?->has_face_encoding ? 'Identidad verificada' : 'Pendiente de captura facial' }}
                        </p>
                    </div>
                </div>
                
                {{-- Decoración de fondo (Marca de agua) --}}
                <div class="absolute -right-4 -bottom-4 opacity-[0.05] dark:opacity-10 text-slate-900 dark:text-white">
                    <x-heroicon-s-finger-print class="w-24 h-24" />
                </div>
            </section>
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
                this.$refs.video.classList.remove('hidden');
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
            
            const startX = (video.videoWidth - size) / 2;
            const startY = (video.videoHeight - size) / 2;

            context.drawImage(video, startX, startY, size, size, 0, 0, size, size);

            canvas.toBlob((blob) => {
                if (!blob) return;

                const file = new File([blob], "webcam_capture.jpg", { type: "image/jpeg" });
                
                // Feedback visual: mostrar que se está procesando
                this.streaming = false; 

                $wire.upload('photo', file, (uploadedFilename) => {
                    // ÉXITO: Disparamos el Toast de tu sistema
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            type: 'success',
                            title: 'Foto Capturada',
                            message: 'La imagen se ha procesado correctamente.',
                            duration: 3000
                        }
                    }));
                    this.stopCamera();
                }, (error) => {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            type: 'error',
                            title: 'Error de captura',
                            message: 'No se pudo subir la imagen.',
                        }
                    }));
                });
            }, 'image/jpeg', 0.95);
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            if(this.$refs.video) this.$refs.video.srcObject = null;
            this.streaming = false;
        }
    }));
</script>
@endscript