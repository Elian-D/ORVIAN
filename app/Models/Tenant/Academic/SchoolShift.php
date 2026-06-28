<?php

namespace App\Models\Tenant\Academic;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SchoolShift extends Model
{
    use BelongsToSchool;

    const TYPE_MORNING   = 'Matutina';
    const TYPE_AFTERNOON = 'Vespertina';
    const TYPE_EXTENDED  = 'Jornada Extendida';
    const TYPE_NIGHT     = 'Nocturna';

    protected $fillable = [
        'school_id',
        'type',
        'start_time',
        'end_time',
        'late_threshold_minutes',
    ];

    /**
     * Al castear a 'datetime:H:i', Laravel nos devuelve 
     * un objeto Carbon cuando accedemos a start_time.
     */
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
    ];

    // Relación inversa
    public function sections()
    {
        return $this->hasMany(SchoolSection::class);
    }

    // Scope útil
    public function scopeWithSectionCount($query)
    {
        return $query->withCount('sections');
    }

    /**
     * Determina si la tanda ya puede ser abierta con una ventana de tiempo estricta.
     */
    public function getCanBeOpenedAttribute(): bool
    {
        $now = Carbon::now();
        
        // Ventana de apertura: 1 hora y media antes del start_time
        $openingWindowStart = Carbon::today()
            ->setTime($this->start_time->hour, $this->start_time->minute)
            ->subMinutes(90);

        // Límite de cierre de ventana: No permitir abrir si ya pasó la hora de entrada 
        // (o puedes cambiarlo a $this->end_time si permites aperturas extremadamente tardías)
        $openingWindowEnd = Carbon::today()
            ->setTime($this->start_time->hour, $this->start_time->minute);

        // El botón solo se activa si la hora actual cae EXACTAMENTE dentro del rango del día de hoy
        return $now->between($openingWindowStart, $openingWindowEnd);
    }

    /**
     * Devuelve un string legible con el estado o tiempo restante para la apertura.
     */
    public function getTimeUntilOpeningAttribute(): string
    {
        $now = Carbon::now();
        
        $openingWindowStart = Carbon::today()
            ->setTime($this->start_time->hour, $this->start_time->minute)
            ->subMinutes(90);

        $openingWindowEnd = Carbon::today()
            ->setTime($this->start_time->hour, $this->start_time->minute);

        // Caso 1: Aún no es hora de abrir (Falta tiempo)
        if ($now->lessThan($openingWindowStart)) {
            return 'Disponible en ' . $now->shortAbsoluteDiffForHumans($openingWindowStart);
        }

        // Caso 2: Ya pasó la hora de entrada reglamentaria para iniciar la sesión
        if ($now->greaterThan($openingWindowEnd)) {
            return 'Horario de apertura vencido para el día de hoy.';
        }

        return '';
    }
}