import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#0067FF',
                    hover: '#0052CC',
                },
                navy: '#181A1E',
                mint: {
                    DEFAULT: '#F0FDF4',
                    accent: '#15803D',
                },
                light: '#E8F2FF',
                page: '#F7FAFF',
                surface: '#FFFFFF',
                line: '#E2E8F0',
                body: '#4E545F',
                muted: '#64748B',
                ink: '#181A1E',
                heading: '#181A1E',
                text: '#4E545F',
                danger: '#B91C1C',
                warning: '#B45309',
                success: '#15803D',
                info: '#1D4ED8',
                iris: '#15803D',
                yellow: '#B45309',
                purple: '#0067FF',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 2px rgba(24, 26, 30, 0.05)',
                lift: '0 8px 24px rgba(24, 26, 30, 0.08)',
            },
            maxWidth: {
                page: '1280px',
            },
        },
    },

    plugins: [forms],
};
