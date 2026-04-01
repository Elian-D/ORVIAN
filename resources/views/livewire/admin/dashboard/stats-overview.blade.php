<div class="space-y-6">
    {{-- Cards de Métricas Rápidas --}}

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-admin.stat-card 
            title="Escuelas Activas" 
            :value="$activeSchools" 
            icon="heroicon-s-academic-cap" 
            color="text-state-success" 
        />
        
        <x-admin.stat-card 
            title="Usuarios Totales" 
            :value="$totalUsers" 
            icon="heroicon-s-user-group" 
            color="text-orvian-blue" 
        />

        {{-- Aquí formateamos el ingreso como moneda --}}
        <x-admin.stat-card 
            title="Ingreso Mensual" 
            :value="'$' . number_format($monthlyRevenue)" 
            icon="heroicon-s-currency-dollar" 
            color="text-orvian-orange" 
        />

        <x-admin.stat-card 
            title="Inactivas / Suspendidas" 
            :value="$inactiveSchools" 
            icon="heroicon-s-pause-circle" 
            color="text-state-error" 
        />
    </div>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Gráfico de Crecimiento --}}
        <div class="lg:col-span-2 bg-white dark:bg-dark-card p-6 rounded-orvian border border-gray-100 dark:border-dark-border">
            <h3 class="text-lg font-bold mb-4">Crecimiento (30 días)</h3>
            <div id="growthChart" wire:ignore></div>
        </div>

        {{-- Gráfico de Planes --}}
        <div class="bg-white dark:bg-dark-card p-6 rounded-orvian border border-gray-100 dark:border-dark-border">
            <h3 class="text-lg font-bold mb-4">Distribución de Planes</h3>
            <div id="plansChart" wire:ignore></div>
        </div>
    </div>

{{-- Listado de Últimas Escuelas --}}
<div class="bg-white dark:bg-dark-card rounded-orvian border border-gray-100 dark:border-dark-border overflow-hidden shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-dark-border bg-gray-50/50 dark:bg-white/5 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Últimos Registros</h3>
        <span class="text-xs font-medium text-gray-400 uppercase tracking-widest">Actividad Reciente</span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="text-gray-400 uppercase text-xs tracking-wider border-b border-gray-100 dark:border-dark-border">
                    <th class="px-6 py-4 font-semibold">Escuela / Centro</th>
                    <th class="px-6 py-4 font-semibold">Director / Responsable</th>
                    <th class="px-6 py-4 font-semibold">Configuración</th>
                    <th class="px-6 py-4 font-semibold text-right">Fecha Registro</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                @forelse($latestSchools as $school)
                    @php
                        // Buscamos al Director por cargo o tomamos el primer usuario asociado
                        $director = $school->users->firstWhere('position', 'Director') 
                                    ?? $school->users->first();
                    @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-gray-100">{{ $school->name }}</div>
                            <div class="text-xs text-gray-500">{{ $school->sigerd_code ?? 'Sin código SIGERD' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($director)
                                <div class="flex items-center gap-2">
                                    <x-ui.avatar :user="$director" size="sm"  />
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $director->name }}</span>
                                </div>
                            @else
                                <span class="text-gray-400 italic">Sin usuario asignado</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($school->is_configured)
                                <x-ui.badge variant="success" size="sm" dot>
                                    Completada
                                </x-ui.badge>
                            @else
                                <x-ui.badge variant="warning" size="sm" dot>
                                    Pendiente
                                </x-ui.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-gray-500 font-medium">
                            {{ $school->created_at->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">
                            No hay escuelas registradas en el sistema.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    {{-- Scripts de ApexCharts --}}
    @push('scripts')
    <script>
        document.addEventListener('livewire:navigated', () => {
            // Función rápida para refrescar opciones basadas en el tema actual
            const getThemeOptions = () => ({
                theme: {
                    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                }
            });

            // Gráfico de Crecimiento
            new ApexCharts(document.querySelector("#growthChart"), {
                ...window.defaultChartOptions,
                ...getThemeOptions(), // Forzamos el tema actual al momento de renderizar
                chart: { ...window.defaultChartOptions.chart, type: 'area', height: 300 },
                series: [{ name: 'Nuevas Escuelas', data: @json($growthValues) }],
                xaxis: { 
                    categories: @json($growthLabels),
                    labels: { style: { colors: '#6b7280' } } // Forzamos gris para los labels del eje X
                },
                colors: ['#f78904']
            }).render();

            // Gráfico de Donut (Planes) - ACTUALIZADO
            new ApexCharts(document.querySelector("#plansChart"), {
                ...window.defaultChartOptions,
                ...getThemeOptions(),
                chart: { 
                    ...window.defaultChartOptions.chart, 
                    type: 'donut', 
                    height: 350, // Aumentamos un poco la altura total
                },
                series: @json($plansDistribution['data']),
                labels: @json($plansDistribution['labels']),
                colors: ['#13294c', '#f78904', '#0ac083'],
                stroke: { show: false },
                
                // CONFIGURACIÓN DE LEYENDA Y ESPACIADO
                legend: {
                    position: 'bottom', // Forzamos leyendas abajo
                    horizontalAlign: 'center',
                    fontSize: '14px',
                    markers: { radius: 12 },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 5
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '75%', // Controla el grosor del anillo
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false // Quitamos los números de encima del gráfico para que se vea más limpio
                }
            }).render();
        });
    </script>
    @endpush
</div>