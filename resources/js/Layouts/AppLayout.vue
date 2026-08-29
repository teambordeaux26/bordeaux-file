<template>
    <div class="h-screen flex overflow-hidden bg-[#f5f5f5] font-sans">
        <!-- Fixed Sidebar -->
        <aside
            class="flex-shrink-0 flex flex-col bg-[#003366] h-full transition-[width] duration-300 ease-in-out overflow-hidden"
            :class="collapsed ? 'w-[4.5rem]' : 'w-64'"
        >
            <!-- Branding -->
            <div
                class="flex-shrink-0 border-b border-white/15"
                :class="collapsed ? 'px-2 py-4' : 'px-5 py-4'"
            >
                <div
                    class="flex items-center"
                    :class="collapsed ? 'justify-center' : 'gap-3'"
                >
                    <img
                        :src="'/images/logo.png'"
                        alt="Seal"
                        class="h-10 w-10 object-contain shrink-0 rounded-full bg-white p-0.5"
                    />
                    <div v-show="!collapsed">
                        <p
                            class="text-[9px] uppercase tracking-widest text-blue-300 font-semibold"
                        >
                            Vice Mayor
                        </p>
                        <h1 class="text-sm font-bold text-white leading-tight">
                            Document Management
                        </h1>
                        <p class="text-[10px] text-blue-300">Oas, Albay</p>
                    </div>
                </div>
            </div>

            <!-- Nav (scrollable if nav is long) -->
            <div
                class="flex-1 overflow-y-auto py-4"
                :class="collapsed ? 'px-1.5' : 'px-3'"
            >
                <SidebarNav :sections="navSections" :collapsed="collapsed" />
            </div>

            <!-- Sidebar Footer -->
            <div
                v-show="!collapsed"
                class="flex-shrink-0 px-5 py-3 border-t border-white/15"
            >
                <p class="text-[10px] text-blue-300 uppercase tracking-widest">
                    Republic of the Philippines
                </p>
                <p class="text-[10px] text-blue-400 mt-0.5">
                    Province of Albay &mdash; Oas
                </p>
            </div>
        </aside>

        <!-- Right column: topbar + scrollable content -->
        <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
            <TopBar />
            <main class="flex-1 overflow-y-auto px-6 py-6 pb-10">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import SidebarNav from "../Components/SidebarNav.vue";
import TopBar from "../Components/TopBar.vue";
import { useSidebar } from "../composables/useSidebar.js";

const page = usePage();
const { collapsed } = useSidebar();
const role = computed(() => page.props.auth?.user?.role ?? "guest");
const employeePages = computed(() => page.props.employeePages ?? null);
const returnedCount = computed(() => page.props.returnedCount ?? 0);

const pageKeyByHref = {
    "/dashboard": "dashboard",
    "/documents": "documents",
    "/documents/returned": "documents",
    "/workflow": "workflow",
    "/visitors": "visitors",
    "/certificates": "certificates",
    "/archive": "archive",
};

function filterNavItems(items) {
    if (role.value === "admin" || !employeePages.value) {
        return items;
    }

    return items.filter((item) => employeePages.value.includes(pageKeyByHref[item.href]));
}

const baseSections = computed(() => [
    {
        label: "Operations",
        items: filterNavItems([
            { label: "Dashboard", href: "/dashboard", icon: "LayoutDashboard" },
            { label: "Documents", href: "/documents", icon: "FileText" },
            { label: "Workflow", href: "/workflow", icon: "GitBranch" },
            {
                label: "Returned Documents",
                href: "/documents/returned",
                icon: "RotateCcw",
                badge: returnedCount.value > 0 ? returnedCount.value : null,
            },
            { label: "Visitors Log", href: "/visitors", icon: "UserCheck" },
            { label: "Certificates", href: "/certificates", icon: "Award" },
            { label: "Archive", href: "/archive", icon: "Archive" },
        ]),
    },
]);

const adminSection = {
    label: "Administration",
    items: [
        { label: "User Management", href: "/users", icon: "Users" },
        { label: "Departments", href: "/departments", icon: "Building2" },
        { label: "Review & Approval", href: "/approvals", icon: "ClipboardCheck" },
        { label: "Request Reviews", href: "/requests", icon: "Inbox" },
        { label: "Audit Trail", href: "/audit", icon: "ScrollText" },
        { label: "Settings", href: "/settings", icon: "Settings" },
    ],
};

const navSections = computed(() => {
    if (role.value === "admin") {
        return [...baseSections.value, adminSection];
    }
    return baseSections.value;
});
</script>
