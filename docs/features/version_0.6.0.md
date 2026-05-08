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


### 1.3 — Checklist de Completitud — Fase 1

- [x] Mover componentes livewire relacionados a asignaciones docentes a `App\Livewire\App\Academic\Teachers` y ``App\Livewire\App\Academic\Students`` respectivamente.
- [x] Actualizar namespaces de las vistas blade relacionadas ``resources/views/app/academic/teachers`` y ``resources/views/app/academic/students``.
- [x] Refactorizar servicios relacionados a asignaciones docentes a `App\Services\Academic\Teachers` y `App\Services\Academic\Students`.
- [x] Actualizar filtros ``App\Filters\Academic\TeacherFilter`` y ``App\Filters\Academic\StudentFilter`` respectivamente.

---

## Fase 2 — Academic Builder (Gestión de Cursos)
**Rama:** `feature/academic-builder`

### Diagnóstico y Decisión Arquitectónica

El wizard de configuración inicial crea una estructura *genérica* basada en combinaciones cartesianas (niveles × grados × paralelos × tandas). Esa data es un punto de partida, no la realidad del centro. El Director necesita un flujo para refinarla: eliminar lo que no existe, crear lo que falta, desactivar lo que ya no aplica al año siguiente.

Meter todo (visualizar + crear + editar) en un solo componente genera una vista inmanejable. La solución es **separar en tres vistas con responsabilidades únicas**.

---

### Arquitectura de las Tres Vistas

```
/academic/courses              → CourseIndex   (visualizar, desactivar, eliminar)
/academic/courses/create       → CourseForm    (crear una sección nueva)
/academic/courses/{section}    → CourseShow    (detalle + estudiantes de la sección)
```

**Regla semántica para secciones:**
- **Eliminar (soft delete):** Solo si la sección nunca tuvo estudiantes. Quita basura del wizard.
- **Desactivar (`is_active = false`):** Si tuvo actividad histórica pero no va el próximo año. Conserva el historial de asistencia y notas.

---

### 2.0 — Migraciones Previas

Antes de implementar las vistas, se necesitan dos cambios en la base de datos.

#### 2.0.1 — `is_active` y `deleted_at` en `school_sections`

```php
// database/migrations/xxxx_update_school_sections_add_status_fields.php
public function up(): void
{
    Schema::table('school_sections', function (Blueprint $table) {
        $table->boolean('is_active')->default(true)->after('technical_title_id');
        $table->softDeletes()->after('updated_at'); // deleted_at
    });
}

public function down(): void
{
    Schema::table('school_sections', function (Blueprint $table) {
        $table->dropColumn('is_active');
        $table->dropSoftDeletes();
    });
}
```

#### 2.0.2 — Confirmar que `school_levels` existe

La tabla `school_levels` (pivote `school_id` ↔ `level_id`) ya tiene su migración. Confirmar que está ejecutada. Es el origen de verdad para saber qué niveles habilitó el wizard para cada escuela.

```bash
php artisan migrate:status | grep school_levels
# Debe aparecer como "Ran"
```

---

### 2.1 — Actualizaciones al Modelo `SchoolSection`

Agregar `SoftDeletes` y los scopes necesarios. El modelo ya tiene `is_active` y `scopeActive()` desde la iteración anterior:

```php
// app/Models/Tenant/Academic/SchoolSection.php
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolSection extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'school_shift_id', 'grade_id',
        'label', 'technical_title_id', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    // ... relaciones existentes sin cambios ...

    // Scope: lo que muestra el Index (activas, no eliminadas)
    public function scopeVisible($query)
    {
        return $query->where('is_active', true);
        // SoftDeletes aplica automáticamente whereNull('deleted_at')
    }

    // Scope: incluye historial para reportes y auditoría
    public function scopeWithHistory($query)
    {
        return $query->withTrashed();
    }

    // Scope: secciones que el wizard creó y nunca tuvieron estudiantes
    public function scopeEmpty($query)
    {
        return $query->doesntHave('students');
    }
}
```

---

### 2.2 — `CourseIndex` (Visualización y Acciones Destructivas)

**Responsabilidad única:** mostrar la estructura agrupada, permitir navegar al detalle y al formulario de creación, y ejecutar las acciones destructivas (desactivar / eliminar silenciosamente las del wizard).

```php
// app/Livewire/App/Academic/CourseIndex.php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\AcademicYear;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CourseIndex extends Component
{
    // ── Confirmación de eliminación ────────────────────────────────
    public ?int  $deletingSectionId   = null;
    public bool  $showDeleteConfirm   = false;

    // ── Estructura computada ───────────────────────────────────────
    #[Computed]
    public function structure(): array
    {
        // Traemos TODAS (activas e inactivas) para que el Director
        // pueda ver qué desactivar. Solo excluimos las soft-deleted.
        $sections = SchoolSection::with([
            'grade.level',
            'shift',
            'technicalTitle.family',
            'students' => fn ($q) => $q->active()->select('id', 'school_section_id'),
        ])
        ->where('school_id', Auth::user()->school_id)
        ->get(); // SoftDeletes excluye deleted_at automáticamente

        return $sections
            ->groupBy(fn ($s) => $s->grade->level->name)
            ->map(fn ($byLevel, $levelName) => [
                'name'   => $levelName,
                'grades' => $byLevel
                    ->groupBy(fn ($s) => $s->grade->id)
                    ->map(fn ($byGrade) => [
                        'id'       => $byGrade->first()->grade->id,
                        'name'     => $byGrade->first()->grade->name,
                        'academic' => $byGrade
                            ->filter(fn ($s) => is_null($s->technical_title_id))
                            ->sortBy('label')
                            ->values(),
                        'technical_groups' => $byGrade
                            ->filter(fn ($s) => ! is_null($s->technical_title_id))
                            ->groupBy(fn ($s) => $s->technicalTitle->name ?? 'Técnico')
                            ->map(fn ($group, $titleName) => [
                                'title'    => $titleName,
                                'family'   => $group->first()->technicalTitle->family->name ?? null,
                                'sections' => $group->sortBy('label')->values(),
                            ])
                            ->values(),
                    ])
                    ->values(),
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        $sections = SchoolSection::where('school_id', Auth::user()->school_id)->get();
        return [
            'total_active'   => $sections->where('is_active', true)->count(),
            'total_inactive' => $sections->where('is_active', false)->count(),
            'total_students' => Student::where('school_id', Auth::user()->school_id)
                ->where('is_active', true)->count(),
        ];
    }

    // ── Toggle activo / inactivo ───────────────────────────────────
    public function toggleSectionStatus(int $sectionId): void
    {
        $section = SchoolSection::with('students')->findOrFail($sectionId);

        if ($section->is_active && $section->students()->where('is_active', true)->exists()) {
            $this->dispatch('notify', type: 'error',
                message: 'No se puede desactivar: la sección tiene estudiantes activos.');
            return;
        }

        $section->update(['is_active' => ! $section->is_active]);
        unset($this->structure, $this->stats);

        $msg = ! $section->is_active ? 'Sección reactivada.' : 'Sección desactivada.';
        $this->dispatch('notify', type: 'info', message: $msg);
    }

    // ── Eliminar (soft delete — solo secciones vacías del wizard) ──
    public function confirmDelete(int $sectionId): void
    {
        $section = SchoolSection::findOrFail($sectionId);

        if ($section->students()->withTrashed()->exists()) {
            $this->dispatch('notify', type: 'error',
                message: 'Esta sección tiene historial de estudiantes y no puede eliminarse. Desactívala.');
            return;
        }

        $this->deletingSectionId = $sectionId;
        $this->showDeleteConfirm = true;
    }

    public function executeDelete(): void
    {
        if (! $this->deletingSectionId) return;

        $section = SchoolSection::findOrFail($this->deletingSectionId);

        // Doble guard en el método de ejecución
        if ($section->students()->withTrashed()->exists()) {
            $this->dispatch('notify', type: 'error', message: 'No se puede eliminar.');
            $this->reset(['deletingSectionId', 'showDeleteConfirm']);
            return;
        }

        $section->delete(); // soft delete
        $this->reset(['deletingSectionId', 'showDeleteConfirm']);
        unset($this->structure, $this->stats);
        $this->dispatch('notify', type: 'success', message: 'Sección eliminada.');
    }

    public function render()
    {
        return view('livewire.app.academic.course-index')
            ->layout('layouts.app-module', config('modules.academico'));
    }
}
```

