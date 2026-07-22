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
                <Link
                    v-for="item in section.items"
                    :key="item.href"
                    :href="item.href"
                    :title="collapsed ? item.label : undefined"
                    class="flex items-center transition"
                    :class="[
                        collapsed
                            ? 'justify-center px-2 py-2.5 rounded-md'
                            : 'justify-between gap-2 px-3 py-2',
                        isActive(item.href)
                            ? collapsed
                                ? 'bg-white/15 text-white'
                                : 'bg-white/15 text-white font-bold border-l-4 border-[#FFD700]'
                            : collapsed
                              ? 'text-blue-200 hover:bg-white/10 hover:text-white border-l-4 border-transparent'
                              : 'text-blue-200 hover:bg-white/10 hover:text-white font-medium border-l-4 border-transparent',
                    ]"
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
            </div>
        </div>
    </nav>
</template>

<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { navIconByHref, navIconMap } from "../lib/navIcons.js";

defineProps({
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
const currentPath = computed(() => page.url);

const isActive = (href) => {
    if (href === "/dashboard") {
        return currentPath.value === href || currentPath.value === "/";
    }
    return currentPath.value.startsWith(href);
};

function resolveIcon(item) {
    const iconName = item.icon ?? navIconByHref[item.href];
    return navIconMap[iconName] ?? navIconMap.LayoutDashboard;
}
</script>
