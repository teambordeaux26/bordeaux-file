<template>
    <SectionCard
        :title="title"
        eyebrow="Reports"
        :subtitle="subtitle"
    >
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <label class="text-xs font-bold uppercase tracking-widest text-gray-500">Period</label>
                <select :value="period" class="soft-select !w-40" @change="onPeriod">
                    <option value="weekly">This week</option>
                    <option value="monthly">This month</option>
                </select>
                <p class="text-xs text-gray-500">{{ range }}</p>
            </div>

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
                <a :href="csvUrl" class="soft-button text-xs">Export CSV</a>
                <a :href="pdfUrl" class="soft-button-light text-xs">Export PDF</a>
            </div>
        </div>
    </SectionCard>
</template>

<script setup>
import { computed } from "vue";
import SectionCard from "./SectionCard.vue";

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: "" },
    period: { type: String, default: "monthly" },
    range: { type: String, default: "" },
    report: { type: Object, default: () => ({ stats: [], breakdown: [] }) },
    exportBase: { type: String, required: true },
});

const emit = defineEmits(["update:period"]);

const csvUrl = computed(
    () => `${props.exportBase}?period=${props.period}&format=csv`
);
const pdfUrl = computed(
    () => `${props.exportBase}?period=${props.period}&format=pdf`
);

function onPeriod(event) {
    emit("update:period", event.target.value);
}
</script>