**Vista `course-index.blade.php`** — idéntica visualmente al `AcademicBuilder` anterior (cards con burbujas, sidebar de resumen), con estos cambios funcionales:

- Botón "Nuevo Curso" → `wire:navigate` a `route('app.academic.courses.create')`
- Cada burbuja de sección → `wire:navigate` a `route('app.academic.courses.show', $section->id)` (no abre slide-over)
- Botón de papelera en hover → `wire:click="confirmDelete({{ $section->id }})"` (solo visible si `students_count === 0`)
- Botón ojo — toggle activo/inactivo → `wire:click="toggleSectionStatus({{ $section->id }})"`
- Modal de confirmación de eliminación (usa el componente `x-modal` existente)

```html
{{-- resources/views/livewire/app/academic/course-index.blade.php --}}
<div>
    <x-app.module-toolbar>
        <x-slot:title>Gestión de Cursos</x-slot:title>
        <x-slot:actions>
            <x-ui.button href="{{ route('app.academic.courses.create') }}"
                variant="primary" size="sm" iconLeft="heroicon-o-plus">
                Nuevo Curso
            </x-ui.button>
        </x-slot:actions>
    </x-app.module-toolbar>

    <div class="p-4 md:p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white leading-tight">
                Gestión de Cursos
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Supervisión y organización de niveles académicos.
            </p>
        </div>

        <div class="flex gap-6 items-start">
            {{-- ══ Contenido principal ══ --}}
            <div class="flex-1 min-w-0 space-y-10">

                @forelse($this->structure as $level)
                    <section>
                        <div class="flex items-center gap-3 mb-5">
                            <h2 class="text-xs font-black uppercase tracking-widest
                                       text-slate-400 dark:text-slate-500 whitespace-nowrap">
                                {{ $level['name'] }}
                            </h2>
                            <div class="flex-grow border-t border-slate-200 dark:border-dark-border"></div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($level['grades'] as $grade)

                                {{-- Card académica --}}
                                @if($grade['academic']->isNotEmpty() || $grade['technical_groups']->isEmpty())
                                    <x-academic.course-card
                                        :grade="$grade"
                                        :sections="$grade['academic']"
                                        type="academic" />
                                @endif

                                {{-- Cards técnicas --}}
                                @foreach($grade['technical_groups'] as $techGroup)
                                    <x-academic.course-card
                                        :grade="$grade"
                                        :sections="$techGroup['sections']"
                                        :tech-group="$techGroup"
                                        type="technical" />
                                @endforeach

                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="flex flex-col items-center justify-center py-24 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-white/5
                                    flex items-center justify-center mb-4">
                            <x-heroicon-o-academic-cap class="w-8 h-8 text-slate-300 dark:text-slate-600" />
                        </div>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">
                            No hay cursos configurados
                        </p>
                        <p class="text-xs text-slate-400 dark:text-slate-600 mt-1">
                            Crea el primer curso para comenzar a organizar los estudiantes.
                        </p>
                        <div class="mt-5">
                            <x-ui.button href="{{ route('app.academic.courses.create') }}"
                                variant="primary" size="sm" iconLeft="heroicon-o-plus">
                                Crear primer curso
                            </x-ui.button>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- ══ Sidebar ══ --}}
            <aside class="hidden lg:flex flex-col gap-4 w-[17rem] flex-shrink-0">
                <div class="bg-white dark:bg-dark-card rounded-2xl
                            border border-slate-200 dark:border-dark-border p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <x-heroicon-s-chart-bar class="w-4 h-4 text-orvian-orange flex-shrink-0" />
                        <h3 class="text-[10px] font-black uppercase tracking-widest
                                   text-slate-700 dark:text-white">
                            Resumen Académico
                        </h3>
                    </div>
                    <div class="space-y-2.5">
                        <div class="rounded-xl p-3.5 bg-slate-50 dark:bg-white/5
                                    border border-slate-100 dark:border-dark-border">
                            <p class="text-[9px] font-black uppercase tracking-widest mb-1
                                       text-slate-400 dark:text-slate-600">Total Estudiantes</p>
                            <p class="text-2xl font-black leading-none text-slate-800 dark:text-white">
                                {{ number_format($this->stats['total_students']) }}
                            </p>
                        </div>
                        <div class="rounded-xl p-3.5 bg-slate-50 dark:bg-white/5
                                    border border-slate-100 dark:border-dark-border">
                            <p class="text-[9px] font-black uppercase tracking-widest mb-1
                                       text-slate-400 dark:text-slate-600">Secciones Activas</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black leading-none text-slate-800 dark:text-white">
                                    {{ $this->stats['total_active'] }}
                                </p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-600">
                                    en {{ collect($this->structure)->count() }} niveles
                                </p>
                            </div>
                            @if($this->stats['total_inactive'] > 0)
                                <p class="text-[9px] mt-1 text-slate-400 dark:text-slate-600">
                                    + {{ $this->stats['total_inactive'] }} inactivas
                                </p>
                            @endif
                        </div>
                        @php
                            $year = \App\Models\Tenant\Academic\AcademicYear::where('school_id', Auth::user()->school_id)
                                ->where('is_active', true)->first();
                        @endphp
                        @if($year)
                            <div class="rounded-xl p-3.5
                                        bg-orvian-orange/8 dark:bg-orvian-orange/10
                                        border border-orvian-orange/15 dark:border-orvian-orange/12">
                                <p class="text-[9px] font-black uppercase tracking-widest mb-1
                                           text-orvian-orange/70">Año Escolar</p>
                                <p class="text-lg font-black leading-none text-orvian-orange">
                                    {{ $year->year_name ?? $year->name }}
                                </p>
                                @if($year->start_date && $year->end_date)
                                    @php
                                        $start     = \Carbon\Carbon::parse($year->start_date);
                                        $end       = \Carbon\Carbon::parse($year->end_date);
                                        $totalDays = max($start->diffInDays($end), 1);
                                        $elapsed   = min($start->diffInDays(now()), $totalDays);
                                        $progress  = round(($elapsed / $totalDays) * 100);
                                    @endphp
                                    <div class="mt-2.5">
                                        <div class="w-full h-1.5 rounded-full bg-orvian-orange/20">
                                            <div class="h-full rounded-full bg-orvian-orange"
                                                 style="width: {{ $progress }}%"></div>
                                        </div>
                                        <p class="text-[9px] text-orvian-orange/60 mt-1 text-right">
                                            {{ $progress }}% completado
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card rounded-2xl
                            border border-slate-200 dark:border-dark-border p-5">
                    <h3 class="text-[10px] font-black uppercase tracking-widest mb-3
                               text-slate-700 dark:text-white">Acciones Rápidas</h3>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            ['icon' => 'heroicon-o-document-text', 'label' => 'Listados'],
                            ['icon' => 'heroicon-o-arrow-up-tray', 'label' => 'Importar'],
                            ['icon' => 'heroicon-o-envelope',      'label' => 'Circular'],
                            ['icon' => 'heroicon-o-cog-6-tooth',   'label' => 'Config'],
                        ] as $action)
                            <button class="flex flex-col items-center gap-2 p-3.5 rounded-xl
                                           text-center group transition-all
                                           bg-slate-50 dark:bg-white/5
                                           border border-slate-100 dark:border-dark-border
                                           hover:bg-slate-100 dark:hover:bg-white/8
                                           hover:border-slate-200 dark:hover:border-white/15">
                                <x-dynamic-component :component="$action['icon']"
                                    class="w-5 h-5 transition-colors
                                           text-slate-400 dark:text-slate-600
                                           group-hover:text-slate-600 dark:group-hover:text-slate-400" />
                                <span class="text-[10px] font-semibold transition-colors
                                             text-slate-500 dark:text-slate-500
                                             group-hover:text-slate-700 dark:group-hover:text-slate-300">
                                    {{ $action['label'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- Modal confirmación de eliminación --}}
    <x-modal name="delete-section-confirm" maxWidth="sm">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950/40
                            flex items-center justify-center flex-shrink-0">
                    <x-heroicon-o-trash class="w-5 h-5 text-red-500 dark:text-red-400" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                        Eliminar sección
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Esta acción no se puede deshacer.
                    </p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-5">
                La sección no tiene estudiantes y puede eliminarse del sistema.
                Si en el futuro necesitas esta combinación, deberás crearla nuevamente.
            </p>
            <div class="flex gap-3 justify-end">
                <x-ui.button
                    x-on:click="$dispatch('close-modal', 'delete-section-confirm')"
                    wire:click="$set('showDeleteConfirm', false)"
                    variant="ghost" size="sm">
                    Cancelar
                </x-ui.button>
                <x-ui.button
                    wire:click="executeDelete"
                    variant="danger" size="sm"
                    wire:loading.attr="disabled" wire:target="executeDelete">
                    <span wire:loading.remove wire:target="executeDelete">Eliminar</span>
                    <span wire:loading wire:target="executeDelete">Eliminando...</span>
                </x-ui.button>
            </div>
        </div>
    </x-modal>
</div>
```

