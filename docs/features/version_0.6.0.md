# ORVIAN v0.6.0 — Módulo de Gestión Académica

**RAMA PADRE:** `feature/academic-management`

**Objetivo:** Consolidar y elevar el Módulo de Gestión Académica de ORVIAN. Esta versión cierra las brechas arquitectónicas identificadas en v0.4.x y v0.5.0, introduce el flujo de importación tolerante de SIGERD (filosofía "Sala de Espera"), refactoriza los namespaces de entidades académicas y entrega interfaces de usuario de mayor fidelidad para la matrícula masiva, enrolamiento biométrico y asignación de materias.

---

## Estado de la Base — v0.5.0 como Fundación

| Componente | Origen | Estado |
| :--- | :--- | :--- |
| Modelos `Student`, `Teacher` en `App\Models\Tenant` (namespace plano) | v0.4.0 | ✅ Disponible — refactorizar en Fase 1 |
| `StudentObserver@created` genera `User` desde RNC | v0.4.1 | ✅ Completado |
| `StudentImportWizard` + `ProcessStudentImport` Job básico | v0.4.0 | ✅ Disponible — evolucionar en Fase 3 |
| Campo `tutor_name` en `students` | v0.4.0 | ✅ Completado |
| Campo `tutor_phone` (E.164) en `students` | v0.5.0 | ✅ Completado |
| `AttendanceAlertEvaluator` + `SendAttendanceAlertJob` | v0.5.0 | ✅ Completado |
| `SchoolSection` con `school_shift_id` | v0.4.0 | ✅ Completado |
| `TeacherSubjectSection` (pivote Maestro ↔ Materia ↔ Sección) | v0.4.0 | ✅ Completado |
| `FacialApiClient` + `FaceEncodingManager` | v0.4.0 | ✅ Completado |
| `Subject` con scopes `basic()`, `technical()`, `availableForSchool()` | v0.4.0 | ✅ Completado |
| Sistema de permisos `students.*`, `teachers.*` | v0.4.0 | ✅ Completado |

---

## Tabla de Requerimientos

| ID | Fase | Área | Descripción | Prioridad |
| :-- | :-- | :-- | :-- | :-- |
| REQ-01 | 1 | Arquitectura | Mover `Student`, `Teacher` y entidades académicas a `App\Models\Tenant\Academic` | Alta |
| REQ-02 | 1 | Arquitectura | Find & Replace global de namespaces con estrategia de backcompat vía aliases | Alta |
| REQ-03 | 2 | UI | Academic Builder — interfaz de Cards para gestionar Niveles, Grados, Secciones y Tandas | Media |
| REQ-04 | 3 | Importación | Actualizar `StudentImportWizard` con mapeo de `tutor_name` y `tutor_phone` | Alta |
| REQ-05 | 3 | Importación | Lógica `resolveSection` tolerante: si no hay match → `school_section_id = null` + guardar `metadata->sigerd_section` | Alta |
| REQ-06 | 4 | UI | Hub de Matriculación — dos paneles: "Sin Asignar" vs Árbol de Secciones | Alta |
| REQ-07 | 4 | UI | Asignación masiva en lote desde panel de Sala de Espera con checkboxes | Alta |
| REQ-08 | 5 | UI | Kiosko de Enrolamiento Biométrico — Grid visual por sección con modal de webcam | Media |
| REQ-09 | 6 | UI | `StudentShow` — sección de Tutor + resumen gráfico de asistencia histórica | Media |
| REQ-10 | 6 | UI | `StudentIndex` — filtros rápidos visuales + Slide-Over preview | Media |
| REQ-11 | 7 | UI | `TeacherAssignments` — reemplazar doble select por paneles con grid de asignaturas | Media |

---

## Fase 1 — Refactorización de Arquitectura (Namespaces)
**Rama:** `feature/academic-namespaces`

### Objetivo

Mover las entidades de dominio académico a un namespace explícito (`App\Models\Tenant\Academic`) para mejorar la legibilidad del codebase, facilitar el onboarding de nuevos desarrolladores y establecer una separación clara entre modelos de infraestructura y modelos del dominio educativo.

### 1.1 — Inventario de Entidades a Migrar

| Archivo Actual | Destino | Impacto |
| :--- | :--- | :--- |
| `App\Models\Tenant\Student` | `App\Models\Tenant\Academic\Student` | Alto — usado en 30+ archivos |
| `App\Models\Tenant\Teacher` | `App\Models\Tenant\Academic\Teacher` | Alto — usado en 15+ archivos |
| `App\Observers\Tenant\StudentObserver` | `App\Observers\Tenant\Academic\StudentObserver` | Medio |
| `App\Observers\Tenant\TeacherObserver` | `App\Observers\Tenant\Academic\TeacherObserver` | Medio |
| `Database\Factories\Tenant\StudentFactory` | `Database\Factories\Tenant\Academic\StudentFactory` | Bajo |
| `Database\Factories\Tenant\TeacherFactory` | `Database\Factories\Tenant\Academic\TeacherFactory` | Bajo |

> **Nota:** Los modelos `Subject`, `TeacherSubjectSection`, `SchoolSection`, `SchoolShift`, `AcademicYear`, `Level`, `Grade` ya están en `App\Models\Tenant\Academic` desde v0.4.0. Esta fase solo consolida `Student` y `Teacher` al mismo namespace.

### 1.2 — Estrategia de Migración (Sin Romper Dependencias)

La estrategia se ejecuta en 4 pasos atómicos para garantizar que el sistema siempre esté en un estado funcional entre commits.

**Paso A — Crear archivos en nueva ubicación:**

```bash
mkdir -p app/Models/Tenant/Academic
mkdir -p app/Observers/Tenant/Academic
mkdir -p database/factories/Tenant/Academic
```

**Paso B — Actualizar namespace en cada archivo:**

```php
// app/Models/Tenant/Academic/Student.php
namespace App\Models\Tenant\Academic;  // ← Cambio clave

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
// ... resto igual
```

```php
// app/Models/Tenant/Academic/Teacher.php
namespace App\Models\Tenant\Academic;  // ← Cambio clave
// ... resto igual
```

**Paso C — Agregar aliases de backward-compatibility en `AppServiceProvider`:**

Este es el paso crítico. Los aliases permiten que el código existente siga funcionando mientras el refactor se propaga por el codebase. Eliminamos los aliases solo cuando todos los `use` han sido actualizados.

```php
// app/Providers/AppServiceProvider.php

use App\Models\Tenant\Academic\Student as AcademicStudent;
use App\Models\Tenant\Academic\Teacher as AcademicTeacher;

public function register(): void
{
    // Backward-compatibility aliases — eliminar al completar el Find & Replace
    // Permite que código legado con el namespace antiguo siga funcionando
    // durante la transición sin errores fatales.
    class_alias(AcademicStudent::class, 'App\Models\Tenant\Student');
    class_alias(AcademicTeacher::class, 'App\Models\Tenant\Teacher');
}
```

**Paso D — Find & Replace global con confirmación por archivo:**

```bash
# Usando git grep para inventariar todos los archivos afectados ANTES de tocarlos
git grep -rl "App\\Models\\Tenant\\Student" --include="*.php" > /tmp/student_refs.txt
git grep -rl "App\\Models\\Tenant\\Teacher" --include="*.php" > /tmp/teacher_refs.txt

wc -l /tmp/student_refs.txt   # Verificar cantidad antes de proceder
wc -l /tmp/teacher_refs.txt
```

```bash
# Reemplazo global (macOS/Linux)
find app database routes -name "*.php" -exec sed -i \
  's/App\\Models\\Tenant\\Student/App\\Models\\Tenant\\Academic\\Student/g' {} \;

find app database routes -name "*.php" -exec sed -i \
  's/App\\Models\\Tenant\\Teacher/App\\Models\\Tenant\\Academic\\Teacher/g' {} \;

# También actualizar en archivos Blade (uso de ::class en @php)
find resources -name "*.blade.php" -exec sed -i \
  's/App\\Models\\Tenant\\Student/App\\Models\\Tenant\\Academic\\Student/g' {} \;
```

```bash
# Verificar que no quedaron referencias antiguas
git grep "App\Models\Tenant\Student[^A-Za-z]" --include="*.php"
git grep "App\Models\Tenant\Teacher[^A-Za-z]" --include="*.php"
# Resultado esperado: 0 líneas
```

**Paso E — Actualizar Observers y Factories:**

```php
// app/Observers/Tenant/Academic/StudentObserver.php
namespace App\Observers\Tenant\Academic;

use App\Models\Tenant\Academic\Student;
// ... resto igual

// app/Observers/Tenant/Academic/TeacherObserver.php
namespace App\Observers\Tenant\Academic;

use App\Models\Tenant\Academic\Teacher;
// ... resto igual
```

```php
// app/Providers/AppServiceProvider.php — actualizar registros de Observers

use App\Models\Tenant\Academic\Student;
use App\Models\Tenant\Academic\Teacher;
use App\Observers\Tenant\Academic\StudentObserver;
use App\Observers\Tenant\Academic\TeacherObserver;

public function boot(): void
{
    Student::observe(StudentObserver::class);
    Teacher::observe(TeacherObserver::class);
    // ... resto de observers
}
```

**Paso F — Actualizar `$useFactory` en los modelos (si aplica) y eliminar aliases:**

```php
// app/Models/Tenant/Academic/Student.php
use HasFactory;

protected static function newFactory()
{
    return \Database\Factories\Tenant\Academic\StudentFactory::new();
}
```

Una vez que todos los `use` han sido actualizados y las pruebas pasan, eliminar los `class_alias` del `AppServiceProvider`.

### 1.3 — Checklist de Completitud — Fase 1

- [ ] Directorio `app/Models/Tenant/Academic/` contiene `Student.php` y `Teacher.php` con namespace correcto
- [ ] Directorio `app/Observers/Tenant/Academic/` contiene observers actualizados
- [ ] Directorio `database/factories/Tenant/Academic/` contiene factories actualizadas
- [ ] Aliases de backward-compatibility registrados en `AppServiceProvider`
- [ ] Find & Replace ejecutado sobre `app/`, `database/`, `routes/`, `resources/`
- [ ] `git grep` para namespace antiguo devuelve 0 resultados
- [ ] `php artisan route:clear && php artisan config:clear && php artisan view:clear` pasa sin errores
- [ ] Aliases eliminados del `AppServiceProvider` tras verificar que todo compila
- [ ] Archivos viejos (`app/Models/Tenant/Student.php`, `app/Models/Tenant/Teacher.php`) eliminados

---

## Fase 2 — Academic Builder (Estructura Institucional)
**Rama:** `feature/academic-builder`

### Objetivo

Proveer una interfaz interactiva basada en Cards para que el Director pueda visualizar y gestionar la estructura académica del centro (Niveles → Grados → Secciones → Tandas) sin necesidad de acceder al panel de administración global.

### 2.1 — Componente Livewire `AcademicBuilder`

