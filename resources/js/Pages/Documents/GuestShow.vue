<template>
    <GuestLayout>
        <Head :title="document.title" />
        <div class="space-y-8">
            <div class="border-l-4 border-[#003366] pl-4">
                <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Public Access</p>
                <h1 class="text-2xl font-bold text-[#003366]">{{ document.title }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ document.tracking }} · {{ document.category }}
                </p>
            </div>

            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Document</p>
                        <h2 class="text-lg font-bold text-[#003366]">Released File</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a
                            :href="download_url"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#003366] text-white text-sm font-bold hover:bg-[#002244] transition"
                        >
                            Download
                        </a>
                        <Link
                            href="/get-documents"
                            class="inline-flex items-center gap-2 px-5 py-2.5 border border-[#003366] text-[#003366] text-sm font-bold hover:bg-[#003366] hover:text-white transition"
                        >
                            Back to Search
                        </Link>
                    </div>
                </div>

                <div class="px-4 sm:px-6 py-4 grid gap-4 sm:grid-cols-2 text-sm border-b border-gray-100">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Tracking No.</p>
                        <p class="font-bold text-[#003366]">{{ document.tracking }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Reference No.</p>
                        <p class="text-gray-900">{{ document.reference }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Category</p>
                        <p class="text-gray-900">{{ document.category }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Released On</p>
                        <p class="text-gray-900">{{ document.released }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Description</p>
                        <p class="text-gray-700">{{ document.description }}</p>
                    </div>
                </div>

                <div v-if="preview_url" class="px-4 sm:px-6 py-4">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-3">File Preview</p>
                    <div class="relative border border-gray-300 bg-gray-100" style="min-height: 520px;">
                        <div
                            v-if="loading"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-gray-400"
                        >
                            <svg class="h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <span class="text-sm">Loading preview…</span>
                        </div>
                        <iframe
                            :src="preview_url"
                            class="w-full border-0 transition-opacity duration-300"
                            :class="loading ? 'opacity-0' : 'opacity-100'"
                            style="height: 520px;"
                            title="Document Preview"
                            @load="loading = false"
                        ></iframe>
                    </div>
                </div>

                <div v-else class="px-4 sm:px-6 py-4">
                    <div class="rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        This file type cannot be previewed in the browser. Use the Download button to open it locally.
                    </div>
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
    document:     { type: Object, required: true },
    preview_url:  { type: String, default: null },
    download_url: { type: String, required: true },
});

const loading = ref(true);
</script>