> **Componente `x-academic.course-card`** — Extraer las cards a `app/View/Components/Academic/CourseCard.php` para no duplicar el HTML de card académica y técnica. Recibe `$grade`, `$sections`, `$type` y opcionalmente `$techGroup`. Emite eventos `wire:click` al padre para toggle y delete. Esto es opcional si la vista es manejable, pero se recomienda cuando hay más de 20 cursos.

---

### 2.3 — `CourseForm` (Creación Guiada de Secciones)

Este es el componente clave que faltaba. Wizard de **4 pasos** en el mismo componente Livewire — sin navegación entre páginas, solo cambio de `$step` con transición.

**Lógica de filtrado de niveles y títulos técnicos:**

- Los niveles disponibles se leen de `school_levels` (la tabla pivote que ya tiene migración). Si la tabla no tiene datos para esa escuela aún, se muestran todos los niveles del sistema como fallback.
- Los títulos técnicos se leen de `school_technical_titles` (ya existe desde v0.2.0). Solo se muestran títulos de la familia/modalidad de la escuela.
- Los grados se filtran por nivel seleccionado usando la relación `Level → Grade`.
- Si el grado tiene `allows_technical = false`, el paso de tipo/título técnico se salta automáticamente.

```php
// app/Livewire/App/Academic/CourseForm.php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\Grade;
use App\Models\Tenant\Academic\Level;
use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use App\Models\Tenant\Academic\TechnicalTitle;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CourseForm extends Component
{
    public int    $step        = 1;
    public int    $totalSteps  = 4;  // Se reduce a 3 si el grado no permite técnico

    // Paso 1: Nivel
    public ?int   $selectedLevelId = null;

    // Paso 2: Grado
    public ?int   $selectedGradeId = null;

    // Paso 3: Tipo + Título Técnico (se salta si grade->allows_technical = false)
    public string $sectionType          = 'academic'; // 'academic' | 'technical'
    public ?int   $selectedTitleId      = null;

    // Paso 4: Paralelo + Tanda
    public string $label   = '';
    public ?int   $shiftId = null;

    // ── Propiedades computadas por paso ──────────────────────────

    #[Computed]
    public function levels(): \Illuminate\Database\Eloquent\Collection
    {
        $schoolId = Auth::user()->school_id;

        // Leer niveles habilitados desde school_levels (tabla pivote del wizard)
        $enabledLevelIds = \DB::table('school_levels')
            ->where('school_id', $schoolId)
            ->pluck('level_id');

        if ($enabledLevelIds->isEmpty()) {
            // Fallback: mostrar todos si la tabla pivote no tiene datos
            return Level::with('grades')->orderBy('id')->get();
        }

        return Level::with('grades')
            ->whereIn('id', $enabledLevelIds)
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function grades(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->selectedLevelId) return collect();

        return Grade::where('level_id', $this->selectedLevelId)
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function selectedGrade(): ?Grade
    {
        return $this->selectedGradeId ? Grade::find($this->selectedGradeId) : null;
    }

    /**
     * ¿El grado elegido soporta secciones técnicas?
     * Determina si se muestra el paso 3 o se salta directo al paso 4.
     */
    #[Computed]
    public function gradeAllowsTechnical(): bool
    {
        return $this->selectedGrade?->allows_technical ?? false;
    }

    /**
     * Títulos técnicos disponibles para esta escuela.
     * Filtra por school_technical_titles (tabla pivote v0.2.0).
     */
    #[Computed]
    public function availableTitles(): \Illuminate\Database\Eloquent\Collection
    {
        $schoolId = Auth::user()->school_id;

        return TechnicalTitle::whereHas('schools', fn ($q) =>
            $q->where('schools.id', $schoolId)
        )
        ->with('family')
        ->orderBy('name')
        ->get();
    }

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    /**
     * Secciones que ya existen para el grado/título elegido.
     * Se muestra en el paso 4 para evitar duplicados.
     */
    #[Computed]
    public function existingSections(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->selectedGradeId) return collect();

        return SchoolSection::with('shift')
            ->where('school_id', Auth::user()->school_id)
            ->where('grade_id', $this->selectedGradeId)
            ->when(
                $this->sectionType === 'technical' && $this->selectedTitleId,
                fn ($q) => $q->where('technical_title_id', $this->selectedTitleId),
                fn ($q) => $q->whereNull('technical_title_id')
            )
            ->orderBy('label')
            ->get();
    }

    // ── Navegación entre pasos ────────────────────────────────────

    public function nextStep(): void
    {
        $this->validateCurrentStep();

        // Si el grado no permite técnico, saltar paso 3
        if ($this->step === 2 && ! $this->gradeAllowsTechnical) {
            $this->sectionType = 'academic';
            $this->step        = 4;
            return;
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        // Si estamos en el paso 4 y el grado no permite técnico,
        // volver al paso 2 (porque el 3 fue saltado)
        if ($this->step === 4 && ! $this->gradeAllowsTechnical) {
            $this->step = 2;
            return;
        }

        $this->step = max(1, $this->step - 1);
    }

    protected function validateCurrentStep(): void
    {
        match ($this->step) {
            1 => $this->validate(['selectedLevelId' => 'required|integer|exists:levels,id']),
            2 => $this->validate(['selectedGradeId' => 'required|integer|exists:grades,id']),
            3 => $this->validateStep3(),
            4 => $this->validate([
                'label'   => 'required|string|max:10',
                'shiftId' => 'required|integer|exists:school_shifts,id',
            ]),
        };
    }

    protected function validateStep3(): void
    {
        $this->validate(['sectionType' => 'required|in:academic,technical']);

        if ($this->sectionType === 'technical') {
            $this->validate(['selectedTitleId' => 'required|integer|exists:technical_titles,id']);
        }
    }

    // ── Crear ─────────────────────────────────────────────────────

    public function create(): void
    {
        $this->validateCurrentStep(); // valida paso 4

        $label     = strtoupper(trim($this->label));
        $schoolId  = Auth::user()->school_id;
        $titleId   = $this->sectionType === 'technical' ? $this->selectedTitleId : null;

        // Guard de duplicado explícito con mensaje claro
        $exists = SchoolSection::where('school_id', $schoolId)
            ->where('grade_id', $this->selectedGradeId)
            ->where('label', $label)
            ->where('school_shift_id', $this->shiftId)
            ->where('technical_title_id', $titleId)
            ->exists();

        if ($exists) {
            $this->addError('label', 'Ya existe una sección con ese paralelo, tanda y tipo para este grado.');
            return;
        }

        $section = SchoolSection::create([
            'school_id'          => $schoolId,
            'grade_id'           => $this->selectedGradeId,
            'school_shift_id'    => $this->shiftId,
            'label'              => $label,
            'technical_title_id' => $titleId,
            'is_active'          => true,
        ]);

        $this->dispatch('notify', type: 'success',
            message: "Sección {$section->full_label} creada correctamente.");

        $this->redirect(route('app.academic.courses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.app.academic.course-form')
            ->layout('layouts.app-module', config('modules.academico'));
    }
}
```

