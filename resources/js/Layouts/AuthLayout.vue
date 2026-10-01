<template>
    <div class="flex h-dvh flex-col overflow-hidden bg-[#f5f5f5] text-gray-900 font-sans">
        <div class="relative z-50 shrink-0">
            <div class="bg-[#003366] text-white text-[9px] sm:text-xs py-1.5 sm:py-1 text-center tracking-wide sm:tracking-widest uppercase leading-snug sm:leading-relaxed px-3 sm:px-4">
                Republic of the Philippines
                <span class="text-white/60"> | </span>
                Province of Albay
                <span class="text-white/60"> | </span>
                Municipality of Oas
            </div>

            <header class="bg-white border-b-4 border-[#FFD700] shadow-md">
                <div class="mx-auto max-w-6xl px-3 sm:px-6 py-3 sm:py-4 flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <img :src="'/images/logo.png'" alt="Oas Albay Seal" class="h-9 w-9 sm:h-14 sm:w-14 object-contain shrink-0" />
                    <div class="min-w-0">
                        <p class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-500 font-semibold hidden sm:block">Official Website</p>
                        <h1 class="text-sm sm:text-lg font-bold text-[#003366] leading-tight">Document Management System</h1>
                        <p class="text-[11px] sm:text-xs text-gray-600">Office of the Vice Mayor &mdash; Oas, Albay</p>
                    </div>
                </div>
            </header>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-y-auto overflow-x-hidden overscroll-y-contain">
            <div class="flex-1 mx-auto w-full max-w-xl px-3 sm:px-6 py-6 sm:py-12">
                <Link href="/login" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-[#003366] transition mb-4 sm:mb-5 min-h-[44px] sm:min-h-0 py-2 sm:py-0">
                    &larr; Back to Sign In
                </Link>
                <slot />
            </div>

            <footer class="bg-[#003366] text-white border-t-4 border-[#FFD700] mt-auto">
                <div class="mx-auto max-w-6xl px-3 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
                    <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider sm:tracking-widest text-[#FFD700] leading-relaxed">
                        Office of the Vice Mayor &mdash; Oas, Albay
                    </p>
                    <div class="text-[11px] sm:text-xs text-blue-200 flex flex-col sm:flex-row flex-wrap gap-1 sm:gap-4">
                        <span class="break-all">Hotline: {{ site.hotline || '(052) 555-0198' }}</span>
                        <span class="break-all">Email: info@oas-albay.gov.ph</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();
const site = computed(() => page.props.site ?? {});
</script>
