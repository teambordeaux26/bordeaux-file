<template>
    <AppLayout>
        <Head :title="`Edit ${document.tracking}`" />
        <div class="space-y-6 fade-up">
            <PageHeader
                :title="`Edit &amp; Resubmit`"
                kicker="Returned Document"
                :subtitle="`${document.tracking} · ${document.reference || 'No reference'}`"
            >
                <template #actions>
                    <Link href="/documents/returned" class="soft-button-light">Back to Returned</Link>
                </template>
            </PageHeader>

            <!-- Return context banner -->
            <div class="border border-orange-300 bg-orange-50 p-4">
                <p class="text-[10px] uppercase tracking-widest text-orange-800 font-semibold">
                    Returned from: {{ returnContext.from_label }}
                </p>
                <p v-if="returnContext.by" class="mt-1 text-xs text-gray-700">
                    Returned by
                    <span class="font-semibold">{{ returnContext.by }}</span>
                </p>
                <p
                    v-if="returnContext.remarks"
                    class="mt-2 text-sm text-gray-900 whitespace-pre-line"
                >
                    <span class="font-semibold">Remarks:</span> {{ returnContext.remarks }}
                </p>
                <p v-else class="mt-2 text-xs text-gray-500 italic">
                    No remarks were left with this return.
                </p>
                <p class="mt-3 text-xs text-gray-600">
                    <template v-if="returnContext.from === 'under_review'">
                        Once you resubmit, the document will re-enter the review queue and any employee (not you) can pick it up.
                    </template>
                    <template v-else-if="returnContext.from === 'for_approval'">
                        Once you resubmit, the document will be reviewed again before it reaches the admin.
                    </template>
                    <template v-else>
                        Once you resubmit, the document will re-enter the workflow starting from review.
                    </template>
                </p>
            </div>

            <div
                v-if="Object.keys(form.errors).length > 0"
                class="flex items-start gap-3 border border-red-400 bg-red-50 px-4 py-3 text-sm font-medium text-red-800"
            >
                <div>
                    <p class="font-semibold">Please fix the following before saving:</p>
                    <ul class="mt-1 list-disc list-inside space-y-0.5 text-xs">
                        <li v-for="(msg, field) in form.errors" :key="field">{{ msg }}</li>
                    </ul>
                </div>
            </div>

            <SectionCard
                title="Revise Document"
                eyebrow="Editable Fields"
                subtitle="Update details, replace the attached file if needed, then choose to save or resubmit."
            >
                <form
                    class="grid gap-5 lg:grid-cols-2"
                    enctype="multipart/form-data"
                    @submit.prevent
                >
                    <div class="lg:col-span-2 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                                Tracking Number
                            </label>
                            <input
                                :value="document.tracking"
                                readonly
                                class="soft-input bg-blue-50 text-[#003366] font-bold cursor-default"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                                Reference Number
                            </label>
                            <input
                                :value="document.reference || '—'"
                                readonly
                                class="soft-input bg-gray-50 text-gray-600 cursor-default"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="title">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="title"
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
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="priority">
                            Priority <span class="text-red-500">*</span>
                        </label>
                        <select id="priority" v-model="form.priority" class="soft-select" required>
                            <option value="Standard">Standard</option>
                            <option value="Priority">Priority</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="retention_days">
                            File Retention Period <span class="text-red-500">*</span>
                        </label>
                        <select id="retention_days" v-model.number="form.retention_days" class="soft-select" required>
                            <option
                                v-for="option in retentionOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="description">
                            Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="soft-input min-h-[120px] resize-y"
                        ></textarea>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Attached File
                        </label>

                        <div
                            v-if="document.file_name"
                            class="mb-2 flex flex-wrap items-center gap-3 border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700"
                        >
                            <span class="font-semibold text-[#003366]">Current file:</span>
                            <span class="truncate">{{ document.file_name }}</span>
                            <a
                                v-if="document.file_url"
                                :href="document.file_url"
                                target="_blank"
                                rel="noopener"
                                class="ml-auto soft-button-light text-[11px] py-1 px-2"
                            >
                                Preview
                            </a>
                        </div>

                        <input
                            type="file"
                            accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                            class="w-full border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-sm text-gray-600 file:mr-4 file:border-0 file:bg-[#003366] file:px-4 file:py-1.5 file:text-xs file:font-bold file:text-white file:cursor-pointer hover:border-[#003366] transition cursor-pointer"
                            @change="handleFile"
                        />
                        <p class="mt-1 text-[11px] text-gray-500">
                            Leave empty to keep the current file. Upload a new one to replace it.
                        </p>
                    </div>

                    <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-3 border-t border-gray-200">
                        <button
                            type="button"
                            class="soft-button w-full sm:w-auto"
                            :disabled="form.processing"
                            @click="submit(true)"
                        >
                            {{ form.processing && form.resubmit ? 'Saving…' : 'Save &amp; Resubmit' }}
                        </button>
                        <button
                            type="button"
                            class="soft-button-light w-full sm:w-auto"
                            :disabled="form.processing"
                            @click="submit(false)"
                        >
                            {{ form.processing && !form.resubmit ? 'Saving…' : 'Save Only' }}
                        </button>
                        <Link
                            href="/documents/returned"
                            class="w-full sm:w-auto px-4 py-2 border border-gray-300 text-sm font-semibold text-gray-700 text-center hover:bg-gray-50 transition"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import CategoryFields from "../../Components/CategoryFields.vue";

const props = defineProps({
    document:         { type: Object, required: true },
    returnContext:    { type: Object, default: () => ({}) },
    categories:       { type: Array, default: () => [] },
    retentionOptions: { type: Array, default: () => [] },
});

const form = useForm({
    _method:        "put",
    title:          props.document.title || "",
    category_id:    props.document.category_id ?? null,
    priority:       props.document.priority || "Standard",
    retention_days: props.document.retention_days || 7,
    description:    props.document.description || "",
    file:           null,
    resubmit:       false,
});

function handleFile(e) {
    form.file = e.target.files[0] ?? null;
}

function submit(resubmit) {
    form.resubmit = resubmit;
    form.post(`/documents/${props.document.id}`, {
        forceFormData: true,
    });
}
</script>
