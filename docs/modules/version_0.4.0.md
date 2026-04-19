# ORVIAN v0.4.0 — Attendance Module (Sistema Dual de Asistencia)

**RAMA PADRE:**
`feature/attendance-module`

**Objetivo:** Implementar un sistema completo de asistencia con **doble dominio** (Plantel y Aula), incluyendo entidad Maestro, asignaturas, validación cruzada, sincronización Offline-Cloud, microservicio de reconocimiento facial, y dashboard operativo de discrepancias.

---

## Arquitectura del Módulo

### Conceptos Clave

**Dos Dominios de Asistencia:**
1. **Asistencia de Plantel (La Puerta):** Registro de entrada al centro educativo. Regida por tanda/jornada. Control de llegada tardía vs horario institucional.
2. **Asistencia de Aula (El Registro Anecdótico Digital):** Registro por materia, gestionado por cada maestro. Detecta "pasilleo" (presente en plantel, ausente en aula).

**Validación Cruzada Estricta:**
- Si estudiante está `Ausente` en Plantel → **No puede** estar `Presente` en Aula (bloqueo del sistema)
- Si estudiante está `Presente` en Plantel pero `Ausente` en Aula → Genera alerta de "Pasilleo" para coordinación

**Apertura Manual del Día:**
- El sistema **NO asume** que hay clases
- Un administrador debe "Abrir Asistencia del Día" (`DailyAttendanceSession`)
- Sin apertura = día feriado/libre, microservicios no procesan alertas

**Modo Offline/Cloud:**
- Variable: `APP_MODE=cloud` (VPS) o `APP_MODE=local` (PC del centro)
- Edge Node local con DB ligera para cortes de internet
- Sincronización automática cada 5 minutos cuando hay conexión

---

## Fase 1 — Entidades Base (Estudiantes y Maestros)
**Rama:** `feature/core-entities`

### 1.1 — Migración y Modelo: Estudiante

- [x] **Migración `create_students_table`:**
  ```php
  Schema::create('students', function (Blueprint $table) {
      $table->id();
      $table->foreignId('school_id')->constrained()->cascadeOnDelete();
      $table->foreignId('school_section_id')->constrained('school_sections');
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Para Classroom Virtual
      
      // Datos personales
      $table->string('first_name', 100);
      $table->string('last_name', 100);
      $table->enum('gender', ['M', 'F']);
      $table->date('date_of_birth');
      $table->string('place_of_birth', 255)->nullable();
      $table->string('rnc', 13)->nullable()->unique(); // Cédula: 402-1234567-8
      
      // Datos médicos
      $table->string('blood_type', 3)->nullable();
      $table->text('allergies')->nullable();
      $table->text('medical_conditions')->nullable();
      
      // Identificación y biometría
      $table->string('photo_path')->nullable();
      $table->string('qr_code', 32)->unique()->index();
      $table->longText('face_encoding')->nullable(); // JSON encoding facial
      
      // Estado y fechas
      $table->boolean('is_active')->default(true);
      $table->date('enrollment_date');
      $table->date('withdrawal_date')->nullable();
      $table->text('withdrawal_reason')->nullable();
      
      // Metadata flexible
      $table->json('metadata')->nullable();
      
      $table->timestamps();
      $table->softDeletes();
      
      // Índices
      $table->index(['school_id', 'is_active']);
      $table->index(['school_section_id', 'is_active']);
  });
  ```

- [x] **Modelo `App\Models\Tenant\Student`:**
  ```php
  namespace App\Models\Tenant;

  use App\Traits\BelongsToSchool;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\SoftDeletes;
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Casts\Attribute;

  class Student extends Model
  {
      use HasFactory, SoftDeletes, BelongsToSchool;

      protected $fillable = [
          'school_id', 'school_section_id', 'user_id',
          'first_name', 'last_name', 'gender', 'date_of_birth',
          'place_of_birth', 'rnc', 'blood_type', 'allergies',
          'medical_conditions', 'photo_path', 'face_encoding',
          'is_active', 'enrollment_date', 'withdrawal_date',
          'withdrawal_reason', 'metadata',
      ];

      protected $casts = [
          'date_of_birth' => 'date',
          'enrollment_date' => 'date',
          'withdrawal_date' => 'date',
          'is_active' => 'boolean',
          'metadata' => 'array',
      ];

      protected $appends = ['full_name', 'age', 'has_face_encoding', 'has_user_account'];

      // Relaciones
      public function school()
      {
          return $this->belongsTo(School::class);
      }

      public function section()
      {
          return $this->belongsTo(SchoolSection::class, 'school_section_id');
      }

      public function user()
      {
          return $this->belongsTo(User::class);
      }

      public function plantelAttendanceRecords()
      {
          return $this->hasMany(PlantelAttendanceRecord::class);
      }

      public function classroomAttendanceRecords()
      {
          return $this->hasMany(ClassroomAttendanceRecord::class);
      }

      // Accessors
      protected function fullName(): Attribute
      {
          return Attribute::make(
              get: fn () => "{$this->first_name} {$this->last_name}"
          );
      }

      protected function age(): Attribute
      {
          return Attribute::make(
              get: fn () => $this->date_of_birth 
                  ? Carbon::parse($this->date_of_birth)->age 
                  : null
          );
      }

      protected function hasFaceEncoding(): Attribute
      {
          return Attribute::make(
              get: fn () => !empty($this->face_encoding)
          );
      }

      protected function hasUserAccount(): Attribute
      {
          return Attribute::make(
              get: fn () => !is_null($this->user_id)
          );
      }

      // Scopes
      public function scopeActive($query)
      {
          return $query->where('is_active', true);
      }

      public function scopeInSection($query, $sectionId)
      {
          return $query->where('school_section_id', $sectionId);
      }

      public function scopeWithIndexRelations($query)
      {
          return $query->with([
              'school:id,name',
              'section:id,grade_id,label',
              'section.grade:id,name',
              'user:id,email',
          ]);
      }
  }
  ```

### 1.2 — Migración y Modelo: Maestro

- [x] **Migración `create_teachers_table`:**
  ```php
  Schema::create('teachers', function (Blueprint $table) {
      $table->id();
      $table->foreignId('school_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Acceso al sistema
      
      // Datos personales
      $table->string('first_name', 100);
      $table->string('last_name', 100);
      $table->enum('gender', ['M', 'F']);
      $table->date('date_of_birth')->nullable();
      $table->string('rnc', 13)->nullable()->unique();
      
      // Datos profesionales
      $table->string('employee_code', 50)->nullable()->unique();
      $table->string('specialization', 255)->nullable(); // Ej: "Matemáticas", "Lengua Española"
      $table->enum('employment_type', ['Full-Time', 'Part-Time', 'Substitute'])->default('Full-Time');
      
      // Contacto
      $table->string('phone', 20)->nullable();
      $table->string('emergency_contact_name', 255)->nullable();
      $table->string('emergency_contact_phone', 20)->nullable();
      
      // Identificación
      $table->string('photo_path')->nullable();
      $table->string('qr_code', 32)->unique()->index();
      
      // Estado
      $table->boolean('is_active')->default(true);
      $table->date('hire_date');
      $table->date('termination_date')->nullable();
      $table->text('termination_reason')->nullable();
      
      // Metadata
      $table->json('metadata')->nullable();
      
      $table->timestamps();
      $table->softDeletes();
      
      // Índices
      $table->index(['school_id', 'is_active']);
  });
  ```

- [x] **Modelo `App\Models\Tenant\Teacher`:**
  ```php
  namespace App\Models\Tenant;

  class Teacher extends Model
  {
      use HasFactory, SoftDeletes, BelongsToSchool;

      protected $fillable = [
          'school_id', 'user_id', 'first_name', 'last_name', 'gender',
          'date_of_birth', 'rnc', 'employee_code', 'specialization',
          'employment_type', 'phone', 'emergency_contact_name',
          'emergency_contact_phone', 'photo_path', 'is_active',
          'hire_date', 'termination_date', 'termination_reason', 'metadata',
      ];

      protected $casts = [
          'date_of_birth' => 'date',
          'hire_date' => 'date',
          'termination_date' => 'date',
          'is_active' => 'boolean',
          'metadata' => 'array',
      ];

      protected $appends = ['full_name', 'has_user_account'];

      // Relaciones
      public function school()
      {
          return $this->belongsTo(School::class);
      }

      public function user()
      {
          return $this->belongsTo(User::class);
      }

      public function assignments()
      {
          return $this->hasMany(TeacherSubjectSection::class);
      }

      public function subjects()
      {
          return $this->belongsToMany(Subject::class, 'teacher_subject_sections')
              ->withPivot(['school_section_id', 'academic_year_id'])
              ->withTimestamps();
      }

      // Accessors
      protected function fullName(): Attribute
      {
          return Attribute::make(
              get: fn () => "{$this->first_name} {$this->last_name}"
          );
      }

      protected function hasUserAccount(): Attribute
      {
          return Attribute::make(
              get: fn () => !is_null($this->user_id)
          );
      }

      // Scopes
      public function scopeActive($query)
      {
          return $query->where('is_active', true);
      }

      public function scopeWithIndexRelations($query)
      {
          return $query->with([
              'school:id,name',
              'user:id,name,email',
          ]);
      }
  }
  ```

### 1.3 — Servicios de Gestión

- [x] **Crear `app/Services/Students/StudentService.php`:**
  ```php
  namespace App\Services\Students;

  use App\Models\Tenant\Student;
  use Illuminate\Http\UploadedFile;
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;

  class StudentService
  {
      public function generateQrCode(): string
      {
          do {
              $code = Str::upper(Str::random(32));
          } while (Student::where('qr_code', $code)->exists());
          
          return $code;
      }

      public function createStudent(array $data): Student
      {
          $data['qr_code'] = $this->generateQrCode();
          return Student::create($data);
      }

      public function updatePhoto(Student $student, UploadedFile $photo): void
      {
          if ($student->photo_path) {
              Storage::disk('public')->delete($student->photo_path);
          }

          $path = $photo->store("schools/{$student->school_id}/students", 'public');
          $student->update(['photo_path' => $path]);
      }

      public function storeFaceEncoding(Student $student, array $encoding): void
      {
          $student->update(['face_encoding' => json_encode($encoding)]);
      }

      public function withdraw(Student $student, string $reason, ?Carbon $date = null): void
      {
          $student->update([
              'is_active' => false,
              'withdrawal_date' => $date ?? now(),
              'withdrawal_reason' => $reason,
          ]);
      }

      public function reactivate(Student $student): void
      {
          $student->update([
              'is_active' => true,
              'withdrawal_date' => null,
              'withdrawal_reason' => null,
          ]);
      }

      public function transferSection(Student $student, int $newSectionId): void
      {
          $oldSectionId = $student->school_section_id;
          
          $student->update(['school_section_id' => $newSectionId]);
          
          // Registrar en metadata
          $metadata = $student->metadata ?? [];
          $metadata['section_history'] = $metadata['section_history'] ?? [];
          $metadata['section_history'][] = [
              'from_section_id' => $oldSectionId,
              'to_section_id' => $newSectionId,
              'transferred_at' => now()->toISOString(),
              'transferred_by' => auth()->id(),
          ];
          $student->update(['metadata' => $metadata]);
      }
  }
  ```

- [x] **Crear `app/Services/Teachers/TeacherService.php`:**
  ```php
  namespace App\Services\Teachers;

  use App\Models\Tenant\Teacher;
  use Illuminate\Http\UploadedFile;
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;

  class TeacherService
  {
      public function generateQrCode(): string
      {
          do {
              $code = 'TCH-' . Str::upper(Str::random(28));
          } while (Teacher::where('qr_code', $code)->exists());
          
          return $code;
      }

      public function generateEmployeeCode(int $schoolId): string
      {
          $year = now()->year;
          $count = Teacher::where('school_id', $schoolId)->count() + 1;
          return sprintf('EMP-%d-%04d', $year, $count);
      }

      public function createTeacher(array $data): Teacher
      {
          $data['qr_code'] = $this->generateQrCode();
          
          if (empty($data['employee_code'])) {
              $data['employee_code'] = $this->generateEmployeeCode($data['school_id']);
          }
          
          return Teacher::create($data);
      }

      public function updatePhoto(Teacher $teacher, UploadedFile $photo): void
      {
          if ($teacher->photo_path) {
              Storage::disk('public')->delete($teacher->photo_path);
          }

          $path = $photo->store("schools/{$teacher->school_id}/teachers", 'public');
          $teacher->update(['photo_path' => $path]);
      }

      public function terminate(Teacher $teacher, string $reason, ?Carbon $date = null): void
      {
          $teacher->update([
              'is_active' => false,
              'termination_date' => $date ?? now(),
              'termination_reason' => $reason,
          ]);

          // Remover asignaciones futuras
          $teacher->assignments()
              ->whereHas('academicYear', fn($q) => $q->where('is_active', true))
              ->delete();
      }

      public function reactivate(Teacher $teacher): void
      {
          $teacher->update([
              'is_active' => true,
              'termination_date' => null,
              'termination_reason' => null,
          ]);
      }
  }
  ```

### 1.4 — Observers

- [x] **Crear `app/Observers/Tenant/StudentObserver.php`:**
  ```php
  namespace App\Observers\Tenant;

  use App\Models\Tenant\Student;
  use App\Services\Students\StudentService;
  use Illuminate\Support\Facades\Log;
  use Illuminate\Support\Facades\Storage;

  class StudentObserver
  {
      public function creating(Student $student): void
      {
          if (empty($student->qr_code)) {
              $student->qr_code = app(StudentService::class)->generateQrCode();
          }
      }

      public function updated(Student $student): void
      {
          if ($student->isDirty('is_active') && !$student->is_active) {
              Log::info('Estudiante dado de baja', [
                  'student_id' => $student->id,
                  'full_name' => $student->full_name,
                  'school_id' => $student->school_id,
                  'withdrawal_reason' => $student->withdrawal_reason,
              ]);
          }
      }

      public function deleted(Student $student): void
      {
          if ($student->photo_path) {
              Storage::disk('public')->delete($student->photo_path);
          }
      }
  }
  ```

- [x] **Crear `app/Observers/Tenant/TeacherObserver.php`:**
  ```php
  namespace App\Observers\Tenant;

  use App\Models\Tenant\Teacher;
  use App\Services\Teachers\TeacherService;
  use Illuminate\Support\Facades\Log;
  use Illuminate\Support\Facades\Storage;

  class TeacherObserver
  {
      public function creating(Teacher $teacher): void
      {
          if (empty($teacher->qr_code)) {
              $teacher->qr_code = app(TeacherService::class)->generateQrCode();
          }

          if (empty($teacher->employee_code)) {
              $teacher->employee_code = app(TeacherService::class)
                  ->generateEmployeeCode($teacher->school_id);
          }
      }

      public function updated(Teacher $teacher): void
      {
          if ($teacher->isDirty('is_active') && !$teacher->is_active) {
              Log::info('Maestro dado de baja', [
                  'teacher_id' => $teacher->id,
                  'full_name' => $teacher->full_name,
                  'school_id' => $teacher->school_id,
                  'termination_reason' => $teacher->termination_reason,
              ]);
          }
      }

      public function deleted(Teacher $teacher): void
      {
          if ($teacher->photo_path) {
              Storage::disk('public')->delete($teacher->photo_path);
          }
      }
  }
  ```

- [x] **Registrar observers en `AppServiceProvider`:**
  ```php
  use App\Models\Tenant\Student;
  use App\Models\Tenant\Teacher;
  use App\Observers\Tenant\StudentObserver;
  use App\Observers\Tenant\TeacherObserver;

  public function boot(): void
  {
      Student::observe(StudentObserver::class);
      Teacher::observe(TeacherObserver::class);
  }
  ```

### 1.5 — Factories y Seeders de Desarrollo

- [x] **Factory `database/factories/Tenant/StudentFactory.php`:**
  ```php
  public function definition(): array
  {
      $gender = $this->faker->randomElement(['M', 'F']);
      
      return [
          'school_section_id' => SchoolSection::inRandomOrder()->first()?->id,
          'first_name' => $this->faker->firstName($gender === 'M' ? 'male' : 'female'),
          'last_name' => $this->faker->lastName(),
          'gender' => $gender,
          'date_of_birth' => $this->faker->dateTimeBetween('-18 years', '-5 years'),
          'place_of_birth' => $this->faker->city(),
          'rnc' => $this->faker->numerify('###-#######-#'),
          'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
          'is_active' => $this->faker->boolean(85),
          'enrollment_date' => $this->faker->dateTimeBetween('-3 years', 'now'),
      ];
  }

  public function withPhoto(): static
  {
      return $this->state(fn () => [
          'photo_path' => 'students/photos/' . $this->faker->uuid() . '.jpg',
      ]);
  }

  public function withUser(): static
  {
      return $this->state(fn () => [
          'user_id' => User::factory()->create()->id,
      ]);
  }
  ```

- [x] **Factory `database/factories/Tenant/TeacherFactory.php`:**
  ```php
  public function definition(): array
  {
      $gender = $this->faker->randomElement(['M', 'F']);
      
      return [
          'first_name' => $this->faker->firstName($gender === 'M' ? 'male' : 'female'),
          'last_name' => $this->faker->lastName(),
          'gender' => $gender,
          'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-25 years'),
          'rnc' => $this->faker->numerify('###-#######-#'),
          'specialization' => $this->faker->randomElement([
              'Matemáticas', 'Lengua Española', 'Ciencias Sociales',
              'Ciencias Naturales', 'Inglés', 'Educación Física',
          ]),
          'employment_type' => $this->faker->randomElement(['Full-Time', 'Part-Time']),
          'phone' => $this->faker->numerify('809-###-####'),
          'is_active' => $this->faker->boolean(90),
          'hire_date' => $this->faker->dateTimeBetween('-10 years', 'now'),
      ];
  }

  public function withUser(): static
  {
      return $this->state(fn () => [
          'user_id' => User::factory()->create()->id,
      ]);
  }
  ```

- [x] **Seeder `database/seeders/Development/StudentSeeder.php`:**
  - Generar 100 estudiantes distribuidos en secciones
  - 85% activos, 15% inactivos
  - 40% con foto, 20% con user_id (para classroom virtual)

- [x] **Seeder `database/seeders/Development/TeacherSeeder.php`:**
  - Generar 20 maestros
  - 90% activos, 10% inactivos
  - 60% con user_id (acceso al sistema)

```bash
./vendor/bin/sail artisan db:seed --class="Database\Seeders\Development\StudentSeeder"
./vendor/bin/sail artisan db:seed --class="Database\Seeders\Development\TeacherSeeder"
```

- [x] Agregar `school_id` en el factory de usuarios.

## EXTRA (PREPARATIVORIO PARA FASES FUTURAS)

- [x] **Actualizar migracion `2026_03_09_211217_create_school_shifts_table.php` + modelo:**
    - Agregar campo `start_time` y `end_time` (time) para definir horarios de tanda
    - Esto permitirá validar llegadas tardías en asistencia de plantel
- [x] **Actializar Actions:**
    - `CompleteTenantOnboardingAction.php` y `CompleteOnboardingAction.php` para que al crear los shifts le asignen los horarios por defecto (ej: Mañana 7:00-13:00, Tarde 13:00-19:00)

---

## Arquitectura del Módulo

### Conceptos Clave

**Dos Dominios de Asistencia:**
1. **Asistencia de Plantel (La Puerta):** Registro de entrada al centro educativo. Regida por tanda/jornada. Control de llegada tardía vs horario institucional.
2. **Asistencia de Aula (El Registro Anecdótico Digital):** Registro por materia, gestionado por cada maestro. Detecta "pasilleo" (presente en plantel, ausente en aula).

**Validación Cruzada Estricta:**
- Si estudiante está `Ausente` en Plantel → **No puede** estar `Presente` en Aula (bloqueo del sistema)
- Si estudiante está `Presente` en Plantel pero `Ausente` en Aula → Genera alerta de "Pasilleo" para coordinación

**Apertura Manual del Día:**
- El sistema **NO asume** que hay clases
- Un administrador debe "Abrir Asistencia del Día" (`DailyAttendanceSession`)
- Sin apertura = día feriado/libre, microservicios no procesan alertas

**Modo Offline/Cloud:**
- Variable: `APP_MODE=cloud` (VPS) o `APP_MODE=local` (PC del centro)
- Edge Node local con DB ligera para cortes de internet
- Sincronización automática cada 5 minutos cuando hay conexión

---

# Fase 2 — Catálogo Académico: Materias y Módulos Formativos
**Rama:** `feature/academic-catalog`

> Esta fase establece el modelo unificado de **materias** capaz de representar tanto asignaturas generales (Español, Matemática) como módulos formativos técnicos del MINERD (MF_053_3, MF_054_3…), vinculados a sus títulos técnicos. La decisión tomada aquí define cómo maestros, secciones y asistencia de aula operan en todas las fases posteriores.

---

## 2.1 — Migración y Modelo: `subjects`

- [x] **Crear migración `create_subjects_table`:**
  ```php
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            // Relación opcional con título técnico.
            // NULL = materia básica (Lengua Española, etc.)
            // Con ID = Módulo Formativo Técnico (MF_...)
            $table->foreignId('technical_title_id')
                  ->nullable()
                  ->constrained('technical_titles')
                  ->nullOnDelete();

            // Identificadores
            $table->string('code', 50)->unique();   // Ej: "ESP", "MF_053_3"
            $table->string('name', 255);            // Nombre del módulo o materia

            // Tipo de materia (Cambiado de ENUM a STRING para mayor flexibilidad)
            // Las constantes se definirán en el Modelo (ej: Subject::TYPE_BASIC)
            $table->string('type', 20)->default('basic');

            // Carga horaria
            $table->integer('hours_weekly')->default(0);  // Horas por semana
            $table->integer('hours_total')->default(0);   // Horas totales según el diseño curricular

            // Estética e Interfaz
            $table->string('color', 7)->default('#64748B');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            // Índices para optimizar búsquedas frecuentes
            $table->index(['type', 'is_active']);
            $table->index('technical_title_id');
        });
  ```

- [x] **Crear modelo `App\Models\Tenant\Academic\Subject`:**
  ```php
    namespace App\Models\Tenant\Academic;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Casts\Attribute;

    class Subject extends Model
    {
        // --- Constantes de Tipo ---
        const TYPE_BASIC = 'basic';
        const TYPE_TECHNICAL = 'technical';

        protected $fillable = [
            'technical_title_id', 
            'code', 
            'name',
            'type', 
            'hours_weekly', 
            'hours_total', 
            'color', 
            'is_active',
        ];

        protected $casts = [
            'is_active' => 'boolean',
        ];

        // ── Getters / Accessors ────────────────────────────────

        /**
        * Retorna un label legible para humanos.
        * Uso: $subject->type_label
        */
        protected function typeLabel(): Attribute
        {
            return Attribute::make(
                get: fn () => match($this->type) {
                    self::TYPE_BASIC => 'Básica/Académica',
                    self::TYPE_TECHNICAL => 'Módulo Técnico',
                    default => 'No definido',
                },
            );
        }

        /**
        * Helper estático para obtener todos los tipos (útil para selects en el UI)
        */
        public static function getTypes(): array
        {
            return [
                self::TYPE_BASIC => 'Básica/Académica',
                self::TYPE_TECHNICAL => 'Módulo Técnico',
            ];
        }

        // ── Relaciones ────────────────────────────────────────

        public function technicalTitle()
        {
            return $this->belongsTo(TechnicalTitle::class);
        }

        public function teacherAssignments()
        {
            return $this->hasMany(TeacherSubjectSection::class);
        }

        // ── Scopes ────────────────────────────────────────────

        public function scopeActive(Builder $query): Builder
        {
            return $query->where('is_active', true);
        }

        public function scopeBasic(Builder $query): Builder
        {
            return $query->where('type', self::TYPE_BASIC);
        }

        public function scopeTechnical(Builder $query): Builder
        {
            return $query->where('type', self::TYPE_TECHNICAL);
        }

        /**
        * Materias visibles para una escuela concreta.
        * Siempre incluye básicas + solo los módulos de sus títulos habilitados.
        */
        public function scopeAvailableForSchool(Builder $query, int $schoolId): Builder
        {
            $titleIds = \App\Models\Tenant\Academic\TechnicalTitle::whereHas(
                'schools', fn ($q) => $q->where('schools.id', $schoolId)
            )->pluck('id');

            return $query->where('is_active', true)
                ->where(function (Builder $q) use ($titleIds) {
                    $q->where('type', self::TYPE_BASIC)
                    ->orWhereIn('technical_title_id', $titleIds);
                });
        }

        // ── Helpers ───────────────────────────────────────────

        public function isTechnical(): bool
        {
            return $this->type === self::TYPE_TECHNICAL;
        }

        public function isBasic(): bool
        {
            return $this->type === self::TYPE_BASIC;
        }
    }
  ```

---

## 2.2 — Seeder: Materias Básicas

- [x] **Crear `database/seeders/AppInit/SubjectSeeder.php`:**

  Carga las materias comunes del plan de estudios dominicano. Son independientes de cualquier título técnico y están presentes en todos los grados.

  ```php
  Subject::insert([
      // Básicas transversales
      ['code' => 'LEN', 'name' => 'Lengua Española',                    'type' => 'basic', 'hours_weekly' => 3, 'color' => '#3B82F6'],
      ['code' => 'MAT', 'name' => 'Matemática',                         'type' => 'basic', 'hours_weekly' => 3, 'color' => '#10B981'],
      ['code' => 'CSO', 'name' => 'Ciencias Sociales',                   'type' => 'basic', 'hours_weekly' => 2, 'color' => '#F59E0B'],
      ['code' => 'CNA', 'name' => 'Ciencias de la Naturaleza',           'type' => 'basic', 'hours_weekly' => 3, 'color' => '#84CC16'],
      ['code' => 'FHR', 'name' => 'Formación Integral Humana y Religiosa','type' => 'basic', 'hours_weekly' => 1, 'color' => '#8B5CF6'],
      ['code' => 'EDF', 'name' => 'Educación Física',                    'type' => 'basic', 'hours_weekly' => 1, 'color' => '#EF4444'],
      ['code' => 'EDA', 'name' => 'Educación Artística',                 'type' => 'basic', 'hours_weekly' => 1, 'color' => '#EC4899'],
      ['code' => 'ING', 'name' => 'Lenguas Extranjeras (Inglés)',         'type' => 'basic', 'hours_weekly' => 4, 'color' => '#06B6D4'],
      ['code' => 'INT', 'name' => 'Inglés Técnico',                      'type' => 'basic', 'hours_weekly' => 4, 'color' => '#0EA5E9'],
      // Módulos comunes ETP (presentes en todos los títulos técnicos)
      ['code' => 'MF_002_3', 'name' => 'Ofimática',                      'type' => 'basic', 'hours_weekly' => 3, 'hours_total' => 135, 'color' => '#6366F1'],
      ['code' => 'MF_004_3', 'name' => 'Emprendimiento',                 'type' => 'basic', 'hours_weekly' => 3, 'hours_total' => 120, 'color' => '#F97316'],
      ['code' => 'MF_006_3', 'name' => 'Formación y Orientación Laboral', 'type' => 'basic', 'hours_weekly' => 2, 'hours_total' => 90,  'color' => '#A78BFA'],
      ['code' => 'PST', 'name' => 'Formación en centros de trabajo', 'type' => 'basic', 'hours_weekly' => 8, 'hours_total' => 360,  'color' => '#0f5ead'],
  ]);
  ```

  > **Nota:** Los tres módulos comunes ETP (`MF_002_3`, `MF_004_3`, `MF_006_3` y `PST (Pasantía)`) se clasifican como `basic` porque pertenecen al plan de estudios de **todos** los títulos técnicos. No necesitan `technical_title_id`.

---

## 2.3 — Importación de Módulos Técnicos desde CSV

> Esta es la pieza central: los ~300 módulos formativos que extrajiste con el script Python van a entrar aquí de forma automatizada y repetible.

### 2.3.1 — Comando de Importación

- [x] **Crear `app/Console/Commands/ImportTechnicalModules.php`:**

  ```php
    namespace App\Console\Commands;

    use App\Models\Tenant\Academic\Subject;
    use App\Models\Tenant\Academic\TechnicalTitle;
    use Illuminate\Console\Command;
    use Illuminate\Support\Facades\DB;

    class ImportTechnicalModules extends Command
    {
        protected $signature = 'orvian:import-technical-modules
                                {--file= : Ruta al CSV (default: database/data/modulos_formativos.csv)}
                                {--fresh : Elimina todos los módulos técnicos antes de importar}
                                {--dry-run : Simula la importación sin escribir en base de datos}';

        protected $description = 'Importa módulos formativos técnicos del MINERD desde el CSV generado por el script Python.';

        public function handle(): int
        {
            $file = $this->option('file') ?? database_path('data/modulos_formativos.csv');
            $isDryRun = $this->option('dry-run');
            $isFresh  = $this->option('fresh');

            if (! file_exists($file)) {
                $this->error("Archivo no encontrado: {$file}");
                $this->line("  Genera el CSV con: python extractor.py");
                $this->line("  Luego colócalo en: database/data/modulos_formativos.csv");
                return self::FAILURE;
            }

            // USO DE CONSTANTE PARA ELIMINACIÓN
            if ($isFresh && ! $isDryRun) {
                Subject::where('type', Subject::TYPE_TECHNICAL)->delete();
                $this->warn('🗑  Módulos técnicos anteriores eliminados (--fresh).');
            }

            $handle = fopen($file, 'r');
            $header = fgetcsv($handle); 

            $expected = ['codigo_titulo', 'titulo', 'familia_profesional', 'nivel', 'codigo_modulo', 'nombre_modulo'];
            if (array_diff($expected, $header)) {
                $this->error('El CSV no tiene las columnas esperadas. Revisa que sea el generado por extractor.py.');
                fclose($handle);
                return self::FAILURE;
            }

            // OPTIMIZACIÓN: Cargar todos los títulos en memoria (key => code, value => id)
            $titlesCache = TechnicalTitle::pluck('id', 'code');

            $bar      = $this->output->createProgressBar();
            $imported = 0;
            $skipped  = 0;
            $errors   = [];

            DB::beginTransaction();

            try {
                while (($row = fgetcsv($handle)) !== false) {
                    // Limpiar espacios en blanco de cada celda por inconsistencias en el CSV
                    $row = array_map('trim', $row);
                    $data = array_combine($header, $row);

                    // FILTRO BÁSICO: Omitir filas que son errores de extracción del PDF
                    if (str_starts_with(strtolower($data['nombre_modulo']), 'duración') || empty($data['codigo_modulo'])) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    $titleId = $titlesCache->get($data['codigo_titulo']);

                    if (! $titleId) {
                        $errors[] = "Título no encontrado: {$data['codigo_titulo']} (módulo: {$data['codigo_modulo']})";
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    if (! $isDryRun) {
                        Subject::updateOrCreate(
                            ['code' => $data['codigo_modulo']],
                            [
                                'technical_title_id' => $titleId,
                                'name'               => $data['nombre_modulo'],
                                // USO DE CONSTANTE EN LA ASIGNACIÓN:
                                'type'               => Subject::TYPE_TECHNICAL, 
                                'hours_weekly'       => 0, 
                                'hours_total'        => 0, 
                                'is_active'          => true,
                            ]
                        );
                    }

                    $imported++;
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();

                if ($isDryRun) {
                    DB::rollBack();
                    $this->info("🔍 DRY RUN: Se importarían {$imported} módulos ({$skipped} omitidos/basura).");
                } else {
                    DB::commit();
                    $this->info("✅ {$imported} módulos importados correctamente ({$skipped} omitidos).");
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error durante la importación: {$e->getMessage()}");
                return self::FAILURE;
            } finally {
                fclose($handle);
            }

            if (! empty($errors)) {
                $this->newLine();
                $this->warn(count($errors) . ' advertencias:');
                foreach (array_slice($errors, 0, 10) as $err) {
                    $this->line("  ⚠  {$err}");
                }
                if (count($errors) > 10) {
                    $this->line('  ... y ' . (count($errors) - 10) . ' más.');
                }
            }

            return self::SUCCESS;
        }
    }
  ```

