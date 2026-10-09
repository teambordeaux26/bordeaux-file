<template>
    <AppLayout>
        <Head title="Audit Trail" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Audit Trail"
                kicker="Administration"
                subtitle="Exact dates and times for logins, document changes, and office activity."
            />

            <SectionCard
                title="Filter Logs"
                eyebrow="Lookup"
                subtitle="Filter by keyword, action, document category, or date."
            >
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[12rem] flex-1 max-w-md">
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Search</label>
                        <input
                            v-model="search"
                            class="soft-input"
                            placeholder="User, action, or detail"
                            @input="scheduleSearch"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Action</label>
                        <select v-model="filterAction" class="soft-select !w-56" @change="applyFilters">
                            <option value="">All actions</option>
                            <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Category</label>
                        <select v-model="filterCategory" class="soft-select !w-64" @change="applyFilters">
                            <option value="">All categories</option>
                            <template v-for="cat in categories" :key="cat.id">
                                <option :value="String(cat.id)">{{ cat.name }}</option>
                                <option v-for="child in cat.children" :key="child.id" :value="String(child.id)">
                                    — {{ child.name }}
                                </option>
                            </template>
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="soft-button-light text-xs" @click="applyPreset('week')">This Week</button>
                        <button type="button" class="soft-button-light text-xs" @click="applyPreset('month')">This Month</button>
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">From</label>
                        <input v-model="filterFrom" type="date" class="soft-input" @change="applyFilters()" />
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">To</label>
                        <input v-model="filterTo" type="date" class="soft-input" @change="applyFilters()" />
                    </div>
                    <button
                        v-if="hasFilters"
                        type="button"
                        class="text-xs font-semibold text-gray-500 hover:text-[#003366]"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </SectionCard>

            <SectionCard
                title="Activity Logs"
                eyebrow="Audit"
                subtitle="Each entry shows the exact date and time it was recorded."
            >
                <p v-if="rows.length === 0" class="text-sm text-slate-500 py-4 text-center">
                    No audit logs found.
                </p>
                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="log in rows"
                        :key="log.id"
                        class="flex flex-wrap items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-[#003366]">{{ log.action }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ log.detail }}</p>
                        </div>
                        <div class="shrink-0 text-right text-xs text-slate-500">
                            <p class="font-medium text-slate-600">{{ log.user }}</p>
                            <p>{{ log.time }}</p>
                        </div>
                    </div>
                </div>
                <Pagination :paginator="logs" />
            </SectionCard>

            <ReportPanel
                title="Audit Reports"
                subtitle="Choose a period, then export the audit trail."
                :period="report.period"
                :range="reportRange"
                :report="report"
                export-base="/audit/reports/export"
                @generate="applyReport"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import Pagination from "../../Components/Pagination.vue";
import ReportPanel from "../../Components/ReportPanel.vue";

const props = defineProps({
    logs: { type: Object, default: () => ({ data: [] }) },
    actions: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    report: { type: Object, default: () => ({ stats: [], breakdown: [], period: "" }) },
    reportRange: { type: String, default: "" },
});

const rows = computed(() => props.logs?.data ?? []);
const search = ref(props.filters.q ?? "");
const filterAction = ref(props.filters.action ?? "");
const filterCategory = ref(props.filters.category_id ?? "");
const filterFrom = ref(props.filters.from ?? "");
const filterTo = ref(props.filters.to ?? "");
const preset = ref(props.filters.preset ?? "");

const hasFilters = computed(() =>
    Boolean(search.value || filterAction.value || filterCategory.value || filterFrom.value || filterTo.value || preset.value)
);

watch(() => props.filters, (value) => {
    search.value = value?.q ?? "";
    filterAction.value = value?.action ?? "";
    filterCategory.value = value?.category_id ?? "";
    filterFrom.value = value?.from ?? "";
    filterTo.value = value?.to ?? "";
    preset.value = value?.preset ?? "";
}, { deep: true });

let timer = null;

function scheduleSearch() {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => applyFilters(), 300);
}

function applyFilters(nextPreset = preset.value) {
    preset.value = nextPreset || "";
    router.get("/audit", {
        q: search.value || undefined,
        action: filterAction.value || undefined,
        category_id: filterCategory.value || undefined,
        from: nextPreset ? undefined : (filterFrom.value || undefined),
        to: nextPreset ? undefined : (filterTo.value || undefined),
        preset: nextPreset || undefined,
        report_period: props.report.period || undefined,
        report_from: props.report.from || undefined,
        report_to: props.report.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function applyPreset(value) {
    filterFrom.value = "";
    filterTo.value = "";
    applyFilters(value);
}

function clearFilters() {
    search.value = "";
    filterAction.value = "";
    filterCategory.value = "";
    filterFrom.value = "";
    filterTo.value = "";
    applyFilters("");
}

function applyReport({ period, from, to }) {
    router.get("/audit", {
        q: search.value || undefined,
        action: filterAction.value || undefined,
        category_id: filterCategory.value || undefined,
        from: filterFrom.value || undefined,
        to: filterTo.value || undefined,
        preset: preset.value || undefined,
        report_period: period,
        report_from: from,
        report_to: to,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

onBeforeUnmount(() => {
    if (timer) clearTimeout(timer);
});
</script>
