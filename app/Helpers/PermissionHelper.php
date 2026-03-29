<?php

use Illuminate\Support\Facades\Lang;

if (!function_exists('trans_permission')) {
    /**
     * Traduce un permiso técnico.
     * @param string $name Ej: 'users.view'
     * @param string $key 'label' o 'description'
     */
    function trans_permission(string $name, string $key = 'label'): string
    {
        // Construye la ruta: permissions.users.view.label
        $langKey = "permissions.{$name}.{$key}";

        if (Lang::has($langKey)) {
            return __($langKey);
        }

        // Fallback: Si no existe, intenta buscar la llave plana o limpia el texto
        // Esto evita que el usuario vea 'schools.view' tal cual
        return ucfirst(str_replace(['.', '_'], ' ', $name));
    }
}