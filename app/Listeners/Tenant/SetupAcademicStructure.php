<?php

namespace App\Listeners\Tenant;

use App\Events\Tenant\SchoolConfigured;
use App\Models\Tenant\Academic\Level;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\TechnicalTitle;

class SetupAcademicStructure
{
    public function handle(SchoolConfigured $event): void
    {
        $school        = $event->school;
        $academicData  = $event->academicData;

        $sectionLabels = $academicData['section_labels'] ?? ['A'];
        $titleIds      = $academicData['title_ids']      ?? [];

        $levels = Level::with('grades')
            ->whereIn('id', $academicData['level_ids'])
            ->get();

        $titles = $titleIds
            ? TechnicalTitle::whereIn('id', $titleIds)->get()
            : collect();

        foreach ($levels as $level) {
            foreach ($level->grades as $grade) {

                if ($grade->allows_technical && $titles->isNotEmpty()) {
                    // Segundo Ciclo Secundaria con títulos técnicos:
                    // En DR los politécnicos NO tienen secciones "generales" en 4to-6to.
                    // Cada sección pertenece a una especialidad específica.
                    foreach ($titles as $title) {
                        foreach ($sectionLabels as $label) {
                            SchoolSection::firstOrCreate([
                                'school_id'          => $school->id,
                                'grade_id'           => $grade->id,
                                'label'              => $label,
                                'technical_title_id' => $title->id,
                            ]);
                        }
                    }
                } else {
                    // Primaria, Secundaria Primer Ciclo,
                    // o Segundo Ciclo sin títulos (modalidad académica pura):
                    // solo secciones generales.
                    foreach ($sectionLabels as $label) {
                        SchoolSection::firstOrCreate([
                            'school_id'          => $school->id,
                            'grade_id'           => $grade->id,
                            'label'              => $label,
                            'technical_title_id' => null,
                        ]);
                    }
                }
            }
        }
    }
}