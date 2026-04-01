import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    // Activamos el modo oscuro por clase (.dark)
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/View/Components/**/*.php', // <-- AÑADE ESTA LÍNEA
        './app/Livewire/**/*.php',        // <-- AÑADE ESTA TAMBIÉN (Recomendado para el TALL stack)
    ],

    theme: {
        extend: {
            fontFamily: {
                // Establecemos Inter como la fuente principal
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                orvian: {
                    // Azul Principal (Deep Blue)
                    'blue': '#13294c',
                    'blue-light': '#0a3d8f',
                    'blue-dark': '#021a41',
                    'navy': '#064083',
                    
                    // Naranja Secundario (Action Orange)
                    'orange': '#f78904',
                    'orange-hover': '#e07a04',
                },
                state: {
                    'success': '#0ac083',
                    'warning': '#f59e0b',
                    'info': '#3b82f6',
                    'error': '#ef4444',
                },
                // Definimos una paleta de grises neutros para el Modo Oscuro
                dark: {
                    'bg': '#0a0a0b',     // Un gris neutro ultra-profundo. Casi negro.
                    'card': '#111113',   // Un tono apenas más claro. La separación es sutil pero elegante.
                    'border': '#1e1e21'  // Un gris oscuro para los bordes del "Line UI".
                }
                // OPCION DOS SI QUIERO UN CAMBIO
                // dark: {
                //     'bg': '#000000',     // Negro puro.
                //     'card': '#09090b',   // Un gris zinc muy oscuro.
                //     'border': '#1e1e21'  // Un gris neutro oscuro.
                // }
            },
            borderRadius: {
                'orvian': '12px', // <--- Clave para el diseño redondeado
            },
            height: {
                '11': '2.75rem', // 44px
                '12': '4rem',    // 48px
                '13': '5.5rem', // 88px
            },
            
        },
    },

    plugins: [forms, typography],
};