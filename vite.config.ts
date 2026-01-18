import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        chunkSizeWarningLimit: 550,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (
                            id.includes('chart.js') ||
                            id.includes('vue-chartjs')
                        ) {
                            return 'chart';
                        }
                        if (id.includes('reka-ui')) {
                            return 'ui';
                        }
                        if (id.includes('lucide-vue-next')) {
                            return 'icons';
                        }
                        if (
                            id.includes('@inertiajs') ||
                            id.includes('@vueuse')
                        ) {
                            return 'vendor';
                        }
                    }
                },
            },
        },
    },
});
