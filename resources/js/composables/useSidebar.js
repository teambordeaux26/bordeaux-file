import { ref, watch } from "vue";

const STORAGE_KEY = "dms-sidebar-collapsed";

const collapsed = ref(
    typeof window !== "undefined" && localStorage.getItem(STORAGE_KEY) === "true"
);

watch(collapsed, (value) => {
    localStorage.setItem(STORAGE_KEY, String(value));
});

export function useSidebar() {
    function toggle() {
        collapsed.value = !collapsed.value;
    }

    return { collapsed, toggle };
}
