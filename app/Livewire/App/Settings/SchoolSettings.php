<?php

namespace App\Livewire\App\Settings;

use App\Models\Tenant\School;
use App\Models\Geo\Province;
use App\Models\Geo\Municipality;
use App\Models\Geo\RegionalEducation;
use App\Models\Geo\EducationalDistrict;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

#[Title('Configuración Institucional')]
class SchoolSettings extends Component
{
    use WithFileUploads;

    public School $school;

    // Propiedades del Formulario
    public $name;
    public $sigerd_code;
    public $phone;
    public $address_detail;
    public $regimen_gestion;
    public $modalidad;
    
    // Geografía
    public $province_id;
    public $municipality_id;
    public $regional_education_id;
    public $educational_district_id;

    // Multimedia
    public $newLogo;

    public $latitude;
    public $longitude;

    public $current_academic_year;

    // ── Nuevas propiedades para el modal de creación ──────────────

    public bool   $showCreateDeviceModal = false;
    public string $newDeviceName         = '';
    public ?string $generatedToken       = null;   // Solo vive mientras el modal está abierto

    // ── Nuevas propiedades para el modal de revocación ────────────

    public bool   $showRevokeModal       = false;
    public ?int   $tokenToRevokeId       = null;
    public string $revokeConfirmName     = '';    // El usuario debe tipear el nombre del dispositivo
    public string $deviceToRevokeName = '';

    // ── Propiedad para gestión del PIN ────────────────────────────

    public string $kioskPin              = '';
    public string $kioskPinConfirm       = '';