**Vista `course-form.blade.php`** — Wizard de 4 pasos con barra de progreso:

```html
{{-- resources/views/livewire/app/academic/course-form.blade.php --}}
<div>
    <x-app.module-toolbar>
        <x-slot:title>Nuevo Curso</x-slot:title>
        <x-slot:actions>
            <x-ui.button href="{{ route('app.academic.courses.index') }}"
                variant="ghost" size="sm" iconLeft="heroicon-o-arrow-left">
                Volver
            </x-ui.button>
        </x-slot:actions>
    </x-app.module-toolbar>

    <div class="p-4 md:p-6 max-w-2xl mx-auto">

        {{-- Barra de progreso --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    Paso {{ $step }} de {{ $this->gradeAllowsTechnical ? 4 : 3 }}
                </p>
                <p class="text-xs text-slate-400 dark:text-slate-600">
                    @if($step === 1) Seleccionar nivel
                    @elseif($step === 2) Seleccionar grado
                    @elseif($step === 3) Tipo de sección
                    @else Configurar paralelo
                    @endif
                </p>
            </div>
            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-white/8">
                @php
                    $totalActual = $this->gradeAllowsTechnical ? 4 : 3;
                    // Mapear step real al step visual (cuando se salta el 3)
                    $stepVisual = $step;
                    if (!$this->gradeAllowsTechnical && $step === 4) $stepVisual = 3;
                    $pct = round(($stepVisual / $totalActual) * 100);
                @endphp
                <div class="h-full rounded-full bg-orvian-orange transition-all duration-300"
                     style="width: {{ $pct }}%"></div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-dark-border">

            {{-- ══ Paso 1: Nivel ══ --}}
            @if($step === 1)
                <div class="p-6">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                        ¿En qué nivel educativo?
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Selecciona el nivel al que pertenece el nuevo curso.
                    </p>

                    <div class="space-y-2">
                        @foreach($this->levels as $level)
                            <button
                                wire:click="$set('selectedLevelId', {{ $level->id }})"
                                class="w-full flex items-center justify-between p-4 rounded-xl
                                       border-2 transition-all text-left
                                       {{ $selectedLevelId === $level->id
                                           ? 'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/8'
                                           : 'border-slate-200 dark:border-dark-border
                                              hover:border-slate-300 dark:hover:border-white/20
                                              bg-slate-50 dark:bg-white/4' }}">
                                <div>
                                    <p class="text-sm font-bold
                                               {{ $selectedLevelId === $level->id
                                                   ? 'text-orvian-orange'
                                                   : 'text-slate-700 dark:text-white' }}">
                                        {{ $level->name }}
                                    </p>
                                    <p class="text-xs mt-0.5
                                               text-slate-400 dark:text-slate-500">
                                        {{ $level->grades->count() }} grados disponibles
                                    </p>
                                </div>
                                @if($selectedLevelId === $level->id)
                                    <x-heroicon-s-check-circle class="w-5 h-5 text-orvian-orange flex-shrink-0" />
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @error('selectedLevelId')
                        <p class="text-xs text-red-500 dark:text-red-400 mt-3">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- ══ Paso 2: Grado ══ --}}
            @if($step === 2)
                <div class="p-6">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                        ¿Qué grado?
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Grados disponibles en
                        <strong class="text-slate-700 dark:text-slate-200">
                            {{ $this->levels->firstWhere('id', $selectedLevelId)?->name }}
                        </strong>.
                    </p>

                    <div class="grid grid-cols-2 gap-2">
                        @foreach($this->grades as $grade)
                            <button
                                wire:click="$set('selectedGradeId', {{ $grade->id }})"
                                class="flex flex-col p-4 rounded-xl border-2 transition-all text-left
                                       {{ $selectedGradeId === $grade->id
                                           ? 'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/8'
                                           : 'border-slate-200 dark:border-dark-border
                                              hover:border-slate-300 dark:hover:border-white/20
                                              bg-slate-50 dark:bg-white/4' }}">
                                <p class="text-sm font-bold
                                           {{ $selectedGradeId === $grade->id
                                               ? 'text-orvian-orange'
                                               : 'text-slate-700 dark:text-white' }}">
                                    {{ $grade->name }}
                                </p>
                                @if($grade->allows_technical)
                                    <span class="mt-1.5 inline-flex items-center gap-1 text-[9px] font-bold
                                                 uppercase tracking-wider text-orvian-orange/70">
                                        <x-heroicon-o-cog-6-tooth class="w-3 h-3" />
                                        Permite técnico
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @error('selectedGradeId')
                        <p class="text-xs text-red-500 dark:text-red-400 mt-3">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- ══ Paso 3: Tipo + Título Técnico ══ --}}
            @if($step === 3)
                <div class="p-6">
                    <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                        ¿Académico o Técnico?
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        El grado <strong class="text-slate-700 dark:text-slate-200">
                            {{ $this->selectedGrade?->name }}
                        </strong> admite secciones técnicas.
                    </p>

                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <button
                            wire:click="$set('sectionType', 'academic')"
                            class="flex flex-col items-center p-5 rounded-xl border-2 transition-all
                                   {{ $sectionType === 'academic'
                                       ? 'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/8'
                                       : 'border-slate-200 dark:border-dark-border
                                          hover:border-slate-300 dark:hover:border-white/20
                                          bg-slate-50 dark:bg-white/4' }}">
                            <div class="w-10 h-10 rounded-xl mb-3
                                        {{ $sectionType === 'academic'
                                            ? 'bg-orvian-orange/15'
                                            : 'bg-slate-100 dark:bg-white/8' }}
                                        flex items-center justify-center">
                                <x-heroicon-o-academic-cap
                                    class="w-5 h-5 {{ $sectionType === 'academic'
                                        ? 'text-orvian-orange'
                                        : 'text-slate-400 dark:text-slate-500' }}" />
                            </div>
                            <p class="text-sm font-bold
                                       {{ $sectionType === 'academic'
                                           ? 'text-orvian-orange'
                                           : 'text-slate-700 dark:text-white' }}">
                                Académico
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 text-center">
                                Plan general de estudios
                            </p>
                        </button>

                        <button
                            wire:click="$set('sectionType', 'technical')"
                            class="flex flex-col items-center p-5 rounded-xl border-2 transition-all
                                   {{ $sectionType === 'technical'
                                       ? 'border-orvian-orange bg-orvian-orange/5 dark:bg-orvian-orange/8'
                                       : 'border-slate-200 dark:border-dark-border
                                          hover:border-slate-300 dark:hover:border-white/20
                                          bg-slate-50 dark:bg-white/4' }}">
                            <div class="w-10 h-10 rounded-xl mb-3
                                        {{ $sectionType === 'technical'
                                            ? 'bg-orvian-orange/15'
                                            : 'bg-slate-100 dark:bg-white/8' }}
                                        flex items-center justify-center">
                                <x-heroicon-o-cog-6-tooth
                                    class="w-5 h-5 {{ $sectionType === 'technical'
                                        ? 'text-orvian-orange'
                                        : 'text-slate-400 dark:text-slate-500' }}" />
                            </div>
                            <p class="text-sm font-bold
                                       {{ $sectionType === 'technical'
                                           ? 'text-orvian-orange'
                                           : 'text-slate-700 dark:text-white' }}">
                                Técnico
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 text-center">
                                Bachiller o título técnico
                            </p>
                        </button>
                    </div>

                    {{-- Selector de título técnico (condicional) --}}
                    @if($sectionType === 'technical')
                        @if($this->availableTitles->isEmpty())
                            <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30
                                        border border-amber-200 dark:border-amber-800/50">
                                <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">
                                    Sin títulos técnicos habilitados
                                </p>
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                    Ve a Configuración → Escuela para habilitar los títulos técnicos de tu centro.
                                </p>
                            </div>
                        @else
                            <x-ui.forms.select
                                label="Título Técnico"
                                name="selectedTitleId"
                                wire:model="selectedTitleId"
                                hint="Solo se muestran los títulos habilitados para este centro."
                                :error="$errors->first('selectedTitleId')">
                                <option value="">Seleccionar título...</option>
                                @foreach($this->availableTitles->groupBy(fn ($t) => $t->family?->name ?? 'General') as $family => $titles)
                                    <optgroup label="{{ $family }}">
                                        @foreach($titles as $title)
                                            <option value="{{ $title->id }}">{{ $title->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </x-ui.forms.select>
                        @endif
                    @endif

                    @error('sectionType')
                        <p class="text-xs text-red-500 dark:text-red-400 mt-3">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- ══ Paso 4: Paralelo + Tanda ══ --}}
            @if($step === 4)
                <div class="p-6 space-y-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white mb-1">
                            Configurar paralelo y tanda
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Define la letra del paralelo y el horario de la nueva sección.
                        </p>
                    </div>

                    {{-- Resumen de lo seleccionado --}}
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg
                                     bg-slate-100 dark:bg-white/8
                                     text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ $this->selectedGrade?->name }}
                        </span>
                        @if($sectionType === 'technical' && $selectedTitleId)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg
                                         bg-orvian-orange/10 dark:bg-orvian-orange/12
                                         text-xs font-semibold text-orvian-orange">
                                <x-heroicon-o-cog-6-tooth class="w-3 h-3" />
                                {{ $this->availableTitles->firstWhere('id', $selectedTitleId)?->name }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg
                                         bg-slate-100 dark:bg-white/8
                                         text-xs font-semibold text-slate-600 dark:text-slate-300">
                                Académico
                            </span>
                        @endif
                    </div>

                    <x-ui.forms.input
                        label="Paralelo"
                        name="label"
                        wire:model="label"
                        placeholder="Ej: A"
                        hint="Una letra identifica cada paralelo del grado. Ej: A, B, C."
                        :error="$errors->first('label')" />

                    <x-ui.forms.select
                        label="Tanda"
                        name="shiftId"
                        wire:model="shiftId"
                        :error="$errors->first('shiftId')">
                        <option value="">Seleccionar tanda...</option>
                        @foreach($this->shifts as $shift)
                            <option value="{{ $shift->id }}">
                                {{ $shift->type }}
                                @if($shift->start_time && $shift->end_time)
                                    — {{ $shift->start_time->format('h:i A') }}
                                    a {{ $shift->end_time->format('h:i A') }}
                                @endif
                            </option>
                        @endforeach
                    </x-ui.forms.select>

                    {{-- Secciones existentes para este grado/tipo --}}
                    @if($this->existingSections->isNotEmpty())
                        <div class="rounded-xl p-4 bg-slate-50 dark:bg-white/4
                                    border border-slate-200 dark:border-dark-border">
                            <p class="text-[10px] font-black uppercase tracking-widest mb-2
                                       text-slate-400 dark:text-slate-600">
                                Paralelos ya configurados
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($this->existingSections as $existing)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg
                                                 bg-white dark:bg-white/6
                                                 border border-slate-200 dark:border-dark-border
                                                 text-xs font-bold text-slate-600 dark:text-slate-300">
                                        {{ $existing->label }}
                                        <span class="text-[9px] text-slate-400 font-normal">
                                            {{ $existing->shift?->type }}
                                        </span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Footer de navegación --}}
            <div class="flex items-center justify-between p-6 pt-4
                        border-t border-slate-100 dark:border-dark-border">
                <x-ui.button
                    wire:click="prevStep"
                    variant="ghost" size="sm"
                    iconLeft="heroicon-o-arrow-left"
                    :disabled="$step === 1">
                    Atrás
                </x-ui.button>

                @if($step < 4)
                    <x-ui.button
                        wire:click="nextStep"
                        variant="primary" size="sm"
                        iconRight="heroicon-o-arrow-right"
                        :disabled="($step === 1 && !$selectedLevelId)
                                    || ($step === 2 && !$selectedGradeId)
                                    || ($step === 3 && $sectionType === 'technical' && !$selectedTitleId)">
                        Continuar
                    </x-ui.button>
                @else
                    <x-ui.button
                        wire:click="create"
                        variant="primary" size="sm"
                        wire:loading.attr="disabled"
                        wire:target="create">
                        <span wire:loading.remove wire:target="create">Crear Sección</span>
                        <span wire:loading wire:target="create">Creando...</span>
                    </x-ui.button>
                @endif
            </div>
        </div>
    </div>
</div>
```