```php
// app/Livewire/App/Academic/AcademicBuilder.php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\Level;
use App\Models\Tenant\Academic\Grade;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AcademicBuilder extends Component
{
    // Panel de Sección seleccionada para edición inline
    public ?int $editingSectionId = null;
    public string $editingLabel   = '';
    public ?int   $editingShiftId = null;

    // Creación de nueva sección
    public bool   $showCreatePanel = false;
    public int    $newGradeId      = 0;
    public string $newLabel        = '';
    public int    $newShiftId      = 0;

    #[Computed]
    public function structure(): array
    {
        $sections = SchoolSection::with([
            'grade.level',
            'shift',
            'technicalTitle',
            'students' => fn ($q) => $q->where('is_active', true)->select('id', 'school_section_id'),
        ])
        ->where('school_id', auth()->user()->school_id)
        ->get();

        // Agrupar: Nivel → Grado → Secciones
        return $sections
            ->groupBy(fn ($s) => $s->grade->level->name)
            ->map(fn ($bLevel, $levelName) => [
                'name'   => $levelName,
                'grades' => $bLevel
                    ->groupBy(fn ($s) => $s->grade->name)
                    ->map(fn ($bGrade, $gradeName) => [
                        'name'     => $gradeName,
                        'sections' => $bGrade->sortBy('label')->values(),
                    ])
                    ->values(),
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', auth()->user()->school_id)->get();
    }

    #[Computed]
    public function grades(): \Illuminate\Database\Eloquent\Collection
    {
        return Grade::with('level')->orderBy('order')->get();
    }

    public function startEdit(int $sectionId): void
    {
        $section = SchoolSection::findOrFail($sectionId);
        $this->editingSectionId = $sectionId;
        $this->editingLabel     = $section->label;
        $this->editingShiftId   = $section->school_shift_id;
    }

    public function saveEdit(): void
    {
        $this->authorize('configuracion.academic_structure');
        $this->validate([
            'editingLabel'   => 'required|string|max:10',
            'editingShiftId' => 'required|integer|exists:school_shifts,id',
        ]);

        SchoolSection::findOrFail($this->editingSectionId)->update([
            'label'           => strtoupper($this->editingLabel),
            'school_shift_id' => $this->editingShiftId,
        ]);

        $this->reset(['editingSectionId', 'editingLabel', 'editingShiftId']);
        unset($this->structure);
        $this->dispatch('notify', type: 'success', message: 'Sección actualizada correctamente.');
    }

    public function createSection(): void
    {
        $this->authorize('configuracion.academic_structure');
        $this->validate([
            'newGradeId' => 'required|integer|exists:grades,id',
            'newLabel'   => 'required|string|max:10',
            'newShiftId' => 'required|integer|exists:school_shifts,id',
        ]);

        SchoolSection::firstOrCreate([
            'school_id'       => auth()->user()->school_id,
            'grade_id'        => $this->newGradeId,
            'label'           => strtoupper($this->newLabel),
            'school_shift_id' => $this->newShiftId,
        ]);

        $this->reset(['showCreatePanel', 'newGradeId', 'newLabel', 'newShiftId']);
        unset($this->structure);
        $this->dispatch('notify', type: 'success', message: 'Sección creada correctamente.');
    }

    public function toggleSectionStatus(int $sectionId): void
    {
        $section = SchoolSection::findOrFail($sectionId);

        // Bloquear si tiene estudiantes activos asignados
        if ($section->students()->where('is_active', true)->exists()) {
            $this->dispatch('notify', type: 'error',
                message: 'No se puede desactivar una sección con estudiantes activos.');
            return;
        }

        $section->update(['is_active' => ! $section->is_active]);
        unset($this->structure);
    }

    public function render()
    {
        return view('livewire.app.academic.academic-builder')
            ->layout('layouts.app-module', config('modules.configuracion'));
    }
}
```

### 2.2 — Vista `academic-builder.blade.php`

La vista implementa un grid de cards con identidad visual clara por nivel educativo. Cada Card de Sección muestra: etiqueta (paralelo), tanda, conteo de estudiantes activos, badge de estado (Activa/Inactiva) y acciones inline de edición.

```blade
{{-- resources/views/livewire/app/academic/academic-builder.blade.php --}}
<div>
    <x-app.module-toolbar>
        <x-slot:title>Estructura Académica</x-slot:title>
        <x-slot:actions>
            <x-ui.button wire:click="$set('showCreatePanel', true)" variant="primary" size="sm"
                iconLeft="heroicon-o-plus">
                Nueva Sección
            </x-ui.button>
        </x-slot:actions>
    </x-app.module-toolbar>

    {{-- Panel de creación --}}
    @if($showCreatePanel)
        <div class="mb-8 p-6 bg-white dark:bg-dark-card border border-orvian-orange/30
                    rounded-2xl shadow-sm space-y-4 animate-in slide-in-from-top duration-200">
            <h3 class="text-sm font-bold text-slate-700 dark:text-white">Nueva Sección</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.forms.select label="Grado" name="newGradeId" wire:model="newGradeId">
                    <option value="">Seleccionar grado...</option>
                    @foreach($this->grades->groupBy(fn ($g) => $g->level->name) as $nivel => $grados)
                        <optgroup label="{{ $nivel }}">
                            @foreach($grados as $grade)
                                <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-ui.forms.select>

                <x-ui.forms.input label="Paralelo (Letra)" name="newLabel"
                    wire:model="newLabel" placeholder="Ej: A, B, C" />

                <x-ui.forms.select label="Tanda" name="newShiftId" wire:model="newShiftId">
                    <option value="">Seleccionar tanda...</option>
                    @foreach($this->shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>
            <div class="flex gap-3 justify-end">
                <x-ui.button wire:click="$set('showCreatePanel', false)" variant="ghost" size="sm">
                    Cancelar
                </x-ui.button>
                <x-ui.button wire:click="createSection" variant="primary" size="sm">
                    Crear Sección
                </x-ui.button>
            </div>
        </div>
    @endif

    {{-- Estructura por Nivel --}}
    @foreach($this->structure as $level)
        <div class="mb-10">
            {{-- Header del Nivel --}}
            <div class="flex items-center gap-3 mb-5">
                <h2 class="text-lg font-extrabold text-orvian-navy dark:text-white">
                    {{ $level['name'] }}
                </h2>
                <div class="flex-grow border-t border-slate-200 dark:border-white/10"></div>
            </div>

            {{-- Grid de Grados --}}
            <div class="space-y-6">
                @foreach($level['grades'] as $grade)
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400
                                  dark:text-slate-500 mb-3">{{ $grade['name'] }}</p>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                            @foreach($grade['sections'] as $section)
                                <div class="group relative bg-white dark:bg-dark-card rounded-2xl p-4
                                            border-2 transition-all
                                            {{ $section->is_active
                                                ? 'border-slate-200 dark:border-white/10 hover:border-orvian-orange/50'
                                                : 'border-dashed border-slate-200 dark:border-white/5 opacity-60' }}">

                                    {{-- Paralelo --}}
                                    <div class="text-3xl font-black text-orvian-orange leading-none mb-2">
                                        {{ $section->label }}
                                    </div>

                                    {{-- Tanda --}}
                                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase truncate">
                                        {{ $section->shift->name ?? '—' }}
                                    </p>

                                    {{-- Conteo de estudiantes --}}
                                    <div class="flex items-center gap-1 mt-2">
                                        <x-heroicon-o-users class="w-3 h-3 text-slate-400" />
                                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                            {{ $section->students->count() }}
                                        </span>
                                    </div>

                                    {{-- Acciones (aparecen en hover) --}}
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100
                                                transition-opacity flex gap-1">
                                        <button wire:click="startEdit({{ $section->id }})"
                                                class="p-1 rounded-lg bg-slate-100 dark:bg-white/10
                                                       hover:bg-orvian-orange/10 text-slate-500
                                                       hover:text-orvian-orange transition-colors">
                                            <x-heroicon-o-pencil-square class="w-3 h-3" />
                                        </button>
                                        <button wire:click="toggleSectionStatus({{ $section->id }})"
                                                class="p-1 rounded-lg bg-slate-100 dark:bg-white/10
                                                       hover:bg-red-50 dark:hover:bg-red-900/20 text-slate-500
                                                       hover:text-red-500 transition-colors">
                                            <x-heroicon-o-{{ $section->is_active ? 'eye-slash' : 'eye' }} class="w-3 h-3" />
                                        </button>
                                    </div>

                                    {{-- Panel de edición inline --}}
                                    @if($editingSectionId === $section->id)
                                        <div class="absolute inset-0 bg-white dark:bg-dark-card rounded-2xl
                                                    border-2 border-orvian-orange p-3 z-10 space-y-2">
                                            <input wire:model="editingLabel"
                                                   class="w-full text-center text-2xl font-black text-orvian-orange
                                                          bg-transparent border-b border-orvian-orange/30
                                                          focus:outline-none uppercase"
                                                   maxlength="3" />
                                            <select wire:model="editingShiftId"
                                                    class="w-full text-xs bg-transparent border border-slate-200
                                                           dark:border-white/10 rounded-lg p-1
                                                           text-slate-600 dark:text-slate-300">
                                                @foreach($this->shifts as $shift)
                                                    <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="flex gap-1">
                                                <button wire:click="saveEdit"
                                                        class="flex-1 py-1 rounded-lg bg-orvian-orange text-white
                                                               text-xs font-bold">✓</button>
                                                <button wire:click="$set('editingSectionId', null)"
                                                        class="flex-1 py-1 rounded-lg bg-slate-100
                                                               dark:bg-white/10 text-slate-500 text-xs">✕</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
```

### 2.3 — Ruta y Configuración de Módulo

```php
// routes/app/academic.php — agregar ruta
Route::get('/academic/builder', AcademicBuilder::class)
    ->middleware('can:configuracion.academic_structure')
    ->name('academic.builder');
```

```php
// config/modules.php — agregar link en módulo configuracion
'moduleLinks' => [
    // ...existentes...
    ['label' => 'Estructura Académica', 'route' => 'app.academic.builder'],
],
```

### 2.4 — Checklist de Completitud — Fase 2

- [ ] Componente `AcademicBuilder` creado con propiedades computadas cacheadas
- [ ] Vista con grid de Cards por Nivel → Grado
- [ ] Edición inline de label y tanda sin salir del grid
- [ ] Panel de creación de nueva sección con validación
- [ ] Toggle de estado (activa/inactiva) con guard de estudiantes activos
- [ ] Ruta protegida por permiso `configuracion.academic_structure`
- [ ] Link en `config/modules.php` del módulo Configuración

---

## Fase 3 — Evolución del Importador SIGERD
**Rama:** `feature/sigerd-importer-v2`

### Filosofía de la Fase

ORVIAN es una herramienta de eficiencia. La fuente de verdad de los estudiantes es el sistema gubernamental **SIGERD**. El Excel que exporta SIGERD no está formateado para ORVIAN — y **no debe serlo**. El usuario no debería tener que manipular el Excel antes de subirlo.

La lógica de importación debe ser **tolerante**: si un estudiante del Excel no puede ser mapeado automáticamente a una sección de ORVIAN (por diferencia en nombres de curso, estructura de tandas no configurada, etc.), ese estudiante no falla — va a una "Sala de Espera" (`school_section_id = null`) y se distribuye masivamente desde el Hub de Matriculación (Fase 4).

### 3.1 — Actualización del Mapeo: `tutor_name` y `tutor_phone`

Los campos `tutor_name` y `tutor_phone` son **críticos** para las alertas de WhatsApp introducidas en v0.5.0. El wizard de importación debe permitir mapear estos campos desde las columnas del Excel de SIGERD.

