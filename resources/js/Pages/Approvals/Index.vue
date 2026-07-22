<template>
    <AppLayout>
        <Head title="Review & Approval" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Review & Approval"
                kicker="Administration"
                subtitle="Evaluate submissions, provide remarks, and finalize document decisions."
            >
                <template #actions>
                    <button class="soft-button-light">Approval Guidelines</button>
                </template>
            </PageHeader>

            <SectionCard
                title="Pending Review Queue"
                eyebrow="Action Required"
                subtitle="Documents awaiting administrative approval or return."
            >
                <p v-if="documents.length === 0" class="text-sm text-slate-500 py-4 text-center">
                    No documents pending review.
                </p>
                <div v-else class="space-y-4">
                    <div
                        v-for="item in documents"
                        :key="item.id"
                        class="card-shell flex flex-wrap items-center justify-between gap-4 px-4 py-4"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
                            <p class="text-xs text-slate-500">
                                {{ item.tracking }} &bull; {{ item.category }} &bull; Submitted by {{ item.owner }}
                            </p>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="priorityClass(item.priority)"
                                >
                                    {{ item.priority }}
                                </span>
                                <span class="text-xs text-slate-400">{{ item.age }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                class="soft-button w-full sm:w-auto"
                                :disabled="processing === item.id + '-approve'"
                                @click="approve(item)"
                            >
                                {{ processing === item.id + '-approve' ? '…' : 'Approve' }}
                            </button>
                            <button
                                class="soft-button-light w-full sm:w-auto"
                                :disabled="processing === item.id + '-return'"
                                @click="returnDoc(item)"
                            >
                                {{ processing === item.id + '-return' ? '…' : 'Return with Remarks' }}
                            </button>
                            <button
                                class="w-full sm:w-auto px-4 py-2 border border-red-300 text-red-700 text-xs font-semibold uppercase tracking-wider bg-white hover:bg-red-50 transition"
                                :disabled="processing === item.id + '-reject'"
                                @click="reject(item)"
                            >
                                {{ processing === item.id + '-reject' ? '…' : 'Reject' }}
                            </button>
                        </div>
                    </div>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

defineProps({
    documents: { type: Array, default: () => [] },
});

const processing = ref(null);

function approve(item) {
    processing.value = item.id + '-approve';
    router.put(`/approvals/${item.id}/approve`, {}, {
        onFinish: () => { processing.value = null; },
    });
}

function returnDoc(item) {
    processing.value = item.id + '-return';
    router.put(`/approvals/${item.id}/return`, {}, {
        onFinish: () => { processing.value = null; },
    });
}

function reject(item) {
    if (!confirm(`Reject "${item.title}"? This action cannot be undone.`)) return;
    processing.value = item.id + '-reject';
    router.put(`/approvals/${item.id}/reject`, {}, {
        onFinish: () => { processing.value = null; },
    });
}

function priorityClass(priority) {
    if (priority === 'Urgent') return 'bg-red-100 text-red-700';
    if (priority === 'High')   return 'bg-orange-100 text-orange-700';
    return 'bg-blue-50 text-blue-700';
}
</script>
