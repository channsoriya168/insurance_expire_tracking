import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp();

if (window.Telegram?.WebApp) {
    window.Telegram.WebApp.ready();
    window.Telegram.WebApp.expand();
}
