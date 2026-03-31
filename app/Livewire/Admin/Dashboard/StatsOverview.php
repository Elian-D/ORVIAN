<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Tenant\School;
use App\Models\Tenant\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin')]
class StatsOverview extends Component
{
    public function render()
    {
        // 1. Métricas rápidas
        $totalSchools = School::count();
        $activeSchools = School::where('schools.is_active', true)->count();
        $inactiveSchools = $totalSchools - $activeSchools;
        $totalUsers = User::count();
        
        // 2. Ingreso Mensual (Resolviendo ambigüedad de is_active)
        $monthlyRevenue = School::where('schools.is_active', true)
            ->join('plans', 'schools.plan_id', '=', 'plans.id')
            ->sum('plans.price');

        // 3. Distribución por Plan
        $plansStats = Plan::withCount('schools')->has('schools')->get();
        $plansDistribution = [
            'labels' => $plansStats->pluck('name'),
            'data'   => $plansStats->pluck('schools_count')
        ];

        // 4. Crecimiento últimos 30 días
        $growthData = School::select(
                DB::raw('DATE(created_at) as date'), 
                DB::raw('count(*) as aggregate')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        /**
         * 5. Últimas Escuelas Registradas
         * Cargamos la relación 'users' para obtener al responsable.
         * Nota: Asegúrate de que el modelo School tenga: public function users() { return $this->hasMany(User::class); }
         */
        $latestSchools = School::with(['users' => function($q) {
                $q->select('id', 'name', 'avatar_color' ,'school_id', 'position');
            }])
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.admin.dashboard.stats-overview', [
            'activeSchools' => $activeSchools,
            'inactiveSchools' => $inactiveSchools,
            'totalUsers' => $totalUsers,
            'monthlyRevenue' => $monthlyRevenue,
            'plansDistribution' => $plansDistribution,
            'growthLabels' => $growthData->pluck('date'),
            'growthValues' => $growthData->pluck('aggregate'),
            'latestSchools' => $latestSchools,
        ]);
    }
}