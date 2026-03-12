<?php

namespace Database\Seeders\AppInit;

use App\Models\Tenant\Academic\TechnicalFamily;
use App\Models\Tenant\Academic\TechnicalTitle;
use Illuminate\Database\Seeder;

class TechnicalCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                "modality" => TechnicalFamily::MODALITY_TECHNICAL,
                "families" => [
                    [
                        "family_code" => "IFC",
                        "family_name" => "Informática y Comunicaciones",
                        "ordenance" => "06-2017",
                        "titles" => [
                            ["code" => "IFC006_3", "name" => "Bachiller Técnico en Desarrollo y Administración de Aplicaciones Informáticas", "level" => 3],
                            ["code" => "IFC007_3", "name" => "Bachiller Técnico en Soporte de Redes y Sistemas Informáticos", "level" => 3],
                            ["code" => "IFC008_2", "name" => "Técnico Básico en Operaciones Informáticas", "level" => 2]
                        ]
                    ],
                    [
                        "family_code" => "AYC",
                        "family_name" => "Administración y Comercio",
                        "ordenance" => "14-2017",
                        "titles" => [
                            ["code" => "AYC038_3", "name" => "Bachiller Técnico en Gestión Administrativa y Tributaria", "level" => 3],
                            ["code" => "AYC036_3", "name" => "Bachiller Técnico en Comercio y Mercadeo", "level" => 3],
                            ["code" => "AYC037_3", "name" => "Bachiller Técnico en Logística y Transporte", "level" => 3],
                            ["code" => "AYC040_2", "name" => "Técnico Básico en Actividades Administrativas", "level" => 2]
                        ]
                    ],
                    [
                        "family_code" => "ELE",
                        "family_name" => "Electricidad y Electrónica",
                        "ordenance" => "17-2017",
                        "titles" => [
                            ["code" => "ELE049_3", "name" => "Bachiller Técnico en Instalaciones Eléctricas", "level" => 3],
                            ["code" => "ELE050_3", "name" => "Bachiller Técnico en Equipos Electrónicos", "level" => 3],
                            ["code" => "ELE051_3", "name" => "Bachiller Técnico en Refrigeración y Acondicionamiento de Aire", "level" => 3],
                            ["code" => "ELE055_3", "name" => "Bachiller Técnico en Mecatrónica", "level" => 3],
                            ["code" => "ELE052_3", "name" => "Bachiller Técnico en Energías Renovables", "level" => 3]
                        ]
                    ],
                    [
                        "family_code" => "SAL",
                        "family_name" => "Salud",
                        "ordenance" => "18-2017",
                        "titles" => [
                            ["code" => "SAL058_3", "name" => "Bachiller Técnico en Cuidados de Enfermería y Promoción de la Salud", "level" => 3],
                            ["code" => "SAL060_3", "name" => "Bachiller Técnico en Farmacia y Parafarmacia", "level" => 3],
                            ["code" => "SAL059_3", "name" => "Bachiller Técnico en Atención a Emergencias de la Salud", "level" => 3]
                        ]
                    ],
                    [
                        "family_code" => "TUH",
                        "family_name" => "Turismo y Hostelería",
                        "ordenance" => "05-2017",
                        "titles" => [
                            ["code" => "TUH001_3", "name" => "Bachiller Técnico en Servicios Gastronómicos", "level" => 3],
                            ["code" => "TUH002_3", "name" => "Bachiller Técnico en Servicios de Alojamiento", "level" => 3],
                            ["code" => "TUH003_3", "name" => "Bachiller Técnico en Servicios Turísticos", "level" => 3]
                        ]
                    ],
                    [
                        "family_code" => "AVG",
                        "family_name" => "Audiovisuales y Gráficas",
                        "ordenance" => "20-2017",
                        "titles" => [
                            ["code" => "AVG067_3", "name" => "Bachiller Técnico en Producción y Realización de Audiovisuales y Espectáculos", "level" => 3],
                            ["code" => "AVG069_3", "name" => "Bachiller Técnico en Multimedia y Gráfica", "level" => 3],
                            ["code" => "AVG070_3", "name" => "Bachiller Técnico en Procesos Gráficos", "level" => 3]
                        ]
                    ],
                    [
                        "family_code" => "FIM",
                        "family_name" => "Fabricación, Instalación y Mantenimiento",
                        "ordenance" => "13-2017",
                        "titles" => [
                            ["code" => "FIM032_3", "name" => "Bachiller Técnico en Electromecánica de Vehículos", "level" => 3],
                            ["code" => "FIM028_3", "name" => "Bachiller Técnico en Construcciones Metálicas", "level" => 3],
                            ["code" => "FIM027_3", "name" => "Bachiller Técnico en Mecanizado", "level" => 3]
                        ]
                    ],
                    [
                        "family_code" => "AGA",
                        "family_name" => "Agraria",
                        "ordenance" => "07-2017",
                        "titles" => [
                            ["code" => "AGA009_3", "name" => "Bachiller Técnico en Agropecuaria", "level" => 3],
                            ["code" => "AGA010_3", "name" => "Bachiller Técnico en Asistencia en Veterinaria", "level" => 3]
                        ]
                    ]
                ]
            ],
            [
                "modality" => TechnicalFamily::MODALITY_ARTS,
                "families" => [
                    [
                        "family_code" => "ART",
                        "family_name" => "Artes",
                        "ordenance" => "05-2017",
                        "titles" => [
                            ["code" => "ART_VIS", "name" => "Bachillerato en Artes, mención Artes Visuales", "level" => null],
                            ["code" => "ART_MUS", "name" => "Bachillerato en Artes, mención Música", "level" => null],
                            ["code" => "ART_ESC_T", "name" => "Bachillerato en Artes, mención Artes Escénicas: Teatro", "level" => null],
                            ["code" => "ART_ESC_D", "name" => "Bachillerato en Artes, mención Artes Escénicas: Danza", "level" => null],
                            ["code" => "ART_CINE", "name" => "Bachillerato en Artes, mención Cine y Fotografía", "level" => null],
                            ["code" => "ART_MULT", "name" => "Bachillerato en Artes, mención Arte Multimedia", "level" => null],
                            ["code" => "ART_PROD", "name" => "Bachillerato en Artes, mención Creación y Producción Artesanal", "level" => null]
                        ]
                    ]
                ]
            ]
        ];

        foreach ($data as $m) {
            foreach ($m['families'] as $f) {
                $family = TechnicalFamily::updateOrCreate(
                    ['code' => $f['family_code']],
                    [
                        'name' => $f['family_name'] ?? $f['name'],
                        'modality' => $m['modality'],
                        'ordenance' => $f['ordenance'] ?? null
                    ]
                );

                foreach ($f['titles'] as $t) {
                    TechnicalTitle::updateOrCreate(
                        ['code' => $t['code']],
                        [
                            'technical_family_id' => $family->id,
                            'name' => $t['name'],
                            'level' => $t['level'] ?? null
                        ]
                    );
                }
            }
        }
    }
}