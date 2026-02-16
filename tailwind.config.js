import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                eden: {
                    bg: '#0F172A',
                    sidebar: '#020617',
                    panel: '#020617',
                    primary: '#22C55E',
                    accent: '#16A34A',
                    text: '#E5E7EB',
                    muted: '#94A3B8',
                    danger: '#EF4444',
                    warning: '#F59E0B',
                },
            },
        },
    },

    plugins: [forms],
};