```php
// app/Livewire/App/Students/StudentImportWizard.php
// Agregar a la lista de campos mapeables en el paso de configuración de columnas

public array $mappableFields = [
    // Existentes
    'first_name'        => 'Nombre(s)',
    'last_name'         => 'Apellido(s)',
    'rnc'               => 'Cédula / RNC',
    'date_of_birth'     => 'Fecha de Nacimiento',
    'gender'            => 'Género (M/F)',
    'enrollment_date'   => 'Fecha de Inscripción',
    'blood_type'        => 'Tipo de Sangre',
    'place_of_birth'    => 'Lugar de Nacimiento',
    
    // ← NUEVO en v0.6.0 — requeridos para alertas WhatsApp (v0.5.0)
    'tutor_name'        => 'Nombre del Tutor/Responsable',
    'tutor_phone'       => 'Teléfono del Tutor (WhatsApp)',
    
    // Campo de sección
    'sigerd_section'    => 'Curso/Sección (tal como aparece en SIGERD)',
];

// Incluir en la validación del paso de mapeo (step 2)
protected function rules(): array
{
    return [
        'columnMapping.first_name' => 'required|string',
        'columnMapping.last_name'  => 'required|string',
        // tutor_phone no es requerido — el Excel de SIGERD puede no tenerlo
        'columnMapping.tutor_phone' => 'nullable|string',
        'columnMapping.tutor_name'  => 'nullable|string',
        'columnMapping.sigerd_section' => 'nullable|string',
    ];
}
```

### 3.2 — Refactorización de `resolveSection`: Lógica Tolerante

Este es el corazón del cambio. La función `resolveSection` deja de ser estricta (falla si no hay match) para ser **tolerante** (guarda el nombre crudo en `metadata` y deja al estudiante en Sala de Espera).

```php
// app/Jobs/Students/ProcessStudentImport.php

/**
 * Intenta resolver la sección de ORVIAN a partir del nombre crudo del curso en SIGERD.
 * 
 * Estrategia de resolución (en orden de prioridad):
 * 1. Match exacto: "4TO A" → buscar sección con grade.name LIKE "4to%" y label = "A"
 * 2. Match fuzzy: normalizar strings y comparar (remover tildes, mayúsculas, etc.)
 * 3. Sección por defecto del wizard: si el usuario configuró una sección default en el step 3
 * 4. Sala de Espera: school_section_id = null + guardar sigerd_section en metadata
 *
 * @param  string|null  $rawSectionName  Ej: "4TO A", "CUARTO A", "4-A", "4to de Secundaria A"
 * @param  int          $schoolId
 * @param  int|null     $defaultSectionId  Sección por defecto configurada en el wizard
 * @return array{section_id: int|null, metadata_sigerd: string|null, resolved: bool}
 */
protected function resolveSection(
    ?string $rawSectionName,
    int $schoolId,
    ?int $defaultSectionId = null
): array {
    if (empty($rawSectionName)) {
        return [
            'section_id'      => $defaultSectionId,
            'metadata_sigerd' => null,
            'resolved'        => $defaultSectionId !== null,
        ];
    }

    // Normalizar el nombre crudo para comparación
    $normalized = $this->normalizeSectionName($rawSectionName);

    // Cargar todas las secciones del centro con sus relaciones en memoria
    // (una sola consulta para toda la importación — se cachea en propiedad)
    $sections = $this->getSectionsCache($schoolId);

    // Intentar match por normalización
    $matched = $sections->first(function ($section) use ($normalized) {
        $sectionLabel = $this->normalizeSectionName(
            $section->grade->name . ' ' . $section->label
        );
        return $sectionLabel === $normalized
            || str_contains($normalized, strtolower($section->label))
               && str_contains($normalized, strtolower(substr($section->grade->name, 0, 3)));
    });

    if ($matched) {
        return [
            'section_id'      => $matched->id,
            'metadata_sigerd' => null,
            'resolved'        => true,
        ];
    }

    // Si hay sección por defecto configurada en el wizard, usarla
    if ($defaultSectionId) {
        return [
            'section_id'      => $defaultSectionId,
            'metadata_sigerd' => $rawSectionName, // Guardar el original para referencia
            'resolved'        => true,
        ];
    }

    // SALA DE ESPERA: No hay match y no hay default
    // El estudiante se crea con section_id = null
    // El nombre crudo queda en metadata para uso en el Hub de Matriculación (Fase 4)
    return [
        'section_id'      => null,
        'metadata_sigerd' => $rawSectionName,
        'resolved'        => false,
    ];
}

/**
 * Normaliza nombres de sección para comparación fuzzy.
 * "4TO A" → "4to a", "CUARTO A" → "cuarto a"
 */
protected function normalizeSectionName(string $name): string
{
    // Remover tildes, convertir a minúsculas, colapsar espacios
    $name = mb_strtolower($name);
    $name = str_replace(
        ['á','é','í','ó','ú','ñ'],
        ['a','e','i','o','u','n'],
        $name
    );
    return preg_replace('/\s+/', ' ', trim($name));
}

/**
 * Cache de secciones en memoria durante el procesamiento del Job.
 * Evita N+1 al procesar cientos de filas.
 */
protected ?Collection $sectionsCache = null;

protected function getSectionsCache(int $schoolId): Collection
{
    if ($this->sectionsCache === null) {
        $this->sectionsCache = SchoolSection::with(['grade'])
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->get();
    }
    return $this->sectionsCache;
}
```

### 3.3 — Persistencia del Estudiante en el Job

```php
// app/Jobs/Students/ProcessStudentImport.php — método processRow()

protected function processRow(array $row, int $schoolId, array $columnMapping, ?int $defaultSectionId): void
{
    // Resolver sección con la nueva lógica tolerante
    $sectionResolution = $this->resolveSection(
        rawSectionName:   $row[$columnMapping['sigerd_section'] ?? ''] ?? null,
        schoolId:         $schoolId,
        defaultSectionId: $defaultSectionId
    );

    // Construir payload del estudiante
    $studentData = [
        'school_id'        => $schoolId,
        'school_section_id'=> $sectionResolution['section_id'],  // null = Sala de Espera
        'first_name'       => $row[$columnMapping['first_name']] ?? '',
        'last_name'        => $row[$columnMapping['last_name']]  ?? '',
        'rnc'              => $this->cleanRnc($row[$columnMapping['rnc'] ?? ''] ?? ''),
        'date_of_birth'    => $this->parseDate($row[$columnMapping['date_of_birth'] ?? ''] ?? ''),
        'gender'           => $this->resolveGender($row[$columnMapping['gender'] ?? ''] ?? ''),
        'enrollment_date'  => now()->toDateString(),
        'is_active'        => true,

        // ← NUEVO en v0.6.0 — campos de tutor para alertas WhatsApp
        'tutor_name'  => $row[$columnMapping['tutor_name']  ?? ''] ?? null,
        'tutor_phone' => $this->normalizePhone($row[$columnMapping['tutor_phone'] ?? ''] ?? ''),

        // ← Metadata con información de SIGERD para el Hub de Matriculación
        'metadata' => array_filter([
            'sigerd_section'   => $sectionResolution['metadata_sigerd'],
            'imported_from'    => 'sigerd',
            'import_batch_id'  => $this->batchId,
            'imported_at'      => now()->toISOString(),
            'section_resolved' => $sectionResolution['resolved'],
        ]),
    ];

    // Usar updateOrCreate por RNC para evitar duplicados
    $rnc = $studentData['rnc'];

    if ($rnc) {
        Student::updateOrCreate(
            ['rnc' => $rnc, 'school_id' => $schoolId],
            $studentData
        );
    } else {
        // Sin RNC: usar nombre + fecha de nacimiento como identificador
        Student::firstOrCreate(
            [
                'first_name'    => $studentData['first_name'],
                'last_name'     => $studentData['last_name'],
                'date_of_birth' => $studentData['date_of_birth'],
                'school_id'     => $schoolId,
            ],
            $studentData
        );
    }
}

/**
 * Normaliza un número de teléfono al formato E.164.
 * "809-555-1234" → "+18095551234"
 * "+1 (809) 555-1234" → "+18095551234"
 */
protected function normalizePhone(?string $phone): ?string
{
    if (empty($phone)) return null;

    $digits = preg_replace('/\D/', '', $phone);

    if (strlen($digits) === 10) {
        // Asumir código de país dominicano/USA
        return '+1' . $digits;
    }

    if (strlen($digits) > 10) {
        return '+' . $digits;
    }

    return null; // Número inválido — no guardar
}
```

### 3.4 — Paso 3 del Wizard: Configurar Sección por Defecto

Agregar al paso 3 del `StudentImportWizard` una opción para definir la sección de fallback. Si el estudiante no hace match automático Y el usuario configuró una sección aquí, va a esa sección en lugar de a la Sala de Espera.

```php
// app/Livewire/App/Students/StudentImportWizard.php — nuevas propiedades

public ?int $defaultSectionId = null;  // Sección de fallback para no-match
public bool $useDefaultSection = false; // Toggle UI

// En la vista del paso 3 (configuración):
// <x-ui.forms.toggle label="Asignar sección por defecto a los no coincidentes"
//     wire:model.live="useDefaultSection" />
// @if($useDefaultSection)
//     <x-ui.forms.select label="Sección de Fallback" wire:model="defaultSectionId">
//         ...opciones de secciones...
//     </x-ui.forms.select>
// @endif
```

### 3.5 — Reporte Post-Importación

El Job debe emitir un reporte detallado al finalizar para que el usuario entienda qué pasó:

```php
// En ProcessStudentImport::handle() — al finalizar

$report = [
    'total_rows'          => $this->totalRows,
    'imported'            => $this->importedCount,
    'updated'             => $this->updatedCount,
    'waiting_room'        => $this->waitingRoomCount,   // Sin sección asignada
    'skipped_duplicates'  => $this->skippedCount,
    'errors'              => $this->errors,
    'waiting_room_groups' => $this->waitingRoomGroups,  // Agrupados por sigerd_section
];

// Notificar al usuario via broadcast/polling
cache()->put("import_result_{$this->batchId}", $report, now()->addHours(24));

// Emitir evento para actualizar UI en tiempo real
event(new StudentImportCompleted(
    schoolId:  $this->schoolId,
    batchId:   $this->batchId,
    report:    $report,
));
```

### 3.6 — Checklist de Completitud — Fase 3

- [ ] `StudentImportWizard` incluye `tutor_name` y `tutor_phone` en `$mappableFields`
- [ ] `tutor_name` y `tutor_phone` opcionales en validación del paso de mapeo
- [ ] Paso 3 del wizard incluye toggle de sección por defecto
- [ ] `resolveSection()` implementa lógica tolerante en 4 niveles
- [ ] `normalizePhone()` convierte a formato E.164
- [ ] Estudiantes sin sección se crean con `school_section_id = null`
- [ ] `metadata->sigerd_section` almacena el nombre crudo del curso de SIGERD
- [ ] `metadata->section_resolved` almacena boolean de si se resolvió automáticamente
- [ ] Cache de secciones en memoria durante el procesamiento del Job (evitar N+1)
- [ ] Reporte post-importación incluye conteo de `waiting_room` y agrupación por `sigerd_section`

---

## Fase 4 — Hub de Matriculación y Asignación Masiva
**Rama:** `feature/enrollment-hub`

### Objetivo

Proveer una interfaz de dos paneles que permite al Director (o Secretaria) distribuir masivamente a los estudiantes que llegaron a la "Sala de Espera" (sin sección asignada) hacia sus cursos definitivos en ORVIAN. La clave de distribución es el campo `metadata->sigerd_section` que almacena el nombre crudo del curso tal como aparece en SIGERD.

### 4.1 — Componente Livewire `EnrollmentHub`

