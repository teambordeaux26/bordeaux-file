<template>
    <AppLayout>
        <Head title="Archive" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Archive Management"
                kicker="Operations"
                subtitle="Documents that have reached their retention period or been moved to long-term storage."
            >
                <template #actions>
                    <Link href="/documents" class="soft-button-light">Back to Documents</Link>
                </template>
            </PageHeader>

            <SectionCard
                title="Archived Documents"
                eyebrow="Archive"
                subtitle="Completed files stored for long-term retention."
            >
                <div
                    v-if="!totalArchives"
                    class="rounded border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center"
                >
                    <p class="text-sm font-semibold text-gray-700">No archived documents yet</p>
                    <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                        Documents are moved here automatically when their retention period ends.
                        Upload new documents with a retention period to enable auto-archiving.
                    </p>
                    <Link href="/documents/upload" class="soft-button inline-flex mt-4">
                        Upload Document
                    </Link>
                </div>

                <div v-else>
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <input
                            v-model="search"
                            class="soft-input !w-auto min-w-[12rem] flex-1 max-w-xs"
                            placeholder="Search title, tracking, or owner"
                        />
                        <select
                            v-model="filterCategory"
                            class="soft-select !w-64 shrink-0"
                            @change="applyFilters"
                        >
                            <option value="">All categories</option>
                            <template v-for="cat in categoryTree" :key="cat.id">
                                <option :value="String(cat.id)">{{ cat.name }}</option>
                                <option
                                    v-for="child in cat.children"
                                    :key="child.id"
                                    :value="String(child.id)"
                                >
                                    — {{ child.name }}
                                </option>
                            </template>
                        </select>
                        <select
                            v-model="filterRetention"
                            class="soft-select !w-40 shrink-0"
                            @change="applyFilters"
                        >
                            <option value="">All retention</option>
                            <option
                                v-for="option in retentionOptions"
                                :key="option.value"
                                :value="String(option.value)"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <select
                            v-model="filterYear"
                            class="soft-select !w-36 shrink-0"
                            @change="applyFilters"
                        >
                            <option value="">All years</option>
                            <option v-for="year in years" :key="year" :value="String(year)">
                                {{ year }}
                            </option>
                        </select>
                        <button
                            v-if="hasFilters"
                            type="button"
                            class="text-xs font-semibold text-gray-500 hover:text-[#003366]"
                            @click="clearFilters"
                        >
                            Clear
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-gray-300">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-[#003366] text-[10px] uppercase tracking-widest text-white">
                                <tr>
                                    <th class="px-4 py-3">Tracking No.</th>
                                    <th class="px-4 py-3">Title</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3">Retention</th>
                                    <th class="px-4 py-3">Archived On</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-if="rows.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">
                                        No archived documents match these filters.
                                    </td>
                                </tr>
                                <tr v-for="item in rows" :key="item.id" class="hover:bg-blue-50/40 transition">
                                    <td class="px-4 py-3 font-bold text-[#003366] whitespace-nowrap">
                                        {{ item.tracking }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-900">{{ item.title }}</p>
                                        <p class="text-xs text-gray-500">{{ item.owner }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ item.category }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ item.retention }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ item.archived }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <Link
                                                :href="item.view_url"
                                                class="soft-button-light inline-flex items-center gap-1.5"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                View
                                            </Link>
                                            <a
                                                v-if="item.download_url"
                                                :href="item.download_url"
                                                class="soft-button-light inline-flex items-center gap-1.5"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                                                </svg>
                                                Download
                                            </a>
                                            <button
                                                v-if="isAdmin"
                                                class="soft-button-light"
                                                :disabled="processing === item.id"
                                                @click="restore(item)"
                                            >
                                                {{ processing === item.id ? '…' : 'Restore' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination :paginator="archives" />
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import Pagination from "../../Components/Pagination.vue";

const props = defineProps({
    archives: { type: Object, default: () => ({ data: [], total: 0 }) },
    totalArchives: { type: Number, default: 0 },
    categoryTree: { type: Array, default: () => [] },
    retentionOptions: { type: Array, default: () => [] },
    years: { type: Array, default: () => [] },
    filters: {
        type: Object,
        default: () => ({ q: "", category_id: "", retention_days: "", year: "" }),
    },
});

const rows = computed(() => props.archives?.data ?? []);
const search = ref(props.filters.q ?? "");
const filterCategory = ref(props.filters.category_id ?? "");
const filterRetention = ref(props.filters.retention_days ?? "");
const filterYear = ref(props.filters.year ?? "");

const hasFilters = computed(() =>
    Boolean(search.value || filterCategory.value || filterRetention.value || filterYear.value)
);

watch(
    () => props.filters,
    (value) => {
        search.value = value?.q ?? "";
        filterCategory.value = value?.category_id ?? "";
        filterRetention.value = value?.retention_days ?? "";
        filterYear.value = value?.year ?? "";
    },
    { deep: true },
);

let searchTimer = null;

watch(search, (value) => {
    if ((value ?? "") === (props.filters.q ?? "")) return;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
});

onBeforeUnmount(() => {
    if (searchTimer) clearTimeout(searchTimer);
});

function applyFilters() {
    router.get("/archive", {
        q: search.value || undefined,
        category_id: filterCategory.value || undefined,
        retention_days: filterRetention.value || undefined,
        year: filterYear.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function clearFilters() {
    search.value = "";
    filterCategory.value = "";
    filterRetention.value = "";
    filterYear.value = "";
    applyFilters();
}

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === "admin");
const processing = ref(null);

function restore(item) {
    if (!confirm(`Restore "${item.title}" from archive?`)) return;
    processing.value = item.id;
    router.put(`/archive/${item.id}/restore`, {}, {
        onFinish: () => { processing.value = null; },
    });
}
</script>
