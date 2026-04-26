<?php

namespace App\Providers;

use App\Models\Tenant\AttendanceExcuse;
use App\Models\Tenant\Plan;
use App\Models\Tenant\School;
use App\Models\Tenant\Student;
use App\Models\Tenant\Teacher;
use App\Models\User;
use App\Observers\Tenant\AttendanceExcuseObserver;
use App\Observers\Tenant\PlanObserver;
use App\Observers\Tenant\SchoolObserver;
use App\Observers\Tenant\StudentObserver;
use App\Observers\Tenant\TeacherObserver;
use App\Observers\UserObserver;
use App\Services\Communications\ChatwootService;
use App\Services\Communications\WhatsAppService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    // -------------------------------------------------------------------------
    // Registro de Singletons
    // -------------------------------------------------------------------------

    public function register(): void
    {
        $this->app->singleton(ChatwootService::class, fn ($app) => new ChatwootService());
        $this->app->singleton(WhatsAppService::class, fn ($app) => new WhatsAppService());
    }

    // -------------------------------------------------------------------------
    // Bootstrap de la Aplicación
    // -------------------------------------------------------------------------

    public function boot(): void
    {
        // --- Observers ---
        School::observe(SchoolObserver::class);
        User::observe(UserObserver::class);
        Plan::observe(PlanObserver::class);
        Student::observe(StudentObserver::class);
        Teacher::observe(TeacherObserver::class);
        AttendanceExcuse::observe(AttendanceExcuseObserver::class);

        // --- Paginación ---
        Paginator::defaultView('pagination.orvian-compact');
        Paginator::defaultSimpleView('pagination.orvian-compact');

        // --- Laravel Pulse ---
        Pulse::user(fn (User $user) => [
            'name'   => $user->name,
            'extra'  => $user->email,
            'avatar' => $user->avatar_path,
        ]);

        Gate::define('viewPulse', fn (User $user) => $user->hasRole('Owner'));

        // Solo Owner y TechnicalSupport pueden ver los logs
        Gate::define('viewLogViewer', function (User $user) {
            return $user->hasAnyRole(['Owner', 'TechnicalSupport']);
        });

        // --- Versión de la aplicación ---
        // Lee el archivo VERSION como fallback seguro (funciona en build time sin Redis)
        $version = file_exists(base_path('VERSION'))
            ? trim(file_get_contents(base_path('VERSION')))
            : 'dev';

        // Intenta cachear en Redis; si no está disponible (ej: build time), usa el fallback
        try {
            $version = Cache::rememberForever('orvian.app_version', fn () => $version);
        } catch (\Exception $e) {
            // Redis no disponible — $version ya tiene el valor correcto del archivo
        }

        View::share('appVersion', $version);

        // --- Listeners del Onboarding (deshabilitados temporalmente) ---
        // Event::listen(SchoolConfigured::class, [SetupAcademicStructure::class, 'handle']);
        // Event::listen(SchoolConfigured::class, [CreateInitialAcademicYear::class, 'handle']);
        // Event::listen(SchoolConfigured::class, [AssignInitialRoles::class, 'handle']);
    }
}