```php
// app/Livewire/App/Academic/EnrollmentHub.php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\Student;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EnrollmentHub extends Component
{
    // Panel izquierdo — filtros de la Sala de Espera
    public string $searchUnassigned    = '';
    public string $filterSigerdSection = '';  // Filtrar por sigerd_section del metadata

    // Selección de estudiantes
    public array $selectedStudentIds = [];
    public bool  $selectAll          = false;

    // Panel derecho — destino de la asignación
    public ?int $targetSectionId = null;
    public ?int $targetShiftId   = null;   // Para filtrar secciones en el árbol derecho

    // Confirmación
    public bool $showConfirmModal = false;

    #[Computed]
    public function unassignedStudents(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Student::query()
            ->whereNull('school_section_id')
            ->where('is_active', true)
            ->when($this->searchUnassigned, fn ($q) =>
                $q->where(fn ($sq) =>
                    $sq->where('first_name', 'like', "%{$this->searchUnassigned}%")
                       ->orWhere('last_name',  'like', "%{$this->searchUnassigned}%")
                       ->orWhere('rnc', 'like', "%{$this->searchUnassigned}%")
                )
            )
            ->when($this->filterSigerdSection, fn ($q) =>
                $q->whereJsonContains('metadata->sigerd_section', $this->filterSigerdSection)
            )
            ->orderBy('last_name')
            ->paginate(30);
    }

    /**
     * Grupos únicos de sigerd_section para el filtro rápido del panel izquierdo.
     * Permite filtrar "todos los que venían del curso 4TO A en SIGERD".
     */
    #[Computed]
    public function sigerdSectionGroups(): Collection
    {
        return Student::query()
            ->whereNull('school_section_id')
            ->where('is_active', true)
            ->whereNotNull('metadata->sigerd_section')
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.sigerd_section')) as sigerd_section, COUNT(*) as total")
            ->groupBy('sigerd_section')
            ->orderBy('total', 'desc')
            ->get();
    }

    #[Computed]
    public function sectionTree(): Collection
    {
        return SchoolSection::with(['grade.level', 'shift'])
            ->where('school_id', auth()->user()->school_id)
            ->where('is_active', true)
            ->when($this->targetShiftId, fn ($q) =>
                $q->where('school_shift_id', $this->targetShiftId)
            )
            ->get()
            ->groupBy(fn ($s) => $s->grade->level->name);
    }

    #[Computed]
    public function shifts(): Collection
    {
        return SchoolShift::where('school_id', auth()->user()->school_id)->get();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selectedStudentIds = $value
            ? Student::whereNull('school_section_id')
                ->where('is_active', true)
                ->pluck('id')
                ->toArray()
            : [];
    }

    public function toggleStudent(int $id): void
    {
        if (in_array($id, $this->selectedStudentIds)) {
            $this->selectedStudentIds = array_values(
                array_filter($this->selectedStudentIds, fn ($i) => $i !== $id)
            );
        } else {
            $this->selectedStudentIds[] = $id;
        }
        $this->selectAll = false;
    }

    public function selectBySigerdSection(string $sigerdSection): void
    {
        $ids = Student::whereNull('school_section_id')
            ->where('is_active', true)
            ->whereJsonContains('metadata->sigerd_section', $sigerdSection)
            ->pluck('id')
            ->toArray();

        $this->selectedStudentIds = array_unique(
            array_merge($this->selectedStudentIds, $ids)
        );
    }

    public function confirmAssign(): void
    {
        if (empty($this->selectedStudentIds)) {
            $this->dispatch('notify', type: 'warning', message: 'Selecciona al menos un estudiante.');
            return;
        }

        if (! $this->targetSectionId) {
            $this->dispatch('notify', type: 'warning', message: 'Selecciona la sección de destino.');
            return;
        }

        $this->showConfirmModal = true;
    }

    public function executeAssignment(): void
    {
        $this->authorize('students.edit');

        $section = SchoolSection::findOrFail($this->targetSectionId);
        $count   = count($this->selectedStudentIds);

        // Asignación masiva en una sola query para performance
        Student::whereIn('id', $this->selectedStudentIds)
            ->whereNull('school_section_id')  // Guard: solo mover los que están en Sala de Espera
            ->update([
                'school_section_id' => $this->targetSectionId,
                // Limpiar sigerd_section del metadata tras asignación exitosa
                // usando JSON_REMOVE para no perder otros campos del metadata
                'metadata' => \DB::raw(
                    "JSON_SET(metadata, '$.assigned_from_waiting_room', true, " .
                    "'$.assigned_at', NOW(), " .
                    "'$.assigned_section_id', {$this->targetSectionId})"
                ),
            ]);

        $this->reset(['selectedStudentIds', 'selectAll', 'targetSectionId', 'showConfirmModal']);
        unset($this->unassignedStudents, $this->sigerdSectionGroups);

        $this->dispatch('notify', type: 'success',
            message: "{$count} estudiante(s) asignados a {$section->fullLabel}.");
    }

    public function render()
    {
        return view('livewire.app.academic.enrollment-hub')
            ->layout('layouts.app-module', config('modules.estudiantes'));
    }
}
```

### 4.2 — Vista `enrollment-hub.blade.php` (Layout de Dos Paneles)

```blade
{{-- resources/views/livewire/app/academic/enrollment-hub.blade.php --}}
<div>
    <x-app.module-toolbar>
        <x-slot:title>
            Hub de Matriculación
            @if($this->unassignedStudents->total() > 0)
                <x-ui.badge variant="warning" size="sm">
                    {{ $this->unassignedStudents->total() }} en Sala de Espera
                </x-ui.badge>
            @endif
        </x-slot:title>
    </x-app.module-toolbar>

    <div class="flex gap-6 h-[calc(100vh-9rem)]">

        {{-- ══ PANEL IZQUIERDO: Sala de Espera ══ --}}
        <div class="w-1/2 flex flex-col bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-white/10 overflow-hidden">

            {{-- Header del panel --}}
            <div class="p-4 border-b border-slate-200 dark:border-white/10 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700 dark:text-white">
                        Estudiantes Sin Asignar
                    </h3>
                    <label class="flex items-center gap-2 text-xs text-slate-500">
                        <input type="checkbox" wire:model.live="selectAll"
                               class="rounded border-slate-300" />
                        Seleccionar todos
                    </label>
                </div>

                {{-- Buscador --}}
                <x-ui.forms.input
                    wire:model.live.debounce.300ms="searchUnassigned"
                    placeholder="Buscar por nombre o cédula..."
                    iconLeft="heroicon-o-magnifying-glass"
                    size="sm" />

                {{-- Filtros rápidos por sigerd_section --}}
                @if($this->sigerdSectionGroups->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        <button wire:click="$set('filterSigerdSection', '')"
                                class="px-2 py-1 rounded-lg text-[10px] font-bold transition-all
                                       {{ empty($filterSigerdSection)
                                           ? 'bg-orvian-orange text-white'
                                           : 'bg-slate-100 dark:bg-white/8 text-slate-500 hover:bg-slate-200' }}">
                            Todos
                        </button>
                        @foreach($this->sigerdSectionGroups as $group)
                            <button
                                wire:click="$set('filterSigerdSection', '{{ $group->sigerd_section }}')"
                                class="px-2 py-1 rounded-lg text-[10px] font-bold transition-all
                                       {{ $filterSigerdSection === $group->sigerd_section
                                           ? 'bg-orvian-orange text-white'
                                           : 'bg-slate-100 dark:bg-white/8 text-slate-500 hover:bg-slate-200' }}">
                                {{ $group->sigerd_section }}
                                <span class="ml-1 opacity-70">({{ $group->total }})</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Botón de selección rápida por grupo --}}
                    @if($filterSigerdSection)
                        <button wire:click="selectBySigerdSection('{{ $filterSigerdSection }}')"
                                class="text-xs text-orvian-orange hover:underline font-semibold">
                            + Seleccionar todos los de "{{ $filterSigerdSection }}"
                        </button>
                    @endif
                @endif
            </div>

            {{-- Lista de estudiantes --}}
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-white/5">
                @forelse($this->unassignedStudents as $student)
                    <label class="flex items-center gap-3 p-3 hover:bg-slate-50
                                  dark:hover:bg-white/3 cursor-pointer transition-colors
                                  {{ in_array($student->id, $selectedStudentIds)
                                      ? 'bg-orvian-orange/5 dark:bg-orvian-orange/10'
                                      : '' }}">
                        <input type="checkbox"
                               wire:click="toggleStudent({{ $student->id }})"
                               @checked(in_array($student->id, $selectedStudentIds))
                               class="rounded border-slate-300 text-orvian-orange" />

                        <x-ui.student-avatar :student="$student" size="sm" />

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                                {{ $student->full_name }}
                            </p>
                            <div class="flex items-center gap-2">
                                @if($student->rnc)
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ $student->rnc }}
                                    </span>
                                @endif
                                @if($student->metadata['sigerd_section'] ?? null)
                                    <x-ui.badge variant="slate" size="xs">
                                        {{ $student->metadata['sigerd_section'] }}
                                    </x-ui.badge>
                                @endif
                            </div>
                        </div>
                    </label>
                @empty
                    <div class="flex flex-col items-center justify-center h-48 text-center p-6">
                        <x-heroicon-o-check-circle class="w-10 h-10 text-green-400 mb-2" />
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                            ¡Sala de Espera vacía!
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Todos los estudiantes tienen sección asignada.
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Paginación --}}
            @if($this->unassignedStudents->hasPages())
                <div class="p-3 border-t border-slate-200 dark:border-white/10">
                    {{ $this->unassignedStudents->links('pagination.orvian-compact') }}
                </div>
            @endif
        </div>

        {{-- ══ PANEL DERECHO: Árbol de Secciones ══ --}}
        <div class="w-1/2 flex flex-col bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-white/10 overflow-hidden">

            {{-- Header del panel --}}
            <div class="p-4 border-b border-slate-200 dark:border-white/10 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700 dark:text-white">
                        Seleccionar Sección de Destino
                    </h3>
                    @if(count($selectedStudentIds) > 0)
                        <x-ui.badge variant="info" size="sm">
                            {{ count($selectedStudentIds) }} seleccionados
                        </x-ui.badge>
                    @endif
                </div>

                {{-- Filtro por tanda --}}
                <div class="flex gap-1.5">
                    <button wire:click="$set('targetShiftId', null)"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all
                                   {{ is_null($targetShiftId)
                                       ? 'bg-orvian-orange text-white'
                                       : 'bg-slate-100 dark:bg-white/8 text-slate-500' }}">
                        Todas las tandas
                    </button>
                    @foreach($this->shifts as $shift)
                        <button wire:click="$set('targetShiftId', {{ $shift->id }})"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all
                                       {{ $targetShiftId === $shift->id
                                           ? 'bg-orvian-orange text-white'
                                           : 'bg-slate-100 dark:bg-white/8 text-slate-500' }}">
                            {{ $shift->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Árbol de secciones (selección por click) --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                @foreach($this->sectionTree as $levelName => $sections)
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400
                                  dark:text-slate-500 mb-3">{{ $levelName }}</p>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($sections->sortBy(fn ($s) => $s->grade->name . $s->label) as $section)
                                <button
                                    wire:click="$set('targetSectionId', {{ $section->id }})"
                                    class="p-3 rounded-xl border-2 text-left transition-all
                                           {{ $targetSectionId === $section->id
                                               ? 'border-orvian-orange bg-orvian-orange/5 ring-1 ring-orvian-orange/20'
                                               : 'border-slate-200 dark:border-white/10 hover:border-slate-300
                                                  dark:hover:border-white/20' }}">
                                    <div class="text-xs font-black text-orvian-orange">
                                        {{ $section->label }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">
                                        {{ $section->grade->name }}
                                    </div>
                                    <div class="text-[9px] text-slate-400 mt-0.5">
                                        {{ $section->shift->name ?? '' }}
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Botón de asignación --}}
            <div class="p-4 border-t border-slate-200 dark:border-white/10">
                <x-ui.button
                    wire:click="confirmAssign"
                    variant="primary"
                    :fullWidth="true"
                    :disabled="empty($selectedStudentIds) || !$targetSectionId">
                    Asignar {{ count($selectedStudentIds) > 0 ? count($selectedStudentIds) . ' estudiante(s)' : '' }}
                    {{ $targetSectionId ? 'a ' . (SchoolSection::find($targetSectionId)?->fullLabel ?? '') : '' }}
                </x-ui.button>
            </div>
        </div>
    </div>

    {{-- Modal de confirmación --}}
    <x-ui.modal wire:model="showConfirmModal" size="sm">
        <x-slot:title>Confirmar Asignación Masiva</x-slot:title>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            Estás a punto de asignar <strong>{{ count($selectedStudentIds) }} estudiante(s)</strong>
            a la sección seleccionada. Esta acción se puede revertir editando cada estudiante.
        </p>
        <x-slot:footer>
            <x-ui.button wire:click="$set('showConfirmModal', false)" variant="ghost" size="sm">
                Cancelar
            </x-ui.button>
            <x-ui.button wire:click="executeAssignment" variant="primary" size="sm">
                Confirmar
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
```

