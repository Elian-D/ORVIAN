<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    // Constantes base (puedes añadir más vía UI luego)
    const BASIC = 'basic';
    const PREMIUM = 'premium';
    const ENTERPRISE = 'enterprise';


    protected $fillable = [
        'name', 'slug', 'limit_students', 'limit_users', 
        'price', 'const_name', 'bg_color', 'text_color'
    ];

    /**
     * Accesor para obtener los estilos CSS directamente.
     * Uso: $plan->badge_style
     */
    protected function badgeStyle(): Attribute
    {
        return Attribute::make(
            get: fn () => "background-color: {$this->bg_color}; color: {$this->text_color}; border-radius: 9999px; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center;"
        );
    }

    /**
     * Un plan puede ser tenido por muchas escuelas.
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}