---

### 2.4 — `CourseShow` (Detalle de Sección)

Vista de solo lectura + edición de metadatos simples (paralelo, tanda). Lista los estudiantes asignados.

```php
// app/Livewire/App/Academic/CourseShow.php

namespace App\Livewire\App\Academic;

use App\Models\Tenant\Academic\SchoolSection;
use App\Models\Tenant\Academic\SchoolShift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CourseShow extends Component
{
    public SchoolSection $section;

    // Campos de edición inline
    public string $editingLabel   = '';
    public ?int   $editingShiftId = null;
    public bool   $isEditing      = false;

    public function mount(SchoolSection $section): void
    {
        // Guard: la sección debe pertenecer a la escuela del usuario
        abort_if($section->school_id !== Auth::user()->school_id, 403);

        $this->section = $section->load([
            'grade.level',
            'shift',
            'technicalTitle.family',
        ]);

        $this->editingLabel   = $section->label;
        $this->editingShiftId = $section->school_shift_id;
    }

    #[Computed]
    public function students(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->section->students()
            ->with('user:id,email')
            ->orderBy('last_name')
            ->paginate(25);
    }

    #[Computed]
    public function shifts(): \Illuminate\Database\Eloquent\Collection
    {
        return SchoolShift::where('school_id', Auth::user()->school_id)->get();
    }

    #[Computed]
    public function stats(): array
    {
        $students = $this->section->students();
        return [
            'total'    => $students->count(),
            'active'   => $students->where('is_active', true)->count(),
            'inactive' => $students->where('is_active', false)->count(),
        ];
    }

    public function startEdit(): void
    {
        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        $this->isEditing      = false;
        $this->editingLabel   = $this->section->label;
        $this->editingShiftId = $this->section->school_shift_id;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editingLabel'   => 'required|string|max:10',
            'editingShiftId' => 'required|integer|exists:school_shifts,id',
        ]);

        $this->section->update([
            'label'           => strtoupper(trim($this->editingLabel)),
            'school_shift_id' => $this->editingShiftId,
        ]);

        $this->section->refresh();
        $this->isEditing = false;
        $this->dispatch('notify', type: 'success', message: 'Sección actualizada.');
    }

    public function toggleStatus(): void
    {
        if ($this->section->is_active
            && $this->section->students()->where('is_active', true)->exists()) {
            $this->dispatch('notify', type: 'error',
                message: 'No se puede desactivar: tiene estudiantes activos.');
            return;
        }

        $this->section->update(['is_active' => ! $this->section->is_active]);
        $this->section->refresh();

        $msg = $this->section->is_active ? 'Sección reactivada.' : 'Sección desactivada.';
        $this->dispatch('notify', type: 'info', message: $msg);
    }

    public function render()
    {
        return view('livewire.app.academic.course-show')
            ->layout('layouts.app-module', config('modules.academico'));
    }
}
```