### 4.3 — Ruta

```php
// routes/app/academic.php
Route::get('/academic/enrollment-hub', EnrollmentHub::class)
    ->middleware('can:students.edit')
    ->name('academic.enrollment-hub');
```

### 4.4 — Checklist de Completitud — Fase 4

- [ ] `EnrollmentHub` carga estudiantes con `school_section_id = null`
- [ ] Filtros rápidos por `metadata->sigerd_section` operativos
- [ ] `selectBySigerdSection()` selecciona masivamente un grupo del metadata
- [ ] Panel derecho filtra secciones por tanda
- [ ] `executeAssignment()` actualiza en una sola query (sin N+1)
- [ ] Guard en `executeAssignment()`: solo mueve estudiantes con `section_id = null`
- [ ] Modal de confirmación antes de ejecutar
- [ ] `metadata` actualizada tras asignación (`assigned_from_waiting_room`, `assigned_at`)
- [ ] Ruta protegida por `students.edit`

---

## Fase 5 — Kiosko de Enrolamiento Biométrico
**Rama:** `feature/biometric-kiosk`

### Objetivo

Una interfaz dedicada para capturar y registrar el `face_encoding` de los estudiantes de forma masiva por sección. El diseño prioriza velocidad: el operador ve todos los estudiantes en un grid visual, identifica fácilmente quiénes no tienen biometría, abre el modal de captura directamente desde la tarjeta y actualiza el estado en tiempo real.

### 5.1 — Componente Livewire `BiometricKiosk`

```php
// app/Livewire/App/Academic/BiometricKiosk.php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\Student;
use App\Services\FacialRecognition\FaceEncodingManager;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class BiometricKiosk extends Component
{
    use WithFileUploads;

    // Filtros del grid
    public ?int  $selectedSectionId = null;
    public string $filterBiometric  = '';  // '' | 'with' | 'without'
    public string $search           = '';

    // Enrolamiento
    public ?int  $enrollingStudentId = null;
    public       $capturedPhoto      = null;  // UploadedFile temporal
    public bool  $enrolling          = false;
    public array $enrollResult       = [];

    #[Computed]
    public function sections(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolSection::with(['grade', 'shift'])
            ->where('school_id', auth()->user()->school_id)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($s) => $s->grade->name . $s->label);
    }

    #[Computed]
    public function students(): \Illuminate\Database\Eloquent\Collection
    {
        return Student::query()
            ->where('is_active', true)
            ->when($this->selectedSectionId, fn ($q) =>
                $q->where('school_section_id', $this->selectedSectionId)
            )
            ->when($this->filterBiometric === 'with', fn ($q) =>
                $q->whereNotNull('face_encoding')
            )
            ->when($this->filterBiometric === 'without', fn ($q) =>
                $q->whereNull('face_encoding')
            )
            ->when($this->search, fn ($q) =>
                $q->where(fn ($sq) =>
                    $sq->where('first_name', 'like', "%{$this->search}%")
                       ->orWhere('last_name', 'like', "%{$this->search}%")
                )
            )
            ->with(['section.grade'])
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $students = $this->students;
        return [
            'total'    => $students->count(),
            'enrolled' => $students->whereNotNull('face_encoding')->count(),
            'pending'  => $students->whereNull('face_encoding')->count(),
        ];
    }

    public function openEnrollModal(int $studentId): void
    {
        $this->enrollingStudentId = $studentId;
        $this->capturedPhoto      = null;
        $this->enrollResult       = [];
    }

    public function closeEnrollModal(): void
    {
        $this->enrollingStudentId = null;
        $this->capturedPhoto      = null;
        $this->enrollResult       = [];
    }

    public function enroll(FaceEncodingManager $manager): void
    {
        if (! $this->capturedPhoto || ! $this->enrollingStudentId) {
            $this->dispatch('notify', type: 'error', message: 'Captura una foto primero.');
            return;
        }

        $this->enrolling = true;

        $student = Student::findOrFail($this->enrollingStudentId);

        $success = $manager->enrollStudent($student, $this->capturedPhoto);

        $this->enrolling = false;

        if ($success) {
            $this->enrollResult = ['success' => true, 'message' => 'Biometría registrada correctamente.'];
            unset($this->students, $this->stats);
            // Cerrar modal después de 1.5 segundos (lo hace Alpine en la vista)
            $this->dispatch('enroll-success');
        } else {
            $this->enrollResult = [
                'success' => false,
                'message' => 'No se detectó un rostro claro. Intenta de nuevo con mejor iluminación.',
            ];
        }
    }

    public function render()
    {
        return view('livewire.app.academic.biometric-kiosk')
            ->layout('layouts.app-module', config('modules.estudiantes'));
    }
}
```

### 5.2 — Vista `biometric-kiosk.blade.php`

```blade
{{-- resources/views/livewire/app/academic/biometric-kiosk.blade.php --}}
<div>
    <x-app.module-toolbar>
        <x-slot:title>Kiosko de Enrolamiento Biométrico</x-slot:title>
    </x-app.module-toolbar>

    {{-- Controles --}}
    <div class="flex flex-wrap gap-3 mb-6">
        {{-- Selector de sección --}}
        <select wire:model.live="selectedSectionId"
                class="rounded-xl border border-slate-200 dark:border-white/10 bg-white
                       dark:bg-dark-card text-sm px-3 py-2 text-slate-700 dark:text-white">
            <option value="">Todas las secciones</option>
            @foreach($this->sections as $section)
                <option value="{{ $section->id }}">
                    {{ $section->grade->name }} — Sección {{ $section->label }}
                    ({{ $section->shift->name ?? '' }})
                </option>
            @endforeach
        </select>

        {{-- Filtro por estado biométrico --}}
        <div class="flex rounded-xl border border-slate-200 dark:border-white/10 overflow-hidden">
            @foreach(['' => 'Todos', 'with' => 'Con Biometría', 'without' => 'Sin Biometría'] as $val => $label)
                <button wire:click="$set('filterBiometric', '{{ $val }}')"
                        class="px-3 py-2 text-xs font-semibold transition-all
                               {{ $filterBiometric === $val
                                   ? 'bg-orvian-orange text-white'
                                   : 'bg-white dark:bg-dark-card text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Búsqueda --}}
        <x-ui.forms.input wire:model.live.debounce.300ms="search"
            placeholder="Buscar estudiante..." iconLeft="heroicon-o-magnifying-glass" size="sm" />

        {{-- Stats rápidas --}}
        <div class="ml-auto flex items-center gap-4 text-sm">
            <span class="text-slate-500">
                Total: <strong class="text-slate-800 dark:text-white">{{ $this->stats['total'] }}</strong>
            </span>
            <span class="text-green-600 dark:text-green-400">
                ✓ {{ $this->stats['enrolled'] }}
            </span>
            <span class="text-amber-600 dark:text-amber-400">
                ○ {{ $this->stats['pending'] }}
            </span>
        </div>
    </div>

    {{-- Grid de estudiantes --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
        @forelse($this->students as $student)
            @php $hasBiometric = !empty($student->face_encoding); @endphp
            <div class="relative bg-white dark:bg-dark-card rounded-2xl p-3 border-2
                        transition-all cursor-pointer group
                        {{ $hasBiometric
                            ? 'border-green-200 dark:border-green-900/50'
                            : 'border-slate-200 dark:border-white/10 hover:border-orvian-orange/50' }}"
                 wire:click="{{ !$hasBiometric ? 'openEnrollModal(' . $student->id . ')' : '' }}">

                {{-- Indicador de estado --}}
                <div class="absolute top-2 right-2">
                    @if($hasBiometric)
                        <div class="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center">
                            <x-heroicon-s-check class="w-3 h-3 text-white" />
                        </div>
                    @else
                        <div class="w-5 h-5 rounded-full bg-amber-400 flex items-center justify-center
                                    opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-heroicon-s-camera class="w-3 h-3 text-white" />
                        </div>
                    @endif
                </div>

                {{-- Avatar --}}
                <div class="flex justify-center mb-2">
                    <x-ui.student-avatar :student="$student" size="lg" />
                </div>

                {{-- Nombre --}}
                <p class="text-xs font-semibold text-slate-700 dark:text-white text-center
                           leading-tight truncate">
                    {{ $student->first_name }}
                </p>
                <p class="text-xs text-slate-400 text-center truncate">
                    {{ $student->last_name }}
                </p>

                {{-- Hover: botón de captura --}}
                @if(!$hasBiometric)
                    <div class="absolute inset-0 bg-orvian-orange/5 rounded-2xl flex items-center
                                justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="bg-orvian-orange text-white rounded-xl px-3 py-1.5 text-xs font-bold
                                    shadow-lg">
                            Capturar
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full py-20 text-center">
                <x-heroicon-o-user-group class="w-12 h-12 mx-auto text-slate-300 mb-3" />
                <p class="text-slate-400">No se encontraron estudiantes con los filtros actuales.</p>
            </div>
        @endforelse
    </div>

    {{-- Modal de captura biométrica --}}
    @if($enrollingStudentId)
        @php $enrollingStudent = App\Models\Tenant\Academic\Student::find($enrollingStudentId); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             x-data="{ autoClose: false }"
             @enroll-success.window="autoClose = true; setTimeout(() => $wire.closeEnrollModal(), 1500)">

            <div class="bg-white dark:bg-dark-bg rounded-3xl shadow-2xl w-full max-w-sm p-6 space-y-4">

                {{-- Header --}}
                <div class="flex items-center gap-3">
                    <x-ui.student-avatar :student="$enrollingStudent" size="md" />
                    <div>
                        <p class="font-bold text-slate-800 dark:text-white text-sm">
                            {{ $enrollingStudent->full_name }}
                        </p>
                        <p class="text-xs text-slate-400">Captura de rostro para biometría</p>
                    </div>
                    <button wire:click="closeEnrollModal"
                            class="ml-auto text-slate-400 hover:text-slate-600 transition-colors">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                {{-- Resultado de enrolamiento --}}
                @if(!empty($enrollResult))
                    <div class="p-4 rounded-xl text-center
                                {{ $enrollResult['success']
                                    ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
                                    : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' }}">
                        <p class="text-sm font-semibold">{{ $enrollResult['message'] }}</p>
                    </div>
                @endif

                {{-- Webcam con Alpine.js --}}
                <div x-data="{
                    stream: null,
                    captured: false,

                    async startCamera() {
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: true });
                        this.$refs.video.srcObject = this.stream;
                        this.$refs.video.play();
                    },

                    capture() {
                        const canvas = this.$refs.canvas;
                        const video  = this.$refs.video;
                        canvas.width  = video.videoWidth;
                        canvas.height = video.videoHeight;
                        canvas.getContext('2d').drawImage(video, 0, 0);
                        this.captured = true;

                        canvas.toBlob(blob => {
                            const file = new File([blob], 'capture.jpg', { type: 'image/jpeg' });
                            @this.upload('capturedPhoto', file,
                                (filename) => {},
                                (error) => { console.error('Upload error', error); }
                            );
                        }, 'image/jpeg', 0.92);
                    },

                    retake() {
                        this.captured = false;
                        this.$refs.canvas.getContext('2d').clearRect(
                            0, 0, this.$refs.canvas.width, this.$refs.canvas.height
                        );
                    }
                }"
                x-init="startCamera()">

                    <div class="relative aspect-square rounded-2xl overflow-hidden bg-slate-900">
                        <video x-ref="video" x-show="!captured" class="w-full h-full object-cover"
                               autoplay muted playsinline></video>
                        <canvas x-ref="canvas" x-show="captured" class="w-full h-full object-cover"></canvas>

                        {{-- Guía de encuadre --}}
                        <div x-show="!captured"
                             class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-40 h-48 border-2 border-dashed border-white/40 rounded-full"></div>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-3">
                        <button x-show="!captured" @click="capture()"
                                class="flex-1 py-2.5 bg-orvian-orange text-white rounded-xl
                                       text-sm font-bold hover:opacity-90 transition-opacity">
                            <x-heroicon-s-camera class="w-4 h-4 inline mr-1" />
                            Capturar Foto
                        </button>

                        <button x-show="captured" @click="retake()"
                                class="flex-1 py-2.5 bg-slate-100 dark:bg-white/10 text-slate-600
                                       dark:text-white rounded-xl text-sm font-semibold">
                            Reintentar
                        </button>

                        <button x-show="captured" wire:click="enroll"
                                wire:loading.attr="disabled" wire:target="enroll"
                                class="flex-1 py-2.5 bg-green-500 text-white rounded-xl
                                       text-sm font-bold hover:opacity-90">
                            <span wire:loading.remove wire:target="enroll">Registrar</span>
                            <span wire:loading wire:target="enroll">Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
```

