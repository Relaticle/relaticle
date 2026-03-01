import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'resources/dist',
        cssCodeSplit: false,
        rollupOptions: {
            input: 'resources/js/workflow-builder/index.js',
            output: {
                format: 'iife',
                entryFileNames: 'workflow-builder.js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.names && assetInfo.names[0] === 'style.css') {
                        return 'workflow-builder.css';
                    }
                    return '[name].[ext]';
                },
                inlineDynamicImports: true,
            },
        },
    },
});