Vista `course-show.blade.php` (estructura, sin todo el HTML completo para brevedad):

```html
{{-- resources/views/livewire/app/academic/course-show.blade.php --}}
<div>
    <x-app.module-toolbar>
        <x-slot:title>{{ $section->full_label }}</x-slot:title>
        <x-slot:actions>
            <x-ui.button href="{{ route('app.academic.courses.index') }}"
                variant="ghost" size="sm" iconLeft="heroicon-o-arrow-left">
                Volver
            </x-ui.button>
            @if(!$isEditing)
                <x-ui.button wire:click="startEdit"
                    variant="secondary" size="sm" iconLeft="heroicon-o-pencil">
                    Editar
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-app.module-toolbar>

    <div class="p-4 md:p-6 space-y-6">

        {{-- Header de la sección --}}
        <div class="bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-dark-border p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center
                                {{ $section->technicalTitle
                                    ? 'bg-orvian-orange/10 dark:bg-orvian-orange/12'
                                    : 'bg-slate-100 dark:bg-white/8' }}">
                        @if($section->technicalTitle)
                            <x-heroicon-o-cog-6-tooth class="w-7 h-7 text-orvian-orange" />
                        @else
                            <x-heroicon-o-academic-cap class="w-7 h-7 text-slate-400 dark:text-slate-300" />
                        @endif
                    </div>
                    <div>
                        @if($isEditing)
                            {{-- Formulario de edición --}}
                            <div class="flex items-center gap-3">
                                <x-ui.forms.input
                                    name="editingLabel"
                                    wire:model="editingLabel"
                                    placeholder="Ej: A"
                                    :error="$errors->first('editingLabel')"
                                    size="sm" />
                                <x-ui.forms.select
                                    name="editingShiftId"
                                    wire:model="editingShiftId"
                                    :error="$errors->first('editingShiftId')"
                                    size="sm">
                                    @foreach($this->shifts as $shift)
                                        <option value="{{ $shift->id }}">{{ $shift->type }}</option>
                                    @endforeach
                                </x-ui.forms.select>
                            </div>
                            <div class="flex gap-2 mt-2">
                                <x-ui.button wire:click="saveEdit" variant="primary" size="sm">
                                    Guardar
                                </x-ui.button>
                                <x-ui.button wire:click="cancelEdit" variant="ghost" size="sm">
                                    Cancelar
                                </x-ui.button>
                            </div>
                        @else
                            <h1 class="text-xl font-black text-slate-900 dark:text-white">
                                {{ $section->full_label }}
                            </h1>
                            <div class="flex items-center gap-2 mt-1">
                                @if($section->technicalTitle)
                                    <span class="text-xs font-semibold text-orvian-orange/70">
                                        {{ $section->technicalTitle->family?->name }}
                                    </span>
                                    <span class="text-slate-300 dark:text-slate-700">·</span>
                                @endif
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ $section->shift?->type ?? 'Sin tanda' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Badge de estado + botón de toggle --}}
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                 text-xs font-bold
                                 {{ $section->is_active
                                     ? 'bg-green-50 dark:bg-green-950/40 text-green-600 dark:text-green-400
                                        border border-green-200 dark:border-green-900/50'
                                     : 'bg-slate-100 dark:bg-white/8 text-slate-500 dark:text-slate-400
                                        border border-slate-200 dark:border-dark-border' }}">
                        <span class="w-1.5 h-1.5 rounded-full
                                     {{ $section->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                        {{ $section->is_active ? 'Activa' : 'Inactiva' }}
                    </span>
                    <button wire:click="toggleStatus"
                        class="text-xs font-semibold transition-colors
                               {{ $section->is_active
                                   ? 'text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300'
                                   : 'text-green-600 dark:text-green-400 hover:opacity-80' }}">
                        {{ $section->is_active ? 'Desactivar' : 'Reactivar' }}
                    </button>
                </div>
            </div>

            {{-- Stats rápidas --}}
            <div class="grid grid-cols-3 gap-3 mt-6">
                @foreach([
                    ['label' => 'Total', 'value' => $this->stats['total']],
                    ['label' => 'Activos', 'value' => $this->stats['active']],
                    ['label' => 'Inactivos', 'value' => $this->stats['inactive']],
                ] as $stat)
                    <div class="rounded-xl p-3 bg-slate-50 dark:bg-white/4
                                border border-slate-100 dark:border-dark-border text-center">
                        <p class="text-lg font-black text-slate-800 dark:text-white">
                            {{ $stat['value'] }}
                        </p>
                        <p class="text-[10px] font-bold uppercase tracking-wider mt-0.5
                                   text-slate-400 dark:text-slate-600">
                            {{ $stat['label'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Lista de estudiantes --}}
        <div class="bg-white dark:bg-dark-card rounded-2xl border
                    border-slate-200 dark:border-dark-border">
            <div class="p-5 border-b border-slate-100 dark:border-dark-border">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                    Estudiantes de esta sección
                </h3>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-dark-border">
                @forelse($this->students as $student)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <x-ui.student-avatar :student="$student" size="sm" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">
                                {{ $student->full_name }}
                            </p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                {{ $student->rnc ?? 'Sin cédula' }}
                            </p>
                        </div>
                        <x-ui.button
                            href="{{ route('app.academic.students.show', $student) }}"
                            variant="ghost" size="xs">
                            Ver
                        </x-ui.button>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <p class="text-sm text-slate-400 dark:text-slate-600">
                            No hay estudiantes en esta sección.
                        </p>
                        <x-ui.button
                            href="{{ route('app.academic.enrollment-hub') }}"
                            variant="ghost" size="sm" class="mt-3">
                            Asignar desde el Hub de Matriculación
                        </x-ui.button>
                    </div>
                @endforelse
            </div>
            @if($this->students->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-dark-border">
                    {{ $this->students->links('pagination.orvian-compact') }}
                </div>
            @endif
        </div>
    </div>
</div>
```