### 5.3 — Ruta

```php
// routes/app/academic.php
Route::get('/academic/biometric-kiosk', BiometricKiosk::class)
    ->middleware('can:students.edit')
    ->name('academic.biometric-kiosk');
```

### 5.4 — Checklist de Completitud — Fase 5

- [ ] Grid visual con indicador verde (con biometría) / ámbar (sin biometría)
- [ ] Filtros por sección, estado biométrico y búsqueda
- [ ] Stats en tiempo real (total / con biometría / sin biometría)
- [ ] Modal de captura con webcam nativa vía Alpine.js
- [ ] Guía de encuadre visual (elipse overlay)
- [ ] Subida asíncrona del blob a Livewire vía `@this.upload`
- [ ] Llamada a `FaceEncodingManager::enrollStudent()` con manejo de error
- [ ] Auto-cierre del modal 1.5s después de enrolamiento exitoso
- [ ] Actualización del grid sin recargar la página (unset computed)

---

## Fase 6 — Evolución de UI Estudiantil
**Rama:** `feature/student-ui-v2`

### 6.1 — `StudentShow` — Sección de Tutor y Resumen de Asistencia

Agregar dos nuevas áreas a la vista de detalle del estudiante: los datos del Tutor (críticos para las alertas de WhatsApp de v0.5.0) y un resumen visual de asistencia histórica con dos barras paralelas (Plantel vs Aula).

```php
// app/Livewire/App/Students/StudentShow.php — nuevas propiedades y computed

use App\Models\Tenant\PlantelAttendanceRecord;
use App\Models\Tenant\Academic\ClassroomAttendanceRecord;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

// Agregar en el componente existente:

public string $attendancePeriod = '30'; // '7' | '30' | '90'

#[Computed]
public function plantelAttendanceSummary(): array
{
    $days  = (int) $this->attendancePeriod;
    $from  = Carbon::now()->subDays($days)->startOfDay();

    $records = PlantelAttendanceRecord::where('student_id', $this->student->id)
        ->where('date', '>=', $from)
        ->get();

    $total   = $records->count();
    $present = $records->whereIn('status', ['present', 'late'])->count();
    $absent  = $records->where('status', 'absent')->count();
    $excused = $records->where('status', 'excused')->count();

    return [
        'total'      => $total,
        'present'    => $present,
        'absent'     => $absent,
        'excused'    => $excused,
        'rate'       => $total > 0 ? round(($present / $total) * 100, 1) : null,
        'late'       => $records->where('status', 'late')->count(),
    ];
}

#[Computed]
public function classroomAttendanceSummary(): array
{
    $days = (int) $this->attendancePeriod;
    $from = Carbon::now()->subDays($days)->startOfDay();

    $records = ClassroomAttendanceRecord::where('student_id', $this->student->id)
        ->where('date', '>=', $from)
        ->get();

    $total   = $records->count();
    $present = $records->whereIn('status', ['present', 'late'])->count();
    $absent  = $records->where('status', 'absent')->count();

    return [
        'total'   => $total,
        'present' => $present,
        'absent'  => $absent,
        'rate'    => $total > 0 ? round(($present / $total) * 100, 1) : null,
    ];
}
```

**Sección de Tutor en la vista** (agregar en el tab "Perfil"):

```blade
{{-- Dentro del tab Perfil de student-show.blade.php --}}
{{-- Sección: Información del Tutor --}}
<div class="mt-8">
    <h4 class="text-[11px] font-black uppercase tracking-widest text-slate-400
               dark:text-slate-500 mb-4">Tutor / Responsable</h4>

    <div class="grid grid-cols-2 gap-4">
        {{-- Nombre del tutor --}}
        <div class="p-4 bg-slate-50 dark:bg-white/3 rounded-xl">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                Nombre
            </p>
            <p class="text-sm font-semibold text-slate-700 dark:text-white">
                {{ $student->tutor_name ?? '—' }}
            </p>
        </div>

        {{-- Teléfono del tutor --}}
        <div class="p-4 bg-slate-50 dark:bg-white/3 rounded-xl">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    WhatsApp
                </p>
                @if($student->tutor_phone)
                    <x-ui.badge variant="success" size="xs">Activo para alertas</x-ui.badge>
                @else
                    <x-ui.badge variant="warning" size="xs">Sin número</x-ui.badge>
                @endif
            </div>
            <p class="text-sm font-semibold text-slate-700 dark:text-white font-mono">
                {{ $student->tutor_phone ?? 'No registrado' }}
            </p>
            @if(!$student->tutor_phone)
                <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-1">
                    ⚠ Sin número de tutor, las alertas automáticas de asistencia no se enviarán.
                </p>
            @endif
        </div>
    </div>
</div>

{{-- Sección: Resumen de Asistencia Histórica --}}
<div class="mt-8">
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-[11px] font-black uppercase tracking-widest text-slate-400
                   dark:text-slate-500">Asistencia Histórica</h4>

        {{-- Selector de período --}}
        <div class="flex rounded-lg border border-slate-200 dark:border-white/10 overflow-hidden">
            @foreach(['7' => '7d', '30' => '30d', '90' => '90d'] as $val => $label)
                <button wire:click="$set('attendancePeriod', '{{ $val }}')"
                        class="px-2.5 py-1 text-xs font-bold transition-all
                               {{ $attendancePeriod === $val
                                   ? 'bg-orvian-orange text-white'
                                   : 'bg-transparent text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Barras de Plantel y Aula --}}
    <div class="space-y-4">
        {{-- Plantel --}}
        @php $plantel = $this->plantelAttendanceSummary; @endphp
        <div>
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="font-semibold text-slate-600 dark:text-slate-300">
                    🚪 Plantel (Entrada)
                </span>
                <span class="font-black text-slate-800 dark:text-white">
                    {{ $plantel['rate'] !== null ? $plantel['rate'] . '%' : 'Sin datos' }}
                </span>
            </div>
            <div class="w-full h-2.5 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500
                            {{ ($plantel['rate'] ?? 100) >= 85 ? 'bg-green-500' :
                               (($plantel['rate'] ?? 100) >= 70 ? 'bg-amber-400' : 'bg-red-500') }}"
                     style="width: {{ $plantel['rate'] ?? 0 }}%"></div>
            </div>
            <div class="flex gap-4 mt-1.5">
                <span class="text-[10px] text-green-600">✓ {{ $plantel['present'] }} pres.</span>
                <span class="text-[10px] text-amber-500">⏱ {{ $plantel['late'] }} tard.</span>
                <span class="text-[10px] text-red-500">✗ {{ $plantel['absent'] }} aus.</span>
                <span class="text-[10px] text-blue-500">📋 {{ $plantel['excused'] }} just.</span>
            </div>
        </div>

        {{-- Aula --}}
        @php $classroom = $this->classroomAttendanceSummary; @endphp
        <div>
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="font-semibold text-slate-600 dark:text-slate-300">
                    📚 Aula (Clases)
                </span>
                <span class="font-black text-slate-800 dark:text-white">
                    {{ $classroom['rate'] !== null ? $classroom['rate'] . '%' : 'Sin datos' }}
                </span>
            </div>
            <div class="w-full h-2.5 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500
                            {{ ($classroom['rate'] ?? 100) >= 85 ? 'bg-purple-500' :
                               (($classroom['rate'] ?? 100) >= 70 ? 'bg-amber-400' : 'bg-red-500') }}"
                     style="width: {{ $classroom['rate'] ?? 0 }}%"></div>
            </div>
            <div class="flex gap-4 mt-1.5">
                <span class="text-[10px] text-purple-600">✓ {{ $classroom['present'] }} pres.</span>
                <span class="text-[10px] text-red-500">✗ {{ $classroom['absent'] }} aus.</span>
            </div>
        </div>
    </div>
</div>
```

### 6.2 — `StudentIndex` — Filtros Rápidos Visuales y Slide-Over Preview

```php
// app/Livewire/App/Students/StudentIndex.php — nuevas propiedades

// Slide-over preview
public ?int  $previewStudentId  = null;
public bool  $showPreviewSlider = false;

// Filtros rápidos visuales (chips)
public string $quickFilter = '';  // '' | 'no_section' | 'no_biometric' | 'no_tutor_phone'

#[Computed]
public function previewStudent(): ?Student
{
    return $this->previewStudentId
        ? Student::with(['section.grade.level', 'section.shift', 'user'])->find($this->previewStudentId)
        : null;
}

public function openPreview(int $studentId): void
{
    $this->previewStudentId  = $studentId;
    $this->showPreviewSlider = true;
}

public function closePreview(): void
{
    $this->showPreviewSlider = false;
    $this->previewStudentId  = null;
}

// En el método de la query base, agregar filtros rápidos:
protected function buildQuery()
{
    return Student::withIndexRelations()
        ->when($this->quickFilter === 'no_section', fn ($q) =>
            $q->whereNull('school_section_id')
        )
        ->when($this->quickFilter === 'no_biometric', fn ($q) =>
            $q->whereNull('face_encoding')
        )
        ->when($this->quickFilter === 'no_tutor_phone', fn ($q) =>
            $q->whereNull('tutor_phone')->orWhere('tutor_phone', '')
        )
        // ... filtros existentes
    ;
}
```

