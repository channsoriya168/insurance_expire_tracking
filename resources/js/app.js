import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp();

const webApp = window.Telegram?.WebApp;

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
