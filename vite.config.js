import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/main.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }

                    // Keep admin route/page modules out of the customer entry graph.
                    // Do not force them into a shared manual chunk — that caused main to
                    // statically import the entire admin bundle.
                    if (
                        id.includes('/resources/js/pages/admin/')
                        || id.includes('/resources/js/components/admin/')
                        || id.includes('/resources/js/layouts/AdminLayout')
                        || id.includes('/resources/js/routes/AdminRoutes')
                        || id.includes('/resources/js/routes/lazyAdminPages')
                        || id.includes('/resources/js/contexts/AdminNotificationsContext')
                        || id.includes('/resources/js/api/admin/')
                    ) {
                        return null;
                    }

                    if (
                        id.includes('/resources/js/api/')
                        || id.includes('/resources/js/lib/')
                        || id.includes('/resources/js/hooks/')
                        || id.includes('/resources/js/contexts/')
                        || id.includes('/resources/js/routes/lazyPage.js')
                        || id.includes('/resources/js/components/storefront/')
                        || id.includes('/resources/js/components/orders/')
                        || id.includes('/resources/js/components/account/')
                        || id.includes('/resources/js/components/performance/')
                        || id.includes('/resources/js/layouts/StorefrontLayout')
                    ) {
                        return 'app-shared';
                    }
                },
            },
        },
        // Production safety: strip accidental debug leftover without changing behavior.
        esbuild: {
            drop: ['debugger'],
            pure: ['console.log', 'console.debug', 'console.table', 'console.info'],
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