- [x] **Registrar el comando** en `app/Console/Kernel.php` (o `bootstrap/app.php` si usas la forma moderna) para que aparezca en `php artisan list`.

### 2.3.2 — Ubicación y flujo del CSV

```
minerd-scraper/          ← Tu proyecto Python
└── modulos_formativos.csv

laravel-project/
└── database/
    └── data/
        └── modulos_formativos.csv   ← Lo copias aquí
```

**Flujo de actualización cuando hay nuevos títulos:**

```bash
# 1. Agregas la URL nueva en KNOWN_URLS del extractor.py y corres:
python extractor.py

# 2. Copias el CSV actualizado a database/data/

# 3. En Laravel, importas solo lo nuevo (updateOrCreate no duplica):
php artisan orvian:import-technical-modules

# Si quieres reimportar todo desde cero:
php artisan orvian:import-technical-modules --fresh

# Para verificar antes de escribir:
php artisan orvian:import-technical-modules --dry-run

# Ruta personalizada (útil en CI/CD):
php artisan orvian:import-technical-modules --file=/ruta/al/modulos_formativos.csv
```

> **Por qué `updateOrCreate` y no `insert`:** Si el mismo módulo ya existe (mismo `code`), actualiza su nombre en lugar de duplicarlo. Esto hace que el comando sea idempotente — puedes correrlo N veces sin miedo.

### 2.3.3 — Ampliar el CSV en el futuro (horas)

El CSV actual tiene 6 columnas. Si en el futuro el script Python extrae también las horas totales del PDF (que sí están en los documentos del MINERD), el comando ya tiene los campos `hours_weekly` y `hours_total` listos — solo agregas la columna al CSV y actualizas las dos líneas del `updateOrCreate`.

---

## 2.4 — Tabla Pivote: `school_technical_titles`

> Ya existe parcialmente en el modelo según el Changelog v0.2.0 (`school_technical_titles`). Aquí se define formalmente para el scope de filtrado de materias.

- [x] **Verificar que la migración `create_school_technical_titles_table` exista con al menos:** Se acualizo la tabla pivote para que tenga claves foráneas y eliminación en cascada.
  ```php
  Schema::create('school_technical_titles', function (Blueprint $table) {
      $table->foreignId('school_id')->constrained()->cascadeOnDelete();
      $table->foreignId('technical_title_id')->constrained()->cascadeOnDelete();
      $table->primary(['school_id', 'technical_title_id']);
  });
  ```

- [x] **Verificar relación en `TechnicalTitle`:**
  ```php
  public function schools()
  {
      return $this->belongsToMany(School::class, 'school_technical_titles');
  }
  ```

- [x] **Verificar relación en `School`:**
  ```php
  public function technicalTitles()
  {
      return $this->belongsToMany(TechnicalTitle::class, 'school_technical_titles');
  }
  ```

---

## 2.5 — Seeder Maestro y Orden de Ejecución

- [x] **Actualizar `DatabaseSeeder.php`** para correr los seeders en este orden:

  ```php
  // Catálogo técnico (ya existente desde v0.2.0)
  $this->call(TechnicalCatalogSeeder::class);

  // Materias básicas
  $this->call(SubjectSeeder::class);

  // Módulos técnicos desde CSV
  // (se ejecuta como comando, no como seeder, para poder usarlo en producción)
  // En desarrollo puedes llamarlo desde aquí si el CSV está presente:
  if (file_exists(database_path('data/modulos_formativos.csv'))) {
      $this->command->call('orvian:import-technical-modules');
  }
  ```

---

## 2.6 — Tabla Pivote: `teacher_subject_sections`

> Conecta Maestro ↔ Materia ↔ Sección. Se define aquí porque depende de `subjects`.

- [x] **Crear migración `create_teacher_subject_sections_table`:**
  ```php
        Schema::create('teacher_subject_sections', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_section_id')->constrained('school_sections')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            
            // Estado y Auditoría
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // REGLA DE ORO: Evita que el mismo maestro tenga la misma materia 
            // en la misma sección durante el mismo año escolar.
            $table->unique(
                ['teacher_id', 'subject_id', 'school_section_id', 'academic_year_id'], 
                'unique_teacher_assignment'
            );

            // Índice para velocidad de carga de horarios y listados por sección/año
            $table->index(['school_section_id', 'academic_year_id']);
        });
  ```

- [x] **Modelo `App\Models\Tenant\Academic\TeacherSubjectSection`:**
  ```php
    namespace App\Models\Tenant\Academic;

    use App\Models\Tenant\Teacher; // Asumiendo que Teacher está en Tenant
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class TeacherSubjectSection extends Model
    {
        protected $fillable = [
            'teacher_id', 
            'subject_id', 
            'school_section_id', 
            'academic_year_id', 
            'is_active',
        ];

        protected $casts = [
            'is_active' => 'boolean',
        ];

        /**
        * El maestro asignado.
        */
        public function teacher(): BelongsTo
        {
            return $this->belongsTo(Teacher::class);
        }

        /**
        * La materia/asignatura.
        */
        public function subject(): BelongsTo
        {
            return $this->belongsTo(Subject::class);
        }

        /**
        * La sección (Grado + Aula).
        */
        public function section(): BelongsTo
        {
            return $this->belongsTo(SchoolSection::class, 'school_section_id');
        }

        /**
        * El periodo escolar de la asignación.
        */
        public function academicYear(): BelongsTo
        {
            return $this->belongsTo(AcademicYear::class, 'academic_year_id');
        }
    }
  ```

---

## Checklist de Completitud — Fase 2

### Modelo y Migración
- [x] Migración `create_subjects_table` con `technical_title_id` nullable
- [x] Modelo `Subject` con scopes `basic()`, `technical()`, `availableForSchool()`
- [x] Migración `create_teacher_subject_sections_table` con unique constraint
- [x] Modelo `TeacherSubjectSection`

### Datos Maestros
- [x] `SubjectSeeder` con materias básicas y módulos comunes ETP
- [x] Pivote `school_technical_titles` verificado (actualizado) y con relaciones en ambos modelos
- [x] CSV `modulos_formativos.csv` generado por `extractor.py` colocado en `database/data/`

### Comando de Importación
- [x] `orvian:import-technical-modules` creado y registrado
- [x] Flag `--fresh` funciona (elimina técnicas y reimporta)
- [x] Flag `--dry-run` funciona (muestra conteo sin escribir)
- [x] Flag `--file=` funciona con ruta alternativa
- [x] Manejo de errores: título no encontrado → advertencia, no falla toda la importación
- [x] `updateOrCreate` idempotente verificado (correr dos veces no duplica)

### Integración
- [x] `DatabaseSeeder` llama al comando si el CSV existe
- [x] Scope `availableForSchool()` devuelve solo básicas + módulos del título de la sección
- [x] Verificado con tinker: escuela con IFC006_3 habilitado ve módulos MF_053_3…MF_060_3, no los de SAL058_3

### Extra

- [x] Modelo `Teacher.php`actualizado para activar la relacion con `Subject` y `TeacherSubjectSection` ya que no estaban creados aun.

---

## Notas de Implementación — Fase 2

**¿Por qué comando y no seeder para los módulos técnicos?**
Los seeders solo corren en `db:seed` (típicamente en desarrollo o en el wizard inicial). El comando `orvian:import-technical-modules` puede correrse en **producción** sin riesgo, cuando agregas nuevos títulos al CSV. Eso es lo que lo hace versátil.

**¿Qué pasa con el `hours_weekly` de los módulos?**
El CSV del MINERD no tiene horas semanales desglosadas (solo horas totales del módulo, que sí están en el PDF). El comando las deja en `0` por defecto. Si necesitas las horas para calcular horarios, el plan de estudios de cada título (página 14 del PDF de IFC006_3, por ejemplo) tiene la tabla completa — podrías extenderla en Python o ingresarla manualmente por título.

**Regla de oro del scope:**
La lógica de qué materias ve cada escuela **no vive en el modelo Subject** con un Global Scope (eso sería peligroso si un admin global necesita ver todo). Vive en el componente Livewire que construye el selector, llamando explícitamente a `Subject::availableForSchool($schoolId)`. Simple y auditable.

---

## Fase 2.5 — Integración de Tandas con Secciones y Wizard
**Rama:** `feature/shift-section-integration`

**Objetivo:** Actualizar la jerarquía académica para que las Secciones dependan de Tandas (`SchoolShift`), refactorizar el Wizard de configuración inicial con validación de exclusividad mutua de tandas, y mejorar la visualización administrativa agrupando cursos por tanda.

---

### 2.5.1 — Actualización de Base de Datos y Modelos

- [x] **Migración: Actualizar `school_sections` para incluir `school_shift_id`:**
  ```php
  Schema::table('school_sections', function (Blueprint $table) {
      $table->foreignId('school_shift_id')
          ->after('school_id')
          ->constrained('school_shifts')
          ->cascadeOnDelete();
      
      // Índice para optimizar consultas por tanda
      $table->index(['school_id', 'school_shift_id']);
  });
  ```

- [x] **Actualizar modelo `App\Models\Tenant\SchoolSection`:**
  ```php
  namespace App\Models\Tenant;

  class SchoolSection extends Model
  {
      use BelongsToSchool;

      protected $fillable = [
          'school_id',
          'school_shift_id', // ← NUEVO
          'grade_id',
          'label',
          'technical_title_id',
      ];

      protected static function booted(): void
      {
          static::addGlobalScope(new SchoolScope);
      }

      // Relaciones
      public function school()
      {
          return $this->belongsTo(School::class);
      }

      public function shift() // ← NUEVA RELACIÓN
      {
          return $this->belongsTo(SchoolShift::class, 'school_shift_id');
      }

      public function grade()
      {
          return $this->belongsTo(Grade::class);
      }

      public function technicalTitle()
      {
          return $this->belongsTo(TechnicalTitle::class);
      }

      public function students()
      {
          return $this->hasMany(Student::class);
      }

      // Scopes
      public function scopeForShift($query, int $shiftId)
      {
          return $query->where('school_shift_id', $shiftId);
      }

      public function scopeWithFullRelations($query)
      {
          return $query->with([
              'shift:id,name,start_time,end_time',
              'grade:id,name,level_id,cycle',
              'grade.level:id,name',
              'technicalTitle:id,name,code',
              'technicalTitle.family:id,name',
          ]);
      }
  }
  ```

- [x] **Actualizar modelo `App\Models\Tenant\SchoolShift` con relación inversa:**
  ```php
  namespace App\Models\Tenant;

  class SchoolShift extends Model
  {
      // ... código existente

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
  }
  ```

### 2.5.2 — Actualización del Wizard de Configuración

- [x] **Actualizar `App\Livewire\Tenant\BaseSchoolWizard.php` — Propiedades de Tandas:**
  ```php
  namespace App\Livewire\Tenant;

  abstract class BaseSchoolWizard extends Component
  {
      // ... propiedades existentes

      // ── Paso 3: Tandas (Shifts) ──────────────────────────────
      public array $selectedShifts = []; // IDs de tandas seleccionadas
      public bool $shiftConflict = false; // Flag para mostrar advertencia

      // ... resto del código

      protected function validateStep(int $step): void
      {
          if ($step === 3) {
              $this->validate([
                  'selectedLevels' => ['required', 'array', 'min:1'],
                  'selectedShifts' => ['required', 'array', 'min:1'], // ← NUEVA VALIDACIÓN
                  'selectedSectionLabels' => ['required', 'array', 'min:1'],
                  'year_start' => ['required', 'date'],
                  'year_end' => ['required', 'date', 'after:year_start'],
                  'selectedTechnicalTitles' => [$this->needsTitles ? 'required' : 'nullable', 'array'],
              ], [
                  'selectedShifts.required' => 'Debe seleccionar al menos una tanda.',
                  'selectedShifts.min' => 'Debe seleccionar al menos una tanda.',
              ]);

              // Validación de exclusividad mutua: Jornada Extendida vs Matutina/Vespertina
              $this->validateShiftExclusivity();

              return;
          }

          // ... validaciones de otros pasos
      }

      /**
       * Validación personalizada: Exclusividad de tandas
       */
      protected function validateShiftExclusivity(): void
      {
          $hasExtended = in_array(SchoolShift::TYPE_EXTENDED, $this->selectedShifts);
          $hasMorning = in_array(SchoolShift::TYPE_MORNING, $this->selectedShifts);
          $hasAfternoon = in_array(SchoolShift::TYPE_AFTERNOON, $this->selectedShifts);

          // Regla: Si se selecciona "Extendida", no puede haber "Matutina" ni "Vespertina"
          if ($hasExtended && ($hasMorning || $hasAfternoon)) {
              throw ValidationException::withMessages([
                  'selectedShifts' => 'La Jornada Extendida no puede combinarse con Matutina o Vespertina. Desmarca una opción.',
              ]);
          }

          // Regla inversa: Si hay "Matutina" o "Vespertina", no puede haber "Extendida"
          if (($hasMorning || $hasAfternoon) && $hasExtended) {
              throw ValidationException::withMessages([
                  'selectedShifts' => 'Matutina/Vespertina no pueden combinarse con Jornada Extendida.',
              ]);
          }

          // La tanda "Nocturna" es independiente y puede combinarse con cualquiera
      }

      protected function academicPayload(): array
      {
          $startDate = Carbon::parse($this->year_start);
          $endDate = Carbon::parse($this->year_end);

          return [
              'year_name' => $startDate->year . '-' . $endDate->year,
              'start_date' => $startDate,
              'end_date' => $endDate,
              'level_ids' => $this->selectedLevels,
              'shift_ids' => $this->selectedShifts, // ← NUEVO
              'section_labels' => $this->selectedSectionLabels,
              'technical_title_ids' => $this->selectedTechnicalTitles,
          ];
      }

      // ... resto del código
  }
  ```


### 2.5.3 — Vista del Paso 3 del Wizard con Alpine.js

- [x] **Actualizar `resources/views/livewire/tenant/steps/_step-3-academic.blade.php`:**
  ```blade
        {{-- 2. Selección de Tandas (Con Validación de Exclusividad) --}}
    @php
        // Definimos el FQN de la clase para evitar errores de "Class not found"
        $shiftModel = \App\Models\Tenant\Academic\SchoolShift::class;
    @endphp

    <div class="space-y-4" 
        x-data="{
            selectedShifts: $wire.entangle('selectedShifts'),
            {{-- Usamos las constantes del modelo con comillas porque son strings en la BD --}}
            get hasExtended() { return this.selectedShifts.includes('{{ $shiftModel::TYPE_EXTENDED }}') },
            get hasMorning() { return this.selectedShifts.includes('{{ $shiftModel::TYPE_MORNING }}') },
            get hasAfternoon() { return this.selectedShifts.includes('{{ $shiftModel::TYPE_AFTERNOON }}') },
            
            get showConflictWarning() { 
                return this.hasExtended && (this.hasMorning || this.hasAfternoon);
            }
        }">
        
        <div class="flex items-center justify-between">
            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Tandas / Jornadas <span class="text-state-error ml-0.5">*</span>
            </label>
            
            {{-- Advertencia de Conflicto en tiempo real con Alpine --}}
            <div x-show="showConflictWarning" 
                x-transition 
                style="display:none;"
                class="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400 font-medium">
                <x-heroicon-s-exclamation-triangle class="w-4 h-4" />
                <span>Conflicto: La Jornada Extendida es exclusiva.</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- En BaseSchoolWizard, shifts() devuelve un array [id => label] --}}
            @foreach($this->shifts as $id => $label)
                @php
                    $meta = match($label) {
                        $shiftModel::TYPE_MORNING   => ['icon' => '☀️', 'hours' => '7:30 AM – 12:30 PM', 'conflict' => 'hasExtended'],
                        $shiftModel::TYPE_AFTERNOON => ['icon' => '🌤️', 'hours' => '1:30 PM – 6:00 PM',  'conflict' => 'hasExtended'],
                        $shiftModel::TYPE_EXTENDED  => ['icon' => '🌞', 'hours' => '8:00 AM – 4:00 PM',  'conflict' => 'hasMorning || hasAfternoon'],
                        $shiftModel::TYPE_NIGHT     => ['icon' => '🌙', 'hours' => '6:00 PM – 10:00 PM', 'conflict' => 'false'],
                        default => ['icon' => '🕐', 'hours' => '', 'conflict' => 'false'],
                    };
                @endphp

                <label 
                    class="relative flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition-all cursor-pointer select-none"
                    :class="{
                        'border-orvian-orange bg-orvian-orange/5 ring-1 ring-orvian-orange/20': selectedShifts.includes('{{ $id }}'),
                        'border-slate-200 dark:border-white/6 opacity-40 cursor-not-allowed': {{ $meta['conflict'] }},
                        'border-slate-200 dark:border-white/6 hover:border-slate-300': !selectedShifts.includes('{{ $id }}') && !({{ $meta['conflict'] }})
                    }">
                    
                    <input 
                        type="checkbox" 
                        wire:model.live="selectedShifts"
                        value="{{ $id }}"
                        class="hidden"
                        :disabled="{{ $meta['conflict'] }}"
                    >
                    
                    <span class="text-2xl">{{ $meta['icon'] }}</span>
                    <div class="text-center">
                        <p class="text-xs font-black text-gray-900 dark:text-white leading-tight">{{ $label }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $meta['hours'] }}</p>
                    </div>

                    {{-- Icono de Checkmark --}}
                    <div x-show="selectedShifts.includes('{{ $id }}')" class="absolute top-2 right-2">
                        <x-heroicon-s-check-circle class="w-5 h-5 text-orvian-orange" />
                    </div>
                </label>
            @endforeach
        </div>

        @error('selectedShifts')
            <p class="text-xs text-state-error mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- ── Secciones (Paralelos) ── --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Secciones (Paralelos) <span class="text-state-error ml-0.5">*</span>
            </p>
            <span class="text-[10px] font-bold text-orvian-orange"
                  x-text="$wire.selectedSectionLabels.length + ' seleccionada(s)'"></span>
        </div>
        <p class="text-[10px] text-slate-500 leading-relaxed">
            Cada letra representa un paralelo por grado. Ej: <strong>A, B, C</strong> → 1roA, 1roB, 1roC en cada grado.
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($this->availableSectionLabels() as $letter)
                <label @class([
                    'flex items-center justify-center w-10 h-10 rounded-xl border-2 cursor-pointer transition-all text-sm font-black select-none',
                    'border-orvian-orange bg-orvian-orange/10 text-orvian-orange shadow-[0_0_8px_rgba(247,137,4,0.2)]' => in_array($letter, $selectedSectionLabels),
                    'border-slate-200 dark:border-white/6 text-slate-400 hover:border-slate-300 dark:hover:border-white/15' => !in_array($letter, $selectedSectionLabels),
                ])>
                    <input type="checkbox" wire:model.live="selectedSectionLabels" value="{{ $letter }}" class="hidden" />
                    {{ $letter }}
                </label>
            @endforeach
        </div>

        {{-- Preview de Secciones a Crear --}}
        @php $estimation = $this->estimatedSections; @endphp

        @if($estimation['total'] > 0)
            <div class="p-4 bg-gradient-to-br from-slate-50 to-orvian-blue/5 dark:from-white/[0.02] dark:to-orvian-orange/5 border border-dashed border-slate-300 dark:border-white/10 rounded-2xl transition-all">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-orvian-orange/10 rounded-lg">
                            <x-heroicon-s-calculator class="w-5 h-5 text-orvian-orange" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200">Resumen de Estructura</h4>
                            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Cálculo basado en selección actual</p>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <span class="text-2xl font-black text-orvian-orange leading-none">
                            {{ $estimation['total'] }}
                        </span>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Secciones</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                    {{-- Detalle Académico --}}
                    <div class="p-2.5 rounded-xl bg-white/50 dark:bg-black/20 border border-slate-200 dark:border-white/5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Cursos Generales</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-lg font-bold text-slate-700 dark:text-slate-300">{{ $estimation['academic'] }}</span>
                            <span class="text-[10px] text-slate-500">secciones</span>
                        </div>
                    </div>

                    {{-- Detalle Técnico (Solo se muestra si hay títulos o modalidad técnica) --}}
                    @if($this->needsTitles)
                        <div class="p-2.5 rounded-xl bg-orvian-orange/[0.03] border border-orvian-orange/20">
                            <p class="text-[10px] font-bold text-orvian-orange/80 uppercase mb-1">Cursos Técnicos</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-lg font-bold text-orvian-orange">{{ $estimation['technical'] }}</span>
                                <span class="text-[10px] text-orvian-orange/60">secciones</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Explicación de la fórmula (Dinámica) --}}
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-white/5">
                    <p class="text-[10px] text-slate-500 leading-relaxed italic">
                        * Nota: 
                        @if($this->needsTitles && count($selectedTitles) > 0)
                            Se calculan {{ count($selectedSectionLabels) }} paralelos por cada uno de los {{ count($selectedTitles) }} títulos técnicos en los grados de 2do Ciclo.
                        @else
                            Se calculan {{ count($selectedSectionLabels) }} paralelos uniformes para todos los grados seleccionados.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        @error('selectedSectionLabels') <p class="text-xs text-state-error mt-1">{{ $message }}</p> @enderror
    </div>
  ```

- [x] **Agregar propiedad computada en `BaseSchoolWizard.php` para preview:**
  ```php
    #[Computed]
    public function estimatedSections(): array
    {
        // Si falta algún dato base, retornamos ceros
        if (empty($this->selectedLevels) || empty($this->selectedShifts) || empty($this->selectedSectionLabels)) {
            return ['total' => 0, 'academic' => 0, 'technical' => 0];
        }

        $levels = \App\Models\Tenant\Academic\Level::whereIn('id', $this->selectedLevels)
            ->with('grades')
            ->get();

        $numShifts = count($this->selectedShifts);
        $numParallels = count($this->selectedSectionLabels);
        $numTitles = count($this->selectedTitles);

        $academicCount = 0;
        $technicalCount = 0;

        foreach ($levels as $level) {
            $gradeCount = $level->grades->count();

            // Lógica: Si el nivel es de 2do Ciclo de Secundaria Y la escuela requiere títulos
            // Nota: Ajusta 'secundaria_2' según el identificador real en tu BD
            $isTechnicalLevel = str_contains(strtolower($level->name), 'segundo ciclo') && $this->needsTitles;

            if ($isTechnicalLevel && $numTitles > 0) {
                // Formula: Grados * Tandas * Paralelos * Cantidad de Títulos
                $technicalCount += ($gradeCount * $numShifts * $numParallels * $numTitles);
            } else {
                // Formula: Grados * Tandas * Paralelos (Normal)
                $academicCount += ($gradeCount * $numShifts * $numParallels);
            }
        }

        return [
            'total' => $academicCount + $technicalCount,
            'academic' => $academicCount,
            'technical' => $technicalCount,
        ];
    }
  ```

### 2.5.4 — Actualización de Listeners del Wizard

- [ ] **Actualizar `App\Listeners\Tenant\SetupAcademicStructure.php` — Integrar Tandas:**
  ```php
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
  ```


### 2.5.5 — Actualización de Visualización Administrativa

- [x] **Actualizar `App\Livewire\Admin\Schools\SchoolShow.php` — Agrupar por Tanda:**
  ```php
  namespace App\Livewire\Admin\Schools;

  use App\Models\Tenant\School;
  use App\Models\Tenant\SchoolSection;
  use App\Models\Tenant\SchoolShift;
  use App\Models\Scopes\SchoolScope;
  use Livewire\Component;
  use Livewire\Attributes\Computed;

  class SchoolShow extends Component
  {
      // ... código existente

      /**
       * Estructura académica agrupada por Tanda > Nivel > Ciclo > Familia > Grado
       */
      #[Computed]
      public function academicStructure(): array
      {
          // 1. Obtener todas las secciones con relaciones completas
          $sections = SchoolSection::withoutGlobalScope(SchoolScope::class)
              ->with([
                  'shift:id,name,start_time,end_time',
                  'grade:id,name,level_id,cycle,allows_technical',
                  'grade.level:id,name',
                  'technicalTitle:id,name,code,technical_family_id',
                  'technicalTitle.family:id,name',
              ])
              ->where('school_id', $this->school->id)
              ->get();

          if ($sections->isEmpty()) {
              return [];
          }

          $structure = [];

          // 2. Agrupar primero por Tanda
          $sectionsByShift = $sections->groupBy('school_shift_id');

          foreach ($sectionsByShift as $shiftId => $shiftSections) {
              $shift = $shiftSections->first()->shift;
              $shiftType = $shift->name;

              $structure[$shiftType] = [
                  'shift' => $shift,
                  'sections_count' => $shiftSections->count(),
                  'levels' => [],
              ];

              // 3. Dentro de cada tanda, agrupar por Nivel (Primario/Secundario)
              foreach ($shiftSections as $section) {
                  $grade = $section->grade;
                  $level = $grade->level;
                  $nivel = $level->name; // "Nivel Inicial", "Nivel Primario", etc.
                  $ciclo = $grade->cycle;

                  // Inicializar estructura si no existe
                  if (!isset($structure[$shiftType]['levels'][$nivel])) {
                      $structure[$shiftType]['levels'][$nivel] = [];
                  }

                  if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo])) {
                      $structure[$shiftType]['levels'][$nivel][$ciclo] = [];
                  }

                  // 4. Lógica de agrupación por Familia Técnica (solo Segundo Ciclo Secundaria)
                  if ($grade->allows_technical && $section->technical_title_id) {
                      $familyName = $section->technicalTitle->family->name;
                      
                      if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo][$familyName])) {
                          $structure[$shiftType]['levels'][$nivel][$ciclo][$familyName] = [];
                      }

                      // Buscar o crear entrada para este grado específico
                      $gradeKey = $grade->id . '-' . $section->technicalTitle->id;
                      
                      if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo][$familyName][$gradeKey])) {
                          $structure[$shiftType]['levels'][$nivel][$ciclo][$familyName][$gradeKey] = [
                              'title' => explode(' ', $grade->name)[0], // "Cuarto", "Quinto", etc.
                              'subtitle' => mb_strtoupper($section->technicalTitle->name),
                              'is_technical' => true,
                              'family_id' => $section->technicalTitle->technical_family_id,
                              'sections' => collect([]),
                          ];
                      }

                      $structure[$shiftType]['levels'][$nivel][$ciclo][$familyName][$gradeKey]['sections']->push($section);
                  } else {
                      // 5. Grados estándar (sin título técnico)
                      if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo]['General'])) {
                          $structure[$shiftType]['levels'][$nivel][$ciclo]['General'] = [];
                      }

                      $gradeKey = $grade->id;

                      if (!isset($structure[$shiftType]['levels'][$nivel][$ciclo]['General'][$gradeKey])) {
                          $structure[$shiftType]['levels'][$nivel][$ciclo]['General'][$gradeKey] = [
                              'title' => explode(' ', $grade->name)[0],
                              'subtitle' => mb_strtoupper(explode(' ', $grade->name)[1] ?? $nivel),
                              'is_technical' => false,
                              'sections' => collect([]),
                          ];
                      }

                      $structure[$shiftType]['levels'][$nivel][$ciclo]['General'][$gradeKey]['sections']->push($section);
                  }
              }

              // 6. Ordenar secciones dentro de cada grado
              foreach ($structure[$shiftType]['levels'] as $nivel => $ciclos) {
                  foreach ($ciclos as $ciclo => $familias) {
                      foreach ($familias as $familia => $grados) {
                          foreach ($grados as $gradeKey => $grado) {
                              $structure[$shiftType]['levels'][$nivel][$ciclo][$familia][$gradeKey]['sections'] = 
                                  $grado['sections']->sortBy('label')->values();
                          }
                      }
                  }
              }
          }

          return $structure;
      }

      // ... resto del código
  }
  ```

- [x] **Actualizar vista `resources/views/livewire/admin/schools/tabs/_courses.blade.php` con Tabs por Tanda:**
  ```blade
  <div class="space-y-8">
      @if(empty($this->academicStructure))
          {{-- Empty State --}}
          <div class="py-20 text-center">
              <x-heroicon-o-academic-cap class="w-16 h-16 mx-auto text-slate-300 dark:text-gray-700 mb-4" />
              <p class="text-slate-400 dark:text-gray-600 font-medium">
                  No se encontraron grados configurados para esta institución.
              </p>
          </div>
      @else
          {{-- Tabs por Tanda --}}
          <div 
              x-data="{ activeShift: '{{ array_key_first($this->academicStructure) }}' }"
              class="space-y-6">
              
              {{-- Navegación de Tabs --}}
              <div class="flex flex-wrap border-b border-slate-200 dark:border-gray-800 gap-4">
                  @foreach($this->academicStructure as $shiftType => $shiftData)
                      <button 
                          @click="activeShift = '{{ $shiftType }}'"
                          class="pb-4 px-4 transition-all font-bold text-sm relative group"
                          :class="{
                              'text-orvian-orange': activeShift === '{{ $shiftType }}',
                              'text-slate-500 dark:text-gray-500 hover:text-orvian-navy dark:hover:text-white': activeShift !== '{{ $shiftType }}'
                          }">
                          
                          <div class="flex items-center gap-2">
                              <x-heroicon-o-clock class="w-4 h-4" />
                              <span>{{ $shiftType }}</span>
                              <x-ui.badge variant="slate" size="xs">
                                  {{ $shiftData['sections_count'] }}
                              </x-ui.badge>
                          </div>

                          {{-- Indicador de borde inferior --}}
                          <div 
                              class="absolute bottom-0 left-0 w-full h-0.5 transition-all"
                              :class="{
                                  'bg-orvian-orange': activeShift === '{{ $shiftType }}',
                                  'bg-transparent group-hover:bg-slate-300 dark:group-hover:bg-gray-700': activeShift !== '{{ $shiftType }}'
                              }">
                          </div>
                      </button>
                  @endforeach
              </div>

              {{-- Contenido de cada Tab --}}
              @foreach($this->academicStructure as $shiftType => $shiftData)
                  <div 
                      x-show="activeShift === '{{ $shiftType }}'"
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0 transform translate-y-2"
                      x-transition:enter-end="opacity-100 transform translate-y-0"
                      class="space-y-12">
                      
                      {{-- Badge de información de la tanda --}}
                      <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-orvian-blue/5 to-orvian-orange/5 dark:from-orvian-blue/10 dark:to-orvian-orange/10 rounded-xl border border-dashed border-orvian-orange/30">
                          <x-heroicon-s-clock class="w-6 h-6 text-orvian-orange" />
                          <div>
                              <p class="text-sm font-bold text-gray-900 dark:text-white">
                                  Horario: {{ $shiftData['shift']->start_time }} - {{ $shiftData['shift']->end_time }}
                              </p>
                              <p class="text-xs text-slate-500 dark:text-gray-400">
                                  Total de {{ $shiftData['sections_count'] }} secciones activas
                              </p>
                          </div>
                      </div>

                      {{-- Iterar por Niveles dentro de esta Tanda --}}
                      @forelse($shiftData['levels'] as $nivel => $ciclos)
                          <div class="space-y-8">
                              {{-- Encabezado de Nivel --}}
                              <div class="flex items-center gap-3">
                                  <span class="text-2xl">
                                      {{ str_contains($nivel, 'Primario') ? '👦' : (str_contains($nivel, 'Inicial') ? '👶' : '🎓') }}
                                  </span>
                                  <h2 class="text-2xl font-bold text-orvian-navy dark:text-white tracking-tight">
                                      {{ $nivel }}
                                  </h2>
                                  <div class="flex-grow border-t border-slate-200 dark:border-gray-800/50 ml-4"></div>
                              </div>

                              @foreach($ciclos as $ciclo => $familias)
                                  <div class="space-y-4">
                                      {{-- Separador de Ciclo --}}
                                      <div class="flex items-center gap-2">
                                          <span class="w-2 h-2 rounded-full {{ $ciclo === 'Primer Ciclo' ? 'bg-orange-500' : 'bg-green-500' }}"></span>
                                          <h3 class="text-[11px] font-black text-slate-400 dark:text-gray-500 uppercase tracking-[0.2em]">
                                              {{ $ciclo }} {{ (str_contains($nivel, 'Secundario') && $ciclo === 'Segundo Ciclo') ? '(TÉCNICO)' : '' }}
                                          </h3>
                                          <div class="flex-grow border-t border-dashed border-slate-200 dark:border-gray-800 ml-4"></div>
                                      </div>

                                      {{-- Contenedor de Familias / Grupos --}}
                                      <div class="space-y-8 pl-4">
                                          @foreach($familias as $familyName => $grados)
                                              <div class="group">
                                                  @if($familyName !== 'General')
                                                      <h4 class="text-xs font-bold text-orvian-orange dark:text-orange-400 mb-4 flex items-center gap-2">
                                                          <x-heroicon-s-tag class="w-3 h-3" />
                                                          {{ $familyName }}
                                                      </h4>
                                                  @endif

                                                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                      @foreach($grados as $grado)
                                                          @php
                                                              // Lógica de colores dinámica
                                                              $borderColors = [
                                                                  1 => 'border-l-blue-500',
                                                                  2 => 'border-l-orange-500',
                                                                  4 => 'border-l-green-500',
                                                                  5 => 'border-l-yellow-500',
                                                              ];
                                                              $borderClass = $grado['is_technical'] 
                                                                  ? ($borderColors[$grado['family_id']] ?? 'border-l-slate-400') 
                                                                  : 'border-l-transparent';
                                                          @endphp

                                                          <div class="bg-white dark:bg-dark-card rounded-2xl p-4 border border-slate-200 dark:border-gray-800 shadow-sm transition-all hover:shadow-md {{ $borderClass }} border-l-4">
                                                              <div class="flex justify-between items-start mb-4">
                                                                  <div>
                                                                      <h5 class="text-xl font-extrabold text-orvian-navy dark:text-white leading-none">
                                                                          {{ $grado['title'] }}
                                                                      </h5>
                                                                      <p class="text-[9px] text-slate-400 dark:text-gray-500 font-bold uppercase tracking-wider mt-1">
                                                                          {{ $grado['subtitle'] }}
                                                                      </p>
                                                                  </div>
                                                                  <x-heroicon-o-academic-cap class="w-4 h-4 text-slate-300 dark:text-gray-700" />
                                                              </div>

                                                              <div class="flex flex-wrap gap-1.5 mt-auto">
                                                                  @forelse($grado['sections'] as $section)
                                                                      <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-gray-900/80 px-2 py-1 rounded-lg border border-slate-100 dark:border-gray-800">
                                                                          <span class="text-[11px] font-black text-orvian-orange">
                                                                              {{ $section->label }}
                                                                          </span>
                                                                          <span class="text-[10px] font-medium text-slate-400 dark:text-gray-600">--</span>
                                                                      </div>
                                                                  @empty
                                                                      <span class="text-[10px] text-slate-400 italic">Vacío</span>
                                                                  @endforelse
                                                              </div>
                                                          </div>
                                                      @endforeach
                                                  </div>
                                              </div>
                                          @endforeach
                                      </div>
                                  </div>
                              @endforeach
                          </div>
                      @empty
                          <div class="py-12 text-center">
                              <p class="text-slate-400 dark:text-gray-600">
                                  No hay grados configurados en esta tanda.
                              </p>
                          </div>
                      @endforelse
                  </div>
              @endforeach
          </div>
      @endif
  </div>
  ```

