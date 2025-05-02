import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './vendor/awcodes/overlook/resources/**/*.blade.php\',',
        './app-modules/Documentation/resources/**/*.blade.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Satoshi', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    '50': '#f5f3ff',
                    '100': '#ede9fe',
                    '200': '#ddd6fe',
                    '300': '#c4b5fd',
                    '400': '#a78bfa',
                    '500': '#8b5cf6',
                    '600': '#7c3aed',
                    '700': '#6d28d9',
                    '800': '#5b21b6',
                    '900': '#4c1d95',
                    '950': '#2e1065',
                    DEFAULT: '#7c3aed',
                }
            },
            animation: {
                'pulse-twice': 'pulse 1s cubic-bezier(0, 0, 0.2, 1) 2',
                blob: "blob 7s infinite",
                'float': 'float 5s ease-in-out infinite',
                'subtle-pulse': 'subtle-pulse 4s ease-in-out infinite',
                'data-flow': 'data-flow 3s ease-in-out infinite',
                'spin-slow': 'spin 6s linear infinite',
                'bounce-slow': 'bounce 3s infinite',
                'fade-in': 'fadeIn 1s ease-out',
                'slide-up': 'slideUp 0.5s ease-out',
                'slide-in-right': 'slideInRight 0.5s ease-out',
                'card-drop': 'cardDrop 0.3s ease-out',
                'column-fade': 'columnFade 0.4s ease-out',
                'appear': 'appear 0.2s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { transform: 'translateY(20px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                slideInRight: {
                    '0%': { transform: 'translateX(20px)', opacity: '0' },
                    '100%': { transform: 'translateX(0)', opacity: '1' },
                },
                cardDrop: {
                    '0%': { transform: 'translateY(-8px)', opacity: '0.6' },
                    '50%': { transform: 'translateY(2px)' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                columnFade: {
                    '0%': { opacity: '0.6', transform: 'translateX(12px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                appear: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
            }
        },
    },
    plugins: [
        forms,
        typography,
        // Add a custom plugin for scrollbar styling
        function({ addUtilities }) {
            const newUtilities = {
                '.scrollbar-thin': {
                    scrollbarWidth: 'thin',
                    '&::-webkit-scrollbar': {
                        width: '4px',
                        height: '4px',
                    },
                },
                '.scrollbar-thumb-rounded': {
                    '&::-webkit-scrollbar-thumb': {
                        borderRadius: '0.25rem',
                    },
                },
                '.scrollbar-track-transparent': {
                    '&::-webkit-scrollbar-track': {
                        backgroundColor: 'transparent',
                    },
                },
            };
            addUtilities(newUtilities, ['responsive', 'dark']);
        },
    ],
};