    public function mount()
    {
        $this->school = Auth::user()->school; 
        
        // Cargar el año escolar activo desde la relación (asumiendo relación 'academicYears')
        $activeYear = $this->school->academicYears()->where('is_active', true)->first();
        $this->current_academic_year = $activeYear ? $activeYear->name : 'No definido';

        $this->fill($this->school->only([
            'name', 'sigerd_code', 'phone', 'address_detail',
            'province_id', 'municipality_id', 
            'regional_education_id', 'educational_district_id',
            'regimen_gestion', 'modalidad',
            'latitude', 'longitude',
        ]));
    }

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sigerd_code' => [
                'required', 
                'max:8', 
                Rule::unique('schools', 'sigerd_code')->ignore($this->school->id)
            ],
            // CORREGIDO: Regex más flexible para teléfonos dominicanos
            // Acepta: 8090000000, 809-000-0000, (809) 000-0000, +1 809 000 0000
            'phone' => [
                'required',
                'regex:/^(\+?1[-.\s]?)?(\(?\d{3}\)?[-.\s]?)?\d{3}[-.\s]?\d{4}$/'
            ],
            'regimen_gestion' => ['required', 'string'],
            'modalidad' => ['required', 'string'],
            'province_id' => ['required', 'exists:provinces,id'],
            'municipality_id' => ['required', 'exists:municipalities,id'],
            'regional_education_id' => ['required', 'exists:regional_education,id'],
            'educational_district_id' => ['required', 'exists:educational_districts,id'],
            'address_detail' => ['nullable', 'string', 'max:500'],
            'newLogo' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    protected $messages = [
        'phone.regex' => 'El formato del teléfono no es válido. Usa formato: 809-000-0000 o similar.',
        'sigerd_code.unique' => 'Este código SIGERD ya está registrado en otra escuela.',
        'newLogo.max' => 'El logotipo no puede superar los 2MB.',
        'newLogo.mimes' => 'El logotipo debe ser una imagen (JPG, PNG o WEBP).',
    ];

    // Agrega este método para procesar el evento del mapa
    public function saveLocation($lat, $lng): void
    {
        $this->latitude = $lat;
        $this->longitude = $lng;

        // Opcional: Si quieres que se guarde inmediatamente al mover el pin, 
        // descomenta la línea de abajo. Si prefieres que se guarde solo al dar 
        // al botón principal de "Guardar Cambios", déjalo así.
        //$this->school->update(['latitude' => $lat, 'longitude' => $lng]);
    }

    public function updatedProvinceId()
    {
        $this->municipality_id = null;
    }

    public function updatedRegionalEducationId()
    {
        $this->educational_district_id = null;
    }

    #[Computed]
    public function provinces()
    {
        return Province::orderBy('name')->get();
    }

    #[Computed]
    public function municipalities()
    {
        if (!$this->province_id) {
            return collect();
        }
        
        return Municipality::where('province_id', $this->province_id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function regionals()
    {
        return RegionalEducation::orderBy('id')->get();
    }

    #[Computed]
    public function districts()
    {
        if (!$this->regional_education_id) {
            return collect();
        }
        
        return EducationalDistrict::where('regional_education_id', $this->regional_education_id)
            ->orderBy('id')
            ->get();
    }

    public function updatedNewLogo()
    {
        $this->validateOnly('newLogo');

        try {
            // Eliminar logo anterior si existe
            if ($this->school->logo_path) {
                Storage::disk('public')->delete($this->school->logo_path);
            }

            // Guardar nuevo logo
            $path = $this->newLogo->store("schools/{$this->school->id}/branding", 'public');
            
            $this->school->update(['logo_path' => $path]);
            $this->school->refresh();

            // Reset del input
            $this->newLogo = null;

            // CORREGIDO: Toast según documentación (array asociativo)
            $this->dispatch('notify', 
                type: 'success',
                title: 'Logotipo actualizado',
                message: 'La identidad visual del centro ha sido guardada correctamente.'
            );
        } catch (\Exception $e) {
            Log::error('Error al actualizar logo', [
                'error' => $e->getMessage(),
                'school_id' => $this->school->id
            ]);

            $this->dispatch('notify',
                type: 'error',
                title: 'Error al guardar',
                message: 'No se pudo actualizar el logotipo. Intenta de nuevo.'
            );
        }
    }

    /**
     * Crea un token individual para un dispositivo.
     * NO revoca tokens existentes.
     */
    public function createDeviceToken(): void
    {
        $this->authorize('settings.update');

        $this->validate([
            'newDeviceName' => ['required', 'string', 'min:3', 'max:50'],
        ]);

        try {
            $school = Auth::user()->school;

            // Verificar que no existe otro token activo con el mismo nombre
            $existingNames = $school->tokens()
                ->where('abilities', json_encode(['kiosk']))
                ->pluck('name');

            if ($existingNames->contains($this->newDeviceName)) {
                $this->addError('newDeviceName', 'Ya existe un dispositivo con ese nombre.');
                return;
            }

            $this->generatedToken = $school->createToken(
                $this->newDeviceName,
                ['kiosk']
            )->plainTextToken;

            // El modal transiciona a mostrar el token. No se cierra aún.
            $this->newDeviceName = '';

        } catch (\Exception $e) {
            Log::error('Error al crear token de dispositivo kiosko', [
                'school_id' => Auth::user()->school_id,
                'error'     => $e->getMessage(),
            ]);

            $this->dispatch('notify',
                type: 'error',
                title: 'Error',
                message: 'No se pudo generar el token. Intente de nuevo.'
            );
        }
    }

    /**
     * Inicia el flujo de revocación mostrando el modal de confirmación.
     */
    public function confirmRevokeDevice(int $tokenId, string $tokenName): void
    {
        $this->authorize('settings.update');
        $this->tokenToRevokeId    = $tokenId;
        $this->revokeConfirmName  = '';
        
        // Almacenamos el nombre original para enviarlo al Placeholder
        $this->deviceToRevokeName = $tokenName; 
        
        // Es recomendable utilizar el evento de Alpine para abrir x-modal
        $this->dispatch('open-modal', 'showRevokeModal');
    }

    /**
     * Ejecuta la revocación tras la confirmación por nombre.
     */
    public function revokeDevice(): void
    {
        $this->authorize('settings.update');

        $token = Auth::user()->school->tokens()->find($this->tokenToRevokeId);

        if (!$token) {
            $this->dispatch('notify', type: 'error', message: 'Token no encontrado.');
            $this->resetRevokeModal();
            return;
        }

        // El usuario debe haber tipeado exactamente el nombre del dispositivo
        if ($this->revokeConfirmName !== $token->name) {
            $this->addError('revokeConfirmName', 'El nombre no coincide. Escríbelo exactamente.');
            return;
        }

        $token->delete();
        $this->dispatch('close-modal', 'showRevokeModal');
        $this->dispatch('notify',
            type: 'success',
            title: 'Dispositivo desconectado',
            message: "El dispositivo \"{$token->name}\" ya no tiene acceso al sistema."
        );

        $this->resetRevokeModal();
    }

    /**
     * Guarda o actualiza el PIN de acceso al modo técnico del kiosko.
     */
    public function saveKioskPin(): void
    {
        $this->authorize('settings.update');

        $this->validate([
            'kioskPin'        => ['required', 'digits_between:4,6'],
            'kioskPinConfirm' => ['required', 'same:kioskPin'],
        ]);

        Auth::user()->school->update([
            'kiosk_pin' => bcrypt($this->kioskPin),
        ]);

        $this->kioskPin        = '';
        $this->kioskPinConfirm = '';

        $this->dispatch('notify',
            type: 'success',
            title: 'PIN actualizado',
            message: 'El nuevo PIN de técnico entrará en efecto en el próximo heartbeat del kiosko.'
        );
    }

    private function resetRevokeModal(): void
    {
        $this->showRevokeModal  = false;
        $this->tokenToRevokeId  = null;
        $this->revokeConfirmName = '';
    }

    public function save()
    {
        $data = $this->validate();
        unset($data['newLogo']);

        try {
            $this->school->update($data);

            // CORREGIDO: Toast según documentación
            $this->dispatch('notify', 
                type: 'success',
                title: 'Configuración guardada',
                message: 'Todos los cambios institucionales han sido aplicados correctamente.'
            );
        } catch (\Exception $e) {
            Log::error('Error al guardar configuración escolar', [
                'error' => $e->getMessage(),
                'school_id' => $this->school->id,
                'data' => $data
            ]);

            $this->dispatch('notify', 
                type: 'error',
                title: 'Error al guardar',
                message: 'No se pudieron guardar los cambios. Verifica los datos e intenta de nuevo.'
            );
        }
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.settings.school-settings');

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}