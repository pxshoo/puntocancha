import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: 'rgb(var(--brand-primary) / <alpha-value>)',
                    hover: 'rgb(var(--brand-hover) / <alpha-value>)',
                    content: 'rgb(var(--brand-content) / <alpha-value>)',
                    ring: 'rgb(var(--brand-ring) / <alpha-value>)',
                    on: 'rgb(var(--brand-on) / <alpha-value>)',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                pulseSlow: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.6' },
                }
            },
            animation: {
                'fade-in': 'fadeIn 0.25s ease-out forwards',
                'pulse-slow': 'pulseSlow 3s ease-in-out infinite',
            }
        },
    },
    plugins: [
        forms,
        require('tailwindcss-animate'),
    ],
};