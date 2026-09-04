<template>
    <AppLayout>
        <Head :title="document.title" />
        <div class="space-y-6 fade-up">
            <PageHeader
                :title="document.title"
                kicker="Documents"
                :subtitle="`${document.tracking} · ${document.category}`"
            >
                <template #actions>
                    <Link href="/workflow" class="soft-button-light">Back to Workflow</Link>
                    <Link href="/documents" class="soft-button-light">Back to List</Link>
                    <a
                        v-if="download_url"
                        :href="download_url"
                        class="soft-button inline-flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        Download
                    </a>
                </template>
            </PageHeader>

            <SectionCard
                title="Document Details"
                eyebrow="Overview"
                subtitle="Submission metadata and attached file preview."
            >
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm border-b border-gray-100 pb-5 mb-5">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Tracking No.</p>
                        <p class="font-bold text-[#003366]">{{ document.tracking }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Reference No.</p>
                        <p class="text-gray-900">{{ document.reference }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Status</p>
                        <span
                            class="inline-block px-2 py-0.5 text-xs font-semibold uppercase tracking-wide"
                            :class="statusClass(document.status)"
                        >
                            {{ document.status }}
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Priority</p>
                        <span
                            class="inline-block text-xs font-bold uppercase tracking-wider px-2 py-0.5"
                            :class="priorityClass(document.priority)"
                        >
                            {{ document.priority }}
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Submitted By</p>
                        <p class="text-gray-900">{{ document.owner }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Currently Handling</p>
                        <p class="font-semibold text-[#003366]">{{ document.handler?.name ?? 'Unassigned' }}</p>
                        <p v-if="document.handler" class="text-xs text-gray-500">{{ document.handler.position }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Access</p>
                        <p class="text-gray-900">
                            {{ document.access?.restricted ? 'Selected staff only' : 'All office staff' }}
                        </p>
                        <p v-if="document.access?.restricted" class="text-xs text-gray-500">
                            {{ document.access.users.map((user) => user.name).join(', ') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Submitted On</p>
                        <p class="text-gray-900">{{ document.submitted }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Retention Period</p>
                        <p class="text-gray-900">{{ document.retention }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Expires On</p>
                        <p class="text-gray-900">{{ document.expires_at }}</p>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Description</p>
                        <p class="text-gray-700">{{ document.description }}</p>
                    </div>
                </div>

                <div v-if="preview_url">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-3">File Preview</p>
                    <div class="relative border border-gray-300 bg-gray-100" style="min-height: 520px;">
                        <div
                            v-if="loading"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-gray-400"
                        >
                            <svg class="h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <span class="text-sm">Loading preview…</span>
                        </div>
                        <iframe
                            :src="preview_url"
                            class="w-full border-0 transition-opacity duration-300"
                            :class="loading ? 'opacity-0' : 'opacity-100'"
                            style="height: 520px;"
                            title="Document Preview"
                            @load="loading = false"
                        ></iframe>
                    </div>
                    <p class="mt-2 text-xs text-gray-400 text-center">
                        If the preview does not load,
                        <a :href="preview_url" target="_blank" class="text-[#003366] underline font-semibold">
                            open in a new tab
                        </a>.
                    </p>
                </div>

                <div v-else-if="document.has_file" class="rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    This file type cannot be previewed in the browser. Use the Download button to open it locally.
                </div>

                <div v-else class="rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                    No file was attached to this document.
                </div>
            </SectionCard>

            <SectionCard
                title="Handling trail"
                eyebrow="Trace"
                subtitle="Who moved this document and when."
            >
                <ol v-if="document.trail?.length" class="space-y-3">
                    <li
                        v-for="step in document.trail"
                        :key="step.id"
                        class="border border-gray-200 bg-white px-4 py-3"
                    >
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold text-[#003366]">{{ step.status }}</p>
                            <p class="text-[11px] text-gray-400">{{ step.at }}</p>
                        </div>
                        <p class="mt-1 text-xs text-gray-600">
                            Handled by <span class="font-semibold text-gray-800">{{ step.by }}</span>
                        </p>
                        <p v-if="step.remarks" class="mt-1 text-sm text-gray-700">{{ step.remarks }}</p>
                    </li>
                </ol>
                <p v-else class="text-sm text-gray-500">
                    No workflow steps yet. Activity appears here as staff start review, forward, approve, or return the file.
                </p>
            </SectionCard>

            <SectionCard
                v-if="document.access?.can_edit"
                title="Access & handler"
                eyebrow="Sharing"
                subtitle="Choose who may open this file and who currently has it."
            >
                <form class="space-y-5" @submit.prevent="saveSharing">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Who can access this document
                        </label>
                        <p class="mb-2 text-xs text-gray-500">
                            Leave empty so all office staff can open it. The owner always keeps access.
                        </p>
                        <StaffChecklist v-model="sharing.access_user_ids" :options="staffOptions" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="handled_by">
                            Currently handling
                        </label>
                        <select id="handled_by" v-model="sharing.handled_by" class="soft-select">
                            <option value="">Unassigned</option>
                            <option v-for="person in staff" :key="person.id" :value="person.id">
                                {{ person.label }}
                            </option>
                        </select>
                    </div>
                    <button type="submit" class="soft-button" :disabled="sharing.processing">
                        {{ sharing.processing ? 'Saving…' : 'Save access & handler' }}
                    </button>
                </form>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import StaffChecklist from "../../Components/StaffChecklist.vue";

const props = defineProps({
    document:     { type: Object, required: true },
    preview_url:  { type: String, default: null },
    download_url: { type: String, default: null },
    staff:        { type: Array, default: () => [] },
});

const loading = ref(true);

const staffOptions = computed(() =>
    props.staff.filter((person) => Number(person.id) !== Number(props.document.owner_id))
);

const sharing = useForm({
    access_user_ids: [],
    handled_by: "",
});

function hydrateSharing() {
    const ownerId = Number(props.document.owner_id);
    sharing.access_user_ids = (props.document.access?.user_ids ?? [])
        .map((id) => Number(id))
        .filter((id) => id !== ownerId);
    sharing.handled_by = props.document.handler?.id ?? "";
}

watch(
    () => [props.document.access, props.document.handler],
    hydrateSharing,
    { immediate: true, deep: true }
);

function saveSharing() {
    sharing
        .transform((data) => ({
            access_user_ids: data.access_user_ids || [],
            handled_by: data.handled_by || null,
        }))
        .put(`/documents/${props.document.id}/sharing`, { preserveScroll: true });
}

const statusClass = (status) => {
    const map = {
        Approved: "bg-green-100 text-green-800",
        "Under Review": "bg-yellow-100 text-yellow-800",
        Returned: "bg-red-100 text-red-700",
        Disapproved: "bg-rose-100 text-rose-800",
        Rejected: "bg-rose-100 text-rose-800",
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
