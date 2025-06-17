import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Marketing website
                'resources/css/app.css',
                'resources/js/app.js',
                // Filament
                'resources/css/filament/app/theme.css',
                'resources/css/filament/admin/theme.css',
                // Documentation
                'app-modules/Documentation/resources/css/documentation.css',
                'app-modules/Documentation/resources/js/documentation.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
