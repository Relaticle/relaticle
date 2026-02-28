import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'resources/dist',
        rollupOptions: {
            input: {
                'workflow-builder': 'resources/js/workflow-builder/index.js',
                'workflow-builder-css': 'resources/css/workflow-builder.css',
            },
            output: {
                entryFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
});
