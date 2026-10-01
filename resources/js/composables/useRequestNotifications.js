import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";

const incoming = ref({ count: 0, items: [] });
let timer = null;
let listeners = 0;

export function useRequestNotifications({ poll = false } = {}) {
    const page = usePage();

    watch(
        () => page.props.requestNotifications,
        (value) => {
            if (value) {
                incoming.value = value;
            }
        },
        { immediate: true },
    );

    async function refresh() {
        try {
            const { data } = await window.axios.get("/requests/notifications");
            incoming.value = {
                count: data.count ?? 0,
                items: data.items ?? [],
            };
        } catch {
            // Keep the last list when a refresh fails.
        }
    }

    if (poll) {
        onMounted(() => {
            listeners += 1;
            if (!timer) {
                timer = window.setInterval(refresh, 30000);
            }
        });

        onUnmounted(() => {
            listeners = Math.max(0, listeners - 1);
            if (listeners === 0 && timer) {
                window.clearInterval(timer);
                timer = null;
            }
        });
    }

    return {
        incoming: computed(() => incoming.value),
        refresh,
    };
}
