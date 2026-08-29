<template>
    <AppLayout>
        <Head title="Request Reviews" />
        <div class="space-y-8 fade-up">

            <PageHeader
                title="Request Reviews"
                kicker="Administration"
                subtitle="Review, process, and update the status of citizen document requests."
            >
                <template #actions>
                    <span class="soft-chip">{{ requests.length }} Total</span>
                </template>
            </PageHeader>

            <!-- Status Tabs -->
            <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-0">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    class="relative px-4 py-2 text-xs font-bold uppercase tracking-widest transition border-b-2 -mb-px"
                    :class="activeTab === tab.value
                        ? 'border-[#003366] text-[#003366]'
                        : 'border-transparent text-gray-500 hover:text-[#003366]'"
                    @click="activeTab = tab.value"
                >
                    {{ tab.label }}
                    <span
                        class="ml-1.5 rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                        :class="activeTab === tab.value ? 'bg-[#003366] text-white' : 'bg-gray-200 text-gray-600'"
                    >
                        {{ tab.value === 'all' ? requests.length : requests.filter(r => r.status === tab.value).length }}
                    </span>
                </button>
            </div>

            <!-- Request List -->
            <div class="space-y-4">
                <p v-if="filtered.length === 0" class="py-8 text-center text-sm text-slate-500">
                    No requests in this category.
                </p>

                <div
                    v-for="req in filtered"
                    :key="req.id"
                    class="card-shell"
                >
                    <!-- Main row -->
                    <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-4">

                        <!-- Left: request info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="font-mono text-xs font-bold text-[#003366] bg-[#003366]/8 px-2 py-0.5">
                                    {{ req.tracking }}
                                </span>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                    :class="statusClass(req.status)"
                                >
                                    {{ statusLabel(req.status) }}
                                </span>
                            </div>
                            <p class="text-sm font-semibold text-slate-900">{{ req.name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ req.type }}
                                <template v-if="req.purpose"> — {{ req.purpose }}</template>
                                &bull; Submitted {{ req.submitted_at }}
                                <template v-if="req.processed_by">
                                    &bull; Processed by {{ req.processed_by }}
                                    <template v-if="req.processed_at"> on {{ req.processed_at }}</template>
                                </template>
                            </p>
                            <div class="flex flex-wrap gap-3 mt-1 text-xs text-slate-400">
                                <span v-if="req.email !== '—'">{{ req.email }}</span>
                                <span v-if="req.phone !== '—'">{{ req.phone }}</span>
                            </div>
                        </div>

                        <!-- Right: action buttons -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <!-- Expand details toggle -->
                            <button
                                class="soft-button-light px-3 py-1.5 text-xs"
                                @click="toggleExpand(req.id)"
                            >
                                {{ expandedId === req.id ? 'Hide Details' : 'View Details' }}
                            </button>

                            <!-- pending actions -->
                            <template v-if="req.status === 'pending'">
                                <button
                                    class="soft-button px-3 py-1.5 text-xs"
                                    :disabled="processing === req.id + '-under_review'"
                                    @click="updateStatus(req, 'under_review')"
                                >
                                    {{ processing === req.id + '-under_review' ? '…' : 'Mark Under Review' }}
                                </button>
                                <button
                                    class="px-3 py-1.5 border border-red-300 bg-white text-red-700 text-xs font-semibold hover:bg-red-50 transition"
                                    :disabled="processing === req.id + '-rejected'"
                                    @click="updateStatus(req, 'rejected')"
                                >
                                    {{ processing === req.id + '-rejected' ? '…' : 'Disapprove' }}
                                </button>
                            </template>

                            <!-- under_review actions -->
                            <template v-else-if="req.status === 'under_review'">
                                <button
                                    class="soft-button px-3 py-1.5 text-xs"
                                    @click="openCompleteModal(req)"
                                >
                                    Mark Completed
                                </button>
                                <button
                                    class="px-3 py-1.5 border border-red-300 bg-white text-red-700 text-xs font-semibold hover:bg-red-50 transition"
                                    :disabled="processing === req.id + '-rejected'"
                                    @click="updateStatus(req, 'rejected')"
                                >
                                    {{ processing === req.id + '-rejected' ? '…' : 'Disapprove' }}
                                </button>
                            </template>

                            <!-- completed / rejected — reopen -->
                            <template v-else>
                                <button
                                    class="soft-button-light px-3 py-1.5 text-xs"
                                    :disabled="processing === req.id + '-under_review'"
                                    @click="updateStatus(req, 'under_review')"
                                >
                                    {{ processing === req.id + '-under_review' ? '…' : 'Reopen' }}
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Expandable details -->
                    <Transition name="expand">
                        <div
                            v-if="expandedId === req.id"
                            class="border-t border-gray-200 bg-gray-50 px-5 py-4"
                        >
                            <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-1">Request Details</p>
                            <p v-if="req.details" class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ req.details }}</p>
                            <p v-else class="text-sm text-gray-400 italic">No additional details provided.</p>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Complete request modal -->
            <Teleport to="body">
                <div
                    v-if="completeTarget"
                    class="fixed inset-0 z-[100] flex items-center justify-center px-4"
                    @click.self="closeCompleteModal"
                >
                    <div class="w-full max-w-lg bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-xl max-h-[90vh] overflow-y-auto">
                        <div class="border-b border-gray-200 px-5 py-4">
                            <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Complete Request</p>
                            <h3 class="text-lg font-bold text-[#003366]">{{ completeTarget.tracking }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ completeTarget.name }}</p>
                        </div>

                        <form class="px-5 py-4 space-y-4" @submit.prevent="submitComplete">
                            <div class="rounded-md bg-[#003366]/5 border border-[#003366]/20 px-3 py-2 text-xs text-gray-700">
                            <template v-if="isCertificateRequest">
                                A Certificate of Appearance will be generated automatically and emailed to
                                <span class="font-semibold">{{ completeTarget.email }}</span>.
                                The requester can also download it from the status page.
                            </template>
                                <template v-else>
                                    Upload the requested document. It will be emailed to
                                    <span class="font-semibold">{{ completeTarget.email }}</span>
                                    and made available for download on the status page.
                                </template>
                            </div>

                            <div v-if="isCertificateRequest" class="space-y-3">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                            Signatory Name
                                        </label>
                                        <input v-model="completeForm.signing_name" type="text" class="soft-input" required />
                                        <p v-if="completeForm.errors.signing_name" class="mt-1 text-xs text-red-600">
                                            {{ completeForm.errors.signing_name }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                            Title
                                        </label>
                                        <input v-model="completeForm.signing_title" type="text" class="soft-input" required />
                                        <p v-if="completeForm.errors.signing_title" class="mt-1 text-xs text-red-600">
                                            {{ completeForm.errors.signing_title }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">E-Signature</p>
                                    <SignaturePad
                                        ref="pad"
                                        :existing-url="signer.signature_url"
                                        @update:model-value="completeForm.signature = $event"
                                    />
                                    <p class="mt-1 text-[11px] text-gray-500">
                                        Draw or upload your signature. It is copied onto this certificate. Changing it later will not rewrite certificates already issued.
                                    </p>
                                    <p v-if="completeForm.errors.signature" class="mt-1 text-xs text-red-600">
                                        {{ completeForm.errors.signature }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="!isCertificateRequest">
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                    Response File <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="file"
                                    accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                                    class="block w-full text-sm text-gray-700 file:mr-3 file:border file:border-gray-300 file:bg-gray-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-[#003366]"
                                    @change="onFileChange"
                                />
                                <p v-if="completeForm.errors.response_file" class="mt-1 text-xs text-red-600">
                                    {{ completeForm.errors.response_file }}
                                </p>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    class="soft-button-light px-4 py-2 text-xs"
                                    @click="closeCompleteModal"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="soft-button px-4 py-2 text-xs"
                                    :disabled="completeForm.processing || (!isCertificateRequest && !completeForm.response_file)"
                                >
                                    {{
                                        completeForm.processing
                                            ? 'Processing…'
                                        : isCertificateRequest
                                          ? 'Complete & Send Certificate'
                                          : 'Complete & Send Email'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SignaturePad from "../../Components/SignaturePad.vue";

const props = defineProps({
    requests: { type: Array, default: () => [] },
    signer: {
        type: Object,
        default: () => ({
            signing_name: "",
            signing_title: "",
            has_signature: false,
            signature_url: null,
        }),
    },
});

const tabs = [
    { label: 'All',          value: 'all' },
    { label: 'Pending',      value: 'pending' },
    { label: 'Under Review', value: 'under_review' },
    { label: 'Completed',    value: 'completed' },
    { label: 'Disapproved',  value: 'rejected' },
];

const activeTab  = ref('all');
const expandedId = ref(null);
const processing = ref(null);
const completeTarget = ref(null);
const pad = ref(null);

const completeForm = useForm({
    response_file: null,
    signing_name: props.signer.signing_name,
    signing_title: props.signer.signing_title,
    signature: "",
});

const filtered = computed(() =>
    activeTab.value === 'all'
        ? props.requests
        : props.requests.filter(r => r.status === activeTab.value)
);

const isCertificateRequest = computed(
    () => Boolean(completeTarget.value?.issues_certificate)
);

function toggleExpand(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

function openCompleteModal(req) {
    if (req.email === '—') {
        alert('This request has no email address. The requester must provide an email before it can be completed.');
        return;
    }

    completeTarget.value = req;
    completeForm.reset();
    completeForm.signing_name = props.signer.signing_name;
    completeForm.signing_title = props.signer.signing_title;
    completeForm.signature = "";
    completeForm.clearErrors();
}

function closeCompleteModal() {
    completeTarget.value = null;
    completeForm.reset();
    completeForm.clearErrors();
}

function onFileChange(event) {
    completeForm.response_file = event.target.files?.[0] ?? null;
}

function submitComplete() {
    if (!completeTarget.value) {
        return;
    }

    if (!isCertificateRequest.value && !completeForm.response_file) {
        return;
    }

    if (isCertificateRequest.value && pad.value?.isDirty?.()) {
        completeForm.signature = pad.value.toDataUrl();
    }

    completeForm.post(`/requests/${completeTarget.value.id}/complete`, {
        forceFormData: !isCertificateRequest.value,
        preserveScroll: true,
        onSuccess: () => closeCompleteModal(),
    });
}

function updateStatus(req, status) {
    if (status === 'rejected') {
        if (!confirm(`Disapprove request ${req.tracking} from ${req.name}? This cannot be undone easily.`)) return;
    }
    processing.value = req.id + '-' + status;
    router.put(`/requests/${req.id}/status`, { status }, {
        preserveScroll: true,
        onFinish: () => { processing.value = null; },
    });
}

const STATUS_MAP = {
    pending:      { label: 'Pending',      cls: 'bg-amber-100 text-amber-800' },
    under_review: { label: 'Under Review', cls: 'bg-blue-100 text-blue-800' },
    completed:    { label: 'Completed',    cls: 'bg-emerald-100 text-emerald-800' },
    rejected:     { label: 'Disapproved',  cls: 'bg-red-100 text-red-800' },
};

function statusLabel(s) { return STATUS_MAP[s]?.label ?? s; }
function statusClass(s) { return STATUS_MAP[s]?.cls  ?? 'bg-slate-100 text-slate-700'; }
</script>

<style scoped>
.expand-enter-active,
.expand-leave-active {
    transition: opacity 0.2s ease, max-height 0.25s ease;
    max-height: 400px;
    overflow: hidden;
}
.expand-enter-from,
.expand-leave-to {
    opacity: 0;
    max-height: 0;
}
</style>
