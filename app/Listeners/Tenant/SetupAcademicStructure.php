<?php

namespace App\Listeners\Tenant;

use App\Events\Tenant\SchoolConfigured;
use App\Models\Tenant\Academic\Level;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift; // Asegúrate de importar esto

class SetupAcademicStructure
{
    public function handle(SchoolConfigured $event): void
    {
        $school = $event->school;
        $data = $event->academicData;

        $levelIds = $data['level_ids'];
        $sectionLabels = $data['section_labels'] ?? ['A'];
        $titleIds = $data['title_ids'] ?? [];

        // --- SOLUCIÓN AQUÍ ---
        // Buscamos los IDs reales de las tandas que pertenecen a esta escuela
        // basándonos en los tipos que recibimos (Matutina, Vespertina, etc.)
        $shiftIds = SchoolShift::where('school_id', $school->id)
            ->whereIn('type', $data['shift_ids'] ?? [])
            ->pluck('id')
            ->toArray();

        // Si por alguna razón no hay tandas, no podemos crear secciones con shift_id
        if (empty($shiftIds)) return;
        // ---------------------

        $levels = Level::whereIn('id', $levelIds)->with('grades')->get();

        foreach ($levels as $level) {
            foreach ($level->grades as $grade) {
                foreach ($shiftIds as $shiftId) {
                    foreach ($sectionLabels as $label) {
                        
                        $baseData = [
                            'school_id'       => $school->id,
                            'school_shift_id' => $shiftId, // Ahora sí es un ID (1, 2, 3...)
                            'grade_id'        => $grade->id,
                            'label'           => $label,
                        ];

                        if ($grade->allows_technical && !empty($titleIds)) {
                            foreach ($titleIds as $titleId) {
                                SchoolSection::firstOrCreate(array_merge($baseData, [
                                    'technical_title_id' => $titleId
                                ]));
                            }
                        } else {
                            SchoolSection::firstOrCreate(array_merge($baseData, [
                                'technical_title_id' => null
                            ]));
                        }
                    }
                }
            }
        }
    }
}