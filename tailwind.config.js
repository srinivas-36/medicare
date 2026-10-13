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
                    DEFAULT: '#1769FF',
                    hover: '#0D57E5',
                },
                navy: '#0B1F3A',
                mint: {
                    DEFAULT: '#DDF8F1',
                    accent: '#00A884',
                },
                light: '#EAF3FF',
                page: '#F4F7FB',
                surface: '#FFFFFF',
                line: '#E2EAF4',
                body: '#53647D',
                muted: '#7C8BA1',
                ink: '#172337',
                heading: '#0B1F3A',
                text: '#53647D',
                danger: '#DC3545',
                warning: '#E99A17',
                success: '#00A884',
                // Older templates still say iris, yellow, or purple.
                iris: '#00A884',
                yellow: '#E99A17',
                purple: '#1769FF',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 10px 28px rgba(11, 31, 58, 0.06)',
                lift: '0 18px 40px rgba(23, 105, 255, 0.14)',
            },
            maxWidth: {
                page: '1280px',
            },
        },
    },

    plugins: [forms],
};
