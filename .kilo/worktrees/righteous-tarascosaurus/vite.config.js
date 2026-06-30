import autoprefixer from 'autoprefixer';
import laravel from 'laravel-vite-plugin';
import tailwindcss from 'tailwindcss';
import { defineConfig } from 'vite';

export default defineConfig({
    server: {
        hmr: {
            host: 'localhost',
        },
        host: '0.0.0.0',
        port: 5174,
        strictPort: true,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/leaflet.css',
                'public/css/advanced-leaflet.css',
                'resources/js/app.js',
                'resources/js/frontend.js',
                'resources/js/pages/home.js',
                'resources/js/admin/neo.js',
                'resources/js/admin/global.js',
                // 'resources/js/admin/portal-ids-validate.js', // ❌ Dosya bulunamadı - geçici olarak devre dışı
                'resources/js/leaflet-loader.js',
                // Person selector components
                'resources/js/components/UnifiedPersonSelector.js',

                // Toast Notification System
                'resources/js/components/ToastNotification.js',

                // Dynamic Form Handler (Wizard forms)
                'resources/js/components/DynamicFormHandler.js',
                'resources/js/components/CortexObserver.js',
                'resources/js/components/SmartFormObserver.js',

                // Enhanced Location Manager (Context7 compliant)
                'resources/js/components/LocationManager.js',
                'resources/js/components/LocationSystemTester.js',

                'resources/js/admin/services/ValidationManager.js',
                'resources/js/admin/services/api-adapter.js',
                'resources/js/admin/services/list-paginate.js',
                'resources/js/admin/helpers/map-helper.js',
                'resources/js/admin/my-listings-search.js',
                'resources/js/admin/dashboard/opportunity-board.js',
                'resources/js/admin/dashboard/market-analysis.js',
                'resources/js/dashboard/cortex-analytics.js', // Cortex Analytics Dashboard (Phase 18)
                // İlan Create Modular JS
                'resources/js/admin/ilan-create.js',
                'resources/js/admin/ilan-create/tkgm-autofill.js',
                // İlan Wizard Page JS
                'resources/js/admin/ilan-wizard-page.js',
                'resources/js/admin/location-wizard.js',

                // ✅ Modular Wizard System v3.0.0 (2026-01-28)
                'resources/js/wizard/index.js', // Entry point with lazy loading

                // 🎯 Wizard Components (2026-01-29)
                'resources/js/wizard/components/price-formatter.js',
                'resources/js/wizard/components/ai-integration.js',
                'resources/js/wizard/components/ai-description.js',

                // Admin Safe Fetch Helper
                'resources/js/admin-safe-fetch.js',

                // AI Settings Modular JS (Hybrid Architecture)
                'resources/js/admin/ai-settings/core.js',
                // AI Register (AdminAIService global export)
                'resources/js/admin/ai-register.js',

                // Advanced OpenStreetMap Integration
                'resources/js/leaflet-integration.js',
                'resources/js/admin/listing-wizard/store.js',
                'resources/js/wizard/step1-cascade.js',
                'resources/js/wizard/step2-category.js',
                'resources/js/wizard/step2-features.js',
                'resources/js/wizard/schema-field-renderer.js',
                // 'public/js/advanced-leaflet-integration.js', // ❌ Dosya bulunamadı - geçici olarak devre dışı

                // Advanced UPS & Matrix Components
                'resources/js/components/SmartFormMatrix.js',
                'resources/js/components/WizardFormHandler.js',
                'resources/js/components/WizardCategorySync.js',
                'resources/js/components/MapPolygonManager.js',
                'resources/js/components/LiveSearchComponent.js',
            ],
            refresh: true,
        }),
    ],
    css: {
        postcss: {
            plugins: [tailwindcss, autoprefixer],
        },
    },
    build: {
        // Production optimizations
        rollupOptions: {
            output: {
                // Asset file names
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    return `assets/${extType}/[name]-[hash][extname]`;
                },
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
            },
        },
        // Minification and optimization
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.logs in production
                drop_debugger: true,
            },
        },
        // Chunk size warnings
        chunkSizeWarningLimit: 1000,
        // Source maps for debugging
        sourcemap: process.env.NODE_ENV !== 'production', // ✅ WFC-006: Enabled in local
    },
    // Resolve aliases
    resolve: {
        alias: {
            '@': '/resources',
            '@js': '/resources/js',
            '@css': '/resources/css',
            '@components': '/resources/js/components',
        },
    },
});
