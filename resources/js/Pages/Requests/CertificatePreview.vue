<template>
    <GuestLayout>
        <Head title="Certificate of Appearance" />
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="border-l-4 border-emerald-500 pl-4">
                <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Guest Services</p>
                <h1 class="text-2xl font-bold text-[#003366]">Certificate of Appearance</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Your certificate has been generated. Preview it below or download a copy.
                </p>
            </div>

            <!-- Success banner -->
            <div class="flex items-start gap-3 bg-emerald-50 border border-emerald-300 px-4 py-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-bold text-emerald-800">Certificate Successfully Generated</p>
                    <p class="text-xs text-emerald-700 mt-0.5">
                        Certificate No. <span class="font-mono font-bold">{{ cert.number }}</span>
                        issued to <span class="font-semibold">{{ cert.name }}</span> on {{ cert.issued }}.
                    </p>
                </div>
            </div>

            <!-- Certificate details summary -->
            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Document</p>
                        <h2 class="text-base font-bold text-[#003366]">Certificate of Appearance</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <!-- Download button -->
                        <a
                            :href="download_url"
                            class="soft-button inline-flex items-center gap-2 px-5 py-2.5"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Download PDF
                        </a>
                        <Link href="/requests/new" class="soft-button-light inline-flex items-center gap-2 px-5 py-2.5">
                            New Request
                        </Link>
                    </div>
                </div>

                <!-- Summary grid -->
                <div class="px-5 py-4 grid gap-4 sm:grid-cols-2 text-sm border-b border-gray-100">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Certificate No.</p>
                        <p class="font-mono font-bold text-[#003366] text-base">{{ cert.number }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Date Issued</p>
                        <p class="text-gray-900">{{ cert.issued }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Issued To</p>
                        <p class="text-gray-900 font-medium">{{ cert.name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Purpose</p>
                        <p class="text-gray-900">{{ cert.purpose }}</p>
                    </div>
                </div>

                <!-- PDF iframe preview -->
                <div class="px-5 py-4">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-3">Document Preview</p>

                    <!-- Loading state shown until iframe loads -->
                    <div class="relative border border-gray-300 bg-gray-100" style="min-height: 520px;">
                        <div
                            v-if="loading"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-gray-400"
                        >
                            <svg class="h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <span class="text-sm">Loading certificate preview…</span>
                        </div>

                        <iframe
                            :src="preview_url"
                            class="w-full border-0 transition-opacity duration-300"
                            :class="loading ? 'opacity-0' : 'opacity-100'"
                            style="height: 520px;"
                            title="Certificate of Appearance Preview"
                            @load="loading = false"
                        ></iframe>
                    </div>

                    <!-- Fallback for browsers that block iframe PDF -->
                    <p class="mt-2 text-xs text-gray-400 text-center">
                        If the preview does not load,
                        <a :href="preview_url" target="_blank" class="text-[#003366] underline font-semibold">
                            open in a new tab
                        </a>
                        or use the Download button above.
                    </p>
                </div>
            </div>

        </div>
    </GuestLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import GuestLayout from "../../Layouts/GuestLayout.vue";

defineProps({
    cert:         { type: Object, required: true },
    preview_url:  { type: String, required: true },
    download_url: { type: String, required: true },
});

const loading = ref(true);
</script>
