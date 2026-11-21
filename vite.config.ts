import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/inertia.css',
                'resources/js/app.tsx',
            ],
            refresh: true,
        }),
        inertia({ ssr: false }),
        react(),
        tailwindcss(),
        wayfinder({ formVariants: true, skipDatabase: true }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**', '**/.agents/**', '**/.claude/**', '**/.github/**', '**/vendor/**'],
        },
    },
});
