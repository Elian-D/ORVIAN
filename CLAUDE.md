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
```

## Architecture

**Orvian** is an educational management system for Dominican schools. It is a **Laravel 12 monolith** with **multi-tenant isolation** (each school is a tenant).

### Stack
- **Backend:** Laravel 12, Livewire 4, Spatie Permissions
- **Frontend:** Blade + Alpine.js + Tailwind CSS, Vite
- **DB:** SQLite (dev default) or MySQL/MariaDB (production)
- **Async:** Database-backed queues + events/listeners

### Key directories

| Path | Purpose |
|------|---------|
| `app/Livewire/Admin/` | Platform-admin Livewire components (schools, users, plans) |
| `app/Livewire/App/` | Tenant Livewire components (attendance, students, grades, etc.) |
| `app/Models/Tenant/` | Eloquent models scoped to a single school/tenant |
| `app/Services/` | Domain business logic (Attendance, Students, Teachers, School, Users) |
| `app/Services/FacialRecognition/` | Optional integration with external Python biometric API |
| `app/Events/Tenant/` | Domain events (async via queue listeners) |
| `app/Filters/` | Table filter pipelines used by Livewire data-table components |
| `routes/app/` | Tenant-scoped route files per feature domain |
| `routes/admin/` | Platform-admin routes |

### Multi-tenancy
Every `Tenant/` model is automatically scoped to the current school. Middleware resolves the tenant context from the authenticated user. Never query `Tenant/` models outside of an authenticated tenant context unless you explicitly understand the scope implications.

### Livewire pattern
Components follow a consistent pattern:
- A `Livewire` class in `app/Livewire/App/<Domain>/` handles state and actions.
- A `Blade` view in `resources/views/livewire/app/<domain>/` renders the UI.
- Filter pipelines in `app/Filters/App/<Domain>/` power sortable/searchable tables.

### Services
Business logic lives in `app/Services/`, not in Livewire components or models. Livewire components call services; services interact with models.

### External services
Configured in `.env` (see `.env.example`):
- `FACIAL_API_URL` / `FACIAL_API_KEY` — optional Python facial-recognition service (default `localhost:8001`)
- `GOOGLE_MAPS_API_KEY` — maps integration

### Permissions
Role-based access uses `spatie/laravel-permission`. Permission groups are defined in `PermissionHelper.php` (autoloaded). Gates/policies use these permission names throughout.
