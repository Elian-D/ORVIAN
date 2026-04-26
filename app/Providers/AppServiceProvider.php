<?php

namespace App\Providers;

use App\Models\Tenant\School;
use App\Observers\Tenant\SchoolObserver;
use Illuminate\Support\ServiceProvider;
use App\Events\Tenant\SchoolConfigured;
use App\Listeners\Tenant\SetupAcademicStructure;
use App\Listeners\Tenant\CreateInitialAcademicYear;
use App\Listeners\Tenant\AssignInitialRoles;
use App\Models\Tenant\AttendanceExcuse;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Facades\Pulse;
use App\Models\Tenant\Plan;
use App\Observers\Tenant\PlanObserver;
use App\Models\Tenant\Student;
use App\Models\Tenant\Teacher;
use App\Observers\Tenant\AttendanceExcuseObserver;
use App\Observers\Tenant\StudentObserver;
use App\Observers\Tenant\TeacherObserver;
use App\Services\Communications\ChatwootService;
use App\Services\Communications\WhatsAppService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChatwootService::class, function ($app) {
            return new ChatwootService();
        });

        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService();
        });
    }

    public function boot(): void
    {
        // Observer para lógica de creación de DB/Tenant si aplica
        School::observe(SchoolObserver::class);
        User::observe(UserObserver::class);
        Plan::observe(PlanObserver::class);
        
        // Observers para lógica específica de cada modelo
        Student::observe(StudentObserver::class);
        Teacher::observe(TeacherObserver::class);
         // Nuevo
        AttendanceExcuse::observe(AttendanceExcuseObserver::class);

                // Vista por defecto para toda la aplicación
        Paginator::defaultView('pagination.orvian-compact');
    
        // Vista simple (onlyTrashed, cursor pagination, etc.)
        Paginator::defaultSimpleView('pagination.orvian-compact');

        Pulse::user(fn (User $user) => [
        'name' => $user->name,
        'extra' => $user->email,
        'avatar' => $user->avatar_path, // Para que Pulse use tus avatares
        ]);

        // Solo tú puedes ver Pulse
        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('Owner');
        });

        if (!app()->runningInConsole() || app()->environment('production')) {
            $version = Cache::rememberForever('orvian.app_version', function () {
                $path = base_path('VERSION');
                return file_exists($path) ? trim(file_get_contents($path)) : 'dev';
            });
        } else {
            $version = file_exists(base_path('VERSION')) 
                ? trim(file_get_contents(base_path('VERSION'))) 
                : 'dev';
        }
        
        View::share('appVersion', $version);

/*         // Registro de los listeners del Onboarding
        Event::listen(
            SchoolConfigured::class,
            [SetupAcademicStructure::class, 'handle']
        );

        Event::listen(
            SchoolConfigured::class,
            [CreateInitialAcademicYear::class, 'handle']
        );

        Event::listen(
            SchoolConfigured::class,
            [AssignInitialRoles::class, 'handle']
        ); */
    }
}