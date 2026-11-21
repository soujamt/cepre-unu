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
                // Pantallas Livewire (se retiran al terminar la migración)
                'resources/css/app.css',
                'resources/js/app.js',
                // Pantallas Inertia + React
                'resources/css/inertia.css',
                'resources/js/app.tsx',
            ],
            refresh: true,
        }),
        inertia({ ssr: false }),
        react(),
        tailwindcss(),
        wayfinder({ formVariants: true }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**', '**/.agents/**', '**/.claude/**', '**/.github/**', '**/vendor/**'],
        },
    },
});
