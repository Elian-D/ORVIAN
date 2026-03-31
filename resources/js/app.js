import './bootstrap';

// Graficos ApexCharts
import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;
import './charts-helper';

/* 
// Importamos Alpine desde el paquete de Livewire (para evitar doble instancia)
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Lo hacemos disponible para los componentes antiguos de Breeze
window.Alpine = Alpine;

// No llamamos a Alpine.start(), Livewire lo hace internamente.
Livewire.start(); */