### 2.5.6 — Actualización de Índice de Escuelas

- [x] **Actualizar `resources/views/livewire/admin/schools/school-show.blade.php` — Mostrar Tandas Activas:**
  ```blade
  {{-- En la columna de información adicional de cada escuela --}}
        {{-- Tandas --}}
        <div class="flex items-center gap-2 bg-gray-100 dark:bg-dark-card pl-2 pr-1 py-1 rounded-lg border border-gray-200 dark:border-gray-800">
            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Tandas:</span>
            <div class="flex -space-x-1">
                @foreach($school->shifts as $shift)
                    <x-ui.badge 
                        variant="info" 
                        size="sm"
                        class="font-bold border-2 border-white dark:border-dark-card"
                        title="{{ $shift->start_time->format('h:i A') }} - {{ $shift->end_time->format('h:i A') }}"
                    >
                        {{ substr($shift->type, 0, 1) }}
                    </x-ui.badge>
                @endforeach
            </div>
        </div>
  ```
<!-- 
### 2.5.7 — Testing y Validación

- [ ] **Crear test `tests/Feature/Wizard/ShiftValidationTest.php`:**
  ```php
  namespace Tests\Feature\Wizard;

  use Tests\TestCase;
  use App\Models\Tenant\SchoolShift;
  use Livewire\Livewire;
  use App\Livewire\Tenant\SchoolWizard;

  class ShiftValidationTest extends TestCase
  {
      /** @test */
      public function cannot_select_extended_with_morning_shift()
      {
          Livewire::test(SchoolWizard::class)
              ->set('step', 3)
              ->set('selectedShifts', [
                  SchoolShift::SHIFT_EXTENDED,
                  SchoolShift::SHIFT_MORNING,
              ])
              ->call('validateStep', 3)
              ->assertHasErrors('selectedShifts');
      }

      /** @test */
      public function cannot_select_extended_with_afternoon_shift()
      {
          Livewire::test(SchoolWizard::class)
              ->set('step', 3)
              ->set('selectedShifts', [
                  SchoolShift::SHIFT_EXTENDED,
                  SchoolShift::SHIFT_AFTERNOON,
              ])
              ->call('validateStep', 3)
              ->assertHasErrors('selectedShifts');
      }

      /** @test */
      public function can_select_extended_with_night_shift()
      {
          Livewire::test(SchoolWizard::class)
              ->set('step', 3)
              ->set('selectedShifts', [
                  SchoolShift::SHIFT_EXTENDED,
                  SchoolShift::SHIFT_NIGHT,
              ])
              ->call('validateStep', 3)
              ->assertHasNoErrors('selectedShifts');
      }

      /** @test */
      public function can_select_morning_afternoon_and_night()
      {
          Livewire::test(SchoolWizard::class)
              ->set('step', 3)
              ->set('selectedShifts', [
                  SchoolShift::SHIFT_MORNING,
                  SchoolShift::SHIFT_AFTERNOON,
                  SchoolShift::SHIFT_NIGHT,
              ])
              ->call('validateStep', 3)
              ->assertHasNoErrors('selectedShifts');
      }
  }
  ```

- [ ] **Crear test `tests/Feature/Listeners/SetupAcademicStructureTest.php`:**
  ```php
  namespace Tests\Feature\Listeners;

  use Tests\TestCase;
  use App\Events\Tenant\SchoolConfigured;
  use App\Listeners\Tenant\SetupAcademicStructure;
  use App\Models\Tenant\School;
  use App\Models\Tenant\SchoolSection;
  use App\Models\Tenant\SchoolShift;

  class SetupAcademicStructureTest extends TestCase
  {
      /** @test */
      public function creates_sections_for_each_shift()
      {
          $school = School::factory()->create();
          
          $event = new SchoolConfigured(
              $school,
              [
                  'level_ids' => [1], // Nivel Primario
                  'shift_ids' => [
                      SchoolShift::SHIFT_MORNING,
                      SchoolShift::SHIFT_AFTERNOON,
                  ],
                  'section_labels' => ['A', 'B'],
                  'technical_title_ids' => [],
              ]
          );

          $listener = new SetupAcademicStructure();
          $listener->handle($event);

          // Verificar que se crearon secciones para ambas tandas
          $this->assertEquals(
              2, // 2 tandas
              SchoolSection::where('school_id', $school->id)
                  ->distinct('school_shift_id')
                  ->count('school_shift_id')
          );

          // Verificar total de secciones: 6 grados × 2 tandas × 2 paralelos = 24
          $this->assertEquals(
              24,
              SchoolSection::where('school_id', $school->id)->count()
          );
      }
  }
  ```

--- -->

## Notas de Implementación

**Orden de Ejecución:**
1. Ejecutar migración para agregar `school_shift_id` a `school_sections`
2. Actualizar modelos con nuevas relaciones
3. Actualizar `BaseSchoolWizard` con lógica de validación
4. Actualizar vista del paso 3 con Alpine.js
5. Actualizar listeners del wizard
6. Actualizar componente `SchoolShow` con nueva lógica de agrupación
7. Actualizar vista `_courses.blade.php` con tabs
8. Ejecutar tests para validar comportamiento

**Consideraciones:**
- Las secciones existentes necesitarán una migración de datos manual para asignarles un `school_shift_id`
- Se recomienda crear un comando `php artisan orvian:assign-default-shifts` para escuelas ya configuradas
- La validación de exclusividad de tandas es fundamental para mantener integridad de datos
- Los tabs por tanda mejoran significativamente la scannability en centros con múltiples jornadas

**Impacto en Features Existentes:**
- El dashboard de asistencia deberá filtrar por tanda al abrir sesiones
- Los reportes académicos podrán segmentarse por tanda
- Las asignaciones de maestros se vincularán a secciones específicas de una tanda

---

# Fases 3, 4 y 5 — Refactorizadas
# Sistema Dual de Asistencia con Constantes, Excusas Retroactivas e Integración UX

---

## CAMBIO TRANSVERSAL — Refactorización de Enums a Constantes PHP

> Aplica a todas las migraciones y modelos de las Fases 3, 4 y 5.
> Todos los `$table->enum(...)` se reemplazan por `$table->string(...)`.
> Los modelos definen sus estados como constantes de clase con Accessor de label y Scopes.

---

## Fase 3 — Sistema de Asistencia de Plantel (La Puerta)
**Rama:** `feature/plantel-attendance`

### 3.1 — Migración: Apertura Manual del Día

- [x] **Migración `create_daily_attendance_sessions_table`:**
  ```php
  Schema::create('daily_attendance_sessions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('school_id')->constrained()->cascadeOnDelete();
      $table->foreignId('school_shift_id')->constrained('school_shifts');
      $table->date('date');

      $table->timestamp('opened_at');
      $table->timestamp('closed_at')->nullable();
      $table->foreignId('opened_by')->constrained('users');
      $table->foreignId('closed_by')->nullable()->constrained('users');

      $table->integer('total_expected')->default(0);
      $table->integer('total_registered')->default(0);
      $table->integer('total_present')->default(0);
      $table->integer('total_late')->default(0);
      $table->integer('total_absent')->default(0);
      $table->integer('total_excused')->default(0);

      $table->json('metadata')->nullable();
      $table->timestamps();

      $table->unique(['school_id', 'date', 'school_shift_id'], 'daily_session_unique');
      $table->index(['school_id', 'date']);
  });
  ```

- [x] **Modelo `App\Models\Tenant\DailyAttendanceSession`:**
  ```php
  namespace App\Models\Tenant;

  class DailyAttendanceSession extends Model
  {
      use BelongsToSchool;

      protected $fillable = [
          'school_id', 'school_shift_id', 'date', 'opened_at', 'closed_at',
          'opened_by', 'closed_by', 'total_expected', 'total_registered',
          'total_present', 'total_late', 'total_absent', 'total_excused', 'metadata',
      ];

      protected $casts = [
          'date' => 'date',
          'opened_at' => 'datetime',
          'closed_at' => 'datetime',
          'metadata' => 'array',
      ];

      // Relaciones
      public function school()
      {
          return $this->belongsTo(School::class);
      }

      public function shift()
      {
          return $this->belongsTo(SchoolShift::class, 'school_shift_id');
      }

      public function openedBy()
      {
          return $this->belongsTo(User::class, 'opened_by');
      }

      public function closedBy()
      {
          return $this->belongsTo(User::class, 'closed_by');
      }

      // Helper methods
      public function isOpen(): bool
      {
          return is_null($this->closed_at);
      }

      public function incrementRegistered(): void
      {
          $this->increment('total_registered');
      }

      public function updateStats(array $stats): void
      {
          $this->update($stats);
      }

      // Scopes
      public function scopeForDate($query, Carbon $date)
      {
          return $query->whereDate('date', $date);
      }

      public function scopeActive($query)
      {
          return $query->whereNull('closed_at');
      }
  }
  ```
---

### 3.2 — Migración: Registros de Asistencia de Plantel

- [x] **Migración `create_plantel_attendance_records_table`:**
  ```php
  Schema::create('plantel_attendance_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('school_id')->constrained()->cascadeOnDelete();
      $table->foreignId('student_id')->constrained()->cascadeOnDelete();
      $table->foreignId('daily_attendance_session_id')
            ->constrained('daily_attendance_sessions')
            ->cascadeOnDelete();
      $table->foreignId('school_shift_id')->constrained('school_shifts');
      $table->date('date');
      $table->time('time');

      // ← CAMBIO: string en lugar de enum
      $table->string('status', 20)->default('present');
      // ← CAMBIO: string en lugar de enum
      $table->string('method', 20)->default('manual');

      $table->foreignId('registered_by')->nullable()->constrained('users');

      $table->decimal('temperature', 4, 2)->nullable();
      $table->text('notes')->nullable();
      $table->json('metadata')->nullable();

      $table->timestamp('verified_at')->nullable();
      $table->foreignId('verified_by')->nullable()->constrained('users');

      $table->timestamps();

      $table->unique(['student_id', 'date', 'school_shift_id'], 'plantel_attendance_unique');
      $table->index(['school_id', 'date']);
      $table->index(['daily_attendance_session_id']);
      $table->index(['status', 'date']);
  });
  ```

- [x] **Modelo `App\Models\Tenant\PlantelAttendanceRecord`:**
  ```php
  namespace App\Models\Tenant;

  use App\Scopes\SchoolScope;
  use App\Traits\BelongsToSchool;
  use Carbon\Carbon;
  use Illuminate\Database\Eloquent\Casts\Attribute;
  use Illuminate\Database\Eloquent\Model;

  class PlantelAttendanceRecord extends Model
  {
      use BelongsToSchool;

      // ── Constantes de Estado ──────────────────────────────────────
      public const STATUS_PRESENT = 'present';
      public const STATUS_LATE    = 'late';
      public const STATUS_ABSENT  = 'absent';
      public const STATUS_EXCUSED = 'excused';

      // ── Constantes de Método ──────────────────────────────────────
      public const METHOD_MANUAL  = 'manual';
      public const METHOD_QR      = 'qr';
      public const METHOD_FACIAL  = 'facial';

      // Labels en español para UI
      public const STATUS_LABELS = [
          self::STATUS_PRESENT => 'Presente',
          self::STATUS_LATE    => 'Tardanza',
          self::STATUS_ABSENT  => 'Ausente',
          self::STATUS_EXCUSED => 'Justificado',
      ];

      public const METHOD_LABELS = [
          self::METHOD_MANUAL  => 'Manual',
          self::METHOD_QR      => 'Código QR',
          self::METHOD_FACIAL  => 'Reconocimiento Facial',
      ];

      protected $fillable = [
          'school_id', 'student_id', 'daily_attendance_session_id',
          'school_shift_id', 'date', 'time', 'status', 'method',
          'registered_by', 'temperature', 'notes', 'metadata',
          'verified_at', 'verified_by',
      ];

      protected $casts = [
          'date'        => 'date',
          'temperature' => 'decimal:2',
          'metadata'    => 'array',
          'verified_at' => 'datetime',
      ];

      protected static function booted(): void
      {
          static::addGlobalScope(new SchoolScope);
      }

      // ── Relaciones ────────────────────────────────────────────────

      public function student()
      {
          return $this->belongsTo(Student::class);
      }

      public function session()
      {
          return $this->belongsTo(DailyAttendanceSession::class, 'daily_attendance_session_id');
      }

      public function shift()
      {
          return $this->belongsTo(SchoolShift::class, 'school_shift_id');
      }

      public function registeredBy()
      {
          return $this->belongsTo(User::class, 'registered_by');
      }

      public function verifiedBy()
      {
          return $this->belongsTo(User::class, 'verified_by');
      }

      // ── Accessors ─────────────────────────────────────────────────

      /**
       * Label en español del estado actual.
       * Uso en Blade: {{ $record->status_label }}
       */
      protected function statusLabel(): Attribute
      {
          return Attribute::make(
              get: fn () => self::STATUS_LABELS[$this->status] ?? $this->status
          );
      }

      protected function methodLabel(): Attribute
      {
          return Attribute::make(
              get: fn () => self::METHOD_LABELS[$this->method] ?? $this->method
          );
      }

      // ── Scopes ────────────────────────────────────────────────────

      public function scopePresent($query)
      {
          return $query->where('status', self::STATUS_PRESENT);
      }

      public function scopeLate($query)
      {
          return $query->where('status', self::STATUS_LATE);
      }

      public function scopeAbsent($query)
      {
          return $query->where('status', self::STATUS_ABSENT);
      }

      public function scopeExcused($query)
      {
          return $query->where('status', self::STATUS_EXCUSED);
      }

      public function scopeByStatus($query, string $status)
      {
          return $query->where('status', $status);
      }

      public function scopeByMethod($query, string $method)
      {
          return $query->where('method', $method);
      }

      public function scopeForDate($query, Carbon $date)
      {
          return $query->whereDate('date', $date);
      }

      public function scopePending($query)
      {
          return $query->whereNull('verified_at');
      }

      public function scopeVerified($query)
      {
          return $query->whereNotNull('verified_at');
      }

      public function scopeWithIndexRelations($query)
      {
          return $query->with([
              'student:id,first_name,last_name,photo_path',
              'shift:id,name',
              'registeredBy:id,name',
          ]);
      }

      // ── Helpers ───────────────────────────────────────────────────

      public function isPresent(): bool
      {
          return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE]);
      }

      public function isAbsent(): bool
      {
          return $this->status === self::STATUS_ABSENT;
      }

      public function isExcused(): bool
      {
          return $this->status === self::STATUS_EXCUSED;
      }
  }
  ```

---

### 3.3 — Servicio de Asistencia de Plantel (Refactorizado)

- [x] **Crear `app/Services/Attendance/PlantelAttendanceService.php`:**
  ```php
  namespace App\Services\Attendance;

  use App\Models\Tenant\DailyAttendanceSession;
  use App\Models\Tenant\PlantelAttendanceRecord;
  use App\Models\Tenant\Student;
  use App\Models\Tenant\SchoolShift;
  use Carbon\Carbon;
  use Illuminate\Support\Facades\Log;

  class PlantelAttendanceService
  {
      public function __construct(
          protected ExcuseService $excuseService
      ) {}

      // ── Gestión de Sesión ─────────────────────────────────────────

      public function openDailySession(int $schoolId, int $shiftId, Carbon $date): DailyAttendanceSession
      {
          $existing = DailyAttendanceSession::where('school_id', $schoolId)
              ->where('date', $date)
              ->where('school_shift_id', $shiftId)
              ->first();

          if ($existing) {
              throw new \Exception('Ya existe una sesión abierta para esta fecha y tanda.');
          }

          $totalExpected = Student::active()
              ->where('school_id', $schoolId)
              ->count();

          return DailyAttendanceSession::create([
              'school_id'       => $schoolId,
              'school_shift_id' => $shiftId,
              'date'            => $date,
              'opened_at'       => now(),
              'opened_by'       => auth()->id(),
              'total_expected'  => $totalExpected,
          ]);
      }

      public function closeDailySession(DailyAttendanceSession $session): void
      {
          if (! $session->isOpen()) {
              throw new \Exception('Esta sesión ya está cerrada.');
          }

          $records = PlantelAttendanceRecord::where('daily_attendance_session_id', $session->id)->get();

          $session->update([
              'closed_at'        => now(),
              'closed_by'        => auth()->id(),
              'total_registered' => $records->count(),
              'total_present'    => $records->where('status', PlantelAttendanceRecord::STATUS_PRESENT)->count(),
              'total_late'       => $records->where('status', PlantelAttendanceRecord::STATUS_LATE)->count(),
              'total_absent'     => $records->where('status', PlantelAttendanceRecord::STATUS_ABSENT)->count(),
              'total_excused'    => $records->where('status', PlantelAttendanceRecord::STATUS_EXCUSED)->count(),
          ]);
      }

      // ── Registro Individual ───────────────────────────────────────

      /**
       * Registrar entrada de un estudiante al plantel.
       *
       * MODIFICACIÓN: Si el estudiante tiene una excusa tipo 'license' o médica
       * activa al momento de registrar entrada, se permite el registro como
       * STATUS_PRESENT pero se agrega una alerta en metadata para coordinación.
       */
      public function recordAttendance(array $data): PlantelAttendanceRecord
      {
          $session = DailyAttendanceSession::where('school_id', $data['school_id'])
              ->where('date', $data['date'])
              ->where('school_shift_id', $data['school_shift_id'])
              ->active()
              ->first();

          if (! $session) {
              throw new \Exception(
                  'No hay sesión de asistencia abierta para esta fecha. Un administrador debe abrirla primero.'
              );
          }

          $existing = PlantelAttendanceRecord::where('student_id', $data['student_id'])
              ->where('date', $data['date'])
              ->where('school_shift_id', $data['school_shift_id'])
              ->first();

          if ($existing) {
              throw new \Exception('Este estudiante ya tiene registro de asistencia para hoy.');
          }

          $status = $data['status'] ?? $this->determineStatus(
              $data['time'],
              $data['school_shift_id']
          );

          // ── Alerta de licencia activa ─────────────────────────────
          // Si el estudiante tiene una licencia o excusa médica aprobada
          // para hoy pero se presentó físicamente, se registra la entrada
          // normalmente pero se agrega un flag en metadata para coordinación.
          $metadata = $data['metadata'] ?? [];
          $activeLicense = $this->excuseService->getActiveLicenseForStudent(
              $data['student_id'],
              Carbon::parse($data['date'])
          );

          if ($activeLicense) {
              $metadata['license_alert'] = true;
              $metadata['license_id']    = $activeLicense->id;
              $metadata['license_type']  = $activeLicense->type;
              $metadata['alert_message'] = 'Estudiante con licencia activa ha ingresado al plantel.';

              Log::info('Estudiante con licencia activa registró entrada', [
                  'student_id'  => $data['student_id'],
                  'excuse_id'   => $activeLicense->id,
                  'excuse_type' => $activeLicense->type,
                  'date'        => $data['date'],
              ]);
          }

          $record = PlantelAttendanceRecord::create([
              ...$data,
              'daily_attendance_session_id' => $session->id,
              'status'                      => $status,
              'metadata'                    => $metadata,
          ]);

          $session->incrementRegistered();

          return $record;
      }

      // ── Marcado Masivo de Ausencias ───────────────────────────────

      /**
       * Marcar como ausentes a todos los estudiantes sin registro al final del día.
       *
       * MODIFICACIÓN: Antes de crear el registro de ausencia, se verifica si el
       * estudiante tiene una excusa aprobada para la fecha de la sesión.
       * Si existe → STATUS_EXCUSED con nota automática.
       * Si no existe → STATUS_ABSENT normal.
       */
      public function markAbsences(DailyAttendanceSession $session): int
      {
          $studentsWithRecord = PlantelAttendanceRecord::where('daily_attendance_session_id', $session->id)
              ->pluck('student_id');

          $absentStudents = Student::active()
              ->where('school_id', $session->school_id)
              ->whereNotIn('id', $studentsWithRecord)
              ->get();

          $sessionDate = Carbon::parse($session->date);
          $marked = 0;

          foreach ($absentStudents as $student) {
              // Verificar si tiene excusa aprobada para este día
              $hasApprovedExcuse = $this->excuseService->hasApprovedExcuseForDate(
                  $student->id,
                  $sessionDate
              );

              PlantelAttendanceRecord::create([
                  'school_id'                   => $session->school_id,
                  'student_id'                  => $student->id,
                  'daily_attendance_session_id' => $session->id,
                  'school_shift_id'             => $session->school_shift_id,
                  'date'                        => $session->date,
                  'time'                        => now()->format('H:i:s'),
                  'status'                      => $hasApprovedExcuse
                                                      ? PlantelAttendanceRecord::STATUS_EXCUSED
                                                      : PlantelAttendanceRecord::STATUS_ABSENT,
                  'method'                      => PlantelAttendanceRecord::METHOD_MANUAL,
                  'registered_by'               => auth()->id(),
                  'notes'                       => $hasApprovedExcuse
                                                      ? 'Excusa aplicada automáticamente.'
                                                      : null,
              ]);

              $marked++;
          }

          return $marked;
      }

      // ── Helpers ───────────────────────────────────────────────────

      protected function determineStatus(string $time, int $shiftId): string
      {
          $shift = SchoolShift::find($shiftId);

          if (! $shift || ! $shift->start_time) {
              return PlantelAttendanceRecord::STATUS_PRESENT;
          }

          $arrivalTime   = Carbon::parse($time);
          $shiftStart    = Carbon::parse($shift->start_time);
          $lateThreshold = $shiftStart->copy()->addMinutes(15);

          return $arrivalTime->lte($lateThreshold)
              ? PlantelAttendanceRecord::STATUS_PRESENT
              : PlantelAttendanceRecord::STATUS_LATE;
      }

      public function isStudentPresentInPlantel(int $studentId, Carbon $date, int $shiftId): bool
      {
          $record = PlantelAttendanceRecord::where('student_id', $studentId)
              ->where('date', $date)
              ->where('school_shift_id', $shiftId)
              ->first();

          return $record && $record->isPresent();
      }
  }
  ```

---

### 3.4 — Componente Livewire: Gestión de Sesión del Día

- [x] **Crear `app/Livewire/App/Attendance/DailySessionManager.php`:**
  ```php
  namespace App\Livewire\App\Attendance;

  use App\Models\Tenant\DailyAttendanceSession;
  use App\Models\Tenant\SchoolShift;
  use App\Services\Attendance\PlantelAttendanceService;
  use Carbon\Carbon;
  use Livewire\Component;

  class DailySessionManager extends Component
  {
      public ?DailyAttendanceSession $session = null;
      public int $selectedShiftId;
      public string $sessionDate;
      public bool $showConfirmClose = false;

      public function mount(): void
      {
          $this->sessionDate    = today()->toDateString();
          $this->selectedShiftId = SchoolShift::first()?->id ?? 0;
          $this->loadActiveSession();
      }

      public function loadActiveSession(): void
      {
          $this->session = DailyAttendanceSession::where('school_id', auth()->user()->school_id)
              ->where('date', $this->sessionDate)
              ->where('school_shift_id', $this->selectedShiftId)
              ->active()
              ->first();
      }

      public function openSession(PlantelAttendanceService $service): void
      {
          $this->authorize('attendance_plantel.open_session');

          try {
              $this->session = $service->openDailySession(
                  auth()->user()->school_id,
                  $this->selectedShiftId,
                  Carbon::parse($this->sessionDate)
              );

              $this->dispatch('session-opened');
              $this->dispatch('toast', type: 'success', message: 'Sesión abierta correctamente.');
          } catch (\Exception $e) {
              $this->dispatch('toast', type: 'error', message: $e->getMessage());
          }
      }

      public function closeSession(PlantelAttendanceService $service): void
      {
          $this->authorize('attendance_plantel.close_session');

          try {
              $service->closeDailySession($this->session);
              $this->showConfirmClose = false;
              $this->session          = null;

              $this->dispatch('session-closed');
              $this->dispatch('toast', type: 'success', message: 'Sesión cerrada. Estadísticas calculadas.');
          } catch (\Exception $e) {
              $this->dispatch('toast', type: 'error', message: $e->getMessage());
          }
      }

      public function markAbsences(PlantelAttendanceService $service): void
      {
          $this->authorize('attendance_plantel.record');

          if (! $this->session) {
              $this->dispatch('toast', type: 'error', message: 'No hay sesión activa.');
              return;
          }

          $count = $service->markAbsences($this->session);
          $this->loadActiveSession();
          $this->dispatch('toast', type: 'info', message: "{$count} estudiantes marcados como ausentes/justificados.");
      }

      public function render()
      {
          return view('livewire.app.attendance.daily-session-manager', [
              'shifts' => SchoolShift::where('school_id', auth()->user()->school_id)->get(),
          ]);
      }
  }
  ```

---


## Fase 4 — Sistema de Asistencia de Aula (El Registro Anecdótico)
**Rama:** `feature/classroom-attendance`

### 4.1 — Migración: Registros de Asistencia de Aula

- [x] **Migración `create_classroom_attendance_records_table`:**
  ```php
  Schema::create('classroom_attendance_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('school_id')->constrained()->cascadeOnDelete();
      $table->foreignId('student_id')->constrained()->cascadeOnDelete();
      $table->foreignId('teacher_subject_section_id')
            ->constrained('teacher_subject_sections')
            ->cascadeOnDelete();
      $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
      $table->date('date');
      $table->time('class_time');

      // ← CAMBIO: string en lugar de enum
      $table->string('status', 20)->default('present');

      $table->text('teacher_notes')->nullable();
      $table->json('metadata')->nullable();
      $table->timestamps();

      $table->unique(
          ['student_id', 'teacher_subject_section_id', 'date'],
          'classroom_attendance_unique'
      );
      $table->index(['school_id', 'date']);
      $table->index(['teacher_id', 'date']);
      $table->index(['status', 'date']);
  });
  ```

- [x] **Modelo `App\Models\Tenant\ClassroomAttendanceRecord`:**
  ```php
  namespace App\Models\Tenant;

  use App\Models\Tenant\Academic\TeacherSubjectSection;
  use App\Scopes\SchoolScope;
  use App\Traits\BelongsToSchool;
  use Carbon\Carbon;
  use Illuminate\Database\Eloquent\Casts\Attribute;
  use Illuminate\Database\Eloquent\Model;

  class ClassroomAttendanceRecord extends Model
  {
      use BelongsToSchool;

      // ── Constantes de Estado ──────────────────────────────────────
      public const STATUS_PRESENT = 'present';
      public const STATUS_ABSENT  = 'absent';
      public const STATUS_LATE    = 'late';
      public const STATUS_EXCUSED = 'excused';

      public const STATUS_LABELS = [
          self::STATUS_PRESENT => 'Presente',
          self::STATUS_ABSENT  => 'Ausente',
          self::STATUS_LATE    => 'Tardanza',
          self::STATUS_EXCUSED => 'Justificado',
      ];

      protected $fillable = [
          'school_id', 'student_id', 'teacher_subject_section_id',
          'teacher_id', 'date', 'class_time', 'status', 'teacher_notes', 'metadata',
      ];

      protected $casts = [
          'date'       => 'date',
          'metadata'   => 'array',
      ];

      protected static function booted(): void
      {
          static::addGlobalScope(new SchoolScope);
      }

      // ── Relaciones ────────────────────────────────────────────────

      public function student()
      {
          return $this->belongsTo(Student::class);
      }

      public function teacher()
      {
          return $this->belongsTo(Teacher::class);
      }

      public function assignment()
      {
          return $this->belongsTo(TeacherSubjectSection::class, 'teacher_subject_section_id');
      }

      // ── Accessors ─────────────────────────────────────────────────

      protected function statusLabel(): Attribute
      {
          return Attribute::make(
              get: fn () => self::STATUS_LABELS[$this->status] ?? $this->status
          );
      }

      // ── Scopes ────────────────────────────────────────────────────

      public function scopePresent($query)
      {
          return $query->where('status', self::STATUS_PRESENT);
      }

      public function scopeAbsent($query)
      {
          return $query->where('status', self::STATUS_ABSENT);
      }

      public function scopeLate($query)
      {
          return $query->where('status', self::STATUS_LATE);
      }

      public function scopeExcused($query)
      {
          return $query->where('status', self::STATUS_EXCUSED);
      }

      public function scopeByStatus($query, string $status)
      {
          return $query->where('status', $status);
      }

      public function scopeForDate($query, Carbon $date)
      {
          return $query->whereDate('date', $date);
      }

      public function scopeWithIndexRelations($query)
      {
          return $query->with([
              'student:id,first_name,last_name,photo_path',
              'teacher:id,first_name,last_name',
              'assignment.subject:id,name,code,color',
          ]);
      }

      // ── Helpers ───────────────────────────────────────────────────

      public function isPresent(): bool
      {
          return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE]);
      }

      public function isExcused(): bool
      {
          return $this->status === self::STATUS_EXCUSED;
      }
  }
  ```

