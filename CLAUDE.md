# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# First-time setup
composer setup          # install deps, generate key, migrate, seed, build frontend

# Development (run all services concurrently)
composer dev            # Laravel server + queue worker + log tail + Vite dev server

# Frontend only
npm run dev             # Vite dev server
npm run build           # Production build

# Tests
composer test           # clears config cache, then runs the full Pest suite
php artisan test --filter=SomeTestName   # run a single test

# Docker (Laravel Sail)
./vendor/bin/sail up -d   # start MySQL 8.4, Redis, Mailpit, phpMyAdmin (port 8080)
./vendor/bin/sail artisan migrate
```

## Architecture

**Orvian** is an educational management system for Dominican schools. It is a **Laravel 12 monolith** with **multi-tenant isolation** (each school is a tenant).

### Stack
- **Backend:** Laravel 12, Livewire 4, Spatie Permissions
- **Frontend:** Blade + Alpine.js + Tailwind CSS, Vite
- **DB:** SQLite (dev default) or MySQL/MariaDB (production)
- **Async:** Database-backed queues + events/listeners
- **Comms:** ChatwootService + WhatsAppService (registered as singletons)

### Key directories

| Path | Purpose |
|------|---------|
| `app/Livewire/Admin/` | Platform-admin Livewire components (schools, users, plans) |
| `app/Livewire/App/` | Tenant Livewire components (attendance, students, grades, etc.) |
| `app/Livewire/Shared/` | Cross-context components (profile, user status) |
| `app/Livewire/Tenant/` | Onboarding wizards (`SchoolWizard`, `TenantSetupWizard`) |
| `app/Livewire/Base/` | Abstract base classes (`DataTable`) |
| `app/Models/Tenant/` | Eloquent models scoped to a single school/tenant |
| `app/Models/Geo/` | Dominican geography models (Province → Municipality → District → Section) |
| `app/Actions/Tenant/` | Single-responsibility orchestrators (create school, onboard user, etc.) |
| `app/Services/` | Domain business logic (Attendance, Students, Teachers, School, Users) |
| `app/Services/Communications/` | ChatwootService, WhatsAppService, AttendanceAlertEvaluator |
| `app/Services/FacialRecognition/` | Optional integration with external Python biometric API |
| `app/Events/Tenant/` | Domain events (async via queue listeners) |
| `app/Filters/` | Table filter pipelines used by Livewire data-table components |
| `app/Tables/` | `TableConfig` interface + per-module implementations (columns, mobile defaults, filter labels) |
| `app/Observers/Tenant/` | Model lifecycle hooks (School, Student, Teacher, AttendanceExcuse, Plan) |
| `app/Imports/` | Excel imports via maatwebsite/excel (e.g., `RawStudentImport`) |
| `app/Exports/` | Excel/PDF exports (`ClassroomAttendanceExport`, `PlantelAttendanceExport`, `ReportExport`) |
| `routes/app/` | Tenant-scoped route files per feature domain |
| `routes/admin/` | Platform-admin routes |
| `routes/api.php` | Kiosk API (`/api/v1/kiosk/*`, Sanctum auth) |

### Multi-tenancy

Tenant isolation has three cooperating pieces:

1. **`BelongsToSchool` trait** (`app/Traits/`) — adds a `SchoolScope` global scope to every tenant model and auto-assigns `school_id` on `creating` from `Auth::user()->school_id`.
2. **`IdentifyTenant` middleware** — resolves tenant from the authenticated user (or an impersonation session for SuperAdmins), calls `setPermissionsTeamId($schoolId)` for Spatie, and binds the `School` instance to `app('currentSchool')`.
3. **`SchoolScope`** (`app/Models/Scopes/`) — the global query scope that enforces `WHERE school_id = ?` on every tenant model query.

Never query `Tenant/` models outside of an authenticated tenant context unless you explicitly understand the scope implications.

### Layered architecture

Requests flow: **Route → Livewire/Controller → Action → Service → Model**

- **Actions** (`app/Actions/Tenant/`) orchestrate multi-step operations (e.g., `CreateSchoolAction` creates school + principal + permissions). Use Actions when a task spans multiple services or requires transactional coordination.
- **Services** (`app/Services/`) contain pure domain logic. Livewire components must call services, not query models directly.
- **Observers** (`app/Observers/Tenant/`) react to model lifecycle events without coupling models to side-effects.

### Livewire DataTable pattern

All paginated list views extend `App\Livewire\Base\DataTable` (which is `#[Lazy]`). Each table component must:
- Implement `getTableDefinition(): string` returning a `TableConfig` class.
- Define `TableConfig::allColumns()`, `defaultDesktop()`, `defaultMobile()`, and `filterLabels()`.

The base class handles column toggle/reset, filter chips (`getActiveChips()`), pagination views, and `#[Lazy]` skeleton placeholders automatically.

### Kiosk API

`/api/v1/kiosk/*` is a JSON API secured with Laravel Sanctum (the `School` model uses `HasApiTokens`). It serves an Electron desktop kiosk app for QR/facial attendance recording. Three endpoints: `status`, `record/qr`, `record/facial`.

### Plans and features

`School` belongs to a `Plan`. Plans have many `Feature` records via a pivot. Gate checks like `$school->plan?->hasFeature('attendance_qr')` control which attendance modes are available. The Kiosk API exposes these to the Electron client.

### Permissions

Role-based access uses `spatie/laravel-permission` with team support (team = school). Permissions are organized into `PermissionGroup` records with `context = 'global'|'tenant'`. The `Permission` model overrides Spatie's `roles()` relation to exclude `SchoolScope` so the permission cache rebuilds correctly for global roles (e.g., Owner/SuperAdmin). Permission labels are translated via `lang/*/permissions.php` and the `trans_permission()` helper.

### External services
Configured in `.env` (see `.env.example`):
- `FACIAL_API_URL` / `FACIAL_API_KEY` — optional Python facial-recognition service (default `localhost:8001`)
- `GOOGLE_MAPS_API_KEY` — maps integration
- `CHATWOOT_*` / `WHATSAPP_*` — communications integrations
