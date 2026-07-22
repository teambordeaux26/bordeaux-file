<template>
    <AppLayout>
        <Head title="Document Tracking" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Document Tracking"
                kicker="Tracking"
                subtitle="Monitor progress and movement of documents across approval stages."
            >
                <template #actions>
                    <button class="soft-button">
                        Generate Tracking Report
                    </button>
                </template>
            </PageHeader>

            <SectionCard
                title="Active Tracking"
                eyebrow="Status Board"
                subtitle="Track documents from submission to archive with real-time statuses."
            >
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        class="soft-input !w-auto min-w-[12rem] flex-1 max-w-xs"
                        placeholder="Search tracking number or title"
                    />
                    <select
                        v-model="filterStatus"
                        class="soft-select !w-56 shrink-0"
                    >
                        <option value="">All statuses</option>
                        <option>Pending</option>
                        <option>Under Review</option>
                        <option>Approved</option>
                        <option>Returned</option>
                    </select>
                </div>

                <p
                    v-if="filtered.length === 0"
                    class="text-sm text-slate-500 py-4 text-center"
                >
                    No documents match your filters.
                </p>
                <div v-else class="space-y-3">
                    <div
                        v-for="item in filtered"
                        :key="item.id"
                        class="card-shell flex flex-wrap items-center justify-between gap-4 px-4 py-3"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                {{ item.title }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ item.tracking }} &bull; {{ item.owner }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="statusClass(item.status)"
                            >
                                {{ item.status }}
                            </span>
                            <span class="text-xs text-slate-500">{{
                                item.updated
                            }}</span>
                        </div>
                    </div>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

const props = defineProps({
    trackingItems: { type: Array, default: () => [] },
});

const search = ref("");
const filterStatus = ref("");

const filtered = computed(() => {
    return props.trackingItems.filter((item) => {
        const matchSearch =
            !search.value ||
            item.title.toLowerCase().includes(search.value.toLowerCase()) ||
            item.tracking.toLowerCase().includes(search.value.toLowerCase());
        const matchStatus =
            !filterStatus.value || item.status === filterStatus.value;
        return matchSearch && matchStatus;
    });
});

function statusClass(status) {
    if (status === "Approved") return "bg-emerald-100 text-emerald-800";
    if (status === "Under Review") return "bg-amber-100 text-amber-800";
    if (status === "Returned") return "bg-rose-100 text-rose-700";
    return "bg-slate-100 text-slate-700";
}
</script>
