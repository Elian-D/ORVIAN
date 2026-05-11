<?php

namespace App\Console\Commands;

use App\Models\Tenant\AttendanceExcuse;
use App\Models\Tenant\ClassroomAttendanceRecord;
use App\Models\Tenant\DailyAttendanceSession;
use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\School;
use App\Models\Tenant\Student;
use App\Models\Tenant\Teacher;
use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\Academic\Subject;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedDemoSchoolData extends Command
{
    protected $signature   = 'orvian:seed-demo
                                {--school_id= : ID de la escuela a poblar (requerido)}
                                {--students=75 : Cantidad de estudiantes a crear si no existen (25-150)}
                                {--days=30 : Días de historial de asistencia a generar (1-90)}
                                {--fresh : Eliminar registros de asistencia anteriores antes de generar}';

    protected $description = 'Genera datos de demostración realistas: estudiantes, profesores con materias asignadas, y 30 días de historial de asistencia (plantel + aula).';

    // ── Distribución de estados plantel ───────────────────────────
    private const DIST_PRESENT = 80;
    private const DIST_LATE    = 10;
    private const DIST_ABSENT  =  5;
    private const DIST_EXCUSED =  5;

    // ── Materias base que se asignan a los maestros de demo ───────
    // Se buscan por nombre en la tabla subjects; si no existen se crean.
    private const DEMO_SUBJECTS = [
        ['name' => 'Lengua Española',    'code' => 'LEN', 'color' => '#3b82f6'],
        ['name' => 'Matemáticas',        'code' => 'MAT', 'color' => '#10b981'],
        ['name' => 'Ciencias Sociales',  'code' => 'SOC', 'color' => '#f59e0b'],
    ];

    public function handle(): int
    {
        $schoolId = (int) $this->option('school_id');

        if (!$schoolId) {
            $this->error('Debes indicar el ID de la escuela: --school_id=1');
            return self::FAILURE;
        }

        $school = School::find($schoolId);

        if (!$school) {
            $this->error("No se encontró la escuela con ID {$schoolId}.");
            return self::FAILURE;
        }

        $this->info("🏫  Escuela: {$school->name} (ID: {$schoolId})");
        $this->newLine();

        setPermissionsTeamId($schoolId);

        DB::transaction(function () use ($school, $schoolId) {

            // Estructura académica base
            $sections = SchoolSection::where('school_id', $schoolId)
                ->where('is_active', true)
                ->with(['grade', 'shift'])
                ->get();

            $shifts = SchoolShift::where('school_id', $schoolId)->get();

            if ($sections->isEmpty()) {
                $this->warn('⚠️  No hay secciones activas. Completa primero el wizard de configuración.');
                return;
            }

            $this->line("  📚  Secciones detectadas: <comment>{$sections->count()}</comment>");
            $this->line("  🕐  Tandas detectadas: <comment>{$shifts->count()}</comment>");

            // Año académico activo (necesario para las asignaciones)
            $academicYear = $school->activeYear()
                ?? AcademicYear::where('school_id', $schoolId)->latest('start_date')->first();

            if (!$academicYear) {
                $this->warn('⚠️  No hay año académico. Se creará uno de prueba.');
                $academicYear = AcademicYear::create([
                    'school_id'  => $schoolId,
                    'name'       => date('Y') . '-' . (date('Y') + 1),
                    'start_date' => Carbon::now()->startOfYear()->toDateString(),
                    'end_date'   => Carbon::now()->endOfYear()->toDateString(),
                    'is_active'  => true,
                ]);
            }

            // Pasos en orden
            $this->seedStudents($school, $sections, $schoolId);
            $teachers   = $this->seedTeachers($school, $schoolId);
            $assignments = $this->seedSubjectsAndAssignments($schoolId, $sections, $teachers, $academicYear);
            $this->seedAttendance($school, $shifts, $sections, $schoolId, $assignments);
            $this->seedExcuses($schoolId);
        });

        $this->newLine();
        $this->info('✅  Seeder completado. El sistema está listo para la demo.');
        $this->newLine();
        $this->line('  Accede a <fg=cyan>/app/attendance/dashboard</> para ver los datos.');

        return self::SUCCESS;
    }

    // ── Paso 1: Estudiantes ───────────────────────────────────────

    private function seedStudents(School $school, $sections, int $schoolId): void
    {
        $existingCount = Student::where('school_id', $schoolId)->count();

        if ($existingCount > 0) {
            $this->line("  👥  Estudiantes existentes: <comment>{$existingCount}</comment> — se omite la creación.");
            return;
        }

        $targetCount = min(max((int) $this->option('students'), 25), 150);
        $this->line("  👥  Creando <comment>{$targetCount}</comment> estudiantes en {$sections->count()} secciones...");

        $perSection = (int) ceil($targetCount / $sections->count());
        $created    = 0;

        $firstNames = ['Carlos', 'María', 'José', 'Ana', 'Luis', 'Laura', 'Juan', 'Sofía',
                       'Pedro', 'Carmen', 'Miguel', 'Valentina', 'Andrés', 'Isabella',
                       'Diego', 'Gabriela', 'Alejandro', 'Camila', 'Ricardo', 'Daniela',
                       'Fernando', 'Natalia', 'Eduardo', 'Paola', 'Jesús', 'Claudia',
                       'Ramón', 'Lucía', 'Francisco', 'Marta', 'Rafael', 'Sandra'];

        $lastNames  = ['García', 'Rodríguez', 'Martínez', 'López', 'González', 'Pérez',
                       'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez',
                       'Díaz', 'Cruz', 'Reyes', 'Morales', 'Ortiz', 'Jiménez',
                       'Medina', 'Santos', 'Herrera', 'Vargas', 'Castillo', 'Ramos',
                       'Núñez', 'Guerrero', 'Mendoza', 'Suárez', 'Molina', 'Silva'];

        foreach ($sections as $section) {
            $count = min($perSection, $targetCount - $created);
            if ($count <= 0) break;

            for ($i = 0; $i < $count; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName  = $lastNames[array_rand($lastNames)] . ' ' . $lastNames[array_rand($lastNames)];
                $rnc       = '4' . str_pad((string) rand(1000000, 9999999), 9, '0', STR_PAD_LEFT) . rand(1, 9);

                Student::create([
                    'school_id'         => $schoolId,
                    'school_section_id' => $section->id,
                    'first_name'        => $firstName,
                    'last_name'         => $lastName,
                    'gender'            => rand(0, 1) ? 'M' : 'F',
                    'date_of_birth'     => Carbon::now()->subYears(rand(14, 18))->subDays(rand(0, 365))->toDateString(),
                    'rnc'               => $rnc,
                    'enrollment_date'   => Carbon::now()->startOfYear()->toDateString(),
                    'is_active'         => true,
                    'tutor_name'        => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                    'tutor_phone'       => '+1829' . rand(1000000, 9999999),
                ]);

                $created++;
            }
        }

        $this->line("  ✔️   <info>{$created} estudiantes creados.</info>");
    }

    // ── Paso 2: Profesores ────────────────────────────────────────
    // Retorna la colección de maestros creados/encontrados.

    private function seedTeachers(School $school, int $schoolId): \Illuminate\Support\Collection
    {
        $teachersData = [
            ['first_name' => 'María',  'last_name' => 'González', 'specialization' => 'Lengua Española',   'gender' => 'F'],
            ['first_name' => 'Carlos', 'last_name' => 'Reyes',    'specialization' => 'Matemáticas',       'gender' => 'M'],
            ['first_name' => 'Ana',    'last_name' => 'Castillo', 'specialization' => 'Ciencias Sociales', 'gender' => 'F'],
        ];

        $existingCount = Teacher::where('school_id', $schoolId)->count();

        if ($existingCount >= 3) {
            $this->line("  👩‍🏫  Profesores existentes: <comment>{$existingCount}</comment> — se omite la creación.");
            return Teacher::where('school_id', $schoolId)->limit(3)->get();
        }

        $this->line('  👩‍🏫  Creando 3 profesores de demo...');

        $teachers = collect();

        foreach ($teachersData as $data) {
            $teacher = Teacher::firstOrCreate(
                ['school_id' => $schoolId, 'first_name' => $data['first_name'], 'last_name' => $data['last_name']],
                [
                    'gender'          => $data['gender'],
                    'specialization'  => $data['specialization'],
                    'employment_type' => 'Full-Time',
                    'is_active'       => true,
                    'hire_date'       => Carbon::now()->subYears(2)->toDateString(),
                ]
            );
            $teachers->push($teacher);
        }

        $this->line('  ✔️   <info>3 profesores creados.</info>');

        return $teachers;
    }

    // ── Paso 3: Materias y Asignaciones Maestro-Materia-Sección ──
    // Crea o reutiliza las 3 materias base y asigna cada maestro
    // a su materia en TODAS las secciones activas.
    // Retorna la colección de TeacherSubjectSection para el seeder de aula.

    private function seedSubjectsAndAssignments(
        int $schoolId,
        $sections,
        \Illuminate\Support\Collection $teachers,
        AcademicYear $academicYear
    ): \Illuminate\Support\Collection {

        $this->line('  📖  Verificando materias y asignaciones...');

        // Crear o encontrar las 3 materias base
        $subjects = collect();
        foreach (self::DEMO_SUBJECTS as $subjectData) {
            $subject = Subject::firstOrCreate(
                ['code' => $subjectData['code']],
                [
                    'name'       => $subjectData['name'],
                    'type'       => Subject::TYPE_BASIC,
                    'color'      => $subjectData['color'],
                    'is_active'  => true,
                    'hours_weekly' => 4,
                ]
            );
            $subjects->push($subject);
        }

        // Verificar si ya existen asignaciones para evitar duplicados
        $existingAssignments = TeacherSubjectSection::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYear->id)
            ->count();

        if ($existingAssignments > 0) {
            $this->line("  ✔️   <info>Asignaciones existentes ({$existingAssignments}) — se reutilizan.</info>");
            return TeacherSubjectSection::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->with(['teacher', 'subject', 'section'])
                ->get();
        }

        // Mapeo: índice del maestro → índice de la materia (mismo orden)
        // María González → Lengua Española
        // Carlos Reyes   → Matemáticas
        // Ana Castillo   → Ciencias Sociales
        $assignments = collect();

        foreach ($sections as $section) {
            foreach ($teachers as $index => $teacher) {
                $subject = $subjects->get($index);
                if (!$subject) continue;

                $assignment = TeacherSubjectSection::firstOrCreate(
                    [
                        'school_id'         => $schoolId,
                        'teacher_id'        => $teacher->id,
                        'subject_id'        => $subject->id,
                        'school_section_id' => $section->id,
                        'academic_year_id'  => $academicYear->id,
                    ],
                    ['is_active' => true]
                );

                $assignments->push($assignment->load(['teacher', 'subject', 'section']));
            }
        }

        $this->line("  ✔️   <info>{$assignments->count()} asignaciones creadas ({$teachers->count()} maestros × {$sections->count()} secciones).</info>");

        return $assignments;
    }

    // ── Paso 4: Historial de Asistencia (Plantel + Aula) ─────────

    private function seedAttendance(
        School $school,
        $shifts,
        $sections,
        int $schoolId,
        \Illuminate\Support\Collection $assignments
    ): void {

        $days     = min(max((int) $this->option('days'), 1), 90);
        $students = Student::where('school_id', $schoolId)->where('is_active', true)->get(['id', 'school_section_id']);
        $fresh    = $this->option('fresh');

        if ($students->isEmpty()) {
            $this->warn('  ⚠️  No hay estudiantes activos. Omitiendo asistencia.');
            return;
        }

        if ($fresh) {
            $this->warn('  🗑️   Eliminando registros anteriores (--fresh)...');
            ClassroomAttendanceRecord::where('school_id', $schoolId)->delete();
            PlantelAttendanceRecord::where('school_id', $schoolId)->delete();
            DailyAttendanceSession::where('school_id', $schoolId)->delete();
        }

        $this->line("  📅  Generando {$days} días — Plantel + Aula para <comment>{$students->count()}</comment> estudiantes...");

        $primaryShift = $shifts->first();
        $registeredBy = User::where('school_id', $schoolId)->first()?->id ?? 1;

        // Agrupar estudiantes por sección para las asignaciones de aula
        $studentsBySection = $students->groupBy('school_section_id');

        // Agrupar asignaciones por sección
        $assignmentsBySection = $assignments->groupBy('school_section_id');

        $bar = $this->output->createProgressBar($days);
        $bar->start();

        for ($d = $days - 1; $d >= 0; $d--) {
            $date = Carbon::today()->subDays($d);

            if ($date->isWeekend()) {
                $bar->advance();
                continue;
            }

            $isToday = $date->isToday();

            // ── Sesión diaria del plantel ──────────────────────────
            $session = DailyAttendanceSession::firstOrCreate(
                [
                    'school_id'       => $schoolId,
                    'date'            => $date->toDateString(),
                    'school_shift_id' => $primaryShift?->id,
                ],
                [
                    'opened_at'      => $date->copy()->setTime(7, 30),
                    'closed_at'      => $isToday ? null : $date->copy()->setTime(13, 0),
                    'opened_by'      => $registeredBy,
                    'closed_by'      => $isToday ? null : $registeredBy,
                    'total_expected' => $students->count(),
                ]
            );

            // ── Registros de plantel ───────────────────────────────
            $plantelExists = PlantelAttendanceRecord::where('school_id', $schoolId)
                ->whereDate('date', $date)
                ->exists();

            // Generar un estado por estudiante y guardarlo para reutilizarlo en aula
            // (coherencia: si está ausente en plantel, también en aula)
            $studentStatusMap = [];

            if (!$plantelExists) {
                $plantelRecords = [];
                $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];

                foreach ($students as $student) {
                    $roll   = rand(1, 100);
                    $status = match (true) {
                        $roll <= self::DIST_PRESENT                                        => PlantelAttendanceRecord::STATUS_PRESENT,
                        $roll <= self::DIST_PRESENT + self::DIST_LATE                      => PlantelAttendanceRecord::STATUS_LATE,
                        $roll <= self::DIST_PRESENT + self::DIST_LATE + self::DIST_ABSENT  => PlantelAttendanceRecord::STATUS_ABSENT,
                        default                                                            => PlantelAttendanceRecord::STATUS_EXCUSED,
                    };

                    $studentStatusMap[$student->id] = $status;

                    $hour   = ($status === PlantelAttendanceRecord::STATUS_LATE) ? rand(8, 9) : 7;
                    $time   = $date->copy()->setTime($hour, rand(0, 59));
                    $method = ($d % 3 === 0) ? PlantelAttendanceRecord::METHOD_FACIAL : PlantelAttendanceRecord::METHOD_QR;

                    $plantelRecords[] = [
                        'school_id'                   => $schoolId,
                        'student_id'                  => $student->id,
                        'daily_attendance_session_id' => $session->id,
                        'school_shift_id'             => $primaryShift?->id,
                        'date'                        => $date->toDateString(),
                        'time'                        => $time,
                        'status'                      => $status,
                        'method'                      => $method,
                        'registered_by'               => $registeredBy,
                        'created_at'                  => $time,
                        'updated_at'                  => $time,
                    ];

                    $counts[$status === PlantelAttendanceRecord::STATUS_PRESENT ? 'present' :
                            ($status === PlantelAttendanceRecord::STATUS_LATE ? 'late' :
                            ($status === PlantelAttendanceRecord::STATUS_ABSENT ? 'absent' : 'excused'))]++;
                }

                foreach (array_chunk($plantelRecords, 200) as $chunk) {
                    PlantelAttendanceRecord::insert($chunk);
                }

                $session->update([
                    'total_registered' => $students->count(),
                    'total_present'    => $counts['present'],
                    'total_late'       => $counts['late'],
                    'total_absent'     => $counts['absent'],
                    'total_excused'    => $counts['excused'],
                ]);
            }

            // ── Registros de aula (ClassroomAttendanceRecord) ─────
            // Por cada sección → por cada asignación (materia) de esa sección
            // → por cada estudiante de esa sección
            $classroomExists = ClassroomAttendanceRecord::where('school_id', $schoolId)
                ->whereDate('date', $date)
                ->exists();

            if (!$classroomExists && $assignments->isNotEmpty()) {
                $classroomRecords = [];

                foreach ($sections as $section) {
                    $sectionStudents    = $studentsBySection->get($section->id, collect());
                    $sectionAssignments = $assignmentsBySection->get($section->id, collect());

                    if ($sectionStudents->isEmpty() || $sectionAssignments->isEmpty()) continue;

                    // Hora de inicio de cada clase — escalonada por materia
                    $classHours = [8, 9, 10]; // Lengua 8h, Matemáticas 9h, Sociales 10h

                    foreach ($sectionAssignments as $assignmentIndex => $assignment) {
                        $classHour = $classHours[$assignmentIndex % count($classHours)];
                        $classTime = $date->copy()->setTime($classHour, 0)->format('H:i:s');

                        foreach ($sectionStudents as $student) {
                            // Coherencia con plantel: si estaba ausente en plantel, ausente en aula
                            $plantelStatus = $studentStatusMap[$student->id]
                                ?? PlantelAttendanceRecord::STATUS_PRESENT;

                            $classroomStatus = match ($plantelStatus) {
                                // Ausente/Excusado en plantel → mismo estado en aula
                                PlantelAttendanceRecord::STATUS_ABSENT  => ClassroomAttendanceRecord::STATUS_ABSENT,
                                PlantelAttendanceRecord::STATUS_EXCUSED => ClassroomAttendanceRecord::STATUS_EXCUSED,
                                // Presente en plantel → puede estar presente, tarde o incluso ausente (pasilleo)
                                default => $this->randomClassroomStatus(),
                            };

                            $classroomRecords[] = [
                                'school_id'                   => $schoolId,
                                'student_id'                  => $student->id,
                                'teacher_subject_section_id'  => $assignment->id,
                                'teacher_id'                  => $assignment->teacher_id,
                                'date'                        => $date->toDateString(),
                                'class_time'                  => $classTime,
                                'status'                      => $classroomStatus,
                                'created_at'                  => $date->copy()->setTime($classHour, 5),
                                'updated_at'                  => $date->copy()->setTime($classHour, 5),
                            ];
                        }
                    }
                }

                foreach (array_chunk($classroomRecords, 200) as $chunk) {
                    ClassroomAttendanceRecord::insert($chunk);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $totalPlantel   = PlantelAttendanceRecord::where('school_id', $schoolId)->count();
        $totalClassroom = ClassroomAttendanceRecord::where('school_id', $schoolId)->count();
        $this->line("  ✔️   <info>Plantel: {$totalPlantel} registros — Aula: {$totalClassroom} registros.</info>");
    }

    /**
     * Distribución de asistencia en aula para estudiantes presentes en plantel.
     * Ligeramente peor que plantel para que haya discrepancias interesantes en el dashboard.
     * 75% Presente · 10% Tardanza · 10% Ausente (pasilleo) · 5% Excusado
     */
    private function randomClassroomStatus(): string
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 75  => ClassroomAttendanceRecord::STATUS_PRESENT,
            $roll <= 85  => ClassroomAttendanceRecord::STATUS_LATE,
            $roll <= 95  => ClassroomAttendanceRecord::STATUS_ABSENT,   // pasilleo
            default      => ClassroomAttendanceRecord::STATUS_EXCUSED,
        };
    }

    // ── Paso 5: Excusas de muestra ────────────────────────────────

    private function seedExcuses(int $schoolId): void
    {
        $existingExcuses = AttendanceExcuse::where('school_id', $schoolId)->count();

        if ($existingExcuses >= 3) {
            $this->line("  📋  Excusas existentes: <comment>{$existingExcuses}</comment> — se omite la creación.");
            return;
        }

        $students = Student::where('school_id', $schoolId)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        if ($students->isEmpty()) return;

        $submitter   = User::where('school_id', $schoolId)->first()?->id ?? 1;
        $excuseTypes = [
            AttendanceExcuse::TYPE_MEDICAL,
            AttendanceExcuse::TYPE_FULL_ABSENCE,
            AttendanceExcuse::TYPE_LICENSE,
        ];

        $this->line('  📋  Creando excusas de muestra...');

        foreach ($students as $i => $student) {
            $dateStart = Carbon::today()->subDays(rand(3, 10));
            $dateEnd   = $dateStart->copy()->addDays(rand(1, 2));

            AttendanceExcuse::create([
                'school_id'    => $schoolId,
                'student_id'   => $student->id,
                'date_start'   => $dateStart->toDateString(),
                'date_end'     => $dateEnd->toDateString(),
                'type'         => $excuseTypes[$i % count($excuseTypes)],
                'reason'       => 'Generado para demo del sistema ORVIAN.',
                'status'       => AttendanceExcuse::STATUS_APPROVED,
                'submitted_by' => $submitter,
                'submitted_at' => $dateStart->copy()->subDay(),
                'reviewed_by'  => $submitter,
                'reviewed_at'  => $dateStart,
            ]);
        }

        $this->line('  ✔️   <info>3 excusas aprobadas creadas.</info>');
    }
}