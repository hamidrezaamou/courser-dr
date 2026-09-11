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
                sans: ['Vazirmatn', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f3f6f9',
                    100: '#e8eef5',
                    200: '#c5d4e4',
                    300: '#9bb4cf',
                    400: '#6b8fb8',
                    500: '#507299',
                    600: '#3d5570',
                    700: '#2e3f50',
                    800: '#243343',
                    900: '#1a2330',
                },
                warn: {
                    DEFAULT: '#ff5722',
                    soft: '#ffe8e0',
                },
            },
            boxShadow: {
                soft: '0 1px 2px rgba(46, 63, 80, 0.05), 0 12px 28px -14px rgba(46, 63, 80, 0.22)',
            },
        },
    },

    plugins: [forms],
};