---

### 4.2 — Servicio de Asistencia de Aula (Completo + Integración ExcuseService)

- [x] **Crear `app/Services/Attendance/ClassroomAttendanceService.php`:**
  ```php
  namespace App\Services\Attendance;

  use App\Models\Tenant\ClassroomAttendanceRecord;
  use App\Models\Tenant\PlantelAttendanceRecord;
  use App\Models\Tenant\Student;
  use App\Models\Tenant\SchoolShift;
  use App\Models\Tenant\Academic\TeacherSubjectSection;
  use Carbon\Carbon;
  use Illuminate\Support\Collection;
  use Illuminate\Support\Facades\Log;

  class ClassroomAttendanceService
  {
      public function __construct(
          protected PlantelAttendanceService $plantelService,
          protected ExcuseService $excuseService
      ) {}

      // ── Registro Individual ───────────────────────────────────────

      /**
       * Registrar asistencia de un estudiante en una clase.
       * Aplica validación cruzada estricta antes de guardar.
       */
      public function recordClassAttendance(array $data): ClassroomAttendanceRecord
      {
          $this->validateCrossAttendance(
              $data['student_id'],
              Carbon::parse($data['date']),
              $data['school_id']
          );

          $existing = ClassroomAttendanceRecord::where('student_id', $data['student_id'])
              ->where('teacher_subject_section_id', $data['teacher_subject_section_id'])
              ->whereDate('date', $data['date'])
              ->first();

          if ($existing) {
              throw new \Exception('Ya existe registro de asistencia para este estudiante en esta clase.');
          }

          return ClassroomAttendanceRecord::create($data);
      }

      // ── Pase de Lista Completo ────────────────────────────────────

      /**
       * Procesar el pase de lista completo de una clase.
       *
       * INTEGRACIÓN CON EXCUSESERVICE: Si el status enviado para un estudiante
       * es 'present' pero el ExcuseService indica que está excusado para la fecha,
       * el status se fuerza a STATUS_EXCUSED para mantener consistencia.
       *
       * @param  array  $studentStatuses  [ student_id => status, ... ]
       */
      public function takeClassAttendance(
          int $assignmentId,
          Carbon $date,
          array $studentStatuses
      ): array {
          $assignment = TeacherSubjectSection::findOrFail($assignmentId);

          // Obtener estudiantes con excusa aprobada para este día (una sola consulta)
          $excusedStudentIds = $this->excuseService->getCoveredStudentsForDate($date);

          $recorded = 0;
          $skipped  = 0;
          $errors   = [];

          foreach ($studentStatuses as $studentId => $status) {
              try {
                  // Si el maestro envía 'present' pero el estudiante tiene excusa,
                  // se fuerza a 'excused' para mantener la lógica del sistema.
                  if (
                      $status === ClassroomAttendanceRecord::STATUS_PRESENT
                      && $excusedStudentIds->contains($studentId)
                  ) {
                      $status = ClassroomAttendanceRecord::STATUS_EXCUSED;
                  }

                  $this->recordClassAttendance([
                      'school_id'                   => $assignment->section->school_id,
                      'student_id'                  => $studentId,
                      'teacher_subject_section_id'  => $assignmentId,
                      'teacher_id'                  => $assignment->teacher_id,
                      'date'                        => $date,
                      'class_time'                  => now()->format('H:i:s'),
                      'status'                      => $status,
                  ]);

                  $recorded++;
              } catch (\Exception $e) {
                  $skipped++;
                  $errors[$studentId] = $e->getMessage();

                  Log::warning('Error al registrar asistencia de aula', [
                      'student_id'    => $studentId,
                      'assignment_id' => $assignmentId,
                      'error'         => $e->getMessage(),
                  ]);
              }
          }

          return [
              'recorded' => $recorded,
              'skipped'  => $skipped,
              'errors'   => $errors,
          ];
      }

      // ── Actualización de Registro Existente ───────────────────────

      public function updateRecord(ClassroomAttendanceRecord $record, string $status, ?string $notes = null): void
      {
          $record->update([
              'status'        => $status,
              'teacher_notes' => $notes ?? $record->teacher_notes,
          ]);
      }

      // ── Detección de Pasilleo ─────────────────────────────────────

      /**
       * Detectar estudiantes presentes en plantel pero ausentes en aula (pasilleo).
       */
      public function detectDiscrepancies(Carbon $date, int $schoolId): Collection
      {
          $discrepancies = collect();

          $presentInPlantel = PlantelAttendanceRecord::where('school_id', $schoolId)
              ->whereDate('date', $date)
              ->whereIn('status', [
                  PlantelAttendanceRecord::STATUS_PRESENT,
                  PlantelAttendanceRecord::STATUS_LATE,
              ])
              ->with('student')
              ->get();

          foreach ($presentInPlantel as $plantelRecord) {
              $classesAbsent = ClassroomAttendanceRecord::where('student_id', $plantelRecord->student_id)
                  ->whereDate('date', $date)
                  ->where('status', ClassroomAttendanceRecord::STATUS_ABSENT)
                  ->count();

              if ($classesAbsent > 0) {
                  $discrepancies->push([
                      'student'        => $plantelRecord->student,
                      'plantel_status' => $plantelRecord->status_label,
                      'classes_absent' => $classesAbsent,
                      'alert_type'     => 'pasilleo',
                  ]);
              }
          }

          return $discrepancies;
      }

      // ── Validación Cruzada ────────────────────────────────────────

      /**
       * Regla de negocio estricta: no se puede registrar presencia en aula
       * si el estudiante está marcado como ausente en plantel.
       */
      protected function validateCrossAttendance(int $studentId, Carbon $date, int $schoolId): void
      {
          $student = Student::with('section.shift')->findOrFail($studentId);
          $shiftId = $student->section?->shift?->id ?? SchoolShift::where('school_id', $schoolId)->first()?->id;

          if (! $shiftId) {
              return; // Sin tanda configurada, se permite el registro
          }

          $plantelRecord = PlantelAttendanceRecord::where('student_id', $studentId)
              ->whereDate('date', $date)
              ->where('school_shift_id', $shiftId)
              ->first();

          if (! $plantelRecord) {
              // Si no tiene registro de plantel pero tiene excusa aprobada para hoy,
              // se permite el registro de aula (el sistema acepta que no pasó por la puerta
              // pero el maestro quiere dejar constancia de la excusa).
              $hasExcuse = $this->excuseService->hasApprovedExcuseForDate($studentId, $date);
              if (! $hasExcuse) {
                  throw new \Exception(
                      'El estudiante no ha registrado entrada al plantel hoy. '.
                      'Debe pasar por la portería primero o tener una excusa aprobada.'
                  );
              }
              return;
          }

          if (in_array($plantelRecord->status, [
              PlantelAttendanceRecord::STATUS_ABSENT,
              PlantelAttendanceRecord::STATUS_EXCUSED,
          ])) {
              throw new \Exception(
                  "El estudiante está marcado como '{$plantelRecord->status_label}' en el plantel. ".
                  'No puede registrarse como presente en aula.'
              );
          }
      }
  }
  ```

---

### 4.3 — Componente Livewire: Pase de Lista del Maestro

- [x] **Crear `app/Livewire/App/Attendance/ClassroomAttendanceLive.php`:**
  ```php
  namespace App\Livewire\App\Attendance;

  use App\Models\Tenant\Academic\TeacherSubjectSection;
  use App\Models\Tenant\Student;
  use App\Services\Attendance\ClassroomAttendanceService;
  use App\Services\Attendance\ExcuseService;
  use Carbon\Carbon;
  use Livewire\Component;

  class ClassroomAttendanceLive extends Component
  {
      public int $assignmentId;
      public string $attendanceDate;

      // Pase de lista: [student_id => status]
      public array $statuses = [];

      // IDs de estudiantes con excusa aprobada para hoy
      // (determinados al montar el componente)
      public array $excusedStudentIds = [];

      // Control de UI
      public bool $submitted = false;
      public array $submitResult = [];

      public function mount(int $assignmentId): void
      {
          $this->assignmentId    = $assignmentId;
          $this->attendanceDate  = today()->toDateString();
      }

      /**
       * Al cargar la lista de estudiantes se consulta ExcuseService para
       * identificar quiénes tienen excusa activa.
       * En la vista, esos estudiantes tendrán:
       *   - Status pre-seleccionado como STATUS_EXCUSED
       *   - Botón "Presente" deshabilitado (disabled en Alpine/Blade)
       */
      public function loadStudents(ExcuseService $excuseService): void
      {
          $date = Carbon::parse($this->attendanceDate);

          // Una sola consulta para todos los excusados de la fecha
          $this->excusedStudentIds = $excuseService
              ->getCoveredStudentsForDate($date)
              ->toArray();

          $assignment = TeacherSubjectSection::with('section.students.active')->find($this->assignmentId);

          if (! $assignment) return;

          $this->statuses = [];

          foreach ($assignment->section->students as $student) {
              // Pre-seleccionar excusados: botón Presente deshabilitado en la vista
              $this->statuses[$student->id] = in_array($student->id, $this->excusedStudentIds)
                  ? \App\Models\Tenant\ClassroomAttendanceRecord::STATUS_EXCUSED
                  : \App\Models\Tenant\ClassroomAttendanceRecord::STATUS_PRESENT;
          }
      }

      public function setStatus(int $studentId, string $status): void
      {
          // Prevenir cambio si el estudiante está excusado
          if (in_array($studentId, $this->excusedStudentIds)) {
              return;
          }

          $this->statuses[$studentId] = $status;
      }

      public function submitAttendance(ClassroomAttendanceService $service): void
      {
          $this->authorize('attendance_classroom.record');

          $result = $service->takeClassAttendance(
              $this->assignmentId,
              Carbon::parse($this->attendanceDate),
              $this->statuses
          );

          $this->submitted   = true;
          $this->submitResult = $result;

          $this->dispatch('toast',
              type: $result['skipped'] === 0 ? 'success' : 'warning',
              message: "Pase de lista guardado. {$result['recorded']} registrados, {$result['skipped']} omitidos."
          );
      }

      public function render()
      {
          $assignment = TeacherSubjectSection::with([
              'subject:id,name,code,color',
              'section.students' => fn ($q) => $q->active()->select('id', 'first_name', 'last_name', 'photo_path'),
          ])->find($this->assignmentId);

          return view('livewire.app.attendance.classroom-attendance-live', [
              'assignment' => $assignment,
              'statusOptions' => \App\Models\Tenant\ClassroomAttendanceRecord::STATUS_LABELS,
          ])->layout('layouts.app-module', config('modules.asistencia'));
      }
  }
  ```

- [x] **Vista `resources/views/livewire/app/attendance/classroom-attendance-live.blade.php`:**
  ```blade
  <div>
      <x-app.module-toolbar>
          <x-slot:title>
              Pase de Lista — {{ $assignment?->subject->name }}
              <span class="text-sm font-normal text-slate-500">
                  {{ $assignment?->section->label }} · {{ \Carbon\Carbon::parse($attendanceDate)->isoFormat('D MMM YYYY') }}
              </span>
          </x-slot:title>
      </x-app.module-toolbar>

      @if ($submitted)
          <div class="p-4 bg-green-50 border border-green-200 rounded-lg mb-4 dark:bg-green-900/20 dark:border-green-800">
              <p class="text-green-700 dark:text-green-300 font-medium">
                  ✅ Pase de lista guardado: {{ $submitResult['recorded'] }} registros.
                  @if ($submitResult['skipped'] > 0)
                      <span class="text-amber-600">⚠ {{ $submitResult['skipped'] }} omitidos.</span>
                  @endif
              </p>
          </div>
      @endif

      <div class="space-y-2">
          @foreach ($assignment?->section->students ?? [] as $student)
              @php
                  $isExcused = in_array($student->id, $excusedStudentIds);
                  $current   = $statuses[$student->id] ?? 'present';
              @endphp

              <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700
                          {{ $isExcused ? 'opacity-75 bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-700' : '' }}">

                  {{-- Avatar + Nombre --}}
                  <div class="flex items-center gap-3">
                      <x-ui.student-avatar :student="$student" size="sm" />
                      <div>
                          <p class="font-medium text-slate-800 dark:text-slate-100 text-sm">
                              {{ $student->full_name }}
                          </p>
                          @if ($isExcused)
                              <p class="text-xs text-amber-600 dark:text-amber-400">
                                  🗒 Excusa aprobada para hoy
                              </p>
                          @endif
                      </div>
                  </div>

                  {{-- Botones de estado --}}
                  <div class="flex gap-1">
                      @foreach (\App\Models\Tenant\ClassroomAttendanceRecord::STATUS_LABELS as $value => $label)
                          <button
                              wire:click="setStatus({{ $student->id }}, '{{ $value }}')"
                              @disabled($isExcused && $value === 'present')
                              class="px-2.5 py-1 rounded text-xs font-medium transition-all
                                  {{ $current === $value
                                      ? match($value) {
                                          'present' => 'bg-green-500 text-white',
                                          'absent'  => 'bg-red-500 text-white',
                                          'late'    => 'bg-amber-500 text-white',
                                          'excused' => 'bg-blue-500 text-white',
                                          default   => 'bg-slate-500 text-white'
                                      }
                                      : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}
                                  {{ ($isExcused && $value === 'present') ? 'cursor-not-allowed opacity-40' : '' }}"
                          >
                              {{ $label }}
                          </button>
                      @endforeach
                  </div>
              </div>
          @endforeach
      </div>

      @if (! $submitted)
          <div class="mt-6 flex justify-end">
              <x-ui.button wire:click="submitAttendance" variant="primary">
                  Guardar Pase de Lista
              </x-ui.button>
          </div>
      @endif
  </div>
  ```

- [x] **Ruta en `routes/app/attendance.php`:**
```php
Route::middleware(['can:attendance_classroom.view'])->prefix('attendance')->name('attendance.')->group(function () {
    // Agregamos el parámetro opcional o requerido en la URL
    Route::get('/{assignmentId}', ClassroomAttendanceLive::class)->name('index');
    
});
```

---

## Fase 5 — Gestión de Excusas y Justificaciones
**Rama:** `feature/attendance-excuses`

### 5.1 — Migración: Excusas

- [x] **Migración `create_attendance_excuses_table`:**
  ```php
  Schema::create('attendance_excuses', function (Blueprint $table) {
      $table->id();
      $table->foreignId('school_id')->constrained()->cascadeOnDelete();
      $table->foreignId('student_id')->constrained()->cascadeOnDelete();

      $table->date('date_start');
      $table->date('date_end');

      // ← CAMBIO: string en lugar de enum para tipo de excusa
      $table->string('type', 30)->default('full_absence');

      $table->text('reason');
      $table->string('attachment_path')->nullable();

      // ← CAMBIO: string en lugar de enum para estado
      $table->string('status', 20)->default('pending');

      $table->foreignId('submitted_by')->constrained('users');
      $table->timestamp('submitted_at');
      $table->foreignId('reviewed_by')->nullable()->constrained('users');
      $table->timestamp('reviewed_at')->nullable();
      $table->text('review_notes')->nullable();

      $table->timestamps();

      $table->index(['student_id', 'date_start', 'date_end']);
      $table->index(['school_id', 'status']);
  });
  ```

- [x] **Modelo `App\Models\Tenant\AttendanceExcuse`:**
  ```php
  namespace App\Models\Tenant;

  use App\Scopes\SchoolScope;
  use App\Traits\BelongsToSchool;
  use Carbon\Carbon;
  use Illuminate\Database\Eloquent\Casts\Attribute;
  use Illuminate\Database\Eloquent\Model;

  class AttendanceExcuse extends Model
  {
      use BelongsToSchool;

      // ── Constantes de Estado ──────────────────────────────────────
      public const STATUS_PENDING  = 'pending';
      public const STATUS_APPROVED = 'approved';
      public const STATUS_REJECTED = 'rejected';

      // ── Constantes de Tipo de Excusa ──────────────────────────────
      public const TYPE_FULL_ABSENCE      = 'full_absence';
      public const TYPE_LATE_ARRIVAL      = 'late_arrival';
      public const TYPE_EARLY_DEPARTURE   = 'early_departure';
      public const TYPE_LICENSE           = 'license';
      public const TYPE_MEDICAL           = 'medical';  // Licencia médica extendida

      // Los tipos que implican una licencia activa (el estudiante puede entrar
      // físicamente aunque el sistema lo tenga justificado).
      public const LICENSE_TYPES = [
          self::TYPE_LICENSE,
          self::TYPE_MEDICAL,
      ];

      public const STATUS_LABELS = [
          self::STATUS_PENDING  => 'Pendiente',
          self::STATUS_APPROVED => 'Aprobada',
          self::STATUS_REJECTED => 'Rechazada',
      ];

      public const TYPE_LABELS = [
          self::TYPE_FULL_ABSENCE    => 'Ausencia Total',
          self::TYPE_LATE_ARRIVAL    => 'Llegada Tardía',
          self::TYPE_EARLY_DEPARTURE => 'Salida Anticipada',
          self::TYPE_LICENSE         => 'Licencia',
          self::TYPE_MEDICAL         => 'Licencia Médica',
      ];

      protected $fillable = [
          'school_id', 'student_id', 'date_start', 'date_end', 'type',
          'reason', 'attachment_path', 'status', 'submitted_by',
          'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes',
      ];

      protected $casts = [
          'date_start'   => 'date',
          'date_end'     => 'date',
          'submitted_at' => 'datetime',
          'reviewed_at'  => 'datetime',
      ];

      protected static function booted(): void
      {
          static::addGlobalScope(new SchoolScope);
      }

      // ── Relaciones ────────────────────────────────────────────────

      public function student()
      {
          return $this->belongsTo(Student::class);
      }

      public function submittedBy()
      {
          return $this->belongsTo(User::class, 'submitted_by');
      }

      public function reviewedBy()
      {
          return $this->belongsTo(User::class, 'reviewed_by');
      }

      // ── Accessors ─────────────────────────────────────────────────

      protected function statusLabel(): Attribute
      {
          return Attribute::make(
              get: fn () => self::STATUS_LABELS[$this->status] ?? $this->status
          );
      }

      protected function typeLabel(): Attribute
      {
          return Attribute::make(
              get: fn () => self::TYPE_LABELS[$this->type] ?? $this->type
          );
      }

      // ── Scopes ────────────────────────────────────────────────────

      public function scopePending($query)
      {
          return $query->where('status', self::STATUS_PENDING);
      }

      public function scopeApproved($query)
      {
          return $query->where('status', self::STATUS_APPROVED);
      }

      public function scopeRejected($query)
      {
          return $query->where('status', self::STATUS_REJECTED);
      }

      public function scopeForDateRange($query, Carbon $start, Carbon $end)
      {
          return $query->where(function ($q) use ($start, $end) {
              $q->whereBetween('date_start', [$start, $end])
                ->orWhereBetween('date_end', [$start, $end])
                ->orWhere(function ($q2) use ($start, $end) {
                    $q2->where('date_start', '<=', $start)
                       ->where('date_end', '>=', $end);
                });
          });
      }

      public function scopeLicense($query)
      {
          return $query->whereIn('type', self::LICENSE_TYPES);
      }

      // ── Helpers ───────────────────────────────────────────────────

      public function isApproved(): bool
      {
          return $this->status === self::STATUS_APPROVED;
      }

      public function isPending(): bool
      {
          return $this->status === self::STATUS_PENDING;
      }

      public function isLicenseType(): bool
      {
          return in_array($this->type, self::LICENSE_TYPES);
      }

      public function coversDate(Carbon $date): bool
      {
          return $date->between($this->date_start, $this->date_end);
      }
  }
  ```

---

### 5.2 — ExcuseService (Con nuevos métodos de cobertura)

- [x] **Crear `app/Services/Attendance/ExcuseService.php`:**
  ```php
  namespace App\Services\Attendance;

  use App\Models\Tenant\AttendanceExcuse;
  use Carbon\Carbon;
  use Illuminate\Support\Collection;

  class ExcuseService
  {
      // ── CRUD de Excusas ───────────────────────────────────────────

      public function submitExcuse(array $data): AttendanceExcuse
      {
          $data['submitted_at'] = now();
          $data['submitted_by'] = auth()->id();

          return AttendanceExcuse::create($data);
      }

      /**
       * Aprobar una excusa.
       * El Observer `AttendanceExcuseObserver` reacciona a este cambio de estado
       * y aplica la excusa retroactivamente sobre registros existentes.
       */
      public function approveExcuse(AttendanceExcuse $excuse, ?string $notes = null): void
      {
          $excuse->update([
              'status'       => AttendanceExcuse::STATUS_APPROVED,
              'reviewed_by'  => auth()->id(),
              'reviewed_at'  => now(),
              'review_notes' => $notes,
          ]);
      }

      public function rejectExcuse(AttendanceExcuse $excuse, string $notes): void
      {
          $excuse->update([
              'status'       => AttendanceExcuse::STATUS_REJECTED,
              'reviewed_by'  => auth()->id(),
              'reviewed_at'  => now(),
              'review_notes' => $notes,
          ]);
      }

      // ── Consultas de Cobertura ────────────────────────────────────

      /**
       * Verificar si un estudiante tiene una excusa aprobada para una fecha concreta.
       * Usado en markAbsences() para decidir el estado del registro.
       */
      public function hasApprovedExcuseForDate(int $studentId, Carbon $date): bool
      {
          return AttendanceExcuse::where('student_id', $studentId)
              ->approved()
              ->where('date_start', '<=', $date->toDateString())
              ->where('date_end', '>=', $date->toDateString())
              ->exists();
      }

      /**
       * Obtener la excusa activa de tipo licencia para un estudiante en una fecha.
       * Retorna null si no tiene licencia activa.
       *
       * Usado en recordAttendance() para emitir la alerta de "licencia activa ha ingresado".
       */
      public function getActiveLicenseForStudent(int $studentId, Carbon $date): ?AttendanceExcuse
      {
          return AttendanceExcuse::where('student_id', $studentId)
              ->approved()
              ->license()
              ->where('date_start', '<=', $date->toDateString())
              ->where('date_end', '>=', $date->toDateString())
              ->first();
      }

      /**
       * Devuelve una colección de IDs de estudiantes con excusa aprobada para la fecha dada.
       *
       * Diseñado para una sola consulta que abastece a ClassroomAttendanceLive al cargar
       * la lista de estudiantes — evita N+1 al renderizar el pase de lista.
       *
       * @return Collection<int>
       */
      public function getCoveredStudentsForDate(Carbon $date): Collection
      {
          return AttendanceExcuse::approved()
              ->where('date_start', '<=', $date->toDateString())
              ->where('date_end', '>=', $date->toDateString())
              ->pluck('student_id')
              ->unique()
              ->values();
      }

      /**
       * Obtener excusas de un estudiante en un rango de fechas.
       * Útil para el historial en StudentShow.
       */
      public function getStudentExcuses(int $studentId, Carbon $from, Carbon $to): Collection
      {
          return AttendanceExcuse::where('student_id', $studentId)
              ->forDateRange($from, $to)
              ->orderBy('date_start', 'desc')
              ->get();
      }
  }
  ```

---

### 5.3 — AttendanceExcuseObserver (Retroactividad)

- [x] **Crear `app/Observers/Tenant/AttendanceExcuseObserver.php`:**
  ```php
  namespace App\Observers\Tenant;

  use App\Models\Tenant\AttendanceExcuse;
  use App\Models\Tenant\ClassroomAttendanceRecord;
  use App\Models\Tenant\PlantelAttendanceRecord;
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Log;

  class AttendanceExcuseObserver
  {
      /**
       * Cuando una excusa pasa a estado APPROVED, aplicarla retroactivamente
       * sobre todos los registros de asistencia (plantel y aula) que estén
       * dentro del rango de fechas y pertenezcan al estudiante.
       *
       * Registros afectados:
       * - PlantelAttendanceRecord con status = 'absent'
       * - ClassroomAttendanceRecord con status = 'absent'
       *
       * Solo se actualizan registros 'absent' — no se toca 'present', 'late'
       * ni 'excused' ya existentes.
       */
      public function updated(AttendanceExcuse $excuse): void
      {
          // Solo actuar cuando el cambio es hacia STATUS_APPROVED
          if (
              ! $excuse->wasChanged('status')
              || $excuse->status !== AttendanceExcuse::STATUS_APPROVED
          ) {
              return;
          }

          DB::transaction(function () use ($excuse) {
              $this->applyToPlantelRecords($excuse);
              $this->applyToClassroomRecords($excuse);
          });

          Log::info('Excusa aplicada retroactivamente', [
              'excuse_id'   => $excuse->id,
              'student_id'  => $excuse->student_id,
              'date_start'  => $excuse->date_start->toDateString(),
              'date_end'    => $excuse->date_end->toDateString(),
          ]);
      }

      // ── Privados ──────────────────────────────────────────────────

      protected function applyToPlantelRecords(AttendanceExcuse $excuse): void
      {
          $updated = PlantelAttendanceRecord::where('student_id', $excuse->student_id)
              ->where('status', PlantelAttendanceRecord::STATUS_ABSENT)
              ->whereBetween('date', [
                  $excuse->date_start->toDateString(),
                  $excuse->date_end->toDateString(),
              ])
              ->update([
                  'status' => PlantelAttendanceRecord::STATUS_EXCUSED,
                  'notes'  => DB::raw(
                      "CONCAT(COALESCE(notes, ''), ' [Justificado por excusa #".$excuse->id."]')"
                  ),
              ]);

          Log::debug("Plantel: {$updated} registros actualizados a 'excused'", [
              'excuse_id' => $excuse->id,
          ]);
      }

      protected function applyToClassroomRecords(AttendanceExcuse $excuse): void
      {
          $updated = ClassroomAttendanceRecord::where('student_id', $excuse->student_id)
              ->where('status', ClassroomAttendanceRecord::STATUS_ABSENT)
              ->whereBetween('date', [
                  $excuse->date_start->toDateString(),
                  $excuse->date_end->toDateString(),
              ])
              ->update([
                  'status'        => ClassroomAttendanceRecord::STATUS_EXCUSED,
                  'teacher_notes' => DB::raw(
                      "CONCAT(COALESCE(teacher_notes, ''), ' [Justificado por excusa #".$excuse->id."]')"
                  ),
              ]);

          Log::debug("Aula: {$updated} registros actualizados a 'excused'", [
              'excuse_id' => $excuse->id,
          ]);
      }
  }
  ```

- [x] **Registrar el Observer en `AppServiceProvider`:**
  ```php
  use App\Models\Tenant\AttendanceExcuse;
  use App\Observers\Tenant\AttendanceExcuseObserver;

  public function boot(): void
  {
      // Observers existentes...
      Student::observe(StudentObserver::class);
      Teacher::observe(TeacherObserver::class);

      // Nuevo
      AttendanceExcuse::observe(AttendanceExcuseObserver::class);
  }
  ```

---

### 5.4 — Componente Livewire: Gestión de Excusas

- [x] **Crear `app/Livewire/App/Attendance/ExcuseManager.php`:**
  ```php
  namespace App\Livewire\App\Attendance;

  use App\Models\Tenant\AttendanceExcuse;
  use App\Models\Tenant\Student;
  use App\Services\Attendance\ExcuseService;
  use Livewire\Component;
  use Livewire\WithFileUploads;

  class ExcuseManager extends Component
  {
      use WithFileUploads;

      // ── Formulario ────────────────────────────────────────────────
      public int    $studentId    = 0;
      public string $dateStart    = '';
      public string $dateEnd      = '';
      public string $type         = AttendanceExcuse::TYPE_FULL_ABSENCE;
      public string $reason       = '';
      public        $attachment   = null;

      // ── UI ────────────────────────────────────────────────────────
      public bool   $showPanel    = false;
      public ?int   $excuseId     = null; // Para aprobación/rechazo
      public string $reviewNotes  = '';
      public bool   $showReview   = false;
      public string $reviewAction = ''; // 'approve' | 'reject'

      // ── Filtros de listado ────────────────────────────────────────
      public string $filterStatus = '';
      public string $search       = '';

      protected function rules(): array
      {
          return [
              'studentId'  => 'required|integer|exists:students,id',
              'dateStart'  => 'required|date',
              'dateEnd'    => 'required|date|after_or_equal:dateStart',
              'type'       => 'required|string',
              'reason'     => 'required|string|min:10|max:1000',
              'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
          ];
      }

      public function openCreate(): void
      {
          $this->reset(['studentId', 'dateStart', 'dateEnd', 'type', 'reason', 'attachment', 'excuseId']);
          $this->showPanel = true;
      }

      public function save(ExcuseService $service): void
      {
          $this->validate();
          $this->authorize('excuses.submit');

          $data = [
              'school_id'  => auth()->user()->school_id,
              'student_id' => $this->studentId,
              'date_start' => $this->dateStart,
              'date_end'   => $this->dateEnd,
              'type'       => $this->type,
              'reason'     => $this->reason,
          ];

          if ($this->attachment) {
              $data['attachment_path'] = $this->attachment->store('excuses', 'public');
          }

          $service->submitExcuse($data);

          $this->showPanel = false;
          $this->dispatch('toast', type: 'success', message: 'Excusa registrada. Pendiente de revisión.');
      }

      public function openReview(int $excuseId, string $action): void
      {
          $this->authorize('excuses.review');
          $this->excuseId     = $excuseId;
          $this->reviewAction = $action;
          $this->reviewNotes  = '';
          $this->showReview   = true;
      }

      public function confirmReview(ExcuseService $service): void
      {
          $this->authorize('excuses.review');

          $excuse = AttendanceExcuse::findOrFail($this->excuseId);

          if ($this->reviewAction === 'approve') {
              $service->approveExcuse($excuse, $this->reviewNotes ?: null);
              $message = 'Excusa aprobada. Registros de asistencia actualizados automáticamente.';
          } else {
              $this->validate(['reviewNotes' => 'required|string|min:5']);
              $service->rejectExcuse($excuse, $this->reviewNotes);
              $message = 'Excusa rechazada.';
          }

          $this->showReview = false;
          $this->dispatch('toast', type: 'success', message: $message);
      }

      public function render()
      {
          $excuses = AttendanceExcuse::with(['student:id,first_name,last_name', 'submittedBy:id,name'])
              ->where('school_id', auth()->user()->school_id)
              ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
              ->when($this->search, function ($q) {
                  $q->whereHas('student', fn ($s) =>
                      $s->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                  );
              })
              ->orderBy('submitted_at', 'desc')
              ->paginate(20);

          return view('livewire.app.attendance.excuse-manager', [
              'excuses'       => $excuses,
              'students'      => Student::active()->select('id', 'first_name', 'last_name')->get(),
              'typeOptions'   => AttendanceExcuse::TYPE_LABELS,
              'statusOptions' => AttendanceExcuse::STATUS_LABELS,
          ])->layout('layouts.app-module', config('modules.asistencia'));
      }
  }
  ```

---

## Checklist de Completitud — Fases 3, 4 y 5

### Cambio Transversal (Enums → Strings)
- [x] Migración `plantel_attendance_records`: `status` y `method` como `string`
- [x] Migración `classroom_attendance_records`: `status` como `string`
- [x] Migración `attendance_excuses`: `type` y `status` como `string`
- [x] `PlantelAttendanceRecord` con constantes, `STATUS_LABELS`, Accessor `status_label`, Scopes por estado
- [x] `ClassroomAttendanceRecord` con constantes, `STATUS_LABELS`, Accessor `status_label`, Scopes por estado
- [x] `AttendanceExcuse` con constantes de estado y tipo, `LICENSE_TYPES`, Accessors, Scopes
- [x] Todos los servicios usan constantes (`::STATUS_ABSENT`) en lugar de strings literales

