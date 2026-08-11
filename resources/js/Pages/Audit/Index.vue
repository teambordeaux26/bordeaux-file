<template>
    <AppLayout>
        <Head title="Audit Trail" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Audit Trail"
                kicker="Administration"
                subtitle="Monitor logins, document changes, and system activities."
            />

            <SectionCard
                title="Filter Logs"
                eyebrow="Lookup"
                subtitle="Narrow results by user, action, or keyword."
            >
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        class="soft-input !w-auto min-w-[12rem] flex-1 max-w-md"
                        placeholder="Search user or action"
                    />
                    <select v-model="filterAction" class="soft-select !w-56 shrink-0">
                        <option value="">All actions</option>
                        <option>Login</option>
                        <option>Document Approved</option>
                        <option>Document Returned</option>
                        <option>Document Rejected</option>
                        <option>Document Restored</option>
                        <option>Visitor Logged</option>
                        <option>Certificate Generated</option>
                    </select>
                </div>
            </SectionCard>

            <SectionCard
                title="Activity Logs"
                eyebrow="Audit"
                subtitle="Chronological view of system actions for accountability."
            >
                <p v-if="filtered.length === 0" class="text-sm text-slate-500 py-4 text-center">
                    No audit logs found.
                </p>
                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="log in filtered"
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
    logs: { type: Array, default: () => [] },
});

const search       = ref('');
const filterAction = ref('');

const filtered = computed(() =>
    props.logs.filter(log => {
        const matchSearch = !search.value
            || log.action.toLowerCase().includes(search.value.toLowerCase())
            || log.user.toLowerCase().includes(search.value.toLowerCase())
            || (log.detail ?? '').toLowerCase().includes(search.value.toLowerCase());
        const matchAction = !filterAction.value || log.action === filterAction.value;
        return matchSearch && matchAction;
    })
);
</script>
