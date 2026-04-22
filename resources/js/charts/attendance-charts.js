/**
 * Attendance Charts — Alpine.js components para el Dashboard de Asistencia.
 *
 * Ambos componentes reciben datos iniciales como parámetro y se actualizan
 * vía eventos Livewire sin re-renderizar el DOM (compatible con wire:ignore).
 *
 * Uso en Blade:
 *   <div wire:ignore x-data="attendanceDonutChart(@js($plantelStats))">
 *   <div wire:ignore x-data="attendanceLineChart(@js($weeklyStats))">
 */

// ── Donut: distribución de estados del plantel ───────────────────────────────
// initialStats: { present: int, late: int, absent: int }
document.addEventListener('alpine:init', () => {
    Alpine.data('attendanceDonutChart', (initialStats = {}) => ({
        chart: null,

        init() {
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new ApexCharts(this.$el, this.buildOptions(initialStats, isDark));
            this.chart.render();

            // Actualizar series cuando Livewire re-emita datos
            Livewire.on('plantel-stats-updated', (stats) => {
                this.chart.updateSeries([
                    stats.present ?? 0,
                    stats.late    ?? 0,
                    stats.absent  ?? 0,
                ]);
            });

            // Sincronizar tema con el toggle dark mode del sistema
            window.addEventListener('theme-changed', () => {
                const dark = document.documentElement.classList.contains('dark');
                this.chart.updateOptions({ theme: { mode: dark ? 'dark' : 'light' } });
            });
        },

        buildOptions(stats, isDark) {
            return {
                ...window.defaultChartOptions,
                chart: {
                    ...window.defaultChartOptions.chart,
                    type: 'donut',
                    height: 280,
                },
                series: [
                    stats.present ?? 0,
                    stats.late    ?? 0,
                    stats.absent  ?? 0,
                ],
                labels: ['Presentes', 'Tardanzas', 'Ausentes'],
                colors: ['#10b981', '#f59e0b', '#ef4444'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '13px',
                                    fontWeight: 600,
                                    color: isDark ? '#9ca3af' : '#6b7280',
                                    formatter: (w) =>
                                        w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                                },
                                value: {
                                    fontSize: '22px',
                                    fontWeight: 700,
                                    color: isDark ? '#f9fafb' : '#111827',
                                },
                            },
                        },
                    },
                },
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    markers: { radius: 6 },
                    itemMargin: { horizontal: 10 },
                },
                dataLabels: { enabled: false },
                stroke: { width: 0 },
                tooltip: {
                    ...window.defaultChartOptions.tooltip,
                    y: { formatter: (val) => `${val} estudiantes` },
                },
            };
        },
    }));

    // ── Línea: tasa de asistencia últimos 7 días escolares ───────────────────
    // initialData: [{ date: 'DD/MM', rate: float 0-100 }, ...]
    Alpine.data('attendanceLineChart', (initialData = []) => ({
        chart: null,

        init() {
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new ApexCharts(this.$el, this.buildOptions(initialData, isDark));
            this.chart.render();

            // Actualizar cuando Livewire re-emita datos
            Livewire.on('weekly-stats-updated', ({ stats }) => {
                this.chart.updateOptions({
                    xaxis: { categories: stats.map((d) => d.date) },
                });
                this.chart.updateSeries([{
                    name: 'Asistencia',
                    data: stats.map((d) => parseFloat(d.rate.toFixed(1))),
                }]);
            });

            window.addEventListener('theme-changed', () => {
                const dark = document.documentElement.classList.contains('dark');
                this.chart.updateOptions({
                    theme: { mode: dark ? 'dark' : 'light' },
                    grid: {
                        borderColor: dark ? '#1e1e21' : '#f3f4f6',
                    },
                });
            });
        },

        buildOptions(data, isDark) {
            return {
                ...window.defaultChartOptions,
                chart: {
                    ...window.defaultChartOptions.chart,
                    type: 'area',
                    height: 280,
                    animations: { enabled: true, speed: 400 },
                    sparkline: { enabled: false },
                },
                series: [{
                    name: 'Asistencia',
                    data: data.map((d) => parseFloat(d.rate.toFixed(1))),
                }],
                xaxis: {
                    categories: data.map((d) => d.date),
                    labels: {
                        style: {
                            fontSize: '11px',
                            colors: isDark ? '#6b7280' : '#9ca3af',
                        },
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    tickAmount: 4,
                    labels: {
                        formatter: (val) => `${val}%`,
                        style: {
                            fontSize: '11px',
                            colors: isDark ? '#6b7280' : '#9ca3af',
                        },
                    },
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.25,
                        opacityTo: 0.02,
                        stops: [0, 95],
                    },
                },
                colors: ['#3b82f6'],
                stroke: {
                    curve: 'smooth',
                    width: 2,
                },
                markers: {
                    size: 4,
                    strokeWidth: 0,
                    hover: { size: 6 },
                },
                dataLabels: { enabled: false },
                tooltip: {
                    ...window.defaultChartOptions.tooltip,
                    y: { formatter: (val) => `${val}%` },
                },
                grid: {
                    ...window.defaultChartOptions.grid,
                    padding: { top: 0, right: 8, bottom: 0, left: 8 },
                },
            };
        },
    }));
});