### Fase 3 — Plantel
- [x] Migración `daily_attendance_sessions` creada
- [x] Modelo `DailyAttendanceSession` con helpers e índices
- [x] Migración `plantel_attendance_records` creada (con strings)
- [x] Modelo `PlantelAttendanceRecord` con constantes y scopes
- [x] `PlantelAttendanceService` inyecta `ExcuseService` en constructor
- [x] `markAbsences()`: verifica excusa antes de crear registro → crea `STATUS_EXCUSED` si aplica
- [x] `recordAttendance()`: detecta licencia activa → agrega alerta en `metadata['license_alert']`
- [x] `DailySessionManager` Livewire creado con `openSession`, `closeSession`, `markAbsences`


### Fase 4 — Aula
- [x] Migración `classroom_attendance_records` creada (con strings)
- [x] Modelo `ClassroomAttendanceRecord` con constantes y scopes
- [x] `ClassroomAttendanceService` inyecta `PlantelAttendanceService` y `ExcuseService`
- [x] `takeClassAttendance()`: fuerza `STATUS_EXCUSED` si estudiante está en lista de excusados
- [x] `validateCrossAttendance()`: permite registro si tiene excusa aunque no pasó por plantel
- [x] `ClassroomAttendanceLive` llama `ExcuseService::getCoveredStudentsForDate()` al montar
- [x] Vista muestra botón "Presente" disabled para estudiantes excusados
- [x] Vista muestra badge/nota "Excusa aprobada para hoy" en cada fila excusada
- [x] Ruta `/attendance/{assignmentId}` protegida por permiso `attendance_classroom.view`

### Fase 5 — Excusas
- [x] Migración `attendance_excuses` creada (con strings)
- [x] Modelo `AttendanceExcuse` con constantes, `LICENSE_TYPES`, Accessors, Scopes
- [x] `ExcuseService::hasApprovedExcuseForDate()` creado
- [x] `ExcuseService::getActiveLicenseForStudent()` creado
- [x] `ExcuseService::getCoveredStudentsForDate()` creado — retorna `Collection<int>`
- [x] `AttendanceExcuseObserver` creado con lógica retroactiva en `updated()`
- [x] Observer aplica solo sobre registros con `status = 'absent'`
- [x] Observer envuelve actualizaciones en `DB::transaction()`
- [x] Observer registrado en `AppServiceProvider`
- [x] `ExcuseManager` Livewire creado con flujo submit → review → approve/reject

---

## Notas de Implementación

**Orden de inyección de dependencias:**
`ExcuseService` no depende de ningún servicio de asistencia.
`PlantelAttendanceService` depende de `ExcuseService`.
`ClassroomAttendanceService` depende de `PlantelAttendanceService` y `ExcuseService`.
Laravel IoC resuelve esto automáticamente sin configuración adicional.

**Por qué el Observer y no un evento:**
El Observer reacciona directamente al `updated` del modelo sin necesidad de disparar un evento explícito. Esto mantiene la lógica de retroactividad encapsulada en la capa de infraestructura. Si en el futuro se necesita notificar al padre del estudiante al aprobar la excusa, se agrega un evento `ExcuseApproved` en el mismo Observer sin tocar el servicio.

**Rendimiento del getCoveredStudentsForDate:**
La consulta es una sola llamada a la base de datos que devuelve únicamente IDs. Al cargarse antes del bucle en `ClassroomAttendanceLive`, evita N+1 sobre la lista de estudiantes. Si una escuela tiene muchas excusas activas el mismo día (poco probable), el `pluck()->unique()` es suficiente para deduplicar en memoria.

**El CONCAT en el Observer:**
Se usa `DB::raw("CONCAT(COALESCE(notes, ''), ' [Justificado...]')")` para preservar notas existentes y agregar la referencia de la excusa al final. Esto crea un audit trail mínimo en el registro mismo, independientemente del sistema de logs.

---

## Fase 6 — Permisos y Roles
**Rama:** `feature/attendance-permissions`

### 6.1 — Grupos y Permisos

- [x] **Actualizar `PermissionGroupSeeder`:**
  ```php
        // Context: TENANT (Nuevos grupos específicos para el Módulo de Asistencia)
        ['order' => 7, 'slug' => 'students',      'name' => 'Gestión de Estudiantes',    'context' => PermissionGroup::CONTEXT_TENANT],
        ['order' => 8, 'slug' => 'teachers',      'name' => 'Gestión de Docentes',       'context' => PermissionGroup::CONTEXT_TENANT],
        ['order' => 9, 'slug' => 'attendance_plantel', 'name' => 'Asistencia Plantel',   'context' => PermissionGroup::CONTEXT_TENANT],
        ['order' => 10, 'slug' => 'attendance_classroom', 'name' => 'Asistencia Aula',   'context' => PermissionGroup::CONTEXT_TENANT],
        ['order' => 11, 'slug' => 'excuses',      'name' => 'Gestión de Excusas',        'context' => PermissionGroup::CONTEXT_TENANT],

  ```

- [x] **Actualizar `PermissionSeeder`:**
  ```php
        // Nuevos permisos integrados
        'students' => [
            'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import'
        ],
        'teachers' => [
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete', 'teachers.assign_subjects'
        ],
        'attendance_plantel' => [
            'attendance_plantel.view', 'attendance_plantel.open_session', 'attendance_plantel.close_session', 
            'attendance_plantel.record', 'attendance_plantel.qr', 'attendance_plantel.facial', 
            'attendance_plantel.verify', 'attendance_plantel.reports'
        ],
        'attendance_classroom' => [
            'attendance_classroom.view', 'attendance_classroom.record', 'attendance_classroom.edit', 'attendance_classroom.reports'
        ],
        'excuses' => [
            'excuses.view', 'excuses.submit', 'excuses.approve', 'excuses.reject'
        ],

  ```

### 6.2 — Traducciones

- [x] **Actualizar `lang/es/permissions.php`:**
  ```php
  // Estudiantes
    'students' => [
        'view'   => ['label' => 'Ver estudiantes',     'description' => 'Permite consultar la lista y fichas técnicas de los estudiantes.'],
        'create' => ['label' => 'Registrar estudiantes', 'description' => 'Permite el alta manual de nuevos estudiantes en el sistema.'],
        'edit'   => ['label' => 'Editar estudiantes',   'description' => 'Permite modificar información personal y académica de los alumnos.'],
        'delete' => ['label' => 'Eliminar estudiantes', 'description' => 'Permite dar de baja o remover registros de estudiantes.'],
        'import' => ['label' => 'Importar estudiantes', 'description' => 'Permite la carga masiva de estudiantes mediante archivos externos.'],
    ],

    'teachers' => [
        'view'            => ['label' => 'Ver maestros',      'description' => 'Permite consultar la lista del personal docente.'],
        'create'          => ['label' => 'Registrar maestros', 'description' => 'Permite el alta de nuevos docentes en la institución.'],
        'edit'            => ['label' => 'Editar maestros',    'description' => 'Permite actualizar los datos del perfil de los maestros.'],
        'delete'          => ['label' => 'Eliminar maestros',  'description' => 'Permite remover registros del personal docente.'],
        'assign_subjects' => ['label' => 'Asignar materias',   'description' => 'Permite vincular maestros con asignaturas, secciones y años escolares.'],
    ],

    'attendance_plantel' => [
        'view'          => ['label' => 'Ver asistencia plantel',  'description' => 'Consulta el historial de entradas y salidas del centro educativo.'],
        'open_session'  => ['label' => 'Abrir sesión del día',    'description' => 'Habilita el registro de asistencia para la jornada actual.'],
        'close_session' => ['label' => 'Cerrar sesión del día',   'description' => 'Finaliza formalmente el registro de asistencia diaria.'],
        'record'        => ['label' => 'Registrar asistencia',    'description' => 'Permite marcar entradas y salidas de forma manual.'],
        'qr'            => ['label' => 'Registro por QR',         'description' => 'Permite el uso de escáneres de códigos QR para la asistencia.'],
        'facial'        => ['label' => 'Reconocimiento facial',   'description' => 'Permite el uso de biometría facial para el control de acceso.'],
        'verify'        => ['label' => 'Verificar identidad',     'description' => 'Realiza validaciones de identidad en los puntos de acceso.'],
        'reports'       => ['label' => 'Reportes de plantel',     'description' => 'Genera estadísticas y listados de asistencia institucional.'],
    ],

    'attendance_classroom' => [
        'view'    => ['label' => 'Ver asistencia aula', 'description' => 'Consulta los registros de asistencia por asignatura y sección.'],
        'record'  => ['label' => 'Pasar lista',         'description' => 'Permite a los docentes registrar la presencia de los alumnos en el aula.'],
        'edit'    => ['label' => 'Editar asistencia',   'description' => 'Permite corregir registros de asistencia en clases ya impartidas.'],
        'reports' => ['label' => 'Reportes de aula',    'description' => 'Genera reportes de ausentismo por materia y alertas de pasilleo.'],
    ],

    'excuses' => [
        'view'    => ['label' => 'Ver excusas',      'description' => 'Consulta el historial de justificaciones médicas o personales.'],
        'submit'  => ['label' => 'Registrar excusas', 'description' => 'Permite someter nuevas solicitudes de justificación de inasistencia.'],
        'approve' => ['label' => 'Aprobar excusas',   'description' => 'Permite validar y autorizar las justificaciones presentadas.'],
        'reject'  => ['label' => 'Rechazar excusas',  'description' => 'Permite denegar justificaciones que no cumplan requisitos.'],
    ],
  ```

- [x] **Actualizar `lang/es/permission_groups.php`:**
  ```php
    // --- TENANT ---
    'usuarios'             => ['name' => 'Gestión de Usuarios',        'description' => 'Cuentas y accesos del personal escolar.'],
    'roles'                => ['name' => 'Roles y Seguridad',          'description' => 'Control de capacidades y niveles de acceso.'],
    'configuracion'        => ['name' => 'Configuración del Sistema',  'description' => 'Ajustes institucionales del centro.'],
    'students'             => ['name' => 'Gestión de Estudiantes',     'description' => 'Control de registros, inscripciones y fichas de alumnos.'],
    'teachers'             => ['name' => 'Gestión de Maestros',        'description' => 'Administración del personal docente y asignaciones académicas.'],
    'attendance_plantel'   => ['name' => 'Asistencia de Plantel',      'description' => 'Control de acceso perimetral y seguridad en la entrada.'],
    'attendance_classroom' => ['name' => 'Asistencia de Aula',         'description' => 'Seguimiento de presencia por asignatura y registros de clase.'],
    'excuses'              => ['name' => 'Gestión de Justificantes',   'description' => 'Trámite y validación de excusas por inasistencias.'],
    'academico'            => ['name' => 'Gestión Académica',          'description' => 'Control de alumnos y registros.'],
    'asistencia'           => ['name' => 'Control de Asistencia',      'description' => 'Registro y seguimiento de presencia diaria.'],
    'reportes'             => ['name' => 'Reportes y Estadísticas',    'description' => 'Análisis de datos e informes institucionales.'],
  ```

### 6.3 — Asignación a Roles

- [x] **Actualizar `RoleAcademicSeeder`:**
  ```php
  // School Principal
  $principal->givePermissionTo([
      'students.*',
      'teachers.*',
      'attendance_plantel.*',
      'attendance_classroom.*',
      'excuses.*',
  ]);

  // Academic Coordinator
  $coordinator->givePermissionTo([
      'students.view', 'students.edit',
      'teachers.view', 'teachers.assign_subjects',
      'attendance_plantel.view', 'attendance_plantel.open_session', 'attendance_plantel.close_session',
      'attendance_classroom.view', 'attendance_classroom.reports',
      'excuses.*',
  ]);

  // Teacher
  $teacher->givePermissionTo([
      'students.view',
      'attendance_classroom.*',
      'excuses.view', 'excuses.submit',
  ]);

  // Secretary
  $secretary->givePermissionTo([
      'students.*',
      'teachers.view',
      'attendance_plantel.view', 'attendance_plantel.record', 'attendance_plantel.reports',
      'excuses.view', 'excuses.submit',
  ]);
  ```

- [x] `FIX:` Corregir indice de la migracion `2026_04_02_101544_create_teacher_subject_sections_table.php` que superaba los 64 caracteres, ahora es más corto:
  ```php
  $table->index(
                ['school_section_id', 'academic_year_id'], 
                'tss_section_year_index' 
            );
  ```

---

## Fase 7 — CRUD de Estudiantes (Interfaz Web)
**Rama:** `feature/students-crud`

### 7.1 — Migracion y rutas

- [x] **Cambiar campos de migración students (REFACTOR):**
    - Cambiar campos `school_section_id`, `gender`, `date_of_birth`, `place_of_birth`, `rnc`, `blood_type`, `allergies`, `medical_conditions`, `enrollment_date` a `nullable()`.

*Se cambian estos campos a nullable porque necesitamos que el sistema sea más flexible para la creación de estudiantes, eliminando la fricción de datos no esenciales en el registro inicial y facilitando la futura importación masiva de datos desde los reportes o bases de datos locales del sistema MINERD.*


- [x] **Crear ruta de modulo Gestión Académica:**
    - Crear `routes/app/academic.php` para englobar modulo de gestion academica y tener rutas de estudianes aqui.
    - Envolver las rutas en un grupo con prefijo `academic/` y nombre `academic.`.

### 7.2 — Filtros

- [x] **`app/Filters/App/Students/SearchFilter.php`** — busca en `first_name`, `last_name`, `rnc`, `qr_code`
- [x] **`app/Filters/App/Students/SectionFilter.php`** — filtra por `school_section_id`
- [x] **`app/Filters/App/Students/StatusFilter.php`** — filtra por `is_active` (`active` / `inactive` / `''`)
- [x] **`app/Filters/App/Students/GenderFilter.php`** — filtra por `gender` (`M` / `F` / `''`)
- [x] **`app/Filters/App/Students/HasPhotoFilter.php`** — toggle boolean: `photo_path IS NOT NULL`
- [x] **`app/Filters/App/Students/HasFaceEncodingFilter.php`** — toggle boolean: `face_encoding IS NOT NULL`
- [x] **`app/Filters/App/Students/StudentFilters.php`** — orquestador que aplica todos los filtros anteriores en pipeline

### 7.3 — Configuración de Tabla

- [x] **Crear `app/Tables/App/StudentTableConfig.php`** que implemente la interfaz `TableConfig`:
  - `allColumns()`: `full_name`, `rnc`, `section`, `gender`, `age`, `status`, `has_face_encoding`, `enrollment_date`
  - `defaultDesktop()`: `full_name`, `rnc`, `section`, `gender`, `age`, `status`
  - `defaultMobile()`: `photo`, `full_name`, `section`, `status`
  - `filterLabels()`: mapa de clave → label legible para chips
  - `cellClass()`: clases CSS por columna

#### 7.4.A — Componente Livewire StudentIndex (Simplificado)
- [x] **Instalar dependencia:** instalar `composer require simplesoftwareio/simple-qrcode` para generar el qr.

Aquí tienes el checklist actualizado y verificado basado en el código final que proporcionaste. He ajustado los puntos para reflejar exactamente cómo quedó la implementación técnica (uso de `selectedStudentId`, redirecciones por Blade y limpieza de lógica de formulario).

---

#### 1. Backend: `StudentIndex.php` (Livewire)
- [x] **Limpieza de Estado Formulario:** Se eliminaron todas las propiedades de entrada de datos (`first_name`, `last_name`, `email`, `school_section_id` del form, etc.).
- [x] **Optimización de Memoria:** Se implementó `public ?int $selectedStudentId` en lugar de almacenar el modelo completo, evitando la serialización pesada en cada request.
- [x] **Propiedad Computada:** Se añadió `#[Computed] public function selectedStudent()` para hidratar el modelo bajo demanda solo cuando se abren los modales de acción.
- [x] **Acciones de Ciclo de Vida:**
    - [x] `confirmWithdraw(int $id)`: Prepara el estado para la baja.
    - [x] `withdraw()`: Ejecuta la lógica de negocio de desactivación.
    - [x] `confirmReactivate(int $id)`: Prepara el estado para re-inscripción.
    - [x] `reactivate()`: Restablece el estado activo.
    - [x] `showQr(int $id)`: Activa el modal de visualización de QR.
- [x] **Navegación:** Se eliminaron los métodos `openCreate()` y `edit()`, delegando la navegación directamente a las rutas de Laravel desde la vista.
- [x] **Reset de Estado:** El método `closeModals()` limpia correctamente todas las variables temporales y cierra los modales.

#### 2. Frontend: `index.blade.php` (Vista)
- [x] **Toolbar del Módulo:** Actualizada para usar `<x-ui.button>` con el atributo `href="{{ route('app.academic.students.create') }}"` para creación.
- [x] **Celdas de Tabla:**
    - [x] El nombre del estudiante ahora es un enlace directo a su perfil (`students.show`).
    - [x] El botón de edición en la columna de acciones usa un `href` directo a `students.edit`.
- [x] **Eliminación de Componentes Huérfanos:** Se eliminó por completo el componente de **Slide-over** que contenía el formulario de creación/edición rápida.
- [x] **Integración de Modales Operativos:**
    - [x] **Modal QR:** Configurado para usar la propiedad computada `$this->selectedStudent`.
    - [x] **Modal de Baja:** Incluye el formulario con `withdrawal_date` y `withdrawal_reason` vinculados correctamente a Livewire.
    - [x] **Modal de Reactivación:** Texto de confirmación dinámico y acción directa.
- [x] **Limpieza Visual:** Eliminación de directivas `@if($isEditing)` y lógica condicional que ensuciaba el layout de la tabla.


Aquí tienes el checklist actualizado reflejando con precisión la implementación técnica que has proporcionado en el componente y la vista:

### 7.4.B — Nuevo Componente StudentForm (Crear/Editar)
- [x] **Crear `app/Livewire/App/Students/StudentForm.php`:**
    - **Gestión de Estado:** Implementa `$isEdit` y carga el modelo `Student` mediante `mount()` con sus relaciones (`user`).
    - **Inyección de Servicios:** Utiliza `StudentService` inyectado directamente en el método `save()` para la creación de registros y procesamiento de imágenes.
    - **Lógica de Persistencia Transaccional:** 
        - Crea un `User` vinculado (con contraseña basada en RNC por defecto) y asigna el rol `student`.
        - Delega al servicio la creación del `Student` (donde se dispara la generación del QR).
        - Soporta actualización de datos existentes conservando la integridad de los modelos vinculados.
    - **Integración Multimedia:** Maneja `WithFileUploads` para procesar la foto enviada desde el input o capturada por la webcam.
    - **Validación:** Reglas dinámicas para el `email` (ignora el ID actual en edición) y limpieza de strings para el RNC.

- [x] **Crear Vista `student-form.blade.php`:**
    - **Layout Avanzado:** Grid de 12 columnas (`lg:col-span-8` para datos, `lg:col-span-4` para sidebar) con soporte para Dark Mode.
    - **Módulo de Cámara (Webcam Handler):** 
        - Lógica de **Alpine.js** para acceso a `navigator.mediaDevices`.
        - Captura en `canvas`, conversión a `Blob` y subida asíncrona a Livewire mediante `$wire.upload`.
        - Feedback visual con estados de carga (`wire:loading`) y notificaciones personalizadas tras captura.
    - **Secciones de Formulario:** 
        - **Información Personal:** Inputs para nombres, correo, género (radio) y fecha de nacimiento.
        - **Ficha Médica:** Selección de tipo de sangre y textareas para notas de salud.
        - **Sidebar Académico:** Selector de curso/sección dinámico.
        - **Sidebar Biométrico:** Card de estatus visual que cambia de color y mensaje según el atributo `has_face_encoding` del estudiante.
    - **Acciones:** Header con botones persistentes para Cancelar y Guardar con estados de carga.



### 7.5 — Componente StudentShow
- [x] **Crear `app/Livewire/App/Students/StudentShow.php`:**
  - [x] Implementa **Route Model Binding** con `Student $student`.
  - [x] **Layout Dinámico:** Configurado para usar `layouts.app-module` con el config de `modules.configuracion`.
  - [x] **Gestión de Tabs:** Propiedad `$activeTab` sincronizada con Alpine.js mediante `@entangle`.
  - [x] **Lógica de Credenciales:** Método `updateCredentials()` con validación de email único, hashing de password opcional y despacho de evento `notify` para Toasts.

- [x] **Vista `resources/views/livewire/app-students/student-show.blade.php`:**
  - [x] **Header Principal:** 
    - Uso de `x-ui.student-avatar` en tamaño `xl` con QR integrado.
    - Información de estado (Activo/Inactivo) con badges dinámicos.
    - Datos de sección y RNC con iconos de Heroicons.
  - [x] **Grid de Estadísticas (Blade Loop):** 
    - Cálculo de Edad, Fecha de inscripción, Días en plantel (diffInDays) y Tasa de asistencia (mockup 96%).
  - [x] **Navegación de Tabs (Alpine.js):** 
    - Estilo tipo "pill" con soporte para Dark Mode.
    - Secciones: Perfil, Asistencia, Académico, Médico.
  - [x] **Contenido de Tabs:**
    - **Perfil:** Grid de lectura con `x-admin.info-item` + Formulario de **Seguridad y Acceso** (actualización de email/password).
    - **Asistencia:** Mini gráfico de barras generado por loop (15 días) y botón de reporte.
    - **Médico:** Sección de alertas críticas (Alergias/Sangre) con colores de advertencia.
  - [x] **Sidebar de Acción:**
    - **Widget QR:** Generación de código QR en tiempo real usando `QrCode` facade.
    - **Estatus Biométrico:** Card con estado visual dinámico (verde/gris) basado en la propiedad `has_face_encoding`.

- [x] **Integración UI:**
  - [x] Uso consistente de componentes `x-ui.button` y `x-ui.forms.input`.
  - [x] Diseño responsivo (Grid de 12 columnas en desktop, stack en mobile).
  - [x] Implementación de **Line UI** para los inputs de credenciales.

### 7.6 — Componente x-ui.student-avatar

- [x] **Crear `app/View/Components/Ui/StudentAvatar.php`:**
  - Props: `Student $student`, `string $size = 'md'` (sm/md/lg/xl), `bool $showQr = false`
  - Renderiza `<img>` si `photo_path`, o iniciales sobre color generado desde primer carácter del nombre
  - Si `showQr = true`, badge de ícono QR en esquina inferior derecha

- [x] **Crear `resources/views/components/ui/student-avatar.blade.php`**

### EXTRAS:

- Actulizar archivo `components/modals.php` para ajuste de los temas modo oscurso.
- Crear asesor `getFullLabelAttribute` en el modelo `SchoolSection.php` para qeu envie el nombre adeucado de la seccion dentro de sus relaicones
- Actualizar `App/UserIndex.php` para que el limine de usuarios no sea afectados por los usuarios con role student (que tiene su propio limite)
- Actualizar `Admin/SchoolShow` `Admin/SchoolIndex`  para que cotnabilice por la entidad de estudiante y no por usuario con rol. Para poder logar que en el index se cotnabilise y se aligere la busqueda agregamos una relacion con studens en el modelo `User.php` (hasOne) y `School.php` (hasMany)
- actualizar el `public function scopeWithIndexRelations($query)` de Studen para que envie su grade, y area tecnica, para su uso creamos el asesor `fullSectionName`.
---
## Fase 7.5 — Sistema de Identificación Estudiantil (QRs y Carnets)
**Rama:** `feature/student-credentials`

**Objetivo:** Implementar un sistema completo de gestión de credenciales estudiantiles, que incluye visualización de carnet digital en el perfil del estudiante, generación masiva de códigos QR para impresión física, y motor de PDF optimizado para producción de carnets en lotes.

---

### 7.5.1 — Refactorización del Perfil para Estudiantes **SE HARÁ DESPUES**

**Contexto:** El componente `ProfileModal` actual está diseñado para usuarios de sistema (staff) con tabs de Información Personal editable, Seguridad y Preferencias. Los estudiantes con acceso al Classroom Virtual necesitan una interfaz diferente que muestre su ficha académica (datos de solo lectura) y su carnet digital con QR.

- [ ] **Actualizar `App\Livewire\Shared\ProfileModal.php` — Discriminación por Rol:**
  ```php
<?php

namespace App\Livewire\Shared;

use App\Models\Tenant\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileModal extends Component
{
    use WithFileUploads;

    public bool   $show      = false;
    public string $activeTab = 'personal';

    // ── Datos del usuario de sistema (staff) ──────────────────────
    public string $name     = '';
    public string $email    = '';
    public string $phone    = '';
    public string $position = '';
    public string $roleName = '';
    public string $roleColor = '#64748b'; // Color por defecto (slate-500)
    public $photo;

    // ── Cambio de contraseña ──────────────────────────────────────
    public string $currentPassword    = '';
    public string $newPassword        = '';
    public string $newPasswordConfirm = '';

    // ── Preferencias ──────────────────────────────────────────────
    public string $theme            = 'system';
    public bool   $sidebarCollapsed = false;

    // ── Estudiante ────────────────────────────────────────────────
    public ?Student $student   = null;
    public bool     $isStudent = false;
    public bool     $qrRendered = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->isStudent = $user->hasRole('Student');

        if ($this->isStudent) {
            $this->student = Student::with([
                'section.grade',
                'section.shift',
                'section.technicalTitle',
                'school',
            ])->where('user_id', $user->id)->first();

            if (!$this->student) {
                $this->isStudent = false;
            } else {
                $this->activeTab = 'ficha';
            }
        }

        if (!$this->isStudent) {
            $this->name     = $user->name;
            $role = $user->roles->first();
            if ($role) {
                $this->roleName = $role->name;
                $this->roleColor = $role->color ?? '#64748b'; 
            }
            $this->email    = $user->email;
            $this->phone    = $user->phone    ?? '';
            $this->position = $user->position ?? '';

            $this->theme            = $user->preference('theme', 'system');
            $this->sidebarCollapsed = (bool) $user->preference('sidebar_collapsed', false);
        }
    }

    // ── Apertura / cierre ─────────────────────────────────────────

    #[On('open-profile-modal')]
    public function open(): void
    {
        $this->show       = true;
        $this->qrRendered = false;
    }

    public function close(): void
    {
        $this->show       = false;
        $this->activeTab  = $this->isStudent ? 'ficha' : 'personal';
        $this->qrRendered = false;
        $this->resetPasswordFields();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'carnet' && !$this->qrRendered) {
            $this->qrRendered = true;
        }

        $this->resetPasswordFields();
        $this->resetValidation();
    }

    // ── Información personal (staff) ──────────────────────────────

    public function updateProfile(): void
    {
        if ($this->isStudent) return;

        /** @var User $user */
        $user = Auth::user();

        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'position' => 'nullable|string|max:80',
        ]);

        $user->update([
            'name'     => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone    ?: null,
            'position' => $this->position ?: null,
        ]);

        $this->dispatch('notify',
            type: 'success',
            title: 'Perfil actualizado',
            message: 'Tus datos han sido guardados correctamente.',
        );
    }

    // ── Foto de perfil (staff) ────────────────────────────────────

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => 'image|max:2048']);

        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $this->photo->store("avatars/users/{$user->id}", 'public');
        $user->update(['avatar_path' => $path]);

        $this->photo = null;

        $this->dispatch('notify',
            type: 'success',
            title: 'Foto actualizada',
            message: 'Tu foto de perfil ha sido actualizada.',
        );
    }

    public function removePhoto(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->dispatch('notify',
            type: 'success',
            title: 'Foto eliminada',
            message: 'Tu foto de perfil ha sido eliminada.',
        );
    }

    // ── Cambio de contraseña ──────────────────────────────────────

    public function updatePassword(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->validate([
            'currentPassword'    => 'required',
            'newPassword'        => 'required|min:8|confirmed:newPasswordConfirm',
            'newPasswordConfirm' => 'required',
        ], [
            'newPassword.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if (!Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'La contraseña actual es incorrecta.');
            return;
        }

        $user->update(['password' => Hash::make($this->newPassword)]);

        $this->resetPasswordFields();

        $this->dispatch('notify',
            type: 'success',
            title: 'Contraseña actualizada',
            message: 'Tu contraseña ha sido cambiada correctamente.',
        );
    }

    // ── Preferencias  ─────────────────────────────────

    public function savePreferences(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $prefs = $user->preferences ?? [];

        $prefs['theme'] = $this->theme;

        if (!$user->school_id) {
            $prefs['sidebar_collapsed'] = $this->sidebarCollapsed;
        }

        $user->update(['preferences' => $prefs]);

        $this->dispatch('notify',
            type: 'success',
            title: 'Preferencias guardadas',
            message: 'Tus preferencias han sido actualizadas. Recargando...',
        );

        // Recargar para aplicar el tema
        $this->dispatch('reload-page');
    }

    // ── Descarga QR (estudiantes) ─────────────────────────────────

    public function downloadQr(): mixed
    {
        if (!$this->isStudent || !$this->student) return null;

        return response()->streamDownload(function () {
            echo \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->generate($this->student->qr_code);
        }, "qr_{$this->student->first_name}_{$this->student->last_name}.png", [
            'Content-Type' => 'image/png',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function resetPasswordFields(): void
    {
        $this->currentPassword    = '';
        $this->newPassword        = '';
        $this->newPasswordConfirm = '';
    }

    public function render()
    {
        return view('livewire.shared.profile-modal');
    }
}
  ```

