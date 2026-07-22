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
                title="Activity Logs"
                eyebrow="Audit"
                subtitle="Chronological view of system actions for accountability."
            >
                <div class="mb-4 flex flex-wrap gap-3">
                    <input
                        v-model="search"
                        class="soft-input w-full sm:w-72"
                        placeholder="Search user or action"
                    />
                    <select v-model="filterAction" class="soft-select w-full sm:w-52">
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

                <p v-if="filtered.length === 0" class="text-sm text-slate-500 py-4 text-center">
                    No audit logs found.
                </p>
                <div v-else class="space-y-3">
                    <div
                        v-for="log in filtered"
                        :key="log.id"
                        class="card-shell flex flex-wrap items-center justify-between gap-4 px-4 py-3"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ log.action }}</p>
                            <p class="text-xs text-slate-500">{{ log.detail }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            <p>{{ log.user }}</p>
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
