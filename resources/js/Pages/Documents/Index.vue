<template>
    <AppLayout>
        <Head title="Documents" />
        <div class="space-y-6 fade-up">
            <PageHeader
                title="Document Management"
                kicker="Documents"
                subtitle="Store, categorize, and monitor records by official document type."
            >
                <template #actions>
                    <Link href="/documents/upload" class="soft-button"
                        >Upload Document</Link
                    >
                </template>
            </PageHeader>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <button
                    type="button"
                    class="border px-4 py-3 text-left transition"
                    :class="!filterCategory ? 'border-[#003366] bg-blue-50' : 'border-gray-300 bg-white hover:border-[#003366]'"
                    @click="selectCategory('')"
                >
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">All categories</p>
                    <p class="mt-1 text-lg font-bold text-[#003366]">{{ totalCategoryCount }}</p>
                </button>
                <button
                    v-for="cat in categoryTree"
                    :key="cat.id"
                    type="button"
                    class="border px-4 py-3 text-left transition"
                    :class="Number(filterCategory) === Number(cat.id) ? 'border-[#003366] bg-blue-50' : 'border-gray-300 bg-white hover:border-[#003366]'"
                    @click="selectCategory(cat.id)"
                >
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">{{ cat.name }}</p>
                    <p class="mt-1 text-lg font-bold text-[#003366]">{{ cat.count }}</p>
                </button>
            </div>

            <div
                v-if="selectedParent?.children?.length"
                class="flex flex-wrap gap-2"
            >
                <button
                    v-for="child in selectedParent.children"
                    :key="child.id"
                    type="button"
                    class="px-3 py-1.5 text-xs font-semibold border"
                    :class="Number(filterCategory) === Number(child.id) ? 'bg-[#003366] text-white border-[#003366]' : 'bg-white text-[#003366] border-[#003366]'"
                    @click="selectCategory(child.id)"
                >
                    {{ child.name }} ({{ child.count }})
                </button>
            </div>

            <SectionCard
                title="Document Queue"
                eyebrow="Current"
                subtitle="Most recent submissions and active files."
            >
                <!-- Filters -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        class="soft-input !w-auto min-w-[12rem] flex-1 max-w-xs"
                        placeholder="Search title or tracking no."
                        @keyup.enter="applyFilters"
                    />
                    <select
                        v-model="filterCategory"
                        class="soft-select !w-64 shrink-0"
                        @change="applyFilters"
                    >
                        <option value="">All categories</option>
                        <template v-for="cat in categoryTree" :key="cat.id">
                            <option :value="cat.id">{{ cat.name }}</option>
                            <option
                                v-for="child in cat.children"
                                :key="child.id"
                                :value="child.id"
                            >
                                — {{ child.name }}
                            </option>
                        </template>
                    </select>
                    <select
                        v-model="filterStatus"
                        class="soft-select !w-40 shrink-0"
                        @change="applyFilters"
                    >
                        <option value="">All statuses</option>
                        <option>Pending</option>
                        <option>Under Review</option>
                        <option>Approved</option>
                        <option>Returned</option>
                        <option>Disapproved</option>
                    </select>
                    <button
                        type="button"
                        class="soft-button-light text-xs"
                        @click="applyFilters"
                    >
                        Search
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto border border-gray-300">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="bg-[#003366] text-[10px] uppercase tracking-widest text-white"
                        >
                            <tr>
                                <th class="px-4 py-3">Tracking No.</th>
                                <th class="px-4 py-3">Reference No.</th>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Priority</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Submitted By</th>
                                <th class="px-4 py-3">Expires</th>
                                <th class="px-4 py-3">Updated</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-if="rows.length === 0">
                                <td
                                    colspan="10"
                                    class="px-4 py-8 text-center text-sm text-gray-400"
                                >
                                    No documents found.
                                </td>
                            </tr>
                            <tr
                                v-for="doc in rows"
                                :key="doc.id"
                                class="hover:bg-blue-50/40 transition"
                            >
                                <td
                                    class="px-4 py-3 font-bold text-[#003366] whitespace-nowrap"
                                >
                                    {{ doc.tracking }}
                                </td>
                                <td
                                    class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap"
                                >
                                    {{ doc.reference }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">
                                        {{ doc.title }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ doc.summary }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 whitespace-nowrap"
                                >
                                    {{ doc.category }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wider px-2 py-0.5"
                                        :class="priorityClass(doc.priority)"
                                    >
                                        {{ doc.priority }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="px-2 py-0.5 text-xs font-semibold uppercase tracking-wide"
                                        :class="statusClass(doc.status)"
                                    >
                                        {{ doc.status }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 whitespace-nowrap"
                                >
                                    {{ doc.owner }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="text-xs text-gray-700">{{ doc.expires_at }}</p>
                                    <p class="text-[10px] text-gray-400">{{ doc.retention }}</p>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap"
                                >
                                    {{ doc.updated }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <Link
                                            :href="doc.view_url"
                                            class="soft-button-light inline-flex items-center gap-1.5"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            View
                                        </Link>
                                        <a
                                            v-if="doc.download_url"
                                            :href="doc.download_url"
                                            class="soft-button-light inline-flex items-center gap-1.5"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                                            </svg>
                                            Download
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :paginator="documents" />
            </SectionCard>

            <ReportPanel
                title="Document Movement Overview"
                subtitle="Generate and export processed documents by week or month."
                :period="reportPeriod"
                :range="reportRange"
                :report="report"
                export-base="/documents/reports/export"
                @update:period="changeReportPeriod"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import Pagination from "../../Components/Pagination.vue";
import ReportPanel from "../../Components/ReportPanel.vue";

const props = defineProps({
    documents: { type: Object, default: () => ({ data: [] }) },
    categoryTree: { type: Array, default: () => [] },
    report: { type: Object, default: () => ({ stats: [], breakdown: [], period: "monthly" }) },
    reportRange: { type: String, default: "" },
    filters: {
        type: Object,
        default: () => ({ q: "", category_id: "", status: "" }),
    },
});

const rows = computed(() => props.documents?.data ?? []);
const totalCategoryCount = computed(() =>
    props.categoryTree.reduce((sum, cat) => sum + (cat.count || 0), 0)
);
const selectedParent = computed(() => {
    const id = Number(filterCategory.value);
    return props.categoryTree.find((cat) => Number(cat.id) === id)
        || props.categoryTree.find((cat) => (cat.children || []).some((child) => Number(child.id) === id))
        || null;
});

const search = ref(props.filters.q ?? "");
const filterCategory = ref(props.filters.category_id ?? "");
const filterStatus = ref(props.filters.status ?? "");
const reportPeriod = ref(props.report.period ?? "monthly");

watch(
    () => props.filters,
    (value) => {
        search.value = value?.q ?? "";
        filterCategory.value = value?.category_id ?? "";
        filterStatus.value = value?.status ?? "";
    },
    { deep: true }
);

function currentParams(extra = {}) {
    return {
        q: search.value || undefined,
        category_id: filterCategory.value || undefined,
        status: filterStatus.value || undefined,
        report_period: reportPeriod.value || undefined,
        ...extra,
    };
}

function applyFilters() {
    router.get("/documents", currentParams(), { preserveState: true, preserveScroll: true, replace: true });
}

function selectCategory(id) {
    filterCategory.value = id;
    applyFilters();
}

function changeReportPeriod(period) {
    reportPeriod.value = period;
    applyFilters();
}

const statusClass = (status) => {
    const map = {
        Approved: "bg-green-100 text-green-800",
        "Under Review": "bg-yellow-100 text-yellow-800",
        Returned: "bg-red-100 text-red-700",
        Disapproved: "bg-rose-100 text-rose-800",
        Archived: "bg-gray-100 text-gray-600",
        Pending: "bg-blue-50 text-[#003366]",
    };
    return map[status] ?? "bg-gray-100 text-gray-600";
};

const priorityClass = (priority) => {
    const map = {
        Urgent: "bg-red-100 text-red-700",
        Priority: "bg-amber-100 text-amber-800",
        Standard: "bg-gray-100 text-gray-600",
    };
    return map[priority] ?? "bg-gray-100 text-gray-600";
};
</script>
