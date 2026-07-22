<template>
    <AppLayout>
        <Head title="Certificates" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Certificate of Appearance"
                kicker="Certificates"
                subtitle="Generate and manage certificates for verified visitors."
            >
                <template #actions>
                    <button class="soft-button" @click="showForm = !showForm">
                        {{ showForm ? "Cancel" : "Generate Certificate" }}
                    </button>
                </template>
            </PageHeader>

            <!-- Generate form -->
            <SectionCard
                v-if="showForm"
                title="Issue New Certificate"
                eyebrow="Generate"
                subtitle="Select a visitor to issue a Certificate of Appearance."
            >
                <form
                    @submit.prevent="submit"
                    class="grid gap-4 md:grid-cols-2"
                >
                    <div>
                        <label
                            class="text-xs uppercase tracking-[0.3em] text-slate-500"
                            for="visit_date"
                            >Visit Date</label
                        >
                        <input
                            id="visit_date"
                            v-model="visitDate"
                            type="date"
                            class="soft-input mt-2 w-full"
                            @change="loadVisitorsForDate"
                        />
                        <p class="mt-1 text-xs text-slate-400">
                            Select the day visitors checked in to issue certificates.
                        </p>
                    </div>

                    <div>
                        <label
                            class="text-xs uppercase tracking-[0.3em] text-slate-500"
                            >Visitor</label
                        >
                        <select
                            v-model="form.visitor_log_id"
                            class="soft-select mt-2 w-full"
                            :class="{
                                'border-red-400': form.errors.visitor_log_id,
                            }"
                        >
                            <option value="">— Select visitor —</option>
                            <option
                                v-for="v in visitors"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.name }} — {{ v.purpose }} ({{ v.time_in }})
                            </option>
                        </select>
                        <p
                            v-if="visitors.length === 0"
                            class="mt-1 text-xs text-amber-600"
                        >
                            No visitors on {{ formattedDate }} without a certificate.
                        </p>
                        <p
                            v-if="form.errors.visitor_log_id"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.visitor_log_id }}
                        </p>
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-3">
                        <button
                            type="submit"
                            class="soft-button w-full sm:w-auto"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? "Generating…"
                                    : "Generate & Issue"
                            }}
                        </button>
                        <button
                            type="button"
                            class="soft-button-light w-full sm:w-auto"
                            @click="showForm = false"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </SectionCard>

            <SectionCard
                title="Recent Certificates"
                eyebrow="Issuance"
                subtitle="Latest certificates created for office visitors."
            >
                <p
                    v-if="certificates.length === 0"
                    class="text-sm text-slate-500 py-4 text-center"
                >
                    No certificates issued yet.
                </p>
                <div v-else class="overflow-hidden border border-slate-200">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="bg-slate-100/80 text-xs uppercase tracking-[0.3em] text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Certificate No.</th>
                                <th class="px-4 py-3">Visitor</th>
                                <th class="px-4 py-3">Purpose</th>
                                <th class="px-4 py-3">Issued</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-for="cert in certificates" :key="cert.id">
                                <td
                                    class="px-4 py-3 font-semibold text-slate-900"
                                >
                                    {{ cert.number }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ cert.name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ cert.purpose }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ cert.issued }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a
                                        :href="cert.download_url"
                                        target="_blank"
                                        class="soft-button-light inline-flex items-center gap-1.5"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                                        </svg>
                                        Download
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

const props = defineProps({
    certificates: { type: Array, default: () => [] },
    visitors: { type: Array, default: () => [] },
    selectedDate: { type: String, default: () => new Date().toISOString().slice(0, 10) },
});

const showForm = ref(false);
const visitDate = ref(props.selectedDate);

watch(
    () => props.selectedDate,
    (date) => {
        visitDate.value = date;
    }
);

const formattedDate = computed(() => {
    if (!visitDate.value) return "the selected date";

    return new Date(`${visitDate.value}T00:00:00`).toLocaleDateString(undefined, {
        month: "long",
        day: "numeric",
        year: "numeric",
    });
});

const form = useForm({
    visitor_log_id: "",
});

function loadVisitorsForDate() {
    form.visitor_log_id = "";
    router.get(
        "/certificates",
        { date: visitDate.value },
        { preserveState: true, replace: true }
    );
}

function submit() {
    form.post("/certificates", {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
}
</script>
