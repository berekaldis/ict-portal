import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Kaldis ICT Portal';

// Handle dynamic import errors automatically after new code deployments
window.addEventListener('vite:preloadError', (event) => {
    event.preventDefault();
    const reloadKey = 'last_vite_preload_reload';
    const lastReload = sessionStorage.getItem(reloadKey);
    const now = Date.now();
    if (!lastReload || now - parseInt(lastReload, 10) > 8000) {
        sessionStorage.setItem(reloadKey, now.toString());
        window.location.reload();
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx')
        ).catch((error) => {
            // Auto-reload once if dynamic chunk import fails due to deployment update
            if (
                error?.message?.includes('Failed to fetch dynamically imported module') ||
                error?.message?.includes('Importing a module script failed') ||
                error?.name === 'TypeError'
            ) {
                const reloadKey = 'last_dynamic_import_reload';
                const lastReload = sessionStorage.getItem(reloadKey);
                const now = Date.now();
                if (!lastReload || now - parseInt(lastReload, 10) > 8000) {
                    sessionStorage.setItem(reloadKey, now.toString());
                    window.location.reload();
                    return;
                }
            }
            throw error;
        }),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#b45309', // Warm amber / coffee tone
    },
});
