<?php

namespace App\Providers;

use App\Models\Tenant\School;
use App\Observers\Tenant\SchoolObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /// Registro del Observer para el modelo School
        School::observe(SchoolObserver::class);
    }
}
