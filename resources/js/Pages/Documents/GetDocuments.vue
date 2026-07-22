<template>
    <GuestLayout>
        <Head title="Get Documents" />
        <div class="space-y-8">
            <div class="border-l-4 border-[#003366] pl-4">
                <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Public Access</p>
                <h1 class="text-2xl font-bold text-[#003366]">Get Documents</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Search approved office documents and open or download available files.
                </p>
            </div>

            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm p-4 sm:p-6">
                <div class="border-b border-gray-200 pb-3 mb-6">
                    <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Lookup</p>
                    <h2 class="text-lg font-bold text-[#003366]">Search Documents</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Search by title, tracking number, reference number, category, or description.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        v-model="q"
                        class="flex-1 border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                        placeholder="Search documents…"
                        @keyup.enter="doSearch"
                    />
                    <div class="flex gap-2 shrink-0">
                        <button
                            class="w-full sm:w-auto px-6 py-2.5 bg-[#003366] text-white text-sm font-bold hover:bg-[#002244] transition"
                            @click="doSearch"
                        >
                            Search
                        </button>
                        <button
                            class="w-full sm:w-auto px-6 py-2.5 border border-[#003366] text-[#003366] text-sm font-bold hover:bg-[#003366] hover:text-white transition"
                            @click="reset"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <p
                        v-if="!searched"
                        class="text-sm text-gray-500 text-center py-8 border border-dashed border-gray-200 rounded"
                    >
                        Enter a search term to find approved documents with available files.
                    </p>

                    <p
                        v-else-if="results.length === 0"
                        class="text-sm text-gray-500 text-center py-8"
                    >
                        No documents found for "{{ filters.q }}". Try a different keyword.
                    </p>

                    <template v-else>
                        <p class="text-xs text-gray-400">
                            {{ results.length }} document(s) found.
                        </p>

                        <div
                            v-for="doc in results"
                            :key="doc.id"
                            class="border border-gray-200 bg-gray-50 px-4 py-4 flex flex-wrap items-center justify-between gap-4"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-[#003366]">{{ doc.title }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ doc.tracking }} · {{ doc.category }} · {{ doc.date }}
                                </p>
                                <p class="mt-1 text-xs text-gray-600">{{ doc.summary }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2 shrink-0">
                                <Link
                                    :href="doc.view_url"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 border border-[#003366] text-[#003366] text-xs font-bold hover:bg-[#003366] hover:text-white transition"
                                >
                                    View
                                </Link>
                                <a
                                    :href="doc.download_url"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#003366] text-white text-xs font-bold hover:bg-[#002244] transition"
                                >
                                    Download
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import GuestLayout from "../../Layouts/GuestLayout.vue";

const props = defineProps({
    results:  { type: Array, default: () => [] },
    filters:  { type: Object, default: () => ({ q: "" }) },
    searched: { type: Boolean, default: false },
});

const q = ref(props.filters.q ?? "");

watch(
    () => props.filters.q,
    (value) => {
        q.value = value ?? "";
    }
);

function doSearch() {
    if (!q.value.trim()) {
        reset();
        return;
    }

    router.get(
        "/get-documents",
        { q: q.value.trim() },
        { preserveState: true, replace: true }
    );
}

function reset() {
    q.value = "";
    router.get("/get-documents", {}, { preserveState: true, replace: true });
}
</script>