- [ ] **Actualizar vista `resources/views/livewire/shared/profile-modal.blade.php` — Interfaz Adaptativa:**
  ```blade
    {{--
        resources/views/livewire/shared/profile-modal.blade.php
        --------------------------------------------------------
        Diseño: sidebar vertical izquierda + contenido derecha
        Lógica: estudiantes → ficha/carnet/seguridad
                staff      → personal/seguridad/preferencias
    --}}

    <div>
        {{-- Overlay --}}
        <div
            x-show="$wire.show"
            x-cloak
            @click.self="$wire.close()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4
                bg-slate-900/80 dark:bg-black/90 backdrop-blur-sm"
        >
            {{-- Panel --}}
            <div
                x-show="$wire.show"
                x-cloak
                @click.stop
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-2xl h-[600px] flex overflow-hidden
                    rounded-3xl shadow-2xl
                    bg-white dark:bg-dark-bg"
            >

                {{-- ══════════════════════════════════════════
                    SIDEBAR IZQUIERDA
                ═══════════════════════════════════════════ --}}
                <aside class="w-56 flex-shrink-0 flex flex-col
                            bg-slate-50 dark:bg-dark-card
                            border-r border-slate-200 dark:border-white/5">

                    {{-- Avatar + Info --}}
                    <div class="flex flex-col items-center px-6 pt-8 pb-6
                                border-b border-slate-200 dark:border-white/5">

                        @if($isStudent && $student)
                            {{-- Avatar del estudiante (solo lectura) --}}
                            <div class=" mb-3">
                                <x-ui.student-avatar :student="$student" size="xl" :showQr="true" />
                            </div>

                            <p class="text-sm font-bold text-slate-800 dark:text-white text-center leading-snug">
                                {{ $student->full_name }}
                            </p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center mt-0.5 truncate max-w-full">
                                {{ auth()->user()->email }}
                            </p>
                            <span class="mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                        bg-orvian-orange/10 text-orvian-orange border border-orvian-orange/20">
                                Student
                            </span>

                        @else
                            {{-- Avatar editable del staff --}}
                    <div class="relative group">
                            <x-ui.avatar :user="auth()->user()" size="xl" showStatus />
                            
                            <label class="absolute inset-0 z-10 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 cursor-pointer">
                                <x-heroicon-s-camera class="w-6 h-6 text-white" />
                                <input type="file" wire:model="photo" class="hidden" accept="image/*">
                            </label>

                            @if(auth()->user()->avatar_path)
                                <button 
                                    wire:click="removePhoto" 
                                    wire:confirm="¿Estás seguro de que deseas eliminar tu foto de perfil?"
                                    class="absolute -top-1 -right-1 z-30 p-1.5 bg-white dark:bg-dark-card border border-slate-200 dark:border-white/10 rounded-full text-red-500 shadow-sm hover:bg-red-50 transition-colors"
                                    title="Eliminar foto">
                                    <x-heroicon-s-trash class="w-3.5 h-3.5" />
                                </button>
                            @endif

                            <div wire:loading wire:target="photo" class="absolute inset-0 z-20 rounded-full bg-black/60 flex items-center justify-center">
                                <svg class="animate-spin h-6 w-6 text-white" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>

                            <p class="text-sm font-bold text-slate-800 dark:text-white text-center leading-snug">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center mt-0.5 truncate max-w-full">
                                {{ auth()->user()->email }}
                            </p>
                            <div class="mt-2">
                                
                                @if($roleName)
                                <x-ui.badge :hex="$roleColor" size="sm" class="mx-auto">
                                    {{ $roleName }}
                                </x-ui.badge>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Navegación vertical --}}
                    <nav class="flex-1 px-3 py-4 space-y-1">

                        @if($isStudent)
                            {{-- Tabs estudiante --}}
                            <button wire:click="switchTab('ficha')"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left
                                        {{ $activeTab === 'ficha'
                                            ? 'bg-orvian-orange text-white shadow-sm shadow-orvian-orange/30'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white' }}">
                                <x-heroicon-o-identification class="w-4 h-4 flex-shrink-0" />
                                Mi Ficha
                            </button>

                            <button wire:click="switchTab('carnet')"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left
                                        {{ $activeTab === 'carnet'
                                            ? 'bg-orvian-orange text-white shadow-sm shadow-orvian-orange/30'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white' }}">
                                <x-heroicon-o-qr-code class="w-4 h-4 flex-shrink-0" />
                                Carnet Digital
                            </button>

                            <button wire:click="switchTab('seguridad')"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left
                                        {{ $activeTab === 'seguridad'
                                            ? 'bg-orvian-orange text-white shadow-sm shadow-orvian-orange/30'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white' }}">
                                <x-heroicon-o-shield-check class="w-4 h-4 flex-shrink-0" />
                                Seguridad
                            </button>

                            <button wire:click="switchTab('preferencias')"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left
                                        {{ $activeTab === 'preferencias'
                                            ? 'bg-orvian-orange text-white shadow-sm shadow-orvian-orange/30'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white' }}">
                                <x-heroicon-o-adjustments-horizontal class="w-4 h-4 flex-shrink-0" />
                                Preferencias
                            </button>

                        @else
                            {{-- Tabs staff --}}
                            <button wire:click="switchTab('personal')"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left
                                        {{ $activeTab === 'personal'
                                            ? 'bg-orvian-orange text-white shadow-sm shadow-orvian-orange/30'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white' }}">
                                <x-heroicon-o-user class="w-4 h-4 flex-shrink-0" />
                                Información Personal
                            </button>

                            <button wire:click="switchTab('seguridad')"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left
                                        {{ $activeTab === 'seguridad'
                                            ? 'bg-orvian-orange text-white shadow-sm shadow-orvian-orange/30'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white' }}">
                                <x-heroicon-o-shield-check class="w-4 h-4 flex-shrink-0" />
                                Seguridad
                            </button>

                            <button wire:click="switchTab('preferencias')"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left
                                        {{ $activeTab === 'preferencias'
                                            ? 'bg-orvian-orange text-white shadow-sm shadow-orvian-orange/30'
                                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white' }}">
                                <x-heroicon-o-adjustments-horizontal class="w-4 h-4 flex-shrink-0" />
                                Preferencias
                            </button>
                        @endif
                    </nav>

                    {{-- Botón cerrar (al fondo del sidebar) --}}
                    <div class="px-3 pb-5">
                        <button wire:click="close"
                                class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl
                                    text-sm font-semibold transition-all
                                    bg-orvian-navy text-white hover:opacity-90
                                    dark:bg-white/8 dark:hover:bg-white/12">
                            Cerrar Ajustes
                        </button>
                    </div>
                </aside>

                {{-- ══════════════════════════════════════════
                    CONTENIDO DERECHA
                ═══════════════════════════════════════════ --}}
                <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

                    {{-- Scroll container --}}
                    <div class="flex-1 overflow-y-auto custom-scroll px-8 py-7">

                        {{-- ══ TAB: Mi Ficha (Estudiante) ══ --}}
                        @if($isStudent && $activeTab === 'ficha' && $student)
                            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">Mi Ficha Académica</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">Datos de tu inscripción. Solo lectura.</p>

                            <div class="space-y-4">
                                <x-ui.forms.input
                                    label="Nombre Completo"
                                    :value="$student->full_name"
                                    icon-left="heroicon-o-user"
                                    readonly />

                                <div class="grid grid-cols-2 gap-4">
                                    <x-ui.forms.input
                                        label="Género"
                                        :value="$student->gender === 'M' ? 'Masculino' : 'Femenino'"
                                        icon-left="heroicon-o-user-circle"
                                        readonly />

                                    <x-ui.forms.input
                                        label="Fecha de Nacimiento"
                                        :value="$student->date_of_birth?->format('d/m/Y') ?? '—'"
                                        icon-left="heroicon-o-calendar"
                                        readonly />
                                </div>

                                <x-ui.forms.input
                                    label="Cédula / RNC"
                                    :value="$student->rnc ?? '—'"
                                    icon-left="heroicon-o-identification"
                                    readonly />

                                <x-ui.forms.input
                                    label="Sección"
                                    :value="$student->full_section_name"
                                    icon-left="heroicon-o-academic-cap"
                                    readonly />

                                <x-ui.forms.input
                                    label="Centro Educativo"
                                    :value="$student->school->name ?? '—'"
                                    icon-left="heroicon-o-building-library"
                                    readonly />

                                <x-ui.forms.input
                                    label="Fecha de Inscripción"
                                    :value="$student->enrollment_date?->format('d/m/Y') ?? '—'"
                                    icon-left="heroicon-o-calendar-days"
                                    readonly />
                            </div>

                        {{-- ══ TAB: Carnet Digital (Estudiante) ══ --}}
                        @elseif($isStudent && $activeTab === 'carnet' && $student)
                            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">Carnet Digital</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">Tu código QR de identificación para el sistema de asistencia.</p>

                            {{-- Preview del carnet --}}
                            <div class="flex justify-center mb-5">
                                <div class="w-full max-w-xs bg-gradient-to-br from-orvian-navy to-orvian-orange p-0.5 rounded-2xl shadow-xl">
                                    <div class="bg-white dark:bg-[#0d1117] rounded-[14px] p-5 space-y-3">

                                        {{-- Logo + nombre del centro --}}
                                        <div class="text-center">
                                            @if($student->school->logo_path)
                                                <img src="{{ Storage::url($student->school->logo_path) }}"
                                                    alt="Logo" class="w-12 h-12 mx-auto object-contain mb-1.5">
                                            @else
                                                <div class="w-12 h-12 mx-auto bg-gradient-to-br from-orvian-navy to-orvian-orange rounded-xl flex items-center justify-center mb-1.5">
                                                    <x-heroicon-s-academic-cap class="w-6 h-6 text-white" />
                                                </div>
                                            @endif
                                            <p class="text-[11px] font-black text-slate-800 dark:text-white uppercase tracking-tight leading-tight">
                                                {{ $student->school->name }}
                                            </p>
                                        </div>

                                        {{-- Foto del estudiante --}}
                                        <div class="flex justify-center">
                                            @if($student->photo_path)
                                                <img src="{{ Storage::url($student->photo_path) }}"
                                                    alt="{{ $student->full_name }}"
                                                    class="w-20 h-20 rounded-xl object-cover border-4 border-slate-100 dark:border-white/10">
                                            @else
                                                <div class="w-20 h-20 rounded-xl border-4 border-slate-100 dark:border-white/10
                                                            flex items-center justify-center text-2xl font-black text-white"
                                                    style="background-color: {{ auth()->user()->avatar_color ?? '#f78904' }}">
                                                    {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Nombre y sección --}}
                                        <div class="text-center">
                                            <p class="text-sm font-black text-slate-800 dark:text-white">{{ $student->full_name }}</p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                                {{ $student->section?->grade?->name }} — Sección {{ $student->section?->label }}
                                            </p>
                                            @if($student->section?->shift)
                                                <p class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold">
                                                    {{ $student->section->shift->type ?? '' }}
                                                </p>
                                            @endif
                                        </div>

                                        {{-- QR con lazy rendering --}}
                                        <div class="flex justify-center">
                                            @if($qrRendered)
                                                <div class="p-2.5 bg-white rounded-xl border-2 border-dashed border-slate-200 dark:border-white/10">
                                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate($student->qr_code) !!}
                                                </div>
                                            @else
                                                <div class="w-[140px] h-[140px] bg-slate-100 dark:bg-white/5 rounded-xl
                                                            flex items-center justify-center">
                                                    <x-heroicon-o-qr-code class="w-10 h-10 text-slate-300 dark:text-white/20" />
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Código alfanumérico --}}
                                        <p class="text-center text-[9px] text-slate-400 dark:text-slate-600 font-mono tracking-wider break-all">
                                            {{ $student->qr_code }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <x-ui.button
                                variant="primary"
                                size="sm"
                                :fullWidth="true"
                                iconLeft="heroicon-o-arrow-down-tray"
                                wire:click="downloadQr">
                                Descargar QR
                            </x-ui.button>

                            <div class="mt-4 p-3 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/50 flex gap-3">
                                <x-heroicon-s-information-circle class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" />
                                <p class="text-xs text-blue-700 dark:text-blue-300">
                                    Este código QR es único y te identifica en el sistema de asistencia. Muéstralo en la entrada del centro para registrar tu llegada.
                                </p>
                            </div>

                        {{-- ══ TAB: Información Personal (Staff) ══ --}}
                        @elseif(!$isStudent && $activeTab === 'personal')
                            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">Información Personal</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">Actualiza tus datos de contacto y posición.</p>

                            <div class="space-y-5">
                                <x-ui.forms.input
                                    label="Correo Electrónico"
                                    name="email"
                                    type="email"
                                    wire:model="email"
                                    icon-left="heroicon-o-envelope"
                                    :error="$errors->first('email')"
                                    hint="El correo no puede ser modificado por el usuario"
                                    readonly />

                                <x-ui.forms.input
                                    label="Nombre Completo"
                                    name="name"
                                    wire:model="name"
                                    icon-left="heroicon-o-user"
                                    :error="$errors->first('name')"
                                    required />

                                <x-ui.forms.input
                                    label="Teléfono"
                                    name="phone"
                                    wire:model="phone"
                                    icon-left="heroicon-o-phone"
                                    :error="$errors->first('phone')"
                                    placeholder="Ej. 809-555-0000"
                                    hint="Opcional — número de contacto interno" />

                                <x-ui.forms.input
                                    label="Cargo / Posición"
                                    name="position"
                                    wire:model="position"
                                    icon-left="heroicon-o-briefcase"
                                    :error="$errors->first('position')"
                                    placeholder="Ej. Coordinador académico"
                                    hint="Visible solo dentro del centro" />
                            </div>

                        {{-- ══ TAB: Seguridad (Staff y Estudiantes) ══ --}}
                        @elseif($activeTab === 'seguridad')
                            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">Seguridad</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">Cambia tu contraseña de acceso.</p>

                            <div class="space-y-5" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">

                                {{-- Contraseña actual --}}
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider mb-2
                                                text-slate-400 dark:text-slate-500">
                                        Contraseña Actual <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-0 top-1/2 -translate-y-1/2 pointer-events-none
                                                    text-slate-400">
                                            <x-heroicon-o-lock-closed class="w-5 h-5" />
                                        </span>
                                        <input
                                            :type="showCurrent ? 'text' : 'password'"
                                            wire:model="currentPassword"
                                            placeholder="Tu contraseña actual"
                                            class="w-full border-0 border-b border-slate-200 dark:border-white/10 bg-transparent
                                                pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400
                                                focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors" />
                                        <button type="button" @click="showCurrent = !showCurrent"
                                                class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-orvian-orange transition-colors">
                                            <x-heroicon-o-eye x-show="!showCurrent" class="w-5 h-5" />
                                            <x-heroicon-o-eye-slash x-show="showCurrent" style="display:none;" class="w-5 h-5" />
                                        </button>
                                    </div>
                                    @error('currentPassword')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Nueva contraseña --}}
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider mb-2
                                                text-slate-400 dark:text-slate-500">
                                        Nueva Contraseña <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-0 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <x-heroicon-o-key class="w-5 h-5" />
                                        </span>
                                        <input
                                            :type="showNew ? 'text' : 'password'"
                                            wire:model="newPassword"
                                            placeholder="Mínimo 8 caracteres"
                                            class="w-full border-0 border-b border-slate-200 dark:border-white/10 bg-transparent
                                                pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400
                                                focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors" />
                                        <button type="button" @click="showNew = !showNew"
                                                class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-orvian-orange transition-colors">
                                            <x-heroicon-o-eye x-show="!showNew" class="w-5 h-5" />
                                            <x-heroicon-o-eye-slash x-show="showNew" style="display:none;" class="w-5 h-5" />
                                        </button>
                                    </div>
                                    @error('newPassword')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Confirmar contraseña --}}
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider mb-2
                                                text-slate-400 dark:text-slate-500">
                                        Confirmar Contraseña <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-0 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <x-heroicon-o-key class="w-5 h-5" />
                                        </span>
                                        <input
                                            :type="showConfirm ? 'text' : 'password'"
                                            wire:model="newPasswordConfirm"
                                            placeholder="Repite la nueva contraseña"
                                            class="w-full border-0 border-b border-slate-200 dark:border-white/10 bg-transparent
                                                pl-7 pr-8 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400
                                                focus:ring-0 focus:outline-none focus:border-orvian-orange transition-colors" />
                                        <button type="button" @click="showConfirm = !showConfirm"
                                                class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-orvian-orange transition-colors">
                                            <x-heroicon-o-eye x-show="!showConfirm" class="w-5 h-5" />
                                            <x-heroicon-o-eye-slash x-show="showConfirm" style="display:none;" class="w-5 h-5" />
                                        </button>
                                    </div>
                                    @error('newPasswordConfirm')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                        {{-- ══ TAB: Preferencias (Staff) ══ --}}
                        @elseif($activeTab === 'preferencias')
                            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">Preferencias</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">Personaliza la apariencia de la plataforma.</p>

                            <div class="space-y-6">
                                {{-- Tema --}}
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider mb-3
                                                text-slate-400 dark:text-slate-500">
                                        Tema de la interfaz
                                    </label>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach(['light' => ['Claro', 'heroicon-o-sun'], 'dark' => ['Oscuro', 'heroicon-o-moon'], 'system' => ['Sistema', 'heroicon-o-computer-desktop']] as $value => [$label, $icon])
                                            <button wire:click="$set('theme', '{{ $value }}')"
                                                    class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all text-sm font-semibold
                                                        {{ $theme === $value
                                                            ? 'border-orvian-orange bg-orvian-orange/5 text-orvian-orange'
                                                            : 'border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:border-slate-300 dark:hover:border-white/20' }}">
                                                <x-dynamic-component :component="$icon" class="w-5 h-5" />
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Sidebar colapsado (solo admin sin school_id) --}}
                                @if(!auth()->user()->school_id)
                                    <x-ui.forms.toggle
                                        label="Sidebar colapsado por defecto"
                                        name="sidebarCollapsed"
                                        wire:model="sidebarCollapsed"
                                        description="El panel lateral comenzará cerrado al iniciar sesión" />
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Footer de acciones --}}
                    <div class="flex-shrink-0 px-8 py-5 border-t border-slate-200 dark:border-white/5 flex justify-end">
                        @if(!$isStudent && $activeTab === 'personal')
                            <x-ui.button variant="primary" size="sm"
                                wire:click="updateProfile"
                                wire:loading.attr="disabled" wire:target="updateProfile">
                                <span wire:loading.remove wire:target="updateProfile">Guardar Cambios</span>
                                <span wire:loading wire:target="updateProfile">Guardando...</span>
                            </x-ui.button>

                        @elseif($activeTab === 'seguridad')
                            <x-ui.button variant="primary" size="sm"
                                wire:click="updatePassword"
                                wire:loading.attr="disabled" wire:target="updatePassword">
                                <span wire:loading.remove wire:target="updatePassword">Cambiar Contraseña</span>
                                <span wire:loading wire:target="updatePassword">Guardando...</span>
                            </x-ui.button>

                        @elseif($activeTab === 'preferencias')
                            <x-ui.button variant="primary" size="sm"
                                wire:click="savePreferences"
                                wire:loading.attr="disabled" wire:target="savePreferences">
                                <span wire:loading.remove wire:target="savePreferences">Guardar Preferencias</span>
                                <span wire:loading wire:target="savePreferences">Guardando...</span>
                            </x-ui.button>

                        @else
                            {{-- Espacio vacío para mantener altura consistente --}}
                            <div></div>
                        @endif
                    </div>
                </div>

            </div>{{-- /panel --}}
        </div>{{-- /overlay --}}
    </div>
  ```


### 7.5.2 — Gestión de Impresión Masiva de Carnets

- [x] **Crear componente Livewire `App\Livewire\App\Students\StudentPrintManager.php`:**
  ```php
  namespace App\Livewire\App\Students;

  use App\Models\Tenant\Student;
  use App\Models\Tenant\SchoolSection;
  use App\Models\Tenant\SchoolShift;
  use App\Models\Tenant\Academic\Grade;
  use App\Filters\App\Students\StudentFilters;
  use Livewire\Component;
  use Livewire\WithPagination;
  use Livewire\Attributes\Layout;
  use Livewire\Attributes\Title;
  use Livewire\Attributes\Computed;

  #[Title('Gestión de Carnets')]
  #[Layout('layouts.app-module', ['module' => config('modules.estudiantes')])]
  class StudentPrintManager extends Component
  {
      use WithPagination;

      // Filtros
      public $search = '';
      public $selectedGrade = null;
      public $selectedSection = null;
      public $selectedShift = null;
      public $statusFilter = 'active'; // active, inactive, all

      // Selección masiva
      public array $selectedStudents = [];
      public bool $selectAll = false;

      // Paginación
      public int $perPage = 20;

      protected $queryString = [
          'search' => ['except' => ''],
          'selectedGrade' => ['except' => null],
          'selectedSection' => ['except' => null],
          'selectedShift' => ['except' => null],
          'statusFilter' => ['except' => 'active'],
      ];

      public function updatingSearch()
      {
          $this->resetPage();
      }

      public function updatingSelectedGrade()
      {
          $this->selectedSection = null;
          $this->resetPage();
      }

      public function updatingSelectedSection()
      {
          $this->resetPage();
      }

      public function updatingSelectedShift()
      {
          $this->resetPage();
      }

      public function updatedSelectAll($value)
      {
          if ($value) {
              // Seleccionar todos los IDs de la página actual
              $this->selectedStudents = $this->getFilteredStudents()
                  ->pluck('id')
                  ->toArray();
          } else {
              $this->selectedStudents = [];
          }
      }

      public function toggleStudent(int $studentId)
      {
          if (in_array($studentId, $this->selectedStudents)) {
              $this->selectedStudents = array_diff($this->selectedStudents, [$studentId]);
          } else {
              $this->selectedStudents[] = $studentId;
          }
      }

      public function clearSelection()
      {
          $this->selectedStudents = [];
          $this->selectAll = false;
      }

      protected function getFilteredStudents()
      {
          $query = Student::query()
              ->with(['section.grade', 'section.shift', 'school']);

          // Búsqueda por nombre o cédula
          if ($this->search) {
              $query->where(function ($q) {
                  $q->where('first_name', 'like', "%{$this->search}%")
                      ->orWhere('last_name', 'like', "%{$this->search}%")
                      ->orWhere('rnc', 'like', "%{$this->search}%")
                      ->orWhere('qr_code', 'like', "%{$this->search}%");
              });
          }

          // Filtro por estado
          if ($this->statusFilter === 'active') {
              $query->where('is_active', true);
          } elseif ($this->statusFilter === 'inactive') {
              $query->where('is_active', false);
          }

          // Filtro por grado
          if ($this->selectedGrade) {
              $query->whereHas('section', function ($q) {
                  $q->where('grade_id', $this->selectedGrade);
              });
          }

          // Filtro por sección específica
          if ($this->selectedSection) {
              $query->where('school_section_id', $this->selectedSection);
          }

          // Filtro por tanda
          if ($this->selectedShift) {
              $query->whereHas('section', function ($q) {
                  $q->where('school_shift_id', $this->selectedShift);
              });
          }

          return $query->orderBy('last_name')->orderBy('first_name');
      }

      public function generatePrintSheet()
      {
          if (empty($this->selectedStudents)) {
              $this->dispatch('notify', [
                  'type' => 'warning',
                  'title' => 'Ningún estudiante seleccionado',
                  'message' => 'Debes seleccionar al menos un estudiante para generar carnets.',
              ]);
              return;
          }

          // Redirigir a la ruta de generación de PDF
          return redirect()->route('app.students.print-qr-sheet', [
              'students' => implode(',', $this->selectedStudents)
          ]);
      }

      #[Computed]
      public function grades()
      {
          return Grade::orderBy('order')->get();
      }

      #[Computed]
      public function sections()
      {
          if (!$this->selectedGrade) {
              return collect();
          }

          return SchoolSection::where('grade_id', $this->selectedGrade)
              ->with('shift')
              ->orderBy('label')
              ->get();
      }

      #[Computed]
      public function shifts()
      {
          return SchoolShift::orderBy('start_time')->get();
      }

      public function render()
      {
          $students = $this->getFilteredStudents()->paginate($this->perPage);

          return view('livewire.app.students.student-print-manager', [
              'students' => $students,
              'totalSelected' => count($this->selectedStudents),
          ]);
      }
  }
  ```

- [x] **Crear vista `resources/views/livewire/app/students/student-print-manager.blade.php`:**


### 7.5.3 — Motor de Generación de PDF para Carnets

- [x] **Instalar DomPDF (si no está instalado):**
  ```bash
  composer require barryvdh/laravel-dompdf
  ```

- [x] **Crear controlador `App\Http\Controllers\App\Students\StudentPrintController.php`:**
  ```php
  namespace App\Http\Controllers\App\Students;

  use App\Http\Controllers\Controller;
  use App\Models\Tenant\Student;
  use Barryvdh\DomPDF\Facade\Pdf;
  use Illuminate\Http\Request;

  class StudentPrintController extends Controller
  {
      public function printQrSheet(Request $request)
      {
          // Validar que se recibieron IDs
          $studentIds = explode(',', $request->input('students', ''));
          
          if (empty($studentIds)) {
              abort(400, 'No se especificaron estudiantes para imprimir.');
          }

          // Cargar estudiantes con relaciones necesarias
          $students = Student::with(['section.grade', 'section.shift', 'school'])
              ->whereIn('id', $studentIds)
              ->where('school_id', auth()->user()->school_id) // Seguridad
              ->get();

          if ($students->isEmpty()) {
              abort(404, 'No se encontraron estudiantes válidos.');
          }

          // Generar PDF
          $pdf = Pdf::loadView('printables.qr-sheet', [
              'students' => $students,
              'school' => auth()->user()->school,
          ]);

          // Configurar opciones de DomPDF
          $pdf->setPaper('letter', 'portrait');
          $pdf->setOption('isHtml5ParserEnabled', true);
          $pdf->setOption('isRemoteEnabled', true); // Para cargar imágenes desde Storage

          // Retornar PDF para descarga
          return $pdf->stream('carnets_' . now()->format('Y-m-d_His') . '.pdf');
      }
  }
  ```

- [x] **Crear plantilla `resources/views/printables/qr-sheet.blade.php`:**

### 7.5.4 — Rutas y Navegación

- [x] **Actualizar `routes/app/students.php` (o crear si no existe):**
  ```php
  use App\Livewire\App\Students\StudentPrintManager;
  use App\Http\Controllers\App\Students\StudentPrintController;

  Route::middleware(['can:students.view'])->group(function () {
      // Ruta existente del índice de estudiantes
      // Route::get('/students', StudentIndex::class)->name('students.index');

      // Nueva ruta para gestión de impresión
      Route::get('/students/print-manager', StudentPrintManager::class)
          ->middleware('can:students.export')
          ->name('students.print-manager');

      // Ruta de generación de PDF
      Route::get('/students/print-qr-sheet', [StudentPrintController::class, 'printQrSheet'])
          ->middleware('can:students.export')
          ->name('students.print-qr-sheet');
  });
  ```


- [x] **Actualizar configuración del módulo `config/modules.php`:**
  ```php
  'estudiantes' => [
      'name' => 'Estudiantes',
      'icon' => 'users',
      'links' => [
          ['label' => 'Listado', 'route' => 'app.students.index'],
          ['label' => 'Gestión de Carnets', 'route' => 'app.students.print-manager'], // ← NUEVO
          ['label' => 'Importar', 'route' => 'app.students.import'],
      ],
  ],
  ```


### EXTRAS

- Actualizar `orvian-ledger.blade.php` para correjir error al contar y pasar de page.
---

## Notas de Implementación

**Orden de Ejecución:**
1. Instalar dependencias: `simple-qrcode` y `laravel-dompdf`
2. Refactorizar `ProfileModal` con discriminación por rol
3. Crear componente `StudentPrintManager` con filtros y selección masiva
4. Implementar controlador y plantilla de PDF
5. Configurar rutas y navegación
7. Optimizar caché de QR si es necesario

**Consideraciones de Diseño:**
- El diseño del carnet en PDF debe ser simple y profesional
- Las guías de corte (bordes punteados) facilitan el recorte con guillotina
- El grid de 3×3 carnets por página optimiza el uso del papel
- Los carnets físicos deben ser laminados para durabilidad

**Impacto en Módulos Existentes:**
- El ProfileModal ahora tiene dos flujos completamente diferentes según el rol
- Los estudiantes con acceso al Classroom Virtual pueden descargar su propio QR
- La gestión de carnets es independiente del flujo de asistencia (se usa para impresión física)
- Los QR codes generados sirven tanto para carnets físicos como para escaneo digital

**Seguridad:**
- Validar siempre que los estudiantes pertenezcan al `school_id` del usuario autenticado
- Los QR codes deben ser únicos a nivel global (no solo por escuela)
- El acceso a la generación de PDF requiere permiso `students.export`

---

## Fase 8 — CRUD de Maestros e Interfaz de Asignaciones
**Rama:** `feature/teachers-crud`

---

### 8.1 — Rutas

- [x] **Crear `routes/app/teachers.php`:**
  ```php
  <?php

  use App\Livewire\App\Teachers\TeacherIndex;
  use App\Livewire\App\Teachers\TeacherShow;
  use App\Livewire\App\Teachers\TeacherForm;
  use App\Livewire\App\Teachers\TeacherAssignments;
  use Illuminate\Support\Facades\Route;

  /*
  |--------------------------------------------------------------------------
  | Módulo de Gestión de Maestros
  |--------------------------------------------------------------------------
  | Prefijo resultante: /app/academic/teachers/...
  | Nombre resultante:  app.academic.teachers....
  */

  Route::prefix('academic')->name('academic.')->group(function () {
      Route::middleware('can:teachers.view')->group(function () {
          Route::get('/teachers', TeacherIndex::class)->name('teachers.index');

          // 1. PRIMERO LAS RUTAS ESTÁTICAS
          Route::get('/teachers/create', TeacherForm::class)
              ->middleware('can:teachers.create')
              ->name('teachers.create');

          Route::get('/teachers/{teacher}/assignments', TeacherAssignments::class)
              ->middleware('can:teachers.assign_subjects')
              ->name('teachers.assignments');

          // 2. ÚLTIMO LAS RUTAS CON PARÁMETROS ({teacher})
          Route::get('/teachers/{teacher}/edit', TeacherForm::class)
              ->middleware('can:teachers.edit')
              ->name('teachers.edit');

          Route::get('/teachers/{teacher}', TeacherShow::class)
              ->name('teachers.show');
      });
  });
  ```

---

### 8.2 — Filtros

Siguiendo el mismo pipeline de `app/Filters/App/Students/`:

- [x] **`app/Filters/App/Teachers/SearchFilter.php`** — busca en `first_name`, `last_name`, `rnc`, `employee_code`:
- [x] **`app/Filters/App/Teachers/StatusFilter.php`** — filtra por `is_active` (`'1'` / `'0'` / `''`):
- [x] **`app/Filters/App/Teachers/EmploymentTypeFilter.php`** — filtra por `employment_type` (`'full_time'` / `'part_time'` / `''`):
- [x] **` app/Filters/App/Teachers/HasUserFilter.php`** — toggle: tiene / no tiene user_id:
- [x] **`app/Filters/App/Teachers/TeacherFilters.php`** — orquestador del pipeline:
---

### 8.3 — Configuración de Tabla

- [x] **Crear `app/Tables/App/TeacherTableConfig.php`** implementando la interfaz `TableConfig`:

---

### 8.4 — Componente Livewire `TeacherIndex`

- [x] **Crear `app/Livewire/App/Teachers/TeacherIndex.php`** extendiendo `DataTable`:
- [x] **Vista `resources/views/livewire/app/teachers/teacher-index.blade.php`:**

---

### 8.5 — Componente `TeacherForm` (Crear y Editar)

- [x] **Crear `app/Livewire/App/Teachers/TeacherForm.php`:**

- [x] **Vista `resources/views/livewire/app/teachers/teacher-form.blade.php`:**
  - Header con `x-ui.page-header`: título "Nuevo Maestro" / "Editar Maestro" + botón "Volver al Listado" con `href` al index.
  - Formulario dividido en **3 secciones** con `wire:submit.prevent="save"`:
    - **Datos Personales:** `first_name`, `last_name`, `gender` (radio M/F), `rnc`, `date_of_birth`, `phone`, `address`.
    - **Datos Laborales:** `specialization`, `employment_type` (select: Tiempo Completo / Tiempo Parcial), `hire_date`, `is_active` (toggle).
    - **Acceso al Sistema (Opcional):** toggle `create_user_account`. Si está activo, se despliegan con Alpine `x-show` los campos `email` y `password`. En modo edición, el campo `password` muestra hint "Dejar vacío para mantener la contraseña actual".
  - **Upload de Foto:** zona de arrastre similar a `StudentForm`, con preview y botón de eliminar.
  - Botones al final: "Cancelar" (link al index) y "Guardar Maestro" / "Actualizar Maestro".

---

### 8.6 — Componente `TeacherShow`

- [x] **Crear `app/Livewire/App/Teachers/TeacherShow.php`:**
  ```php
  namespace App\Livewire\App\Teachers;

  use App\Models\Tenant\Teacher;
  use Livewire\Component;
  use Livewire\WithPagination;

  class TeacherShow extends Component
  {
      use WithPagination;

      public Teacher $teacher;
      public string $activeTab = 'perfil';

      public function mount(Teacher $teacher): void
      {
          $this->teacher = $teacher->load(['user', 'assignments.subject', 'assignments.section']);
      }

      public function render()
      {
          return view('livewire.app.teachers.teacher-show')
              ->layout('layouts.app-module', config('modules.configuracion'));
      }
  }
  ```

- [x] **Vista `resources/views/livewire/app/teachers/teacher-show.blade.php`:**
  - **Header:** foto `x-ui.student-avatar` (reutilizada con prop genérica, o nuevo `x-ui.person-avatar`), nombre completo, badge estado activo/inactivo, badge tipo de contrato, badge especialización.
  - **Grid de stats** (4 cards): código de empleado, fecha de contratación, antigüedad calculada (`hire_date->diffForHumans()`), total de asignaciones activas.
  - **Tabs Alpine** con `activeTab`:
    - **Perfil:** datos completos (teléfono, dirección, cédula, fecha nacimiento, edad). Si tiene `user_id`, sección "Acceso al Sistema" con email y botón "Actualizar Credenciales" (abre modal inline).
    - **Asignaciones:** listado de `TeacherSubjectSection` del año activo agrupadas por sección. Cada fila: badge de materia (con `subject->color`), badge de sección. Botón "Gestionar Asignaciones" que navega a `teachers.assignments`.
    - **Historial:** placeholder para fases futuras — "Historial de asistencias registradas por este maestro estará disponible próximamente."
  - **Botones de acción en header:** "Editar" (link a `teachers.edit`), "Gestionar Asignaciones" (link a `teachers.assignments`).

