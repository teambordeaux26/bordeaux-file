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
                :subtitle="(documents.total ?? documents.length ?? 0) + ' document(s) to address.'"
            >
                <p
                    v-if="rows.length === 0"
                    class="text-sm text-slate-500 py-6 text-center"
                >
                    You have no returned documents. Great work.
                </p>

                <div v-else class="space-y-4">
                    <div
                        v-for="doc in rows"
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
                                <button
                                    type="button"
                                    class="soft-button text-xs"
                                    @click="toggleEdit(doc)"
                                >
                                    {{ editingId === doc.id ? 'Close Editor' : 'Edit & Resubmit' }}
                                </button>
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

                        <form
                            v-if="editingId === doc.id"
                            class="mt-4 grid gap-4 border border-[#003366]/20 bg-slate-50 p-4 lg:grid-cols-2"
                            @submit.prevent
                        >
                            <div
                                v-if="Object.keys(form.errors).length > 0"
                                class="lg:col-span-2 border border-red-400 bg-red-50 px-3 py-2 text-sm text-red-800"
                            >
                                <p class="font-semibold">Please fix the following before saving:</p>
                                <ul class="mt-1 list-disc list-inside space-y-0.5 text-xs">
                                    <li v-for="(msg, field) in form.errors" :key="field">{{ msg }}</li>
                                </ul>
                            </div>

                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                    Tracking Number
                                </label>
                                <input
                                    :value="doc.tracking"
                                    readonly
                                    class="soft-input cursor-default bg-blue-50 font-bold text-[#003366]"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                    Reference Number
                                </label>
                                <input
                                    :value="doc.reference || '—'"
                                    readonly
                                    class="soft-input cursor-default bg-gray-50 text-gray-600"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-600" :for="`title-${doc.id}`">
                                    Title <span class="text-red-500">*</span>
                                </label>
                                <input
                                    :id="`title-${doc.id}`"
                                    v-model="form.title"
                                    class="soft-input"
                                    required
                                />
                            </div>

                            <CategoryFields
                                v-model="form.category_id"
                                :categories="categories"
                                :error="form.errors.category_id"
                            />

                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-600" :for="`priority-${doc.id}`">
                                    Priority <span class="text-red-500">*</span>
                                </label>
                                <select :id="`priority-${doc.id}`" v-model="form.priority" class="soft-select" required>
                                    <option value="Standard">Standard</option>
                                    <option value="Priority">Priority</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-600" :for="`retention-${doc.id}`">
                                    File Retention Period <span class="text-red-500">*</span>
                                </label>
                                <select :id="`retention-${doc.id}`" v-model.number="form.retention_days" class="soft-select" required>
                                    <option
                                        v-for="option in retentionChoices"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-600" :for="`description-${doc.id}`">
                                    Description
                                </label>
                                <textarea
                                    :id="`description-${doc.id}`"
                                    v-model="form.description"
                                    class="soft-input min-h-[100px] resize-y"
                                ></textarea>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-600">
                                    Attached File
                                </label>

                                <div
                                    v-if="doc.versions?.length"
                                    class="mb-2 border border-gray-200 bg-white"
                                >
                                    <div
                                        v-for="version in doc.versions"
                                        :key="version.id"
                                        class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-3 py-2 text-xs last:border-b-0"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-widest"
                                                :class="version.label === 'Revised version' ? 'text-[#003366]' : 'text-gray-500'"
                                            >
                                                {{ version.label }}
                                            </p>
                                            <p class="truncate text-gray-800">{{ version.name }}</p>
                                        </div>
                                        <a
                                            :href="version.preview_url"
                                            target="_blank"
                                            rel="noopener"
                                            class="soft-button-light px-2 py-1 text-[11px]"
                                        >
                                            Preview
                                        </a>
                                    </div>
                                </div>

                                <div
                                    v-else-if="doc.file_name"
                                    class="mb-2 flex flex-wrap items-center gap-3 border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700"
                                >
                                    <span class="font-semibold text-[#003366]">Current file:</span>
                                    <span class="truncate">{{ doc.file_name }}</span>
                                    <a
                                        v-if="doc.file_url"
                                        :href="doc.file_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="ml-auto soft-button-light px-2 py-1 text-[11px]"
                                    >
                                        Preview
                                    </a>
                                </div>

                                <input
                                    :key="`file-${doc.id}-${fileInputKey}`"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                                    class="w-full cursor-pointer border border-dashed border-gray-300 bg-white px-4 py-4 text-sm text-gray-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-[#003366] file:px-4 file:py-1.5 file:text-xs file:font-bold file:text-white hover:border-[#003366]"
                                    @change="handleFile"
                                />
                                <p class="mt-1 text-[11px] text-gray-500">
                                    Leave empty to keep the current file. A new upload stays with this document: the current file is marked Old version and the upload is the Revised version.
                                </p>
                            </div>

                            <div class="lg:col-span-2">
                                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-600" :for="`remarks-${doc.id}`">
                                    Remarks for the reviewer <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    :id="`remarks-${doc.id}`"
                                    v-model="form.owner_remarks"
                                    class="soft-input min-h-[90px] resize-y"
                                    placeholder="Tell the reviewer what you changed."
                                ></textarea>
                                <p class="mt-1 text-[11px] text-gray-500">
                                    The admin sees this note on the workflow card and in the document details.
                                </p>
                                <p v-if="form.errors.owner_remarks" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.owner_remarks }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-gray-200 pt-3 sm:flex-row lg:col-span-2">
                                <button
                                    type="button"
                                    class="soft-button w-full sm:w-auto"
                                    :disabled="form.processing"
                                    @click="submit(true)"
                                >
                                    {{ form.processing && form.resubmit ? 'Saving…' : 'Save & Resubmit' }}
                                </button>
                                <button
                                    type="button"
                                    class="soft-button-light w-full sm:w-auto"
                                    :disabled="form.processing"
                                    @click="submit(false)"
                                >
                                    {{ form.processing && !form.resubmit ? 'Saving…' : 'Save Only' }}
                                </button>
                                <button
                                    type="button"
                                    class="w-full border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 transition hover:bg-gray-50 sm:w-auto"
                                    @click="cancelEdit"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <Pagination :paginator="documents" />
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import Pagination from "../../Components/Pagination.vue";
import CategoryFields from "../../Components/CategoryFields.vue";

