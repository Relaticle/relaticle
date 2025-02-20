import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import colors from "tailwindcss/colors.js";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Satoshi', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    '50': '#ecfeff',
                    '100': '#cffafe',
                    '200': '#a5f3fc',
                    '300': '#67e8f9',
                    '400': '#22d3ee',
                    '500': '#06b6d4',
                    '600': '#0891b2',
                    '700': '#0e7490',
                    '800': '#155e75',
                    '900': '#164e63',
                    '950': '#083344',
                    DEFAULT: '#06b6d4',
                }
            },
            animation: {
                'pulse-twice': 'pulse 1s cubic-bezier(0, 0, 0.2, 1) 2',
            }
        },
    },

    plugins: [forms, typography],
};
