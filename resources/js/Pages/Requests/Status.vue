<template>
    <GuestLayout>
        <Head title="Request Status" />
        <div class="space-y-8">
            <div class="border-l-4 border-[#003366] pl-4">
                <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Guest Services</p>
                <h1 class="text-2xl font-bold text-[#003366]">Request Status</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Check the progress of your submitted request using your tracking number.
                </p>
            </div>

            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm p-4 sm:p-6">
                <div class="border-b border-gray-200 pb-3 mb-6">
                    <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Tracking</p>
                    <h2 class="text-lg font-bold text-[#003366]">Status Lookup</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Enter your tracking number to view updates.</p>
                </div>

                <form @submit.prevent="check" class="flex flex-col gap-3 sm:flex-row">
                    <input
                        v-model="trackingInput"
                        class="flex-1 border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                        placeholder="e.g. REQ-2026-0001"
                    />
                    <button
                        type="submit"
                        class="w-full sm:w-auto px-6 py-2.5 bg-[#003366] text-white text-sm font-bold hover:bg-[#002244] transition"
                    >
                        Check Status
                    </button>
                </form>
            </div>

            <div
                v-if="result"
                class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm p-4 sm:p-6"
            >
                <div class="border-b border-gray-200 pb-3 mb-5">
                    <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Found</p>
                    <h2 class="text-lg font-bold text-[#003366]">Request Details</h2>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 bg-[#003366]/5 border border-[#003366]/20 px-4 py-3 mb-5">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Tracking Number</p>
                        <p class="text-xl font-bold text-[#003366] font-mono">{{ result.tracking_number }}</p>
                    </div>
                    <span
                        class="rounded-full px-4 py-1 text-xs font-bold uppercase tracking-wider"
                        :class="statusClass(result.status)"
                    >
                        {{ statusLabel(result.status) }}
                    </span>
                </div>

                <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2 text-sm">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Requester</p>
                        <p class="text-gray-900 font-medium">{{ result.requester_name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Request Type</p>
                        <p class="text-gray-900 font-medium">{{ result.request_type }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Date Submitted</p>
                        <p class="text-gray-900">{{ result.submitted_at }}</p>
                    </div>
                    <div v-if="result.processed_at">
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Date Processed</p>
                        <p class="text-gray-900">{{ result.processed_at }}</p>
                    </div>
                    <div v-if="result.details" class="sm:col-span-2">
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Details</p>
                        <p class="text-gray-700 leading-relaxed">{{ result.details }}</p>
                    </div>
                </div>

                <div
                    v-if="result.status === 'completed'"
                    class="mt-5 pt-4 border-t border-[#003366]/20 bg-[#003366]/5 -mx-4 sm:-mx-5 px-4 sm:px-5 pb-4 space-y-4"
                >
                    <div>
                        <p class="text-sm font-bold text-[#003366]">Your request has been completed.</p>
                        <p v-if="result.has_certificate" class="text-xs text-gray-600 mt-0.5">
                            Your Certificate of Appearance has been generated. Download it below or check the copy sent to your email.
                        </p>
                        <p v-else-if="result.has_file" class="text-xs text-gray-600 mt-0.5">
                            Your requested document is ready. Download it below or check the copy sent to your email.
                        </p>
                        <p v-else-if="result.request_type === 'Certificate of Appearance'" class="text-xs text-gray-600 mt-0.5">
                            You may register your visit and generate your
                            <strong>Certificate of Appearance</strong>.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            v-if="result.download_url"
                            :href="result.download_url"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#003366] text-white text-sm font-bold hover:bg-[#002244] transition shrink-0"
                        >
                            {{ result.has_certificate ? 'Download Certificate' : 'Download Document' }}
                        </a>
                        <Link
                            v-if="result.certificate_url"
                            :href="result.certificate_url"
                            class="inline-flex items-center gap-2 px-5 py-2.5 border border-[#003366] text-[#003366] text-sm font-bold hover:bg-[#003366] hover:text-white transition shrink-0"
                        >
                            View Certificate
                        </Link>
                        <Link
                            v-else-if="result.request_type === 'Certificate of Appearance'"
                            :href="`/requests/${result.id}/certificate`"
                            class="inline-flex items-center gap-2 px-5 py-2.5 border border-[#003366] text-[#003366] text-sm font-bold hover:bg-[#003366] hover:text-white transition shrink-0"
                        >
                            Get Certificate of Appearance
                        </Link>
                    </div>
                </div>

                <div v-else class="mt-5 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500">
                        For further inquiries, please visit the Office of the Vice Mayor or call
                        <span class="font-semibold text-gray-700">{{ site.hotline || '(052) 555-0198' }}</span>
                        during office hours (Mon–Fri, 8 AM – 5 PM).
                    </p>
                </div>
            </div>

            <div
                v-else-if="notFound"
                class="border border-red-300 bg-red-50 px-5 py-4"
            >
                <p class="text-sm font-bold text-red-700">No request found</p>
                <p class="mt-1 text-sm text-red-600">
                    No record matches tracking number
                    <span class="font-mono font-semibold">{{ query }}</span>.
                    Please double-check the number and try again.
                </p>
            </div>

            <div
                v-else
                class="bg-[#003366]/5 border border-[#003366]/20 px-4 py-3 text-xs text-gray-700 leading-relaxed"
            >
                <span class="font-bold text-[#003366]">Tip:</span>
                Your tracking number was provided when you submitted your request.
                It follows the format <span class="font-mono font-semibold">REQ-YYYY-NNNN</span>.
                If you haven't submitted a request yet,
                <Link href="/requests/new" class="text-[#003366] font-semibold underline">submit one here</Link>.
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import GuestLayout from "../../Layouts/GuestLayout.vue";

const props = defineProps({
    query:    { type: String,  default: null },
    result:   { type: Object,  default: null },
    notFound: { type: Boolean, default: false },
});

const page = usePage();
const site = computed(() => page.props.site ?? {});
const trackingInput = ref(props.query ?? '');

function check() {
    const t = trackingInput.value.trim();
    if (t) {
        router.get('/requests/status', { tracking: t }, { preserveScroll: false });
    }
}

const STATUS_MAP = {
    pending:      { label: 'Pending',      cls: 'bg-amber-100 text-amber-800' },
    under_review: { label: 'Under Review', cls: 'bg-blue-100 text-blue-800' },
    approved:     { label: 'Approved',     cls: 'bg-green-100 text-green-800' },
    completed:    { label: 'Completed',    cls: 'bg-emerald-100 text-emerald-800' },
    rejected:     { label: 'Rejected',     cls: 'bg-red-100 text-red-800' },
};

function statusLabel(status) {
    return STATUS_MAP[status]?.label ?? status;
}

function statusClass(status) {
    return STATUS_MAP[status]?.cls ?? 'bg-slate-100 text-slate-700';
}
</script>
