// Función para detectar si estamos en modo oscuro
const isDarkMode = () => document.documentElement.classList.contains('dark');

window.defaultChartOptions = {
    chart: {
        fontFamily: 'Inter, ui-sans-serif, system-ui',
        // Ya no fijamos el foreColor aquí, dejamos que el theme mode lo maneje
        toolbar: { show: false },
        background: 'transparent',
    },
    // Esta es la clave:
    theme: {
        mode: isDarkMode() ? 'dark' : 'light',
    },
    stroke: {
        curve: 'smooth',
        width: 2
    },
    grid: {
        // Colores de las líneas de la cuadrícula adaptables
        borderColor: isDarkMode() ? '#1e1e21' : '#f3f4f6', 
        strokeDashArray: 4,
    },
    tooltip: {
        theme: isDarkMode() ? 'dark' : 'light',
    }
};