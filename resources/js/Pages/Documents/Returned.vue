<template>
    <AppLayout>
        <Head title="Returned Documents" />
        <div class="space-y-6 fade-up">
            <PageHeader
                title="Returned Documents"
                kicker="My Revisions"
                subtitle="Documents sent back to you for revision. Address the remarks and resubmit."
            >
                <template #actions>
                    <Link href="/workflow" class="soft-button-light">Back to Workflow</Link>
                </template>
            </PageHeader>

            <SectionCard
                title="Awaiting Your Revision"
                eyebrow="Action Required"
                :subtitle="documents.length + ' document(s) to address.'"
            >
                <p
                    v-if="documents.length === 0"
                    class="text-sm text-slate-500 py-6 text-center"
                >
                    You have no returned documents. Great work.
                </p>

                <div v-else class="space-y-4">
                    <div
                        v-for="doc in documents"
                        :key="doc.id"
                        class="border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-bold text-slate-900">
                                        {{ doc.title }}
                                    </h3>
                                    <span
                                        v-if="doc.priority"
                                        class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="priorityClass(doc.priority)"
                                    >
                                        {{ doc.priority }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ doc.tracking }} &bull; {{ doc.category }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Owner:
                                    <span class="text-gray-700 font-medium">{{ doc.owner }}</span>
                                    &bull; Returned {{ doc.returned_at }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Link
                                    :href="doc.view_url"
                                    class="soft-button-light text-xs"
                                >
                                    View Details
                                </Link>
                                <Link
                                    :href="doc.edit_url"
                                    class="soft-button text-xs"
                                >
                                    Edit &amp; Resubmit
                                </Link>
                            </div>
                        </div>

                        <div class="mt-3 border-l-4 border-orange-400 bg-orange-50/60 px-3 py-2">
                            <p class="text-[10px] uppercase tracking-widest text-orange-800 font-semibold">
                                Returned from: {{ doc.returned_from }}
                            </p>
                            <p
                                v-if="doc.returned_by"
                                class="text-xs text-gray-600"
                            >
                                By <span class="font-medium">{{ doc.returned_by }}</span>
                            </p>
                            <p
                                v-if="doc.remarks"
                                class="mt-1 text-sm text-gray-800 whitespace-pre-line"
                            >
                                “{{ doc.remarks }}”
                            </p>
                            <p
                                v-else
                                class="mt-1 text-xs text-gray-500 italic"
                            >
                                No remarks were provided.
                            </p>
                        </div>
                    </div>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

defineProps({
    documents: { type: Array, default: () => [] },
});

function priorityClass(priority) {
    if (priority === "Urgent") return "bg-red-100 text-red-700";
    if (priority === "High") return "bg-orange-100 text-orange-700";
    if (priority === "Priority") return "bg-amber-100 text-amber-800";
    if (priority === "Low") return "bg-slate-100 text-slate-600";
    return "bg-blue-50 text-blue-700";
}
</script>