**Chips de filtro rápido en la vista** (agregar en la toolbar de StudentIndex):

```blade
{{-- Chips de filtros rápidos visuales --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach([
        ''               => ['label' => 'Todos',              'icon' => 'heroicon-o-users'],
        'no_section'     => ['label' => 'Sala de Espera',      'icon' => 'heroicon-o-clock'],
        'no_biometric'   => ['label' => 'Sin Biometría',       'icon' => 'heroicon-o-eye-slash'],
        'no_tutor_phone' => ['label' => 'Sin Tel. de Tutor',   'icon' => 'heroicon-o-phone-x-mark'],
    ] as $value => $chip)
        <button wire:click="$set('quickFilter', '{{ $value }}')"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold
                       transition-all border
                       {{ $quickFilter === $value
                           ? 'bg-orvian-orange text-white border-orvian-orange shadow-sm'
                           : 'bg-white dark:bg-dark-card text-slate-500 border-slate-200
                              dark:border-white/10 hover:border-slate-300' }}">
            <x-dynamic-component :component="$chip['icon']" class="w-3.5 h-3.5" />
            {{ $chip['label'] }}
        </button>
    @endforeach
</div>
```

**Slide-Over Preview** (agregar al final del template):

```blade
{{-- Slide-Over de Preview del Estudiante --}}
@teleport('body')
<div x-data="{ show: @entangle('showPreviewSlider') }"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50 flex justify-end">

    {{-- Overlay --}}
    <div @click="$wire.closePreview()"
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

    {{-- Panel --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="relative w-80 bg-white dark:bg-dark-bg shadow-2xl flex flex-col h-full">

        @if($this->previewStudent)
            @php $s = $this->previewStudent; @endphp

            {{-- Header --}}
            <div class="p-5 border-b border-slate-200 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <x-ui.student-avatar :student="$s" size="lg" />
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-800 dark:text-white text-sm leading-snug">
                            {{ $s->full_name }}
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $s->section?->fullLabel ?? 'Sin sección' }}
                        </p>
                    </div>
                    <button wire:click="closePreview" class="text-slate-400 hover:text-slate-600">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
            </div>

            {{-- Datos rápidos --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-4">

                {{-- RNC --}}
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                        Cédula / RNC
                    </p>
                    <p class="text-sm font-mono text-slate-700 dark:text-white">
                        {{ $s->rnc ?? '—' }}
                    </p>
                </div>

                {{-- Tanda --}}
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                        Tanda
                    </p>
                    <p class="text-sm text-slate-700 dark:text-white">
                        {{ $s->section?->shift?->name ?? '—' }}
                    </p>
                </div>

                {{-- Tutor --}}
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                        Tutor
                    </p>
                    <p class="text-sm text-slate-700 dark:text-white">
                        {{ $s->tutor_name ?? '—' }}
                    </p>
                    @if($s->tutor_phone)
                        <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $s->tutor_phone }}</p>
                    @else
                        <p class="text-xs text-amber-500 mt-0.5">Sin teléfono de tutor</p>
                    @endif
                </div>

                {{-- Estado biométrico --}}
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                        Biometría
                    </p>
                    @if($s->face_encoding)
                        <x-ui.badge variant="success" size="sm">Enrolado</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning" size="sm">Sin biometría</x-ui.badge>
                    @endif
                </div>

                {{-- Estado sala de espera (si aplica) --}}
                @if(isset($s->metadata['sigerd_section']) && is_null($s->school_section_id))
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border
                                border-amber-200 dark:border-amber-700/50">
                        <p class="text-xs font-bold text-amber-700 dark:text-amber-300 mb-1">
                            ⏳ En Sala de Espera
                        </p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">
                            Curso SIGERD: "{{ $s->metadata['sigerd_section'] }}"
                        </p>
                    </div>
                @endif
            </div>

            {{-- Acciones --}}
            <div class="p-5 border-t border-slate-200 dark:border-white/10 space-y-2">
                <x-ui.button :href="route('app.academic.students.show', $s)" variant="primary"
                    :fullWidth="true" size="sm">
                    Ver Perfil Completo
                </x-ui.button>
                <x-ui.button :href="route('app.academic.students.edit', $s)" variant="ghost"
                    :fullWidth="true" size="sm">
                    Editar
                </x-ui.button>
            </div>
        @endif
    </div>
</div>
@endteleport
```

### 6.3 — Checklist de Completitud — Fase 6

- [ ] `StudentShow` tiene sección de Tutor (nombre + teléfono + badge de alertas activas)
- [ ] `StudentShow` tiene resumen de asistencia con barras Plantel vs Aula
- [ ] Selector de período (7d / 30d / 90d) actualiza las barras reactivamente
- [ ] `plantelAttendanceSummary` y `classroomAttendanceSummary` como `#[Computed]`
- [ ] `StudentIndex` tiene chips de filtro rápido visual (todos / sala de espera / sin biometría / sin tel.)
- [ ] `quickFilter` integrado en el `buildQuery()` del index
- [ ] Slide-Over preview implementado con `@teleport('body')`
- [ ] Preview muestra: nombre, sección, tanda, tutor, estado biométrico, sala de espera
- [ ] Slide-Over incluye botones de "Ver Perfil" y "Editar"

---

## Fase 7 — Rediseño UX de Asignación de Materias (`TeacherAssignments`)
**Rama:** `feature/teacher-assignments-ux`

### Objetivo

Reemplazar la interfaz de doble select (Sección → Materia → Botón Asignar) por una experiencia de paneles fluida. El operador selecciona la sección en el panel izquierdo y el panel derecho presenta un grid de botones de asignaturas agrupadas (Básicas vs Técnicas), coloreadas con `$subject->color`, que actúan como toggles de 1 clic para asignar o desasignar.

### 7.1 — Actualización del Componente `TeacherAssignments`

```php
// app/Livewire/App/Teachers/TeacherAssignments.php — versión rediseñada

namespace App\Livewire\App\Academic\Teachers;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\Subject;
use App\Models\Tenant\Academic\TeacherSubjectSection;
use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Academic\Teacher;
use App\Services\Academic\Teachers\TeacherAssignmentService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeacherAssignments extends Component
{
    public Teacher $teacher;
    public ?int    $activeSectionId = null;  // Sección seleccionada en panel izquierdo

    public function mount(Teacher $teacher): void
    {
        $this->teacher = $teacher->load(['assignments.subject', 'assignments.section.grade']);

        // Pre-seleccionar la primera sección del maestro si tiene asignaciones
        $this->activeSectionId = $this->teacher->assignments->first()?->school_section_id;
    }

    #[Computed]
    public function sections(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolSection::with(['grade.level', 'shift'])
            ->where('school_id', $this->teacher->school_id)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($s) => $s->grade->name . $s->label);
    }

    /**
     * Todas las materias disponibles para la escuela, con indicador de si están
     * asignadas al maestro en la sección activa.
     */
    #[Computed]
    public function subjectsForActiveSection(): array
    {
        if (! $this->activeSectionId) {
            return ['basic' => collect(), 'technical' => collect()];
        }

        $year = AcademicYear::where('school_id', $this->teacher->school_id)
            ->where('is_active', true)
            ->first();

        $assignedSubjectIds = TeacherSubjectSection::where('teacher_id', $this->teacher->id)
            ->where('school_section_id', $this->activeSectionId)
            ->where('academic_year_id', $year?->id)
            ->where('is_active', true)
            ->pluck('subject_id')
            ->toArray();

        $allSubjects = Subject::availableForSchool($this->teacher->school_id)
            ->active()
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'code'       => $s->code,
                'color'      => $s->color,
                'type'       => $s->type,
                'is_assigned'=> in_array($s->id, $assignedSubjectIds),
            ]);

        return [
            'basic'     => $allSubjects->where('type', Subject::TYPE_BASIC)->values(),
            'technical' => $allSubjects->where('type', Subject::TYPE_TECHNICAL)->values(),
        ];
    }

    /**
     * Toggle de asignación: asigna si no está asignada, desasigna si ya está.
     * Un solo clic — sin confirmación (la UI muestra el estado claramente).
     */
    public function toggleSubject(int $subjectId): void
    {
        $this->authorize('teachers.assign_subjects');

        $year = AcademicYear::where('school_id', $this->teacher->school_id)
            ->where('is_active', true)
            ->firstOrFail();

        $existing = TeacherSubjectSection::where('teacher_id', $this->teacher->id)
            ->where('subject_id', $subjectId)
            ->where('school_section_id', $this->activeSectionId)
            ->where('academic_year_id', $year->id)
            ->first();

        if ($existing) {
            // Desasignar
            app(TeacherAssignmentService::class)->remove($existing);
            $this->dispatch('notify', type: 'info', message: 'Materia desasignada.');
        } else {
            // Asignar
            try {
                app(TeacherAssignmentService::class)->assign(
                    $this->teacher,
                    $subjectId,
                    $this->activeSectionId
                );
                $this->dispatch('notify', type: 'success', message: 'Materia asignada.');
            } catch (\Illuminate\Database\QueryException) {
                $this->dispatch('notify', type: 'error', message: 'Esta asignación ya existe.');
            }
        }

        // Invalidar computed para re-renderizar el grid
        unset($this->subjectsForActiveSection);
        $this->teacher->refresh();
    }

    public function render()
    {
        return view('livewire.app.academic.teachers.teacher-assignments')
            ->layout('layouts.app-module', config('modules.configuracion'));
    }
}
```

### 7.2 — Vista `teacher-assignments.blade.php` (Layout de Dos Paneles)

