<template>
    <AppLayout>
        <Head title="Search & Retrieval" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Search & Retrieval"
                kicker="Search"
                subtitle="Search documents, certificates, visitors, and requests across the system."
            />

            <SectionCard
                title="Search Records"
                eyebrow="Lookup"
                subtitle="Search by name, category, type, status, tracking number, and other record details."
            >
                <div class="flex flex-col gap-3 sm:flex-row">
                    <input
                        v-model="q"
                        class="soft-input flex-1"
                        placeholder="Search names, categories, types, statuses, tracking no…"
                        @keyup.enter="doSearch"
                    />
                    <div class="flex gap-2 shrink-0">
                        <button class="soft-button flex-1 sm:flex-none" @click="doSearch">Search</button>
                        <button class="soft-button-light flex-1 sm:flex-none" @click="reset">Reset</button>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <p
                        v-if="!searched"
                        class="text-sm text-slate-500 text-center py-8 border border-dashed border-slate-200 rounded"
                    >
                        Enter a search term to find records across the system.
                    </p>

                    <p
                        v-else-if="results.length === 0"
                        class="text-sm text-slate-500 text-center py-8"
                    >
                        No results found for "{{ filters.q }}". Try a different keyword.
                    </p>

                    <template v-else>
                        <p class="text-xs text-slate-400">
                            {{ results.length }} result(s) found. Click a record to open it.
                        </p>

                        <template v-for="result in results" :key="result.key">
                            <Link
                                v-if="!result.external"
                                :href="result.url"
                                class="card-shell flex flex-wrap items-center justify-between gap-4 px-4 py-3 transition hover:bg-blue-50/60 hover:border-[#003366]/30 cursor-pointer"
                            >
                                <SearchResultRow :result="result" />
                            </Link>

                            <a
                                v-else
                                :href="result.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="card-shell flex flex-wrap items-center justify-between gap-4 px-4 py-3 transition hover:bg-blue-50/60 hover:border-[#003366]/30 cursor-pointer"
                            >
                                <SearchResultRow :result="result" />
                            </a>
                        </template>
                    </template>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import SearchResultRow from "../../Components/SearchResultRow.vue";

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
        "/search",
        { q: q.value.trim() },
        { preserveState: true, replace: true }
    );
}

function reset() {
    q.value = "";
    router.get("/search", {}, { preserveState: true, replace: true });
}
</script>
