<?php

namespace App\Services\Communications;

use App\Models\Tenant\Student;

class WhatsAppTemplates
{
    public static function absenceAlert(Student $student, int $count, string $month): string
    {
        return <<<MSG
        📋 *ORVIAN — Notificación de Asistencia*

        Estimado/a tutor(a) *{$student->tutor_name}* de *{$student->full_name}*,

        Le informamos que su representado/a ha acumulado *{$count} ausencia(s) injustificada(s)* durante el mes de {$month}.

        Le solicitamos comunicarse con la dirección del centro para coordinar el seguimiento correspondiente.

        _Este mensaje es generado automáticamente por el Sistema de Gestión ORVIAN._
        MSG;
    }

    public static function tardinessAlert(Student $student, int $count, string $month): string
    {
        return <<<MSG
        ⏰ *ORVIAN — Aviso de Puntualidad*

        Estimado/a tutor(a) *{$student->tutor_name}* de *{$student->full_name}*,

        Le notificamos que su representado/a ha registrado *{$count} llegada(s) tarde* durante el mes de {$month}.

        La puntualidad es fundamental para el aprovechamiento académico. Le agradecemos su atención.

        _Este mensaje es generado automáticamente por el Sistema de Gestión ORVIAN._
        MSG;
    }
}