import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import compression from 'vite-plugin-compression';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        compression({
            algorithm: 'gzip',
            ext: '.gz',
            threshold: 10240,
        }),
    ],
    build: {
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        rollupOptions: {
            output: {
                manualChunks: {
                    charts: ['chart.js/auto'],
                    swiper: ['swiper'],
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        hmr: {
            protocol: 'ws',
            host: 'localhost',
            port: 5173,
        },
    },
});
