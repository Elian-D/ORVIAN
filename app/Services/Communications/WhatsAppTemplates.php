<?php

namespace App\Services\Communications;

use App\Models\Tenant\Student;

class WhatsAppTemplates
{
    /**
     * Determina el término de género para el estudiante.
     */
    protected static function getGenderTerm(Student $student): string
    {
        // Asumiendo que 'M' es masculino y 'F' es femenino. 
        // Ajusta los valores según cómo los tengas guardados en tu BD (ej. 'male'/'female')
        return $student->gender === 'M' ? 'su representado' : 'su representada';
    }

    public static function absenceAlert(Student $student, int $count, string $month): string
    {
        $term = self::getGenderTerm($student);

        return <<<MSG
        📋 *ORVIAN — Notificación de Asistencia*

        Estimado/a tutor(a) *{$student->tutor_name}*,

        Le informamos que {$term} *{$student->full_name}* ha acumulado *{$count} ausencia(s) injustificada(s)* durante el mes de {$month}.

        Le solicitamos comunicarse con la dirección del centro para coordinar el seguimiento correspondiente.

        _Este mensaje es generado automáticamente por el Sistema de Gestión ORVIAN._
        MSG;
    }

    public static function tardinessAlert(Student $student, int $count, string $month): string
    {
        $term = self::getGenderTerm($student);

        return <<<MSG
        ⏰ *ORVIAN — Aviso de Puntualidad*

        Estimado/a tutor(a) *{$student->tutor_name}*,

        Le notificamos que {$term} *{$student->full_name}* ha registrado *{$count} llegada(s) tarde* durante el mes de {$month}.

        La puntualidad es fundamental para el aprovechamiento académico. Le agradecemos su atención.

        _Este mensaje es generado automáticamente por el Sistema de Gestión ORVIAN._
        MSG;
    }
}