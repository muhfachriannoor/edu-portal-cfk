import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import inject from '@rollup/plugin-inject';

export default defineConfig(({ command }) => ({
    server: command === 'serve' ? {
        hmr: {
            host: 'localhost',
        },
    } : undefined,
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        inject({
            $: 'jquery',
            jQuery: 'jquery',
        }),
    ],
    optimizeDeps: {
        include: ['jquery'],
    },
}));
