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