---

### 2.5 — Rutas

```php
// routes/app/academic.php
use App\Livewire\App\Academic\CourseIndex;
use App\Livewire\App\Academic\CourseForm;
use App\Livewire\App\Academic\CourseShow;

Route::prefix('academic')->name('academic.')->group(function () {

    // Gestión de cursos / secciones
    Route::middleware('can:configuracion.academic_structure')->group(function () {
        Route::get('/courses',          CourseIndex::class)->name('courses.index');
        Route::get('/courses/create',   CourseForm::class)->name('courses.create');
        Route::get('/courses/{section}',CourseShow::class)->name('courses.show');
    });

});
```

> **Nota de transición:** El componente `AcademicBuilder` anterior se puede renombrar a `CourseIndex` moviendo el archivo, o mantener ambos durante un período de transición si ya hay rutas activas apuntando al anterior. Las rutas antiguas se deben redirigir a las nuevas.

---

### 2.6 — Checklist de Completitud — Fase 2

#### Migraciones
- [x] `school_sections`: `is_active` (boolean, default true) agregado
- [x] `school_sections`: `deleted_at` (softDeletes) agregado
- [x] Migración `school_levels` confirmada como ejecutada (`migrate:status`)

#### Modelo `SchoolSection`
- [x] `SoftDeletes` importado y en el `use`
- [x] `is_active` en `$fillable` y en `$casts`
- [x] `scopeVisible()` — activas + no soft-deleted
- [x] `scopeWithHistory()` — incluye trashed
- [x] `scopeEmpty()` — sin estudiantes (incluye trashed de students)
- [x] `scopeActive()` — existente, sin cambios

