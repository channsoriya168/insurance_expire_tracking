import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp();

const webApp = typeof window !== 'undefined' ? window.Telegram?.WebApp : null;

if (webApp) {
    webApp.ready();
    webApp.expand();

    try {
        webApp.setHeaderColor('#1e3a8a');
        webApp.setBackgroundColor('#f8fafc');
    } catch {
        // Older Telegram clients don't support arbitrary header/background colors.
    }
}
