<template>
    <header class="flex-shrink-0 bg-white border-b-4 border-[#FFD700] shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-3">
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
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Operations Portal</p>
                    <h2 class="text-base font-bold text-[#003366] leading-tight">Vice Mayor's Office &mdash; Oas, Albay</h2>
                </div>
            </div>

            <!-- Right: user info + logout -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-[#003366]">
                        {{ user?.name ?? "Guest User" }}
                    </p>
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
                        {{ roleLabel }}
                    </p>
                </div>
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
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { PanelLeft, PanelLeftClose } from "@lucide/vue";
import { useSidebar } from "../composables/useSidebar.js";

const page = usePage();
const { collapsed, toggle } = useSidebar();

const user = computed(() => page.props.auth?.user);
const roleLabel = computed(() => {
    const role = user.value?.role ?? "guest";
    if (role === "admin") return "Administrator";
    if (role === "employee") return "Employee";
    return "Guest";
});
</script>
