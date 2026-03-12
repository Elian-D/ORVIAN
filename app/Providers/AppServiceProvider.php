<?php

namespace App\Providers;

use App\Models\Tenant\School;
use App\Observers\Tenant\SchoolObserver;
use Illuminate\Support\ServiceProvider;
use App\Events\Tenant\SchoolConfigured;
use App\Listeners\Tenant\SetupAcademicStructure;
use App\Listeners\Tenant\CreateInitialAcademicYear;
use App\Listeners\Tenant\AssignInitialRoles;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Observer para lógica de creación de DB/Tenant si aplica
        School::observe(SchoolObserver::class);

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