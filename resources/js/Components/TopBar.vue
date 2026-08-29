<template>
    <header class="flex-shrink-0 bg-white border-b-4 border-[#FFD700] shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-3">
            <!-- Left: collapse + office label -->
            <div class="flex items-center gap-3 min-w-0">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-gray-300 text-[#003366] hover:bg-gray-50 transition"
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    @click="toggle"
                >
                    <PanelLeftClose v-if="!collapsed" class="h-5 w-5" :stroke-width="2" />
                    <PanelLeft v-else class="h-5 w-5" :stroke-width="2" />
                </button>
                <div class="min-w-0 hidden sm:block">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Operations Portal</p>
                    <h2 class="text-base font-bold text-[#003366] leading-tight truncate">
                        Vice Mayor's Office &mdash; Oas, Albay
                    </h2>
                </div>
            </div>

            <!-- Center: global search -->
            <div
                v-if="canSearch"
                ref="searchWrap"
                class="relative flex-1 min-w-[12rem] max-w-xl order-last w-full sm:order-none sm:w-auto"
            >
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        :stroke-width="2"
                    />
                    <input
                        v-model="query"
                        type="search"
                        class="w-full border border-gray-300 bg-gray-50 py-2 pl-10 pr-10 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                        placeholder="Search documents, users, visitors…"
                        autocomplete="off"
                        aria-label="Global search"
                        aria-autocomplete="list"
                        :aria-expanded="open"
                        @focus="open = true"
                        @keydown.escape.prevent="closeDropdown"
                        @keydown.enter.prevent="openActiveResult"
                        @keydown.down.prevent="moveActive(1)"
                        @keydown.up.prevent="moveActive(-1)"
                    />
                    <button
                        v-if="query"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-6 w-6 items-center justify-center text-gray-400 hover:text-gray-700"
                        aria-label="Clear search"
                        @click="clearSearch"
                    >
                        <X class="h-3.5 w-3.5" :stroke-width="2.25" />
                    </button>
                </div>

                <div
                    v-if="open && (query.trim().length >= 2 || loading || results.length)"
                    class="absolute left-0 right-0 top-full z-50 mt-1 border border-gray-300 bg-white shadow-lg max-h-[24rem] overflow-y-auto"
                >
                    <div v-if="loading" class="px-4 py-3 text-xs text-gray-500">
                        Searching…
                    </div>

                    <template v-else-if="query.trim().length < 2">
                        <p class="px-4 py-3 text-xs text-gray-500">
                            Type at least 2 characters to search the system.
                        </p>
                    </template>

                    <template v-else-if="results.length === 0">
                        <p class="px-4 py-3 text-xs text-gray-500">
                            No matches for “{{ query.trim() }}”.
                        </p>
                    </template>

                    <template v-else>
                        <p class="sticky top-0 border-b border-gray-100 bg-gray-50 px-4 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-gray-500">
                            {{ results.length }} result{{ results.length === 1 ? '' : 's' }}
                        </p>

                        <component
                            :is="item.external ? 'a' : Link"
                            v-for="(item, index) in results"
                            :key="item.key"
                            :href="item.url"
                            :target="item.external ? '_blank' : undefined"
                            :rel="item.external ? 'noopener noreferrer' : undefined"
                            class="flex items-start gap-3 border-b border-gray-100 px-4 py-2.5 transition last:border-b-0"
                            :class="index === activeIndex ? 'bg-blue-50' : 'hover:bg-gray-50'"
                            @mouseenter="activeIndex = index"
                            @click="closeDropdown"
                        >
                            <span class="mt-0.5 shrink-0 border border-gray-200 bg-white px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-gray-600">
                                {{ item.type }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-[#003366]">{{ item.title }}</p>
                                <p class="truncate text-xs text-gray-500">{{ item.subtitle }}</p>
                            </div>
                            <span
                                v-if="item.status"
                                class="shrink-0 text-[10px] font-semibold uppercase tracking-wider text-gray-500"
                            >
                                {{ item.status }}
                            </span>
                        </component>
                    </template>
                </div>
            </div>

            <!-- Right: user info + logout -->
            <div class="flex flex-wrap items-center gap-3 sm:gap-4 shrink-0">
                <div class="text-right">
                    <p class="text-sm font-bold text-[#003366]">
                        {{ user?.name ?? "Guest User" }}
                    </p>
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
                        {{ roleLabel }}
                    </p>
                </div>
                <Link
                    href="/account"
                    class="border border-gray-400 px-4 py-1.5 text-xs font-semibold text-gray-600 hover:border-[#003366] hover:text-[#003366] transition"
                >
                    Account
                </Link>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="border border-gray-400 px-4 py-1.5 text-xs font-semibold text-gray-600 hover:border-red-500 hover:text-red-600 transition"
                >
                    Logout
                </Link>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { PanelLeft, PanelLeftClose, Search, X } from "@lucide/vue";
import { useSidebar } from "../composables/useSidebar.js";

const page = usePage();
const { collapsed, toggle } = useSidebar();

const user = computed(() => page.props.auth?.user);
const role = computed(() => user.value?.role ?? "guest");
const employeePages = computed(() => page.props.employeePages ?? null);

const canSearch = computed(() => {
    if (role.value === "admin") return true;
    if (role.value === "employee") {
        return Array.isArray(employeePages.value) && employeePages.value.includes("search");
    }
    return false;
});

const roleLabel = computed(() => {
    if (role.value === "admin") return "Administrator";
    if (role.value === "employee") return "Employee";
    return "Guest";
});

const searchWrap = ref(null);
const query = ref("");
const results = ref([]);
const loading = ref(false);
const open = ref(false);
const activeIndex = ref(-1);

let debounceTimer = null;
let abortController = null;

function closeDropdown() {
    open.value = false;
    activeIndex.value = -1;
}

function clearSearch() {
    query.value = "";
    results.value = [];
    loading.value = false;
    closeDropdown();
}

function openActiveResult() {
    if (activeIndex.value < 0 || !results.value[activeIndex.value]) return;

    const item = results.value[activeIndex.value];
    closeDropdown();

    if (item.external) {
        window.open(item.url, "_blank", "noopener,noreferrer");
    } else {
        router.visit(item.url);
    }
}

function moveActive(delta) {
    if (!results.value.length) return;
    open.value = true;
    const len = results.value.length;
    if (activeIndex.value < 0) {
        activeIndex.value = delta > 0 ? 0 : len - 1;
        return;
    }
    activeIndex.value = (activeIndex.value + delta + len) % len;
}

async function fetchSuggest(q) {
    if (abortController) abortController.abort();
    abortController = new AbortController();
    loading.value = true;

    try {
        const { data } = await window.axios.get("/search/suggest", {
            params: { q },
            signal: abortController.signal,
        });
        results.value = data.results ?? [];
        activeIndex.value = results.value.length ? 0 : -1;
    } catch (err) {
        if (err?.code === "ERR_CANCELED" || err?.name === "CanceledError") return;
        results.value = [];
    } finally {
        loading.value = false;
    }
}

watch(query, (value) => {
    const q = value.trim();
    open.value = true;

    if (debounceTimer) clearTimeout(debounceTimer);

    if (q.length < 2) {
        results.value = [];
        loading.value = false;
        return;
    }

    debounceTimer = setTimeout(() => fetchSuggest(q), 280);
});

function onDocClick(e) {
    if (!searchWrap.value) return;
    if (!searchWrap.value.contains(e.target)) {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener("mousedown", onDocClick);
});

onBeforeUnmount(() => {
    document.removeEventListener("mousedown", onDocClick);
    if (debounceTimer) clearTimeout(debounceTimer);
    if (abortController) abortController.abort();
});
</script>