#### `CourseIndex`
- [x] Muestra activas e inactivas (todas, no solo visible)
- [x] `toggleSectionStatus()` con guard de estudiantes activos
- [x] `confirmDelete()` con guard de historial (incluye `withTrashed`)
- [x] `executeDelete()` con doble guard
- [x] Modal de confirmación de eliminación operativo
- [x] Botón "Nuevo Curso" navega a `CourseForm` (no abre slide-over)
- [x] Burbuja de sección navega a `CourseShow` (no abre slide-over)

#### `CourseForm`
- [x] `levels` computado desde `school_levels` con fallback a todos
- [x] `grades` filtrado por `selectedLevelId`
- [x] Paso 3 se salta si `grade->allows_technical === false`
- [x] `availableTitles` filtrado por `school_technical_titles` de la escuela
- [x] `existingSections` muestra paralelos ya creados para ese grado/tipo
- [x] Guard de duplicado antes de `SchoolSection::create()`
- [x] Barra de progreso refleja los pasos reales (3 o 4 según el grado)
- [x] Redirección a `CourseIndex` tras crear exitosamente

#### `CourseShow`
- [x] Guard `abort_if` de school_id
- [x] Edición inline de label y tanda
- [x] Toggle activo/inactivo con guard
- [x] Listado de estudiantes paginado
- [x] Link a `EnrollmentHub` cuando la sección está vacía

#### Rutas
- [x] `courses.index`, `courses.create`, `courses.show` registradas
- [x] Ruta antigua `AcademicBuilder` redirige a `courses.index`
- [x] Permisos `can:configuracion.academic_structure` aplicados

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

- [x] `StudentImportWizard` incluye `tutor_name` y `tutor_phone` en `$mappableFields`
- [x] `tutor_name` y `tutor_phone` opcionales en validación del paso de mapeo
- [x] Paso 3 del wizard incluye toggle de sección por defecto
- [x] `resolveSection()` implementa lógica tolerante en 4 niveles
- [x] `normalizePhone()` convierte a formato E.164
- [x] Estudiantes sin sección se crean con `school_section_id = null`
- [x] `metadata->sigerd_section` almacena el nombre crudo del curso de SIGERD
- [x] `metadata->section_resolved` almacena boolean de si se resolvió automáticamente
- [x] Cache de secciones en memoria durante el procesamiento del Job (evitar N+1)
- [x] Reporte post-importación incluye conteo de `waiting_room` y agrupación por `sigerd_section`

### 3.7 — Extras

- [x] Eliminar archivos obsoletos:

D app/Livewire/App/Students/StudentForm.php
D app/Livewire/App/Students/StudentImportWizard.php
D app/Livewire/App/Students/StudentIndex.php
D app/Livewire/App/Students/StudentPrintManager.php
D app/Livewire/App/Students/StudentShow.php
D app/Livewire/App/Teachers/TeacherAssignments.php
D app/Livewire/App/Teachers/TeacherForm.php
D app/Livewire/App/Teachers/TeacherIndex.php
D app/Livewire/App/Teachers/TeacherShow.php

- [x] Fix

El fix en StudentObserver::created():

1. Eliminé la dependencia del estado global (setPermissionsTeamId) para encontrar el rol
2. Uso Role::withoutGlobalScopes()->where('school_id', $student->school_id) para obtener el rol del tenant directamente por
school_id — determinístico, sin ambigüedad
3. Uso $user->roles()->attach($roleId, ['school_id' => $school_id]) para insertar en el pivot con el school_id correcto hardcodeado
— no depende de ningún estado global
4. Llama forgetCachedPermissions() para limpiar el cache de Spatie después de la asignación
5. Log de warning si el rol del tenant no existe (centro sin roles configurados)

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

```html
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

- [x] `EnrollmentHub` carga estudiantes con `school_section_id = null`
- [x] Filtros rápidos por `metadata->sigerd_section` operativos
- [x] `selectBySigerdSection()` selecciona masivamente un grupo del metadata
- [x] Panel derecho filtra secciones por tanda
- [x] `executeAssignment()` actualiza en una sola query (sin N+1)
- [x] Guard en `executeAssignment()`: solo mueve estudiantes con `section_id = null`
- [x] Modal de confirmación antes de ejecutar
- [x] `metadata` actualizada tras asignación (`assigned_from_waiting_room`, `assigned_at`)
- [x] Ruta protegida por `students.edit`
- [x] Agregar ruta a `config/modules.php` para que sea accesible facil

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

```html
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

```html
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

```html
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

```html
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

```html
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
- [x] `Student` y `Teacher` en `App\Models\Tenant\Academic`
- [x] Observers en `App\Observers\Tenant\Academic`
- [x] Factories en `Database\Factories\Tenant\Academic`
- [x] Aliases de backward-compat añadidos, Find & Replace ejecutado, aliases eliminados
- [x] `git grep` para namespace antiguo = 0 resultados
- [x] Suite de tests pasa sin errores tras la migración

### Fase 2 — Academic Builder
- [x] Componente `AcademicBuilder` con CRUD de secciones via Cards
- [x] Edición inline sin modales separados
- [x] Guard de estudiantes activos antes de desactivar sección
- [x] Ruta y link en `config/modules.php` activos

### Fase 3 — Importador SIGERD v2
- [x] `tutor_name` y `tutor_phone` en `$mappableFields` del wizard
- [x] `resolveSection()` tolerante: 4 niveles de resolución
- [x] Sala de Espera funcional (`school_section_id = null`)
- [x] `metadata->sigerd_section` almacena nombre crudo
- [x] `normalizePhone()` convierte a E.164
- [x] Reporte post-importación incluye `waiting_room_count` y agrupación por `sigerd_section`

### Fase 4 — Hub de Matriculación
- [x] Dos paneles: Sala de Espera vs Árbol de Secciones
- [x] Filtros rápidos por `sigerd_section` del metadata
- [x] `selectBySigerdSection()` para selección masiva por grupo
- [x] `executeAssignment()` en una sola query
- [x] Modal de confirmación antes de ejecutar
- [x] `metadata` actualizada tras asignación exitosa

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