---

### 8.7 — Componente `TeacherAssignments`

- [x] **Actualizar `app/Services/Teachers/TeacherAssignmentService.php`:**
  ```php
  namespace App\Services\Teachers;

  use App\Models\Tenant\Academic\AcademicYear;
  use App\Models\Tenant\Academic\Subject;
  use App\Models\Tenant\Academic\SchoolSection;
  use App\Models\Tenant\Academic\TeacherSubjectSection;
  use App\Models\Tenant\Teacher;
  use Illuminate\Support\Collection;

  class TeacherAssignmentService
  {
      /**
       * Asignar una materia a un maestro en una sección para el año activo.
       * Lanza excepción si ya existe la combinación (unique constraint).
       */
      public function assign(Teacher $teacher, int $subjectId, int $sectionId): TeacherSubjectSection
      {
          $year = AcademicYear::where('school_id', $teacher->school_id)
              ->where('is_active', true)
              ->firstOrFail();

          return TeacherSubjectSection::create([
              'teacher_id'        => $teacher->id,
              'subject_id'        => $subjectId,
              'school_section_id' => $sectionId,
              'academic_year_id'  => $year->id,
              'is_active'         => true,
          ]);
      }

      /**
       * Eliminar una asignación. No elimina físicamente si tiene registros de asistencia;
       * en ese caso la desactiva.
       */
      public function remove(TeacherSubjectSection $assignment): void
      {
          $hasAttendance = $assignment->classroomAttendanceRecords()->exists();

          if ($hasAttendance) {
              $assignment->update(['is_active' => false]);
          } else {
              $assignment->delete();
          }
      }

      /**
       * Obtener las materias disponibles para asignar a un maestro en una sección.
       * Solo muestra materias que la escuela tiene habilitadas y que el maestro
       * aún no tiene en esa sección en el año activo.
       */
      public function getAvailableSubjects(Teacher $teacher, int $sectionId): Collection
      {
          $section = SchoolSection::with('technicalTitle')->find($sectionId);
          $year    = AcademicYear::where('school_id', $teacher->school_id)
                        ->where('is_active', true)->first();

          $alreadyAssigned = TeacherSubjectSection::where('teacher_id', $teacher->id)
              ->where('school_section_id', $sectionId)
              ->where('academic_year_id', $year?->id)
              ->pluck('subject_id');

          return Subject::availableForSchool($teacher->school_id)
              ->whereNotIn('id', $alreadyAssigned)
              ->active()
              ->get();
      }
  }
  ```

- [x] **Crear `app/Livewire/App/Teachers/TeacherAssignments.php`:**
  ```php
  namespace App\Livewire\App\Teachers;

  use App\Models\Tenant\Academic\SchoolSection;
  use App\Models\Tenant\Academic\TeacherSubjectSection;
  use App\Models\Tenant\Teacher;
  use App\Services\Teachers\TeacherAssignmentService;
  use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
  use Livewire\Attributes\Computed;
  use Livewire\Component;

  class TeacherAssignments extends Component
  {
      use AuthorizesRequests;

      public Teacher $teacher;

      // Formulario de nueva asignación
      public int    $selectedSubjectId = 0;
      public int    $selectedSectionId = 0;

      public function mount(Teacher $teacher): void
      {
          $this->teacher = $teacher->load(['assignments.subject', 'assignments.section.grade']);
      }

      /**
       * Secciones disponibles en la escuela para el selector.
       */
      #[Computed]
      public function sections()
      {
          return SchoolSection::with(['grade', 'shift', 'technicalTitle'])
              ->where('school_id', $this->teacher->school_id)
              ->get();
      }

      /**
       * Materias disponibles en función de la sección seleccionada.
       * Se recalcula reactivamente cuando $selectedSectionId cambia.
       */
      #[Computed]
      public function availableSubjects()
      {
          if (! $this->selectedSectionId) return collect();

          return app(TeacherAssignmentService::class)
              ->getAvailableSubjects($this->teacher, $this->selectedSectionId);
      }

      /**
       * Asignaciones actuales agrupadas por sección para el panel izquierdo.
       */
      #[Computed]
      public function currentAssignments()
      {
          return TeacherSubjectSection::with(['subject', 'section.grade'])
              ->where('teacher_id', $this->teacher->id)
              ->where('is_active', true)
              ->get()
              ->groupBy('school_section_id');
      }

      public function assign(TeacherAssignmentService $service): void
      {
          $this->authorize('teachers.assign_subjects');

          $this->validate([
              'selectedSubjectId' => 'required|integer|exists:subjects,id',
              'selectedSectionId' => 'required|integer|exists:school_sections,id',
          ]);

          try {
              $service->assign($this->teacher, $this->selectedSubjectId, $this->selectedSectionId);
              $this->reset(['selectedSubjectId', 'selectedSectionId']);
              unset($this->currentAssignments, $this->availableSubjects);
              $this->dispatch('notify', type: 'success', message: 'Asignación creada correctamente.');
          } catch (\Illuminate\Database\QueryException $e) {
              // Violación del unique constraint
              $this->dispatch('notify', type: 'error', message: 'Esta asignación ya existe para el año activo.');
          }
      }

      public function remove(int $assignmentId, TeacherAssignmentService $service): void
      {
          $this->authorize('teachers.assign_subjects');

          $assignment = TeacherSubjectSection::findOrFail($assignmentId);
          $service->remove($assignment);

          unset($this->currentAssignments);
          $this->dispatch('notify', type: 'info', message: 'Asignación eliminada.');
      }

      public function render()
      {
          return view('livewire.app.teachers.teacher-assignments')
              ->layout('layouts.app-module', config('modules.configuracion'));
      }
  }
  ```

- [x] Realcion en `TeacherSubjectSection.php`:

```php
    /**
     * Registros de asistencia vinculados a esta asignación específica.
     */
    public function classroomAttendanceRecords(): HasMany
    {
        return $this->hasMany(ClassroomAttendanceRecord::class, 'teacher_subject_section_id');
    }

```

- [x] **Vista `resources/views/livewire/app/teachers/teacher-assignments.blade.php`:**
- [x] Actualizar:
 M app/Livewire/Admin/Schools/SchoolIndex.php
 M app/Livewire/Admin/Schools/SchoolShow.php
 M app/Livewire/App/Users/UserIndex.php
para que los datos de usuarios que tienen realcion con teacher y están inactivos no cuenten.

### Fase 8.8 — Integración de Autenticación Biométrica (QR Login)

*   **Rutas:** Se mantuvo la ruta `POST /login` apuntando a `AuthenticatedSessionController@store` para procesar ambos métodos de entrada.
*   **Validación:** Se actualizó `LoginRequest` para que `email` y `password` sean opcionales mediante `required_without:qr_code`.
*   **Controlador:** Se modificó `AuthenticatedSessionController` para buscar usuarios mediante la relación `teacher` o `student` usando el token del QR.
*   **Frontend:** Se integró la librería `html5-qrcode` en `login.blade.php` para gestionar el acceso a la cámara y la lectura del código.
*   **Interactividad:** Se implementó un componente Alpine.js (`qrLogin`) que coordina la apertura del modal, el escaneo y el envío automático del formulario.
*   **Sincronización:** Se añadió un input oculto y un retraso de 100ms en el script para asegurar que el valor del QR se procese antes del `submit`.
*   **Interfaz:** Se sustituyó el toggle de modo oscuro en el layout de invitados por un botón de acceso rápido al escáner institucional.
*   **Seguridad:** Se mantuvo el flujo original de Breeze como respaldo (fallback) en caso de que el escaneo falle o el usuario prefiera entrada manual.

---

## Fase 9 — Registro de Asistencia de Plantel (Interfaz)
**Rama:** `feature/plantel-attendance-ui`

### 9.1 — Rutas

- [x] **Crear `routes/app/attendance.php`:**
  ```php
  Route::middleware('can:attendance_plantel.view')->group(function () {
      Route::get('/attendance', AttendanceDashboard::class)->name('attendance.dashboard');
      Route::get('/attendance/history', PlantelAttendanceIndex::class)->name('attendance.history');
  });

  Route::middleware('can:attendance_plantel.open_session')->group(function () {
      Route::get('/attendance/session', AttendanceSession::class)->name('attendance.session');
  });

  Route::middleware('can:attendance_plantel.qr')->group(function () {
      Route::get('/attendance/qr', QrScanner::class)->name('attendance.qr');
  });

  Route::middleware('can:attendance_plantel.facial')->group(function () {
      Route::get('/attendance/facial', FacialScanner::class)->name('attendance.facial');
  });
  ```

### 9.2 — Apertura de Sesión del Día

- [x] **Crear `app/Livewire/App/Attendance/AttendanceSessionManager.php`:**
  - Muestra sesiones abiertas del día (por tanda)
  - Método `openSession(int $shiftId)`: llama `PlantelAttendanceService::openDailySession()`; guard: `can:attendance_plantel.open_session`
  - Método `closeSession(int $sessionId)`: llama `PlantelAttendanceService::closeDailySession()` → calcula stats finales; guard: `can:attendance_plantel.close_session`
  - Método `markAllAbsences(int $sessionId)`: llama `PlantelAttendanceService::markAbsences()` — registra ausentes a todos los que no tienen registro; requiere confirmación modal
  - `->layout('layouts.app-module', config('modules.asistencia'))`

- [x] **Vista `resources/views/livewire/app/attendance/session-manager.blade.php`:**
  - Banner informativo si no hay sesión abierta para hoy: "No se ha abierto la asistencia del día. Inicia la sesión para comenzar el registro."
  - Por cada tanda: card con nombre de tanda, horario, estado (abierta/cerrada), hora de apertura, contador en tiempo real `N / M estudiantes`
  - Botón "Abrir Sesión" si no hay sesión de esa tanda
  - Botón "Cerrar Sesión" + "Marcar Ausentes Faltantes" si hay sesión abierta
  - Modal de confirmación para Cerrar Sesión con resumen de estadísticas

- Crear metodo  public function scopeInShift($query, $shiftId) para hacer filtro de estudainte en la tanda de la seccion, utilizado en el servicio de `PlantelAttendanceService.php` para que no solo busque los activos sino tambien en las tandas.

- Crear scope de public function scopeWithIndexRelations($query) en `DailyAttendanceSession.php` para enviar las relaciones correctas.

- Agregar en time en protected $casts para especificar el tipo de dato.

### 9.3 — Registro Manual (Lista de Estudiantes)

- [x] **Componente `app/Livewire/App/Attendance/ManualAttendance.php`:**
  - **Gestión de Sesión:** Carga la sesión activa del día (`DailyAttendanceSession`) filtrada por la tanda seleccionada (`selectedShiftId`) y el centro educativo del usuario autenticado.
  - **Sistema de Filtros:** - Implementa búsqueda por nombre/RNC, filtrado por sección y estado de asistencia (`pending`, `registered`).
    - Integra `ExcuseService` para identificar estudiantes con excusas vigentes y permite ocultarlos mediante el filtro `hide_excused` (activo por defecto).
  - **Propiedades Computadas:**
    - `students()`: Retorna una colección paginada enriquecida dinámicamente con el estado de asistencia, hora, método de registro y verificación de excusa.
    - `statistics()`: Calcula en tiempo real el total de registrados, presentes, ausentes, tardanzas y excusados de la tanda actual.
  - **Métodos de Acción:**
    - `record(int $studentId, string $status)`: Valida permisos, verifica sesión activa y procesa el registro mediante `PlantelAttendanceService`. Emite eventos de notificación y `student-recorded`.
    - `clearAllFilters()`: Resetea el estado de búsqueda y filtros a sus valores iniciales.

- [x] **Vista `resources/views/livewire/app/attendance/manual-attendance.blade.php`:**
  - **Header Dinámico:**
    - Muestra `x-ui.page-header` con conteo total de estudiantes.
    - Botón de acción polimórfico: "Abrir Sesión" (Primary) o "Cerrar Sesión" (Error Outline) según el estado de `$activeSession`.
  - **Barra de Herramientas:**
    - Input de búsqueda con debounce y selectores para Secciones y Estados.
    - Botón "Limpiar" (`funnel-minus`) que aparece condicionalmente cuando hay filtros activos.
  - **Interfaz de Usuario (Tabla/Grid):**
    - Listado de estudiantes con visualización de estado de asistencia y validación visual de excusas.
    - Acciones por fila para marcar estados (Presente, Tardanza, Ausente).
  - **Footer de Estadísticas Minimalista (Responsive):**
    - Organización mediante Badges: Texto plano seguido de `x-ui.badge` para Total, Presentes, Ausentes, Tardes y Excusas.
    - Soporte para scroll horizontal en dispositivos móviles (`overflow-x-auto`).
    - Barra de progreso visual con porcentaje de completado y hora actual del sistema.


Aquí tienes la continuación para el archivo `.md`, siguiendo la estructura y el estilo técnico de las secciones anteriores.

---

### 9.3.A — Hub de Gestión y Auditoría (Interfaz)

**Objetivo:** Implementar un centro de mando para la supervisión de asistencia que permita la navegación histórica mediante un calendario interactivo y la visualización de métricas por tanda (mañana/tarde/noche) en tiempo real.

#### 1. — Ruta
**Archivo:** `routes/app/attendance.php`
```php
Route::middleware('can:attendance_plantel.view')->group(function () {
    // ... rutas anteriores
    Route::get('/attendance/hub', AttendanceSessionHub::class)->name('attendance.hub');
});
```

#### 2. — Componente (Lógica)
**Archivo:** `app/Livewire/App/Attendance/AttendanceSessionHub.php`
- **Gestión de Estado:** Implementación de propiedad reactiva `$date` sincronizada con la URL para permitir navegación persistente.
- **Calendario Dinámico:** Generación de un grid de días con indicadores de estado (`success` para cierres normales, `warning` para sesiones abiertas y `error` para ausentismo crítico >20%).
- **Cálculo de Métricas:** Uso de propiedades computadas (`#[Computed]`) para agrupar estadísticas de `presentes`, `tardanzas`, `ausentes` y `excusados` sumando los datos de las sesiones del día seleccionado.
- **Auto-selección:** Lógica en `mount` y `updated` para seleccionar automáticamente la primera tanda disponible al cambiar de fecha.

#### 3. — Vista (UI)
**Archivo:** `resources/views/livewire/app/attendance/attendance-session-hub.blade.php`
- **Layout Bilateral:** Diseño responsivo con calendario a la izquierda (1/3) y detalle de sesiones a la derecha (2/3).
- **Selector de Tandas:** Grid inteligente que adapta sus columnas según la cantidad de jornadas (Matutina, Vespertina, Nocturna) del plantel.
- **Visualización de Progreso:** Barra segmentada con transiciones de CSS para representar la distribución porcentual de los estados de asistencia.
- **Acciones Contextuales:** Botón dinámico que alterna entre "Gestionar" (para sesiones abiertas) y "Auditar" (para sesiones cerradas), redirigiendo al usuario según el estado de la tanda.
- **Modo Oscuro:** Soporte nativo utilizando los tokens de color del sistema de diseño (bordes `white/10` y fondos `dark-card`).

#### 9.3.B — Auditoría de Asistencia (Interfaz y Lógica)
**Rama:** `feature/plantel-attendance-audit`

### 1. — Ruta
**Archivo:** `routes/app/attendance.php`
```php
Route::middleware('can:attendance_plantel.view')->group(function () {
    // ... rutas anteriores
    Route::get('/attendance/audit/{sessionId}', AttendanceAudit::class)->name('attendance.audit');
});
```

### 2. — Componente (Lógica)
**Archivo:** `app/Livewire/App/Attendance/AttendanceAudit.php`
- **Protección de Integridad:** Implementa una validación estricta en el `mount` para asegurar que solo se puedan auditar sesiones con `closed_at`, previniendo discrepancias antes del cierre oficial.
- **Caché en Memoria:** Uso de la propiedad `$cachedRecords` para evitar consultas redundantes a la base de datos durante el ciclo de vida de la petición, optimizando el rendimiento al filtrar o recalcular estadísticas.
- **Transaccionalidad Atómica:** El método `updateStatus` envuelve la actualización del registro individual y el ajuste de los contadores en la sesión (`DailyAttendanceSession`) dentro de una transacción de base de datos (`DB::transaction`) para garantizar la consistencia de los reportes.
- **Mapeo de Estados Dinámico:** Centralización de colores, iconos y etiquetas mediante propiedades computadas y métodos auxiliares, facilitando el mantenimiento visual del sistema.

### 3. — Vista (UI)
**Archivo:** `resources/views/livewire/app/attendance/attendance-audit.blade.php`
- **Banner de Modo Crítico:** Incorpora un aviso visual con animación de pulso para alertar al usuario que se encuentra en un modo de edición que afecta directamente los reportes finales.
- **Dashboard de Micro-Filtros:** Sistema de "cards" interactivas que funcionan como filtros rápidos por estado (Presente, Tardanza, Ausente, Excusado), mostrando conteos en tiempo real con bordes acentuados según la categoría.
- **Grid de Auditoría:** Layout de tarjetas compactas optimizado para visualización masiva, que prioriza el avatar del estudiante y su estado actual.
- **Overlay de Control Rápido:** Implementación de un menú flota  nte táctil que aparece al hacer *hover* o *focus*, permitiendo el cambio de estado con un solo clic sin salir de la vista general.
- **Seguridad Visual para Excusas:** Los registros marcados como "Excusados" (provenientes de procesos administrativos) se presentan en modo de solo lectura con un diseño diferenciado para evitar modificaciones accidentales desde la auditoría de planta.


### 9.4 — Arquitectura Unificada: `AttendanceScanner` (Híbrido QR/Facial)

El sistema ahora opera bajo un único componente central que gestiona el estado y la lógica, delegando la visualización a sub-componentes de Blade.

#### 1. Lógica Central (`AttendanceScanner.php`)
- **Control de Estado Único:** Usa la propiedad `public string $mode = 'qr'` para alternar entre `'qr'` y `'facial'` sin recargar la página.
- **Gestión de Sesión:** Centraliza la verificación de `DailyAttendanceSession`. Si no hay sesión, el componente bloquea la interacción en ambos modos.
- **Procesamiento de Identidad:**
  - **Para QR:** Recibe el `decodedText`, busca al estudiante por su `qr_code` y registra la asistencia.
  - **Para Facial:** Recibe la `capturedPhoto`, la envía al microservicio de Python vía `FaceEncodingManager`, y si hay un *match* de alta confianza, registra la asistencia.
- **Feedback Unificado:** Ambos métodos alimentan la misma propiedad `$lastRegistered` y el array `$recentScans`, manteniendo la interfaz actualizada sin importar el método de entrada.

#### 2. Componentes de Vista (Blade & Alpine.js)
La vista se dividió para ser mantenible y escalable:

- **`attendance-scanner.blade.php` (El Orquestador):**
  - Implementa el **Layout 60/40** que pediste.
  - Gestiona los avisos globales (como el banner de "Sin sesión activa").
  - Contiene los botones de acción para abrir/cerrar sesiones y cambiar manualmente entre modos.

- **`scanner-visor.blade.php` (El "Ojo" del sistema):**
  - **Carga Condicional:** Si el modo es `qr`, inicializa `Html5Qrcode`. Si es `facial`, activa el `video` stream y el `canvas`.
  - **Inteligencia en el Cliente:** - En **QR**: Pausa el escáner al detectar un código para evitar registros múltiples y lo resume tras 2.5 segundos.
    - En **Facial**: Tiene un intervalo de detección que dibuja un recuadro guía y dispara la captura (`captureFace`) solo cuando detecta presencia, subiendo el `blob` automáticamente a Livewire.

- **`scanner-stats.blade.php` (El Panel de Control 40%):**
  - **Preview en Tiempo Real:** Muestra la foto grande, nombre y estado (`Presente`, `Tarde`) del último estudiante.
  - **Historial Dinámico:** Renderiza los últimos 10 escaneos con timestamps formateados y badges de colores según el estado de la asistencia.

### 9.5 Extras

- Agregar docs/modules con verion_0.4.0.md (tambien el 0.2.0 y 0.3.0) con resumen de cada fase, objetivos, retos y aprendizajes.
- Actualiar .gitignore para excluir los archivos de documentación de módulos.
- Crear archivo CLAUDE.md con instrucciones para el uso de Claude en el proyecto, incluyendo ejemplos de prompts para cada tipo de tarea (desarrollo, documentación, testing, etc).



---

## Fase 10 — Registro de Asistencia de Aula (Interfaz del Maestro)
**Rama:** `feature/classroom-attendance-ui`

### 10.1 — Rutas

- [ ] **Agregar en `routes/app/attendance.php`:**
  ```php
  Route::middleware('can:attendance_classroom.record')->group(function () {
      Route::get('/attendance/classroom', ClassroomAttendanceLive::class)->name('attendance.classroom.live');
  });

  Route::middleware('can:attendance_classroom.view')->group(function () {
      Route::get('/attendance/classroom/history', ClassroomAttendanceHistory::class)->name('attendance.classroom.history');
  });
  ```

### 10.2 — Vista del Maestro (Pase de Lista)

- [ ] **Crear `app/Livewire/App/Attendance/ClassroomAttendanceLive.php`:**
  - `mount()`: carga las asignaciones del maestro autenticado para el año activo (`Teacher::where('user_id', auth()->id())`)
  - Propiedades: `selectedAssignmentId`, `selectedDate`, `studentStatuses` (array `[id => status]`), `showDiscrepancyWarnings` (array de warnings de validación cruzada)
  - Método `loadStudents()`: carga estudiantes de la sección de la asignación seleccionada + verifica registro de plantel de cada uno
  - Método `setStatus(int $studentId, string $status)`:
    1. Llama `ClassroomAttendanceService::validateCrossAttendance()` silenciosamente
    2. Si estudiante ausente en plantel → añade warning a `$showDiscrepancyWarnings` (no bloquea, pero advierte)
    3. Si estudiante marcado ausente/excused en plantel y se intenta `present` → lanza error
    4. Actualiza `$studentStatuses[$studentId]`
  - Método `saveAttendance()`: llama `ClassroomAttendanceService::takeClassAttendance()` con el array completo; notifica cuántos se guardaron
  - `->layout('layouts.app-module', config('modules.asistencia'))`

- [ ] **Vista `resources/views/livewire/app/attendance/classroom-attendance-live.blade.php`:**
  - Selector de asignación (dropdown con "Materia — Sección" como label)
  - Selector de fecha (default hoy)
  - Panel de advertencias de validación cruzada: banner amarillo "Los siguientes estudiantes no han registrado entrada al plantel hoy: [nombres]" — no bloquea pero es visible
  - Lista de estudiantes con:
    - Foto + nombre + estado plantel (badge pequeño: 🟢/🟡/🔴/⚫ según registro de plantel)
    - Botones de estado de aula: Presente / Ausente / Tardanza / Excusado
    - Si el estado de plantel es `absent` o `excused`, el botón "Presente" aparece deshabilitado con tooltip "Ausente en plantel"
  - Botón "Guardar Pase de Lista" al final + confirmación si quedan estudiantes sin marcar

---

## Fase 11 — Gestión de Excusas (Interfaz)
**Rama:** `feature/excuses-ui`

### 11.1 — Rutas

- [x] **Agregar en `routes/app/attendance.php`:**
  ```php
  Route::prefix('excuses')->name('excuses.')->group(function () {
      Route::get('/', ExcuseIndex::class)->middleware('can:excuses.view')->name('index');
  });
  ```

### 11.2 — Componentes

- [x] **Crear `app/Livewire/App/Attendance/ExcuseIndex.php`:**
  - Lista todas las excusas con filtros: por estudiante (búsqueda), por estado (pending/approved/rejected), por rango de fechas
  - Método `submit(array $data)`: llama `ExcuseService::submitExcuse()` — guard: `can:excuses.submit`
  - Método `approve(int $excuseId, ?string $notes)`: guard: `can:excuses.approve`
  - Método `reject(int $excuseId, string $notes)`: requiere notas + guard: `can:excuses.reject`
  - Slide-over panel para crear excusa nueva: búsqueda de estudiante, tipo, rango de fechas, motivo, adjunto (opcional)

- [x] **Vista con tabla** `x-data-table.base-table` con columnas: estudiante, tipo, rango de fechas, estado (badge), registrado por, fecha registro, acciones (aprobar/rechazar si pending)

---

## Fase 12 — Arquitectura Offline / Sincronización
**Rama:** `feature/offline-sync`

Esta fase implementa el soporte para que el módulo de asistencia funcione sin internet y sincronice cuando haya conexión.

### 12.1 — Variable de Entorno y Configuración

- [ ] **Agregar a `.env.example`:**
  ```
  # APP_MODE=cloud (VPS en internet) | APP_MODE=local (PC del centro)
  APP_MODE=cloud

  # Solo relevante en APP_MODE=local
  SYNC_API_URL=https://orvian.tudominio.com
  SYNC_API_KEY=shared-secret-for-sync
  SYNC_INTERVAL_MINUTES=5
  ```

- [ ] **Crear `config/orvian.php`** (o agregar a uno existente):
  ```php
  'app_mode' => env('APP_MODE', 'cloud'), // 'cloud' | 'local'
  'sync' => [
      'api_url'  => env('SYNC_API_URL'),
      'api_key'  => env('SYNC_API_KEY'),
      'interval' => env('SYNC_INTERVAL_MINUTES', 5),
  ],
  ```

- [ ] **Helper `app()->isLocalMode(): bool`** — `config('orvian.app_mode') === 'local'`

### 12.2 — Columna `synced_at` en Tablas de Asistencia

- [ ] **Crear migración `add_sync_columns_to_attendance_tables`:**
  ```php
  // Aplicar a: plantel_attendance_records, classroom_attendance_records,
  //            daily_attendance_sessions, attendance_excuses

  $table->timestamp('synced_at')->nullable()->after('updated_at');
  $table->string('sync_status', 20)->default('pending')->after('synced_at');
  // sync_status: 'pending' | 'synced' | 'failed' | 'conflict'

  $table->index(['sync_status', 'created_at']);
  ```

### 12.3 — Modelo: Cola de Sincronización

- [ ] **Crear migración `create_sync_queue_table`:**
  ```php
  Schema::create('sync_queue', function (Blueprint $table) {
      $table->id();
      $table->string('model_type', 100); // Nombre completo del modelo (morphable)
      $table->unsignedBigInteger('model_id');
      $table->enum('operation', ['create', 'update', 'delete']);
      $table->json('payload');           // Snapshot del registro al momento de encolarse
      $table->integer('attempts')->default(0);
      $table->timestamp('last_attempt_at')->nullable();
      $table->text('last_error')->nullable();
      $table->timestamp('synced_at')->nullable();
      $table->timestamps();

      $table->index(['model_type', 'synced_at']);
      $table->index(['synced_at', 'attempts']);
  });
  ```

- [ ] **Modelo `App\Models\SyncQueue`:**
  ```php
  protected $casts = ['payload' => 'array'];

  public function scopePending($query)
  {
      return $query->whereNull('synced_at')->where('attempts', '<', 5);
  }

  public function scopeFailed($query)
  {
      return $query->whereNull('synced_at')->where('attempts', '>=', 5);
  }
  ```

### 12.4 — Observer: Encolar Cambios Automáticamente

- [ ] **Crear `app/Observers/SyncObserver.php`:**
  ```php
  namespace App\Observers;

  class SyncObserver
  {
      // Solo actúa en modo local
      protected function shouldSync(): bool
      {
          return config('orvian.app_mode') === 'local';
      }

      public function created($model): void
      {
          if (!$this->shouldSync()) return;

          SyncQueue::create([
              'model_type' => get_class($model),
              'model_id'   => $model->id,
              'operation'  => 'create',
              'payload'    => $model->toArray(),
          ]);
      }

      public function updated($model): void
      {
          if (!$this->shouldSync()) return;

          SyncQueue::create([
              'model_type' => get_class($model),
              'model_id'   => $model->id,
              'operation'  => 'update',
              'payload'    => $model->toArray(),
          ]);
      }
  }
  ```

- [ ] **Registrar en `AppServiceProvider::boot()` solo para los modelos de asistencia:**
  ```php
  if (config('orvian.app_mode') === 'local') {
      PlantelAttendanceRecord::observe(SyncObserver::class);
      ClassroomAttendanceRecord::observe(SyncObserver::class);
      DailyAttendanceSession::observe(SyncObserver::class);
      AttendanceExcuse::observe(SyncObserver::class);
  }
  ```

### 12.5 — Servicio de Sincronización

- [ ] **Crear `app/Services/Sync/SyncManager.php`:**
  ```php
  namespace App\Services\Sync;

  use App\Models\SyncQueue;
  use Illuminate\Support\Facades\Http;

  class SyncManager
  {
      protected string $apiUrl;
      protected string $apiKey;

      public function __construct()
      {
          $this->apiUrl = config('orvian.sync.api_url');
          $this->apiKey = config('orvian.sync.api_key');
      }

      /**
       * Verificar conectividad con el servidor cloud
       */
      public function isConnected(): bool
      {
          try {
              $response = Http::timeout(5)->get("{$this->apiUrl}/api/sync/health");
              return $response->successful();
          } catch (\Exception $e) {
              return false;
          }
      }

      /**
       * Sincronizar todos los registros pendientes
       * Devuelve [synced => N, failed => M]
       */
      public function syncPending(): array
      {
          if (!$this->isConnected()) {
              Log::warning('[Sync] No hay conexión con el servidor cloud. Se reintentará en el próximo ciclo.');
              return ['synced' => 0, 'failed' => 0];
          }

          $pending = SyncQueue::pending()->orderBy('created_at')->limit(100)->get();
          $synced = 0;
          $failed = 0;

          foreach ($pending as $item) {
              try {
                  $response = Http::withHeaders(['X-Sync-Key' => $this->apiKey])
                      ->timeout(10)
                      ->post("{$this->apiUrl}/api/sync/receive", [
                          'model_type' => $item->model_type,
                          'model_id'   => $item->model_id,
                          'operation'  => $item->operation,
                          'payload'    => $item->payload,
                          'school_id'  => $item->payload['school_id'] ?? null,
                      ]);

                  if ($response->successful()) {
                      $item->update([
                          'synced_at'       => now(),
                          'last_attempt_at' => now(),
                      ]);
                      $synced++;
                  } else {
                      $this->markFailed($item, $response->body());
                      $failed++;
                  }
              } catch (\Exception $e) {
                  $this->markFailed($item, $e->getMessage());
                  $failed++;
              }
          }

          Log::info("[Sync] Ciclo completado: {$synced} sincronizados, {$failed} fallidos.");
          return compact('synced', 'failed');
      }

      protected function markFailed(SyncQueue $item, string $error): void
      {
          $item->update([
              'attempts'        => $item->attempts + 1,
              'last_attempt_at' => now(),
              'last_error'      => $error,
          ]);
      }
  }
  ```

### 12.6 — Comando Artisan de Sincronización

- [ ] **Crear `app/Console/Commands/SyncAttendance.php`:**
  ```php
  namespace App\Console\Commands;

  use App\Services\Sync\SyncManager;

  #[AsCommand(name: 'orvian:sync-attendance')]
  class SyncAttendance extends Command
  {
      protected $signature   = 'orvian:sync-attendance {--force : Forzar sincronización incluso si APP_MODE=cloud}';
      protected $description = 'Sincroniza los registros de asistencia pendientes con el servidor cloud';

      public function handle(SyncManager $sync): int
      {
          if (config('orvian.app_mode') !== 'local' && !$this->option('force')) {
              $this->info('APP_MODE=cloud. No se requiere sincronización. Usa --force para ejecutar igualmente.');
              return Command::SUCCESS;
          }

          $this->info('[Sync] Iniciando sincronización de asistencia...');
          $result = $sync->syncPending();
          $this->info("[Sync] ✓ {$result['synced']} sincronizados, {$result['failed']} fallidos.");

          return Command::SUCCESS;
      }
  }
  ```

