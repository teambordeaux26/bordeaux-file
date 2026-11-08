import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Toaster, toast } from 'vue-sonner';
import 'vue-sonner/style.css';

function showFlash(flash) {
    if (!flash) return;
    if (flash.success) toast.success(flash.success);
    if (flash.error) toast.error(flash.error);
}

createInertiaApp({
    title: title => `${title} - DMS`,
    resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Register after Inertia is initializing so the listener is bound.
        router.on('success', (event) => {
            showFlash(event.detail?.page?.props?.flash);
        });

        // Initial page load (full refresh / first visit with a flash).
        showFlash(props.initialPage?.props?.flash);

        const httpMessages = {
            403: 'You do not have permission to access this page.',
            404: 'The page you requested could not be found.',
            419: 'Your session expired. Please sign in again.',
            500: 'Something went wrong. Please try again.',
            503: 'The system is temporarily unavailable.',
        };

        router.on('invalid', (event) => {
            event.preventDefault();
            const status = event.detail.response?.status;
            toast.error(httpMessages[status] || 'Something went wrong. Please try again.');
            if (status === 419) {
                window.location.assign('/login');
            }
        });

        router.on('exception', (event) => {
            event.preventDefault();
            toast.error('Something went wrong. Please try again.');
        });

        // A page restored from the back button must be loaded again so a
        // logged-out session cannot keep showing the office portal.
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                window.location.reload();
            }
        });

        createApp({
            render: () => [
                h(App, props),
                h(Toaster, {
                    position: 'top-right',
                    closeButton: true,
                    closeButtonPosition: 'top-right',
                    duration: 4500,
                    theme: 'light',
                }),
            ],
        })
            .use(plugin)
            .mount(el);
    },
});
