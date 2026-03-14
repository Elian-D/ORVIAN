<?php

namespace App\Models;

use App\Models\Tenant\School;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'school_id',
        'avatar_path',
        'avatar_color',
        'phone',
        'position',
        'status',
        'last_login_at',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'preferences' => 'array', // Cast automático de JSON a Array de PHP
        ];
    }

    /**
     * Helper para obtener preferencias con dot notation.
     * Ejemplo: $user->preference('theme', 'light')
     */
    public function preference(string $key, mixed $default = null): mixed
    {
        return data_get($this->preferences, $key, $default);
    }

    /**
     * Determina la ruta de redirección según el rol y estado del usuario.
     */
    public function redirectPath(): string
    {
        if ($this->hasRole('Owner')) {
            return route('admin.hub');
        }

        if ($this->school_id && !$this->school->is_configured) {
            return route('wizard');
        }

        return route('app.dashboard');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public static function createWithSchool(array $data, int $schoolId): self
    {
        return DB::transaction(function () use ($data, $schoolId) {
            $user = self::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'school_id' => $schoolId,
                // Al crear, el Observer asignará el avatar_color
                'preferences' => [
                    'theme' => 'system',
                    'sidebar_collapsed' => false,
                ],
            ]);

            setPermissionsTeamId($schoolId);
            $user->assignRole($data['role'] ?? 'Staff');

            return $user;
        });
    }
}