<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Support\Str;

class UserAvatarService
{
    /**
     * Paleta de colores inspirada en UI modernas (Chatwoot/Slack).
     * Son colores de fondo; el texto se manejará con una variante oscura en el componente.
     */
    protected const AVATAR_PALETTE = [
        '#FF8A65', // Deep Orange
        '#4DB6AC', // Teal
        '#64B5F6', // Blue
        '#BA68C8', // Purple
        '#F06292', // Pink
        '#AED581', // Light Green
        '#FFD54F', // Amber
        '#4FC3F7', // Light Blue
        '#FFB74D', // Orange
        '#9575CD', // Deep Purple
    ];

    /**
     * Genera las iniciales del usuario (Max 2 letras).
     */
    public function initials(string $name): string
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
        }
        return strtoupper(substr($name, 0, 1));
    }

    /**
     * Selecciona un color aleatorio de la paleta.
     */
    public function generateColor(): string
    {
        return self::AVATAR_PALETTE[array_rand(self::AVATAR_PALETTE)];
    }

    /**
     * Retorna la URL del avatar (Imagen o Placeholder).
     */
    public function avatarUrl(User $user): ?string
    {
        if ($user->avatar_path) {
            return asset('storage/' . $user->avatar_path);
        }

        return null; // El componente Blade manejará el renderizado de iniciales si es null
    }
}