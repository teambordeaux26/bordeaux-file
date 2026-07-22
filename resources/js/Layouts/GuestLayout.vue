<template>
    <div class="min-h-screen bg-[#f5f5f5] text-gray-900 font-sans flex flex-col">

        <!-- Top Government Banner -->
        <div class="bg-[#003366] text-white text-[10px] sm:text-xs py-1 text-center tracking-widest uppercase leading-relaxed px-4">
            Republic of the Philippines &nbsp;|&nbsp; Province of Albay &nbsp;|&nbsp; Municipality of Oas
        </div>

        <!-- Header -->
        <header class="relative z-50 bg-white border-b-4 border-[#FFD700] shadow-md">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-3">
                <!-- Branding -->
                <Link href="/" class="flex items-center gap-3">
                    <img :src="'/images/logo.png'" alt="Oas Albay Seal" class="h-10 w-10 sm:h-14 sm:w-14 object-contain shrink-0" />
                    <div>
                        <p class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-500 font-semibold hidden sm:block">Official Website</p>
                        <h1 class="text-sm sm:text-lg font-bold text-[#003366] leading-tight">Guest Services Portal</h1>
                        <p class="text-xs text-gray-600 hidden sm:block">Office of the Vice Mayor &mdash; Oas, Albay</p>
                    </div>
                </Link>

                <!-- Desktop Nav -->
                <nav v-if="currentNavItem" class="hidden md:flex items-center gap-2 text-sm font-semibold">
                    <Link
                        :href="currentNavItem.href"
                        class="px-4 py-2 bg-[#003366] text-white transition"
                    >
                        {{ currentNavItem.label }}
                    </Link>
                </nav>

                <!-- Mobile Hamburger -->
                <button
                    v-if="currentNavItem"
                    @click="menuOpen = !menuOpen"
                    class="md:hidden flex flex-col justify-center items-center gap-1.5 p-2 border border-[#003366] text-[#003366]"
                    aria-label="Toggle menu"
                >
                    <span class="block w-5 h-0.5 bg-[#003366] transition-all" :class="menuOpen ? 'rotate-45 translate-y-2' : ''"></span>
                    <span class="block w-5 h-0.5 bg-[#003366] transition-all" :class="menuOpen ? 'opacity-0' : ''"></span>
                    <span class="block w-5 h-0.5 bg-[#003366] transition-all" :class="menuOpen ? '-rotate-45 -translate-y-2' : ''"></span>
                </button>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div v-if="menuOpen && currentNavItem" class="md:hidden absolute left-0 right-0 top-full z-50 border-t border-gray-200 bg-white shadow-lg">
                <div class="px-4 py-3 flex flex-col gap-2">
                    <Link
                        :href="currentNavItem.href"
                        class="px-4 py-2 bg-[#003366] text-white text-sm font-semibold text-center"
                        @click="menuOpen = false"
                    >
                        {{ currentNavItem.label }}
                    </Link>
                </div>
            </div>
        </header>

        <div
            v-if="menuOpen"
            class="fixed inset-0 z-40 bg-black/30 md:hidden"
            @click="menuOpen = false"
        ></div>

        <!-- Notice Bar -->
        <div class="bg-[#FFD700] text-[#003366] text-[10px] sm:text-xs py-2 text-center font-semibold tracking-wide px-4 leading-relaxed">
            Office Hours: {{ site.office_hours || 'Monday to Friday, 8:00 AM – 5:00 PM' }} &nbsp;|&nbsp; Hotline: {{ site.hotline || '(052) 555-0198' }}
        </div>

        <main class="flex-1 mx-auto w-full max-w-6xl px-4 sm:px-6 pb-10 sm:pb-12 pt-8 sm:pt-10">
            <Link href="/" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-[#003366] transition mb-6">
                &larr; Back to Home
            </Link>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-[#003366] text-white border-t-4 border-[#FFD700]">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs font-bold uppercase tracking-widest text-[#FFD700]">Office of the Vice Mayor &mdash; Oas, Albay</p>
                <div class="text-xs text-blue-200 flex flex-wrap gap-3 sm:gap-4">
                    <span>Hotline: {{ site.hotline || '(052) 555-0198' }}</span>
                    <span>Email: info@oas-albay.gov.ph</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const menuOpen = ref(false);
const page = usePage();
const site = computed(() => page.props.site ?? {});

const navItems = [
    { label: "Get Documents", href: "/get-documents", match: (path) => path.startsWith("/get-documents") },
    { label: "New Request", href: "/requests/new", match: (path) => path === "/requests/new" },
    { label: "Request Status", href: "/requests/status", match: (path) => path === "/requests/status" || (path.startsWith("/requests/") && path.includes("/certificate")) },
];

const currentPath = computed(() => page.url.split("?")[0]);

const currentNavItem = computed(() =>
    navItems.find((item) => item.match(currentPath.value)) ?? null
);
</script>
