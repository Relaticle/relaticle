import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/app/theme.css',
                'resources/css/filament/admin/theme.css',
                // Documentation
                'app-modules/Documentation/resources/css/documentation.css',
                'app-modules/Documentation/resources/js/documentation.js',
            ],
            refresh: true,
        }),
    ],
});
