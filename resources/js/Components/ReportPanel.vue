<template>
    <SectionCard
        :title="title"
        eyebrow="Reports"
        :subtitle="subtitle"
    >
        <div class="flex flex-col gap-4">
            <div class="grid gap-3 lg:grid-cols-[12rem_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-500">Period</label>
                    <select v-model="draft.period" class="soft-select" @change="onPeriodChange">
                        <option value="weekly">This week</option>
                        <option value="monthly">This month</option>
                        <option value="custom">Custom dates</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-500">From</label>
                    <input v-model="draft.from" type="date" class="soft-input" @change="onDateChange">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-500">To</label>
                    <input v-model="draft.to" type="date" class="soft-input" @change="onDateChange">
                </div>
                <button
                    type="button"
                    class="soft-button text-xs disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!canGenerate"
                    @click="generate"
                >
                    Generate report
                </button>
            </div>

            <p v-if="dateError" class="text-xs font-semibold text-red-700">{{ dateError }}</p>
            <p v-else-if="isDirty" class="text-xs text-gray-500">
                Choose From and To dates, then generate the report before exporting.
            </p>
            <p v-else class="text-xs text-gray-500">{{ range || appliedLabel }}</p>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="stat in report.stats"
                    :key="stat.label"
                    class="border border-gray-200 bg-gray-50 px-3 py-3"
                >
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">{{ stat.label }}</p>
                    <p class="mt-1 text-2xl font-bold text-[#003366]">{{ stat.value }}</p>
                </div>
            </div>

            <div v-if="report.breakdown?.length" class="border border-gray-200">
                <div class="bg-gray-50 px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                    Breakdown
                </div>
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="row in report.breakdown"
                        :key="row.label"
                        class="flex items-center justify-between px-3 py-2 text-sm"
                    >
                        <span class="text-gray-700">{{ row.label }}</span>
                        <span class="font-semibold text-[#003366]">{{ row.value }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    v-if="canExport"
                    :href="csvUrl"
                    class="soft-button text-xs"
                >Export CSV</a>
                <button
                    v-else
                    type="button"
                    class="soft-button text-xs opacity-50"
                    disabled
                >Export CSV</button>
                <a
                    v-if="canExport"
                    :href="pdfUrl"
                    class="soft-button-light text-xs"
                >Export PDF</a>
                <button
                    v-else
                    type="button"
                    class="soft-button-light text-xs opacity-50"
                    disabled
                >Export PDF</button>
            </div>
        </div>
    </SectionCard>
</template>

<script setup>
import { computed, reactive, watch } from "vue";
import SectionCard from "./SectionCard.vue";

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: "" },
    period: { type: String, default: "monthly" },
    range: { type: String, default: "" },
    report: { type: Object, default: () => ({ stats: [], breakdown: [], period: "monthly" }) },
    exportBase: { type: String, required: true },
});

const emit = defineEmits(["generate"]);

const draft = reactive({
    period: "monthly",
    from: "",
    to: "",
});

const appliedPeriod = computed(() => props.report.period || props.period || "monthly");
const appliedFrom = computed(() => props.report.from || "");
const appliedTo = computed(() => props.report.to || "");
const appliedLabel = computed(() => props.report.label || props.range || "");

const dateError = computed(() => {
    if (draft.from && draft.to && draft.from > draft.to) {
        return "The From date must be on or before the To date.";
    }

    return "";
});

const canGenerate = computed(() => Boolean(draft.from && draft.to) && dateError.value === "");

const isDirty = computed(() => (
    draft.period !== appliedPeriod.value
    || draft.from !== appliedFrom.value
    || draft.to !== appliedTo.value
));

const canExport = computed(() => Boolean(appliedFrom.value && appliedTo.value) && !isDirty.value);

function exportUrl(format) {
    const params = new URLSearchParams({
        period: appliedPeriod.value,
        from: appliedFrom.value,
        to: appliedTo.value,
        format,
    });

    return `${props.exportBase}?${params.toString()}`;
}

const csvUrl = computed(() => exportUrl("csv"));
const pdfUrl = computed(() => exportUrl("pdf"));

function toIsoDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function startOfWeek(date) {
    const value = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const weekday = value.getDay();
    const diff = weekday === 0 ? -6 : 1 - weekday;
    value.setDate(value.getDate() + diff);

    return value;
}

function applyPresetDates(period) {
    const today = new Date();

    if (period === "weekly") {
        const start = startOfWeek(today);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        draft.from = toIsoDate(start);
        draft.to = toIsoDate(end);
        return;
    }

    const start = new Date(today.getFullYear(), today.getMonth(), 1);
    const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    draft.from = toIsoDate(start);
    draft.to = toIsoDate(end);
}

function syncFromProps() {
    draft.period = appliedPeriod.value;
    draft.from = appliedFrom.value;
    draft.to = appliedTo.value;

    if (!draft.from || !draft.to) {
        applyPresetDates(draft.period === "custom" ? "monthly" : draft.period);
    }
}

function onPeriodChange() {
    if (draft.period === "weekly" || draft.period === "monthly") {
        applyPresetDates(draft.period);
    }
}

function onDateChange() {
    draft.period = "custom";
}

function generate() {
    if (!canGenerate.value) {
        return;
    }

    emit("generate", {
        period: draft.period,
        from: draft.from,
        to: draft.to,
    });
}

syncFromProps();

watch(
    () => [props.report?.period, props.report?.from, props.report?.to, props.period],
    () => {
        syncFromProps();
    }
);
</script>
