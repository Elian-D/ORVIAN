<?php

namespace App\Livewire\App\Teachers;

use App\Models\Tenant\Teacher;
use App\Models\User;
use App\Services\Teachers\TeacherService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeacherForm extends Component
{
    use WithFileUploads, AuthorizesRequests;

    public ?Teacher $teacher = null;
    public bool $isEdit      = false;

    // Datos personales
    public string $first_name  = '';
    public string $last_name   = '';
    public string $gender      = 'M';
    public string $rnc         = '';
    public string $date_of_birth = '';
    public string $phone        = '';
    public string $address      = '';

    // Datos laborales
    public string $specialization   = '';
    public string $employment_type = 'Full-Time';
    public string $hire_date        = '';
    public bool   $is_active        = true;

    // Acceso al sistema (opcional)
    public string $email    = '';
    public string $password = '';
    public bool   $create_user_account = false;

    // Foto
    public $photo = null;

    protected function rules(): array
    {
        $userId = $this->teacher?->user_id ?? 'NULL';

        return [
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'gender'          => 'required|in:M,F',
            'rnc'             => 'nullable|string|min:11|max:15',
            'date_of_birth'   => 'nullable|date',
            'phone'           => 'nullable|string|max:20',
            'address'         => 'nullable|string|max:500',
            'specialization'  => 'nullable|string|max:150',
            'employment_type' => 'required|in:Full-Time,Part-Time,Substitute',
            'hire_date'       => 'required|date',
            'is_active'       => 'boolean',
            'photo'           => 'nullable|image|max:2048',
            // Validaciones de usuario solo si se crea cuenta
            'email'           => $this->create_user_account
                                    ? "required|email|unique:users,email,{$userId}"
                                    : 'nullable|email',
            'password'        => $this->create_user_account && ! $this->isEdit
                                    ? 'required|min:8'
                                    : 'nullable|min:8',
        ];
    }

    public function mount(?Teacher $teacher = null): void
    {
        if ($teacher && $teacher->exists) {
            $this->teacher    = $teacher;
            $this->isEdit     = true;

            $this->first_name      = $teacher->first_name;
            $this->last_name       = $teacher->last_name;
            $this->gender          = $teacher->gender;
            $this->rnc             = $teacher->rnc ?? '';
            $this->date_of_birth   = $teacher->date_of_birth?->format('Y-m-d') ?? '';
            $this->phone           = $teacher->phone ?? '';
            $this->address         = $teacher->address ?? '';
            $this->specialization  = $teacher->specialization ?? '';
            $this->employment_type = $teacher->employment_type;
            $this->hire_date       = $teacher->hire_date?->format('Y-m-d') ?? '';
            $this->is_active       = $teacher->is_active;

            if ($teacher->user_id) {
                $this->create_user_account = true;
                $this->email               = $teacher->user->email;
            }
        } else {
            $this->hire_date = now()->format('Y-m-d');
        }
    }

    public function save(TeacherService $teacherService): void
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('notify',
                type: 'error',
                title: 'Error de validación',
                message: 'Por favor, revisa los campos marcados.'
            );
            throw $e;
        }

        $cleanRnc = preg_replace('/[^0-9]/', '', $this->rnc);

        try {
            DB::beginTransaction();

            $schoolId = Auth::user()->school_id;
            $userId   = null;

            // ── Gestión de cuenta de usuario ──────────────────────
            if ($this->create_user_account) {
                if (! $this->isEdit || ! $this->teacher->user_id) {
                    // Crear nueva cuenta
                    $user = User::create([
                        'name'      => "{$this->first_name} {$this->last_name}",
                        'email'     => $this->email,
                        'password'  => Hash::make($this->password),
                        'school_id' => $schoolId,
                        'status'    => 'active',
                    ]);
                    setPermissionsTeamId($schoolId);
                    $user->assignRole('teacher');
                    $userId = $user->id;
                } else {
                    // Actualizar cuenta existente
                    $this->teacher->user->update([
                        'name'  => "{$this->first_name} {$this->last_name}",
                        'email' => $this->email,
                    ]);
                    if (! empty($this->password)) {
                        $this->teacher->user->update([
                            'password' => Hash::make($this->password),
                        ]);
                    }
                    $userId = $this->teacher->user_id;
                }
            }

            $teacherData = [
                'school_id'       => $schoolId,
                'user_id'         => $userId,
                'first_name'      => $this->first_name,
                'last_name'       => $this->last_name,
                'gender'          => $this->gender,
                'rnc'             => $cleanRnc ?: null,
                'date_of_birth'   => $this->date_of_birth ?: null,
                'phone'           => $this->phone ?: null,
                'address'         => $this->address ?: null,
                'specialization'  => $this->specialization ?: null,
                'employment_type' => $this->employment_type,
                'hire_date'       => $this->hire_date,
                'is_active'       => $this->is_active,
            ];

            if (! $this->isEdit) {
                $this->teacher = $teacherService->createTeacher($teacherData);
            } else {
                $this->teacher->update($teacherData);
            }

            // ── Foto ──────────────────────────────────────────────
            if ($this->photo) {
                $teacherService->updatePhoto($this->teacher, $this->photo);
            }

            DB::commit();

            $message = $this->isEdit
                ? 'Perfil del maestro actualizado correctamente.'
                : 'Maestro registrado correctamente.';

            redirect()->route('app.academic.teachers.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify',
                type: 'error',
                title: 'Error de sistema',
                message: 'No se pudo procesar: ' . $e->getMessage()
            );
        }
    }

    public function render()
    {
        /** @var \Livewire\Features\SupportPageComponents\View $view */
        $view = view('livewire.app.teachers.teacher-form');

        return $view->layout('layouts.app-module', config('modules.configuracion'));
    }
}