- [ ] **Registrar en `routes/console.php`:**
  ```php
  if (config('orvian.app_mode') === 'local') {
      Schedule::command('orvian:sync-attendance')
          ->everyFiveMinutes()
          ->withoutOverlapping()
          ->runInBackground();
  }
  ```

### 12.7 — Endpoint de Recepción (Servidor Cloud)

- [ ] **Crear `routes/api.php` (o en `routes/admin/sync.php`):**
  ```php
  Route::middleware('sync.auth')->prefix('sync')->group(function () {
      Route::get('/health', fn() => response()->json(['status' => 'ok']));
      Route::post('/receive', SyncReceiveController::class);
  });
  ```

- [ ] **Crear `app/Http/Middleware/SyncAuthMiddleware.php`:**
  - Valida header `X-Sync-Key` contra `config('orvian.sync.api_key')`

- [ ] **Crear `app/Http/Controllers/Sync/SyncReceiveController.php`:**
  - Recibe `model_type`, `operation`, `payload`, `school_id`
  - Usa `$payload['school_id']` para asignar `setPermissionsTeamId()` correcto
  - Para `create`: `$modelType::updateOrCreate(['id' => $payload['id']], $payload)`
  - Para `update`: `$modelType::where('id', $payload['id'])->update($payload)`
  - Marca `sync_status = 'synced'` en el servidor cloud también
  - Retorna `200 OK` o `422` si hay error de validación

### 12.8 — Indicador de Estado de Sincronización en UI

- [ ] **Crear `app/Livewire/Shared/SyncStatusIndicator.php`** (componente pequeño):
  - Solo visible si `APP_MODE=local`
  - `wire:poll.30s` para actualizar estado
  - Muestra: `SyncQueue::pending()->count()` registros pendientes, última sincronización exitosa, estado de conectividad

- [ ] **Vista**: badge compacto para el `module-toolbar` de las vistas de asistencia
  - 🟢 "Sincronizado" — pendientes = 0
  - 🟡 "N pendientes" — hay registros por sincronizar
  - 🔴 "Sin conexión" — último intento fallido hace > 10 min

- [ ] **Embeber en `module-toolbar` de las vistas de asistencia** en el slot `secondary`

---

## Fase 13 — Microservicio de Reconocimiento Facial (Python)
**Rama:** `feature/facial-recognition-microservice`
**Repositorio separado:** `orvian-facial-recognition`

### 13.1 — Estructura del Proyecto Python

- [ ] Crear repositorio `orvian-facial-recognition` con estructura:
  ```
  orvian-facial-recognition/
  ├── app/
  │   ├── main.py              # Punto de entrada FastAPI
  │   ├── config.py            # Configuración vía pydantic-settings
  │   ├── models/schemas.py    # Pydantic schemas de request/response
  │   ├── services/
  │   │   ├── face_detection.py    # Detección de rostros (HOG/CNN)
  │   │   ├── face_encoding.py     # Generación de encodings 128-dim
  │   │   └── face_matching.py     # Comparación y búsqueda del mejor match
  │   ├── routers/
  │   │   ├── health.py
  │   │   ├── enroll.py        # POST /api/v1/enroll/
  │   │   └── verify.py        # POST /api/v1/verify/
  │   └── utils/image_processing.py
  ├── tests/
  │   ├── test_face_detection.py
  │   └── test_face_matching.py
  ├── requirements.txt
  ├── Dockerfile
  ├── docker-compose.yml
  └── .env.example
  ```

### 13.2 — Dependencias (`requirements.txt`)

- [ ] Definir dependencias exactas:
  ```
  fastapi==0.110.0
  uvicorn[standard]==0.27.1
  python-multipart==0.0.9
  face-recognition==1.3.0
  opencv-python==4.9.0.80
  numpy==1.26.4
  pillow==10.2.0
  python-dotenv==1.0.1
  pydantic==2.6.1
  pydantic-settings==2.1.0
  redis==5.0.1
  ```

### 13.3 — Configuración (`app/config.py`)

- [ ] `Settings` con `pydantic_settings.BaseSettings`:
  - `API_KEY`: requerido, valida header de autenticación
  - `ALLOWED_ORIGINS`: list[str]
  - `FACE_DETECTION_MODEL`: `"hog"` (CPU) o `"cnn"` (GPU, más preciso)
  - `FACE_ENCODING_MODEL`: `"large"` o `"small"`
  - `TOLERANCE`: float, default `0.6` (menor = más estricto)
  - `MAX_IMAGE_SIZE`: 5MB por defecto
  - `REDIS_HOST`, `REDIS_PORT`, `REDIS_DB`: para cache de encodings (opcional)

### 13.4 — Servicios Python

- [ ] **`FaceDetectionService`:**
  - `detect_faces(image_bytes) → List[Tuple]`: usa `face_recognition.face_locations(model=self.model)`
  - `has_single_face(image_bytes) → bool`
  - `get_largest_face(image_bytes) → Optional[Tuple]`: devuelve el más grande si hay varios

- [ ] **`FaceEncodingService`:**
  - `generate_encoding(image_bytes) → Optional[List[float]]`: devuelve lista de 128 floats o `None` si no detecta rostro
  - `generate_multiple_encodings(image_bytes, num_jitters=10) → List[List[float]]`: para enrollment más robusto

- [ ] **`FaceMatchingService`:**
  - `compare_faces(known, unknown) → Dict`: retorna `{match: bool, distance: float, confidence: float}`
  - `find_best_match(unknown_encoding, known_encodings) → Optional[Dict]`: itera lista de `{id, name, encoding}` y devuelve el mejor match dentro de tolerancia

### 13.5 — Endpoints

- [ ] **`GET /health`** → `{"status": "healthy", "version": "1.0.0"}`
- [ ] **`POST /api/v1/enroll/`** (multipart):
  - Params: `student_id`, `school_id`, imagen en `image`
  - Respuesta: `{success, student_id, encoding: [128 floats], faces_detected, message}`
  - Casos de error: sin rostro, múltiples rostros, fallo de encoding
- [ ] **`POST /api/v1/verify/`** (multipart):
  - Params: `school_id`, `known_encodings: [{id, name, encoding}]` (JSON), imagen en `image`
  - Respuesta: `{success, matched, student_id?, student_name?, confidence?, distance?, faces_detected, message}`

### 13.6 — Dockerización

- [ ] **`Dockerfile`** basado en `python:3.11-slim` con dependencias del sistema: `build-essential`, `cmake`, `libopenblas-dev`, `liblapack-dev`
- [ ] **`docker-compose.yml`** con servicios `facial-api` (puerto 8001) y `redis` (puerto 6379)

### 13.7 — Cliente HTTP en Laravel

- [ ] **Crear `app/Services/FacialRecognition/FacialApiClient.php`:**
  - `health() → array`
  - `enrollFace(int $studentId, int $schoolId, UploadedFile $image) → array`
  - `verifyFace(int $schoolId, array $knownEncodings, UploadedFile $image) → array`
  - Timeout: 5s en health, 30s en enroll/verify
  - Lanza `\Exception` si `!$response->successful()`

- [ ] **Crear `app/Services/FacialRecognition/FaceEncodingManager.php`:**
  - `enrollStudent(Student $student, UploadedFile $photo) → bool`: llama API, guarda encoding en DB si éxito
  - `identifyStudent(int $schoolId, UploadedFile $photo) → ?array`: carga todos los encodings activos del school + llama verify + retorna `{student_id, student_name, confidence, distance}`
  - `isServiceHealthy() → bool`

- [ ] **Agregar a `config/services.php`:**
  ```php
  'facial_api' => [
      'url' => env('FACIAL_API_URL', 'http://localhost:8001'),
      'key' => env('FACIAL_API_KEY'),
  ],
  ```

- [ ] **Actualizar `.env.example`** con `FACIAL_API_URL` y `FACIAL_API_KEY`

---

## Fase 14 — Dashboard Operativo de Asistencia (Doble Visión)
**Rama:** `feature/attendance-dashboard`

Este es el dashboard central del módulo. Requiere todas las fases anteriores completadas.

### 14.1 — Componente Principal

- [ ] **Crear `app/Livewire/App/Attendance/AttendanceDashboard.php`:**
  - `wire:poll.10s` para actualización en tiempo real durante el día
  - Propiedades: `selectedDate` (default hoy), `selectedSection` (nullable), `selectedShift` (nullable)
  - Método `loadPlantelStats()`: cuenta por estado desde `PlantelAttendanceRecord` del día
  - Método `loadClassroomStats()`: cuenta por estado desde `ClassroomAttendanceRecord` del día
  - Método `loadDiscrepancies()`: llama `ClassroomAttendanceService::detectDiscrepancies()`
  - Método `loadRecentActivity()`: últimos 15 registros de plantel ordenados por `time` DESC
  - `->layout('layouts.app-module', config('modules.asistencia'))`

### 14.2 — Vista del Dashboard

- [ ] **Vista `resources/views/livewire/app/attendance/attendance-dashboard.blade.php`:**

  **Sección 1 — Estado del Día:**
  - Banner si no hay sesión abierta: "No se ha abierto la asistencia de hoy. [Abrir Sesión]"
  - Banner de modo: si `APP_MODE=local`, mostrar `x-attendance.sync-indicator` en posición fija

  **Sección 2 — Métricas Duales (side by side):**
  - **Panel Plantel** (`bg-blue-*/10`): total esperado, presentes, tardanzas, ausentes, tasa de asistencia
  - **Panel Aula** (`bg-purple-*/10`): total clases registradas hoy, presentes, ausentes, tardanzas
  - Cada panel tiene mini barra de progreso visual

  **Sección 3 — Panel de Discrepancias (Pasilleo):**
  - Solo visible si hay discrepancias
  - Tabla: foto + nombre del estudiante, estado en plantel (badge), N clases ausente en aula, botón "Ver Detalle"
  - Badge de alerta en la pestaña de navegación cuando hay discrepancias

  **Sección 4 — Gráficos (ApexCharts):**
  - **Donut:** distribución de estados de plantel (presente/tardanza/ausente) — se actualiza con polling
  - **Línea:** tasa de asistencia de plantel de los últimos 7 días escolares
  - `wire:ignore` en los contenedores de gráficos + listener `Livewire.hook` para actualizar series sin re-renderizar

  **Sección 5 — Timeline de Actividad Reciente:**
  - Lista de últimas 15 entradas al plantel: avatar + nombre + hora + método (badge QR/Facial/Manual) + estado (badge)
  - Auto-actualiza con polling

### 14.3 — Instalación de ApexCharts

- [ ] `npm install apexcharts` + importar en `resources/js/app.js`: `window.ApexCharts = ApexCharts`
- [ ] Alpine component `attendanceDonutChart()` y `attendanceLineChart()` definidos en `resources/js/charts/attendance-charts.js`

---

## Fase 15 — Historial y Reportes de Asistencia
**Rama:** `feature/attendance-reports`

### 15.1 — Historial de Plantel

- [ ] **Crear `app/Livewire/App/Attendance/PlantelAttendanceIndex.php`** extendiendo `DataTable`:
  - Filtros: rango de fechas, sección, estado, método, verificado/pendiente
  - `->layout('layouts.app-module', config('modules.asistencia'))`
  - Acción `edit(int $recordId, string $newStatus, ?string $notes)`: guard `can:attendance_plantel.verify`
  - Acción `verify(int $recordId)`: marca `verified_at = now()`, `verified_by = auth()->id()`
  - Exportar a Excel

### 15.2 — Historial de Aula

- [ ] **Crear `app/Livewire/App/Attendance/ClassroomAttendanceHistory.php`** extendiendo `DataTable`:
  - Filtros: rango de fechas, maestro, materia, sección, estado
  - Solo puede ver registros propios si el usuario es maestro (`Teacher::where('user_id', auth()->id())`)
  - Director/Coordinador ve todo

### 15.3 — Componente de Reportes

- [ ] **Crear `app/Livewire/App/Attendance/AttendanceReports.php`:**
  - Tipos de reporte (selector):
    - **Resumen general del período:** tasa de asistencia por sección, comparativa mensual
    - **Por estudiante:** historial completo de un estudiante, ausencias justificadas vs no justificadas
    - **Discrepancias del período:** listado de eventos de pasilleo detectados
    - **Por maestro:** cobertura de pase de lista (cuántas clases registró vs cuántas debía)
  - Generador de reporte: aplica filtros → genera `Collection` → renderiza vista previa en página
  - Exportar a Excel y PDF

### 15.4 — Exportación

- [ ] **Instalar:** `composer require maatwebsite/excel` + `composer require barryvdh/laravel-dompdf`

- [ ] **Crear `app/Exports/PlantelAttendanceExport.php`:**
  - `WithHeadings`, `WithMapping`, `FromCollection`
  - Columnas: Fecha, Estudiante, Cédula, Sección, Tanda, Hora, Estado, Método, Registrado por

- [ ] **Crear `app/Exports/ClassroomAttendanceExport.php`:**
  - Columnas: Fecha, Estudiante, Materia, Sección, Maestro, Estado, Notas

- [ ] **Crear `resources/views/reports/attendance-plantel.blade.php`** para PDF:
  - Header con logo de escuela + nombre + período
  - Tabla de registros
  - Footer con totales y tasa

---

## Fase 16 — Configuración del Módulo
**Rama:** `feature/attendance-config`

### 16.1 — Actualizar `config/modules.php`

- [ ] **Agregar entrada `asistencia`:**
  ```php
  'asistencia' => [
      'module'      => 'Asistencia',
      'moduleIcon'  => 'asistencia',
      'moduleLinks' => [
          ['label' => 'Dashboard',          'route' => 'app.attendance.dashboard'],
          ['label' => 'Sesión del Día',     'route' => 'app.attendance.session'],
          ['label' => 'Registro Manual',    'route' => 'app.attendance.manual'],
          ['label' => 'Escáner QR',         'route' => 'app.attendance.qr'],
          ['label' => 'Reconoc. Facial',    'route' => 'app.attendance.facial'],
          ['label' => 'Pase de Lista',      'route' => 'app.attendance.classroom.live'],
          ['label' => 'Historial',          'route' => 'app.attendance.history'],
          ['label' => 'Reportes',           'route' => 'app.attendance.reports'],
          ['label' => 'Excusas',            'route' => 'app.excuses.index'],
      ],
  ],

  'estudiantes' => [
      'module'      => 'Estudiantes',
      'moduleIcon'  => 'estudiantes',
      'moduleLinks' => [
          ['label' => 'Listado',    'route' => 'app.students.index'],
          ['label' => 'Maestros',   'route' => 'app.teachers.index'],
      ],
  ],
  ```

### 16.2 — Actualizar Dashboard (`app/dashboard.blade.php`)

- [ ] **Activar tiles de Asistencia y Estudiantes** (quitar `comingSoon`):
  ```blade
  <x-ui.app-tile module="asistencia" title="Asistencia" url="{{ route('app.attendance.dashboard') }}" />
  <x-ui.app-tile module="estudiantes" title="Estudiantes" url="{{ route('app.students.index') }}" />
  ```

### 16.3 — Actualizar `config/modules.php` — links en módulo configuración

- [ ] **Agregar link "Maestros"** en `configuracion.moduleLinks`:
  ```php
  ['label' => 'Maestros', 'route' => 'app.teachers.index'],
  ```

### 16.4 — Actualizar `RoleAcademicSeeder` para Nuevos Roles

- [ ] **Agregar rol `Academic Coordinator`** con permisos intermedios entre `School Principal` y `Teacher` (ver Fase 6.3)
- [ ] **Actualizar `SchoolRoleService::BASE_ROLES`** para incluir `Academic Coordinator`

---

## Fase 17 — Planes y Features
**Rama:** `feature/attendance-plans`

### 17.1 — Actualizar `PlanFeatureSeeder`

- [ ] **Agregar features del módulo de asistencia:**
  ```php
  ['name' => 'Estudiantes',               'slug' => 'attendance_students',   'module' => 'Asistencia'],
  ['name' => 'Registro Manual',           'slug' => 'attendance_manual',     'module' => 'Asistencia'],
  ['name' => 'Asistencia de Aula',        'slug' => 'attendance_classroom',  'module' => 'Asistencia'],
  ['name' => 'Escáner QR',               'slug' => 'attendance_qr',         'module' => 'Asistencia'],
  ['name' => 'Reconocimiento Facial',    'slug' => 'attendance_facial',     'module' => 'Asistencia'],
  ['name' => 'Reportes de Asistencia',   'slug' => 'attendance_reports',    'module' => 'Asistencia'],
  ['name' => 'Sincronización Offline',   'slug' => 'attendance_offline',    'module' => 'Asistencia'],
  ['name' => 'Importación de Est.',      'slug' => 'students_import',       'module' => 'Estudiantes'],
  ```

### 17.2 — Asignación a Planes

- [ ] **Actualizar asignaciones en `PlanFeatureSeeder`:**
  - **Básico:** `attendance_students`, `attendance_manual`, `students_import`
  - **Profesional:** Básico + `attendance_classroom`, `attendance_qr`, `attendance_reports`
  - **Enterprise:** Profesional + `attendance_facial`, `attendance_offline`

### 17.3 — Traducción de Features

- [ ] **Crear/actualizar `lang/es/features.php`:**
  ```php
  'attendance_students'  => 'Gestión de Estudiantes',
  'attendance_manual'    => 'Registro manual de asistencia',
  'attendance_classroom' => 'Asistencia de Aula (pase de lista)',
  'attendance_qr'        => 'Registro por código QR',
  'attendance_facial'    => 'Reconocimiento facial',
  'attendance_reports'   => 'Reportes avanzados de asistencia',
  'attendance_offline'   => 'Modo sin conexión / sincronización',
  'students_import'      => 'Importación masiva de estudiantes',
  ```

---

## Fase 18 — Testing y QA
**Rama:** `feature/attendance-testing`

### 18.1 — Tests de Unidad (PHP)

- [ ] **`tests/Unit/Services/StudentServiceTest.php`:**
  - `test_generate_qr_code_is_unique()`
  - `test_create_student_with_qr_auto_generated()`
  - `test_withdraw_sets_inactive_and_date()`
  - `test_reactivate_clears_withdrawal_fields()`
  - `test_transfer_section_updates_metadata_history()`

- [ ] **`tests/Unit/Services/TeacherServiceTest.php`:**
  - `test_generate_employee_code_format()`
  - `test_terminate_removes_active_assignments()`

- [ ] **`tests/Unit/Services/PlantelAttendanceServiceTest.php`:**
  - `test_cannot_open_duplicate_session_same_date_shift()`
  - `test_record_attendance_returns_correct_status_on_time()`
  - `test_record_attendance_returns_late_status_past_threshold()`
  - `test_record_attendance_throws_on_duplicate()`
  - `test_record_attendance_throws_without_open_session()`
  - `test_mark_absences_only_marks_students_without_record()`

- [ ] **`tests/Unit/Services/ClassroomAttendanceServiceTest.php`:**
  - `test_cannot_mark_present_if_absent_in_plantel()`
  - `test_cannot_record_without_plantel_record()`
  - `test_detect_discrepancies_finds_pasilleo()`

- [ ] **`tests/Unit/Services/ExcuseServiceTest.php`:**
  - `test_approve_excuse_updates_status()`
  - `test_reject_excuse_requires_notes()`
  - `test_covers_date_returns_correct_boolean()`

### 18.2 — Tests de Integración (Feature)

- [ ] **`tests/Feature/Students/StudentCrudTest.php`:**
  - `test_can_create_student_with_qr_auto_generated()`
  - `test_cannot_create_student_without_permission()`
  - `test_can_view_student_list_scoped_to_school()`
  - `test_can_transfer_student_to_new_section()`
  - `test_cannot_delete_student_with_attendance_records()`

- [ ] **`tests/Feature/Attendance/PlantelSessionTest.php`:**
  - `test_admin_can_open_daily_session()`
  - `test_cannot_open_duplicate_session()`
  - `test_close_session_calculates_stats()`
  - `test_mark_all_absences_skips_already_registered()`

- [ ] **`tests/Feature/Attendance/QrScanTest.php`:**
  - `test_valid_qr_records_attendance_in_open_session()`
  - `test_invalid_qr_returns_error()`
  - `test_duplicate_qr_scan_returns_warning_not_duplicate_record()`
  - `test_qr_from_other_school_is_rejected()`

- [ ] **`tests/Feature/Attendance/CrossValidationTest.php`:**
  - `test_cannot_mark_classroom_present_if_plantel_absent()`
  - `test_can_mark_classroom_absent_if_plantel_present()`
  - `test_discrepancy_detected_when_present_plantel_absent_classroom()`

- [ ] **`tests/Feature/Sync/SyncManagerTest.php`:**
  - `test_sync_enqueues_record_on_local_mode()`
  - `test_sync_skips_enqueue_on_cloud_mode()`
  - `test_sync_pending_marks_synced_on_success()`
  - `test_sync_pending_increments_attempts_on_failure()`

### 18.3 — Tests del Microservicio Python

- [ ] **`tests/test_face_detection.py`:** single face, multiple faces, no face
- [ ] **`tests/test_face_matching.py`:** match found, no match, tolerance boundary
- [ ] **Fixtures en `tests/fixtures/`:** `single_face.jpg`, `multiple_faces.jpg`, `no_face.jpg`
- [ ] **Ejecutar:** `pytest tests/ -v --cov=app`

---

## Fase 19 — Documentación
**Rama:** `feature/attendance-docs`

### 19.1 — Documentación de Arquitectura

- [ ] **Crear `docs/architecture/attendance-domains.md`:**
  - Explicar la separación Plantel vs Aula y por qué son modelos separados
  - Diagrama de la validación cruzada (regla de pasilleo)
  - Diagrama de flujo del día: apertura → QR/Facial/Manual → cierre → marcado de ausentes
  - Documentar que sin `DailyAttendanceSession` abierta el sistema no acepta registros

- [ ] **Crear `docs/architecture/offline-sync.md`:**
  - Explicar `APP_MODE=cloud` vs `APP_MODE=local`
  - Flujo de la cola de sincronización: Observer → `sync_queue` → comando → API cloud
  - Manejo de conflictos (last-write-wins con timestamp)
  - Cómo agregar nuevos modelos al sistema de sincronización

- [ ] **Crear `docs/architecture/facial-recognition.md`:**
  - Arquitectura del microservicio Python
  - Flujo Laravel → FastAPI → `face_recognition` → respuesta
  - Consideraciones de privacidad de datos biométricos (encodings almacenados solo en DB, nunca las fotos completas)
  - Guía de deployment del microservicio en Docker

### 19.2 — Documentación de Usuario

- [ ] **Crear `docs/modules/students.md`:** guía de gestión de estudiantes, proceso de importación, captura de rostro para reconocimiento facial
- [ ] **Crear `docs/modules/teachers.md`:** gestión de maestros, asignación de materias, vinculación con cuenta de sistema
- [ ] **Crear `docs/modules/attendance.md`:** apertura del día, registro QR, pase de lista, gestión de excusas, interpretación del dashboard

### 19.3 — API del Microservicio

- [ ] **Crear `docs/api/facial-recognition-api.md`:** documentación completa de endpoints, schemas, ejemplos curl, códigos de error

---

## Fase 20 — Importación Masiva de Estudiantes
**Rama:** `feature/students-import`

### 20.1 — Componente de Importación

- [ ] **Instalar:** `composer require maatwebsite/excel`

- [ ] **Crear `app/Imports/StudentsImport.php`:**
  ```php
  namespace App\Imports;

  use Maatwebsite\Excel\Concerns\ToModel;
  use Maatwebsite\Excel\Concerns\WithHeadingRow;
  use Maatwebsite\Excel\Concerns\WithValidation;
  use Maatwebsite\Excel\Concerns\SkipsOnError;
  use Maatwebsite\Excel\Concerns\SkipsErrors;

  class StudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
  {
      use SkipsErrors;

      protected int $schoolId;
      protected int $sectionId;

      public function __construct(int $schoolId, int $sectionId)
      {
          $this->schoolId = $schoolId;
          $this->sectionId = $sectionId;
      }

      public function model(array $row): ?Student
      {
          return Student::create([
              'school_id'         => $this->schoolId,
              'school_section_id' => $this->sectionId,
              'first_name'        => $row['nombre'],
              'last_name'         => $row['apellidos'],
              'gender'            => $row['genero'],
              'date_of_birth'     => Carbon::parse($row['fecha_nacimiento']),
              'rnc'               => $row['cedula'] ?? null,
              'enrollment_date'   => now(),
          ]);
      }

      public function rules(): array
      {
          return [
              'nombre'            => 'required|string|max:100',
              'apellidos'         => 'required|string|max:100',
              'genero'            => 'required|in:M,F',
              'fecha_nacimiento'  => 'required|date',
              'cedula'            => 'nullable|string|size:13',
          ];
      }
  }
  ```

- [ ] **Crear `app/Livewire/App/Students/StudentImport.php`:**
  - `use WithFileUploads`
  - Paso 1: subir archivo Excel + seleccionar sección destino + previsualizar primeras 5 filas
  - Paso 2: confirmar importación → `Excel::import(new StudentsImport($schoolId, $sectionId), $this->file)`
  - Reportar filas importadas vs filas con error
  - Guard: `can:students.import`

- [ ] **Template Excel descargable:** `resources/templates/import-students.xlsx` con columnas y ejemplos

---

## Checklist de Completitud v0.4.0

### Entidades Base
- [x] Migración y modelo `Student`
- [x] Migración y modelo `Teacher`
- [x] Catálogo `Subject` con seeder
- [x] Tabla pivote `teacher_subject_sections`
- [x] Observer `StudentObserver` y `TeacherObserver` registrados

### Servicios de Negocio
- [ ] `StudentService` y `StudentPhotoService`
- [ ] `TeacherService` y `TeacherAssignmentService`
- [ ] `PlantelAttendanceService` con validación de sesión abierta
- [ ] `ClassroomAttendanceService` con validación cruzada
- [ ] `ExcuseService`
- [ ] `SyncManager` + comando `orvian:sync-attendance`
- [ ] `FacialApiClient` y `FaceEncodingManager`

### Permisos y Roles
- [ ] `PermissionGroup` con nuevos grupos de asistencia
- [ ] Nuevos permisos en `PermissionSeeder`
- [ ] Roles actualizados en `RoleAcademicSeeder`

### Interfaz Web
- [ ] CRUD Estudiantes (index + show + slide-over)
- [ ] CRUD Maestros (index + asignaciones)
- [ ] Gestión de Sesión del Día
- [ ] Registro Manual de Plantel
- [ ] Escáner QR
- [ ] Escáner Facial (dependiente de microservicio)
- [ ] Pase de Lista del Maestro (aula)
- [ ] Gestión de Excusas
- [ ] Dashboard con doble visión + panel de discrepancias
- [ ] Historial de plantel y aula
- [ ] Reportes con exportación Excel/PDF
- [ ] Importación masiva de estudiantes

### Sincronización Offline
- [ ] Columnas `synced_at`, `sync_status` en tablas de asistencia
- [ ] Tabla `sync_queue`
- [ ] `SyncObserver` registrado condicionalmente
- [ ] Comando `orvian:sync-attendance` programado
- [ ] Endpoint de recepción en servidor cloud
- [ ] Indicador visual de estado de sincronización en UI

### Microservicio Python
- [ ] Repositorio `orvian-facial-recognition` creado
- [ ] Endpoints `/health`, `/enroll`, `/verify` implementados
- [ ] Tests pasando con fixtures
- [ ] Dockerfile y docker-compose configurados
- [ ] Cliente HTTP en Laravel conectado y probado

### Módulo y Planes
- [ ] `config/modules.php` actualizado con `asistencia` y `estudiantes`
- [ ] Tiles activados en `app/dashboard.blade.php`
- [ ] Features añadidas en `PlanFeatureSeeder`
- [ ] Features asignadas a planes correctos

### Testing
- [ ] Tests unitarios de servicios pasando
- [ ] Tests de integración (CRUD, sesiones, validación cruzada, sync) pasando
- [ ] Tests del microservicio Python pasando

### Documentación
- [ ] `docs/architecture/attendance-domains.md`
- [ ] `docs/architecture/offline-sync.md`
- [ ] `docs/architecture/facial-recognition.md`
- [ ] `docs/modules/students.md`, `teachers.md`, `attendance.md`

---

## Notas de Implementación

**Orden de dependencias obligatorio:**
1. Fases 1–2 (entidades y catálogo) antes que cualquier otra cosa
2. Fase 6 (permisos) antes de crear las interfaces
3. Fase 3 (plantel) antes de Fase 4 (aula) — la validación cruzada lo requiere
4. Fase 12 (sync) puede implementarse en paralelo con las fases de UI
5. Fase 13 (microservicio Python) puede desarrollarse en paralelo con las fases PHP; el escáner facial en PHP solo requiere que el microservicio esté corriendo

**Consideraciones de privacidad:**
- Los `face_encoding` (128 floats) se almacenan en BD, no las imágenes de los estudiantes
- Las fotos de captura para verificación nunca se persisten — se procesan y descartan
- El microservicio no almacena ninguna imagen, solo procesa en memoria

**Modo local vs cloud:**
- En `APP_MODE=local`, el scheduler necesita estar corriendo (`php artisan schedule:work`)
- La sincronización es eventual — los datos del día estarán en el servidor cloud máximo 5 minutos después de registrarse localmente
- Los reportes del servidor cloud siempre muestran datos hasta la última sincronización

**Tiempo estimado:** 8–10 semanas de desarrollo (con equipo de 3 desarrolladores)

### Análisis de Código y Oportunidades de Mejora

He detectado algunos detalles técnicos a nivel de base de datos y lógica que podrías ajustar antes de empezar a tirar código.

| Componente / Fase | Observación Técnica | Recomendación de Ajuste |
| :--- | :--- | :--- |
| **Modelos (Biometría)** | Tienes un campo `face_encoding` (JSON) directamente en la tabla `students`[cite: 3]. Un array de 128 *floats* puede hacer que la fila sea muy pesada en memoria al hacer un `Student::all()`. | Mueve los datos biométricos a una tabla separada (ej. `student_biometrics`) o asegúrate de agregarlo a la propiedad `$hidden` del modelo para que no viaje en consultas masivas innecesariamente. |
| **Fase 1 (Soft Deletes)** | La migración usa `cascadeOnDelete()` para la llave foránea `school_id`, pero el modelo usa el trait `SoftDeletes`[cite: 3]. | Si eliminas un colegio físicamente (hard delete), los estudiantes se borrarán físicamente también, ignorando el soft delete. Esto es normal en arquitecturas multitenant, pero tenlo en cuenta para tus auditorías. |
| **Fase 3 y 4 (Husos Horarios)** | Tu lógica para calcular tardanzas depende fuertemente de `now()` y los límites de los turnos (`school_shifts`)[cite: 3]. | Configura explícitamente el timezone de Laravel en `America/Santo_Domingo` (AST) en `config/app.php` para evitar falsas tardanzas por desajustes con UTC en el servidor. |
| **Fase 12 (Sync Offline)** | El controlador `SyncReceiveController` en el cloud confía ciegamente en el payload local y usa "el último en llegar gana"[cite: 3]. | Para la asistencia es un riesgo bajo, pero considera agregar un campo `version` o validar el `updated_at` para evitar que un nodo local desactualizado sobrescriba un cambio hecho desde el cloud. |
| **Fase 14 (Dashboard)** | El método `loadRecentActivity` hace un *polling* cada 10 segundos[cite: 3]. Si hay 5 pantallas abiertas en el colegio, son muchas consultas a la DB. | Asegúrate de indexar correctamente las columnas `time` y `date` en `plantel_attendance_records`[cite: 3]. Considera usar caché de corta duración (ej. 5 segundos) en el Livewire component para aliviar la base de datos. |