const props = defineProps({
    documents: { type: Object, default: () => ({ data: [] }) },
    categories: { type: Array, default: () => [] },
    retentionOptions: { type: Array, default: () => [] },
    editingId: { type: Number, default: null },
});

const rows = computed(() => props.documents?.data ?? []);
const editingId = ref(props.editingId ?? null);
const fileInputKey = ref(0);

const form = useForm({
    _method: "put",
    title: "",
    category_id: null,
    priority: "Standard",
    retention_days: null,
    description: "",
    owner_remarks: "",
    file: null,
    resubmit: true,
});

const retentionChoices = computed(() => {
    const options = [...props.retentionOptions];
    const doc = rows.value.find((row) => row.id === editingId.value);
    if (doc?.retention_days && !options.some((option) => option.value === doc.retention_days)) {
        options.unshift({ value: doc.retention_days, label: `${doc.retention_days} Days` });
    }
    return options;
});

function fillForm(doc) {
    form.clearErrors();
    form.title = doc.title || "";
    form.category_id = doc.category_id ?? null;
    form.priority = doc.priority || "Standard";
    form.retention_days = doc.retention_days || null;
    form.description = doc.description || "";
    form.owner_remarks = "";
    form.file = null;
    form.resubmit = true;
    fileInputKey.value += 1;
}

function toggleEdit(doc) {
    if (editingId.value === doc.id) {
        cancelEdit();
        return;
    }
    editingId.value = doc.id;
    fillForm(doc);
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    if (props.editingId) {
        router.get("/documents/returned", {}, { preserveState: true, preserveScroll: true, replace: true });
    }
}

function handleFile(event) {
    form.file = event.target.files[0] ?? null;
}

function submit(resubmit) {
    form.resubmit = resubmit;
    form.clearErrors("owner_remarks");
    if (resubmit && !String(form.owner_remarks || "").trim()) {
        form.setError("owner_remarks", "Please add remarks for the reviewer.");
        return;
    }
    form.post(`/documents/${editingId.value}`, {
        forceFormData: true,
        preserveScroll: true,
    });
}

watch(
    () => props.editingId,
    (id) => {
        if (!id) return;
        const doc = rows.value.find((row) => row.id === id);
        if (!doc) return;
        editingId.value = id;
        fillForm(doc);
    },
    { immediate: true },
);

function priorityClass(priority) {
    if (priority === "Urgent") return "bg-red-100 text-red-700";
    if (priority === "High") return "bg-orange-100 text-orange-700";
    if (priority === "Priority") return "bg-amber-100 text-amber-800";
    if (priority === "Low") return "bg-slate-100 text-slate-600";
    return "bg-blue-50 text-blue-700";
}
</script>