```blade
{{-- resources/views/livewire/app/teachers/teacher-assignments.blade.php --}}
<div>
    <x-app.module-toolbar>
        <x-slot:title>
            Asignación de Materias —
            <span class="font-normal text-slate-500">{{ $teacher->full_name }}</span>
        </x-slot:title>
        <x-slot:actions>
            <x-ui.button :href="route('app.academic.teachers.show', $teacher)" variant="ghost" size="sm"
                iconLeft="heroicon-o-arrow-left">
                Volver
            </x-ui.button>
        </x-slot:actions>
    </x-app.module-toolbar>

    <div class="flex gap-6">

        {{-- ══ PANEL IZQUIERDO: Secciones ══ --}}
        <div class="w-64 flex-shrink-0">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400
                      dark:text-slate-500 mb-3">Secciones del Centro</p>

            <div class="space-y-1">
                @foreach($this->sections->groupBy(fn ($s) => $s->grade->level->name) as $nivel => $secciones)
                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400
                              dark:text-slate-600 px-2 pt-3 pb-1">{{ $nivel }}</p>

                    @foreach($secciones as $section)
                        @php
                            $assignedCount = $teacher->assignments
                                ->where('school_section_id', $section->id)
                                ->where('is_active', true)
                                ->count();
                        @endphp

                        <button wire:click="$set('activeSectionId', {{ $section->id }})"
                                class="w-full flex items-center justify-between px-3 py-2.5
                                       rounded-xl text-left transition-all
                                       {{ $activeSectionId === $section->id
                                           ? 'bg-orvian-orange text-white shadow-sm'
                                           : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100
                                              dark:hover:bg-white/5' }}">

                            <div>
                                <p class="text-xs font-bold leading-none">
                                    {{ $section->grade->name }}
                                    <span class="font-black">{{ $section->label }}</span>
                                </p>
                                <p class="text-[10px] opacity-70 mt-0.5">
                                    {{ $section->shift->name ?? '' }}
                                </p>
                            </div>

                            @if($assignedCount > 0)
                                <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full
                                             {{ $activeSectionId === $section->id
                                                 ? 'bg-white/20 text-white'
                                                 : 'bg-orvian-orange/10 text-orvian-orange' }}">
                                    {{ $assignedCount }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- ══ PANEL DERECHO: Grid de Materias ══ --}}
        <div class="flex-1 min-w-0">
            @if(! $activeSectionId)
                <div class="flex flex-col items-center justify-center h-64 text-center">
                    <x-heroicon-o-arrow-left class="w-8 h-8 text-slate-300 mb-3" />
                    <p class="text-sm text-slate-400">Selecciona una sección para ver las materias</p>
                </div>
            @else
                @php $subjects = $this->subjectsForActiveSection; @endphp

                {{-- Materias Básicas --}}
                @if($subjects['basic']->isNotEmpty())
                    <div class="mb-8">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400
                                  dark:text-slate-500 mb-3">Materias Básicas / Académicas</p>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                            @foreach($subjects['basic'] as $subject)
                                <button wire:click="toggleSubject({{ $subject['id'] }})"
                                        class="relative p-3 rounded-2xl border-2 text-left transition-all
                                               hover:shadow-sm active:scale-95
                                               {{ $subject['is_assigned']
                                                   ? 'border-transparent text-white shadow-sm'
                                                   : 'border-slate-200 dark:border-white/10 bg-white dark:bg-dark-card
                                                      text-slate-600 dark:text-slate-400 hover:border-slate-300' }}"
                                        style="{{ $subject['is_assigned']
                                            ? 'background-color: ' . $subject['color'] . '; border-color: ' . $subject['color']
                                            : '' }}">

                                    {{-- Indicador de asignado --}}
                                    @if($subject['is_assigned'])
                                        <div class="absolute top-2 right-2">
                                            <x-heroicon-s-check-circle class="w-4 h-4 text-white/80" />
                                        </div>
                                    @endif

                                    {{-- Dot de color (cuando no está asignado) --}}
                                    @if(!$subject['is_assigned'])
                                        <div class="w-3 h-3 rounded-full mb-2"
                                             style="background-color: {{ $subject['color'] }}"></div>
                                    @endif

                                    <p class="text-xs font-black leading-snug pr-5">
                                        {{ $subject['name'] }}
                                    </p>
                                    <p class="text-[9px] font-mono opacity-60 mt-0.5">
                                        {{ $subject['code'] }}
                                    </p>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Módulos Técnicos --}}
                @if($subjects['technical']->isNotEmpty())
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400
                                  dark:text-slate-500 mb-3">Módulos Técnicos</p>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                            @foreach($subjects['technical'] as $subject)
                                <button wire:click="toggleSubject({{ $subject['id'] }})"
                                        class="relative p-3 rounded-2xl border-2 text-left transition-all
                                               hover:shadow-sm active:scale-95
                                               {{ $subject['is_assigned']
                                                   ? 'border-transparent text-white shadow-sm'
                                                   : 'border-dashed border-slate-200 dark:border-white/10 bg-white
                                                      dark:bg-dark-card text-slate-600 dark:text-slate-400
                                                      hover:border-slate-300' }}"
                                        style="{{ $subject['is_assigned']
                                            ? 'background-color: ' . $subject['color'] . '; border-color: ' . $subject['color']
                                            : '' }}">

                                    @if($subject['is_assigned'])
                                        <div class="absolute top-2 right-2">
                                            <x-heroicon-s-check-circle class="w-4 h-4 text-white/80" />
                                        </div>
                                    @endif

                                    @if(!$subject['is_assigned'])
                                        <div class="w-3 h-3 rounded-full mb-2 opacity-70"
                                             style="background-color: {{ $subject['color'] }}"></div>
                                    @endif

                                    <p class="text-xs font-black leading-snug pr-5">
                                        {{ $subject['name'] }}
                                    </p>
                                    <p class="text-[9px] font-mono opacity-60 mt-0.5">
                                        {{ $subject['code'] }}
                                    </p>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($subjects['basic']->isEmpty() && $subjects['technical']->isEmpty())
                    <div class="flex flex-col items-center justify-center h-48 text-center">
                        <x-heroicon-o-book-open class="w-10 h-10 text-slate-300 mb-3" />
                        <p class="text-sm text-slate-400">
                            No hay materias disponibles para esta sección.
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Verifica la configuración de títulos técnicos del centro.
                        </p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
```

### 7.3 — Checklist de Completitud — Fase 7

- [ ] Panel izquierdo muestra secciones agrupadas por nivel con conteo de materias asignadas
- [ ] Panel derecho carga materias dinámicamente al seleccionar sección
- [ ] Botones de materias actúan como toggles de 1 clic (asignar/desasignar)
- [ ] Materias asignadas muestran fondo de color (`$subject->color`) + check icon
- [ ] Materias no asignadas muestran dot de color + borde neutro
- [ ] Módulos técnicos separados visualmente (sección aparte con borde dashed)
- [ ] `toggleSubject()` usa `#[Computed]` con invalidación (`unset`) para re-renderizar sin AJAX manual
- [ ] Guard de unique constraint al asignar
- [ ] `TeacherAssignmentService::remove()` desactiva si tiene registros de asistencia, elimina si no

---

## Checklist de Completitud Final — v0.6.0

### Fase 1 — Refactorización de Namespaces
- [ ] `Student` y `Teacher` en `App\Models\Tenant\Academic`
- [ ] Observers en `App\Observers\Tenant\Academic`
- [ ] Factories en `Database\Factories\Tenant\Academic`
- [ ] Aliases de backward-compat añadidos, Find & Replace ejecutado, aliases eliminados
- [ ] `git grep` para namespace antiguo = 0 resultados
- [ ] Suite de tests pasa sin errores tras la migración

### Fase 2 — Academic Builder
- [ ] Componente `AcademicBuilder` con CRUD de secciones via Cards
- [ ] Edición inline sin modales separados
- [ ] Guard de estudiantes activos antes de desactivar sección
- [ ] Ruta y link en `config/modules.php` activos

### Fase 3 — Importador SIGERD v2
- [ ] `tutor_name` y `tutor_phone` en `$mappableFields` del wizard
- [ ] `resolveSection()` tolerante: 4 niveles de resolución
- [ ] Sala de Espera funcional (`school_section_id = null`)
- [ ] `metadata->sigerd_section` almacena nombre crudo
- [ ] `normalizePhone()` convierte a E.164
- [ ] Reporte post-importación incluye `waiting_room_count` y agrupación por `sigerd_section`

### Fase 4 — Hub de Matriculación
- [ ] Dos paneles: Sala de Espera vs Árbol de Secciones
- [ ] Filtros rápidos por `sigerd_section` del metadata
- [ ] `selectBySigerdSection()` para selección masiva por grupo
- [ ] `executeAssignment()` en una sola query
- [ ] Modal de confirmación antes de ejecutar
- [ ] `metadata` actualizada tras asignación exitosa

### Fase 5 — Kiosko Biométrico
- [ ] Grid visual con indicadores de estado biométrico
- [ ] Filtros por sección, estado y búsqueda
- [ ] Modal de captura con webcam nativa
- [ ] Guía de encuadre (elipse overlay)
- [ ] Integración con `FaceEncodingManager::enrollStudent()`
- [ ] Auto-cierre tras éxito

### Fase 6 — UI Estudiantil Mejorada
- [ ] `StudentShow` tiene sección de Tutor con alert de alertas WhatsApp
- [ ] Barras de asistencia Plantel vs Aula con selector de período
- [ ] `StudentIndex` tiene chips de filtro rápido
- [ ] Slide-Over preview implementado

### Fase 7 — TeacherAssignments Rediseñado
- [ ] Doble select eliminado, reemplazado por paneles
- [ ] Grid de materias coloreadas como toggles
- [ ] Asignación/desasignación en 1 clic
- [ ] Separación visual Básicas vs Técnicas

---

## Decisiones de Arquitectura Registradas

**Filosofía del Importador Tolerante:** ORVIAN no obliga al usuario a preparar el Excel de SIGERD. El sistema absorbe el archivo tal como viene y resuelve la estructura internamente. Lo que no puede resolver automáticamente lo coloca en la "Sala de Espera" para distribución manual masiva en el Hub de Matriculación. Esta decisión reduce drásticamente la fricción de onboarding académico al inicio del año escolar.

**`metadata->sigerd_section` como puente entre sistemas:** Al preservar el nombre crudo del curso de SIGERD en el campo `metadata`, ORVIAN mantiene la trazabilidad de origen de cada estudiante sin necesidad de una tabla adicional. El Hub de Matriculación aprovecha esta información para agrupar y facilitar la distribución masiva.

**Toggles de 1 clic vs Formulario de asignación:** La decisión de usar botones-toggle en `TeacherAssignments` elimina el "flujo formulario" (seleccionar → confirmar → guardar) reemplazándolo por interacción directa. El estado visual del botón (coloreado = asignado / neutro = libre) es suficiente feedback. Esta elección prioriza la velocidad de operación para un Director que asigna 30+ materias al inicio del año.

**`BiometricKiosk` como interfaz dedicada vs modal en el Index:** Tener un componente dedicado para el enrolamiento biométrico permite que el operador trabaje en una pantalla optimizada para captura (grid grande, sin distracción de otros controles). Es más eficiente que abrir modales desde el índice general de estudiantes.

**Aliases de backward-compatibility durante la migración de namespaces:** El uso de `class_alias()` garantiza que el refactor de namespaces sea completamente reversible y no produzca errores fatales en producción si algún `use` quedó sin actualizar. Los aliases se eliminan solo cuando todas las referencias están actualizadas y los tests pasan.

**`#[Computed]` con `unset` para invalidación reactiva:** En lugar de usar `wire:poll` o forzar re-renders completos, los componentes de esta versión usan el patrón `unset($this->computedProperty)` para invalidar selectivamente la caché del computed y forzar su recálculo en el siguiente render. Esto mantiene la UI reactiva sin el costo de renders innecesarios.

---

## Notas de Implementación

**Orden de dependencias obligatorio:**
1. Fase 1 (Namespaces) debe completarse antes de tocar cualquier otro archivo — un namespace incorrecto puede romper silenciosamente la lógica de importación.
2. Fase 3 (Importador) debe completarse antes que Fase 4 (Hub) — el Hub depende de que existan estudiantes en Sala de Espera.
3. Fases 2, 5, 6 y 7 son independientes entre sí y pueden desarrollarse en paralelo.

**Rendimiento del Hub de Matriculación:** La query de `sigerdSectionGroups` usa `JSON_UNQUOTE(JSON_EXTRACT(...))` que puede ser lento sin índice en MySQL 5.7. En MySQL 8+ o MariaDB 10.5+, considerar un Generated Column indexado para `metadata->sigerd_section` si el volumen de estudiantes en Sala de Espera supera los 500 registros.

**Webcam en entornos HTTPS:** La API `navigator.mediaDevices.getUserMedia()` del Kiosko Biométrico requiere `HTTPS` o `localhost`. En desarrollo con `sail`, esto funciona en `localhost`. En producción, asegurar que `APP_URL` use `https://` y el servidor tenga certificado SSL válido.

**Tiempo estimado de desarrollo:** 4–6 semanas (con un desarrollador trabajando en ORVIAN). Las fases 1 y 3 son las más críticas y deben ser priorizadas.