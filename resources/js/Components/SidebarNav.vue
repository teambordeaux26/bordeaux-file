<template>
    <nav :class="collapsed ? 'space-y-2' : 'space-y-5'">
        <div v-for="section in sections" :key="section.label">
            <p
                v-show="!collapsed"
                class="text-[10px] uppercase tracking-widest text-blue-400 font-semibold px-2 mb-2"
            >
                {{ section.label }}
            </p>
            <div class="space-y-0.5">
                <div v-for="item in section.items" :key="item.href">
                    <div
                        class="flex items-stretch transition"
                        :class="[
                            collapsed ? 'rounded-md' : '',
                            rowActive(item)
                                ? collapsed
                                    ? 'bg-white/15 text-white'
                                    : 'bg-white/15 text-white font-bold border-l-4 border-[#FFD700]'
                                : collapsed
                                  ? 'text-blue-200 hover:bg-white/10 hover:text-white border-l-4 border-transparent'
                                  : 'text-blue-200 hover:bg-white/10 hover:text-white font-medium border-l-4 border-transparent',
                        ]"
                    >
                        <Link
                            :href="item.href"
                            :title="collapsed ? item.label : undefined"
                            class="flex min-w-0 flex-1 items-center transition"
                            :class="collapsed ? 'justify-center px-2 py-2.5' : 'justify-between gap-2 px-3 py-2'"
                            @click="onItemClick($event, item)"
                        >
                            <span
                                class="flex items-center min-w-0"
                                :class="collapsed ? '' : 'gap-2.5'"
                            >
                                <component
                                    :is="resolveIcon(item)"
                                    class="h-4 w-4 shrink-0"
                                    :stroke-width="2"
                                />
                                <span v-show="!collapsed" class="truncate">{{ item.label }}</span>
                            </span>
                            <span
                                v-if="item.badge && !collapsed"
                                class="bg-[#FFD700] text-[#003366] px-2 py-0.5 text-[10px] font-bold shrink-0"
                            >
                                {{ item.badge }}
                            </span>
                        </Link>
                        <button
                            v-if="item.children?.length && !collapsed"
                            type="button"
                            class="shrink-0 px-2 text-blue-200 hover:text-white"
                            :class="isDrawerOpen(item) ? 'text-[#FFD700]' : ''"
                            :aria-expanded="isDrawerOpen(item)"
                            :aria-label="`Open ${item.label} folders`"
                            @click="toggleDrawer(item)"
                        >
                            <ChevronRight
                                class="h-4 w-4 transition-transform"
                                :class="isDrawerOpen(item) ? 'rotate-90' : ''"
                                :stroke-width="2.25"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <Teleport to="body">
        <div v-if="drawerItem">
            <button
                type="button"
                class="fixed bottom-0 right-0 top-0 z-30 bg-black/40"
                :style="{ left: sidebarOffset }"
                aria-label="Close folders"
                @click="closeDrawer"
            />
            <aside
                class="fixed bottom-0 top-0 z-40 flex w-80 flex-col border-l border-white/15 bg-[#003366] shadow-xl"
                :style="{ left: sidebarOffset }"
            >
                <div class="flex items-start justify-between gap-3 border-b border-white/15 px-4 py-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-blue-300">Folders</p>
                        <h2 class="text-sm font-bold text-white">{{ drawerItem.label }}</h2>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center border border-white/20 text-blue-100 hover:bg-white/10"
                        aria-label="Close"
                        @click="closeDrawer"
                    >
                        <X class="h-4 w-4" :stroke-width="2.25" />
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto px-3 py-3">
                    <Link
                        :href="drawerItem.href"
                        class="mb-2 block border border-white/15 px-3 py-2 text-sm font-semibold transition"
                        :class="isActive(drawerItem.href) ? 'bg-white/15 text-[#FFD700]' : 'text-blue-100 hover:bg-white/10 hover:text-white'"
                        @click="closeDrawer"
                    >
                        All documents
                    </Link>
                    <Link
                        v-for="child in drawerItem.children"
                        :key="child.href"
                        :href="child.href"
                        class="block px-3 py-2.5 text-sm leading-snug transition"
                        :class="isActive(child.href) ? 'bg-white/15 font-bold text-[#FFD700]' : 'text-blue-100 hover:bg-white/10 hover:text-white'"
                    >
                        {{ child.label }}
                    </Link>
                </nav>
            </aside>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { ChevronRight, X } from "@lucide/vue";
import { navIconByHref, navIconMap } from "../lib/navIcons.js";

const props = defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const drawerHref = ref(null);
const currentUrl = computed(() => page.url || "");
const sidebarOffset = computed(() => (props.collapsed ? "4.5rem" : "16rem"));

const drawerItem = computed(() => {
    for (const section of props.sections) {
        const match = (section.items || []).find((item) => item.href === drawerHref.value && item.children?.length);
        if (match) return match;
    }

    return null;
});

function isDrawerOpen(item) {
    return drawerHref.value === item.href;
}

function rowActive(item) {
    return isActive(item.href)
        || isDrawerOpen(item)
        || (item.children || []).some((child) => isActive(child.href));
}

function toggleDrawer(item) {
    drawerHref.value = isDrawerOpen(item) ? null : item.href;
}

function closeDrawer() {
    drawerHref.value = null;
}

function onItemClick(event, item) {
    if (props.collapsed && item.children?.length) {
        event.preventDefault();
        toggleDrawer(item);
    }
}

function onEsc(event) {
    if (event.key === "Escape") closeDrawer();
}

watch(drawerItem, (item) => {
    document.body.style.overflow = item ? "hidden" : "";
    if (item) window.addEventListener("keydown", onEsc);
    else window.removeEventListener("keydown", onEsc);
});

onBeforeUnmount(() => {
    document.body.style.overflow = "";
    window.removeEventListener("keydown", onEsc);
});
const currentPath = computed(() => currentUrl.value.split("?")[0]);

const allHrefs = computed(() =>
    props.sections.flatMap((section) =>
        (section.items || []).flatMap((item) => [
            item.href,
            ...(item.children || []).map((child) => child.href),
        ]).filter(Boolean)
    )
);

const isActive = (href) => {
    const path = currentPath.value;
    const [itemPath, itemQuery] = String(href).split("?");

    if (itemQuery) {
        const params = new URLSearchParams(currentUrl.value.split("?")[1] || "");
        const expected = new URLSearchParams(itemQuery);
        return path === itemPath && [...expected.entries()].every(([key, value]) => params.get(key) === value);
    }

    if (href === "/dashboard") {
        return path === href || path === "/";
    }

    if (itemPath === "/documents") {
        const params = new URLSearchParams(currentUrl.value.split("?")[1] || "");
        if (params.get("category_id")) return false;
    }

    const pathHrefs = allHrefs.value
        .map((candidate) => candidate.split("?")[0])
        .filter((candidate, index, list) => list.indexOf(candidate) === index);

    const matches = pathHrefs.filter(
        (candidate) => path === candidate || path.startsWith(`${candidate}/`)
    );

    if (matches.length === 0) return false;

    const best = matches.reduce((a, b) => (a.length >= b.length ? a : b));
    return best === itemPath;
};

function resolveIcon(item) {
    const iconName = item.icon ?? navIconByHref[item.href];
    return navIconMap[iconName] ?? navIconMap.LayoutDashboard;
}
</script>
