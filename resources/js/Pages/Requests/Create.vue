<template>
    <GuestLayout>
        <Head title="New Request" />
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="border-l-4 border-[#003366] pl-4">
                <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Guest Services</p>
                <h1 class="text-2xl font-bold text-[#003366]">New Request</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Provide accurate information to help the office process your request quickly.
                </p>
            </div>

            <!-- Info Notice -->
            <div class="bg-[#003366]/5 border border-[#003366]/20 px-4 py-3 text-xs text-gray-700 leading-relaxed">
                <span class="font-bold text-[#003366]">Important:</span> All fields are required unless noted.
                You may attach files, such as a proposal, for the office to review.
                Upon submission, you will receive a <strong>tracking number</strong> to monitor your request.
            </div>

            <!-- Request Form -->
            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm p-4 sm:p-6">
                <div class="border-b border-gray-200 pb-3 mb-6">
                    <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Submission</p>
                    <h2 class="text-lg font-bold text-[#003366]">Request Form</h2>
                    <p class="text-xs text-gray-500 mt-0.5">All fields are required unless noted otherwise.</p>
                </div>

                <form @submit.prevent="submit" class="grid gap-5 md:grid-cols-2">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.requester_name"
                            class="soft-input"
                            :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400': form.errors.requester_name }"
                            placeholder="Your full name"
                        />
                        <p v-if="form.errors.requester_name" class="mt-1 text-xs text-red-600">
                            {{ form.errors.requester_name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.requester_email"
                            type="email"
                            required
                            class="soft-input"
                            :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400': form.errors.requester_email }"
                            placeholder="you@example.com"
                        />
                        <p v-if="form.errors.requester_email" class="mt-1 text-xs text-red-600">
                            {{ form.errors.requester_email }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Contact Number
                        </label>
                        <input
                            v-model="form.requester_phone"
                            type="tel"
                            class="soft-input"
                            placeholder="Mobile or landline"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Barangay (Oas, Albay) <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            :model-value="form.requester_address"
                            :options="barangays"
                            :input-class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400': form.errors.requester_address }"
                            placeholder="Search or select a barangay in Oas"
                            @update:model-value="form.requester_address = $event"
                        />
                        <p v-if="form.errors.requester_address" class="mt-1 text-xs text-red-600">
                            {{ form.errors.requester_address }}
                        </p>
                    </div>

                    <div class="md:col-span-2 grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                Request Type <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.request_type_id"
                                class="soft-select"
                                :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400': form.errors.request_type_id }"
                            >
                                <option value="">— Select a request type —</option>
                                <option
                                    v-for="type in availableRequestTypes"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                </option>
                            </select>
                            <p v-if="availableRequestTypes.length === 0" class="mt-1 text-xs text-amber-600">
                                Request types are not available yet. Please contact the office.
                            </p>
                            <p v-if="form.errors.request_type_id" class="mt-1 text-xs text-red-600">
                                {{ form.errors.request_type_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                Purpose
                            </label>
                            <input
                                class="soft-input bg-gray-50"
                                type="text"
                                readonly
                                :value="selectedType?.purpose || 'Select a request type to see its purpose'"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                This purpose is set by the office and saved with your request.
                            </p>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="mb-1 flex items-baseline justify-between gap-3">
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600">
                                Supporting File
                            </label>
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Optional</span>
                        </div>

                        <div
                            v-if="form.attachments.length < maxFiles"
                            class="relative border-2 border-dashed px-4 text-center transition"
                            :class="[dropZoneClass, form.attachments.length ? 'py-4' : 'py-8']"
                            @dragenter.prevent="dragging = true"
                            @dragover.prevent="dragging = true"
                            @dragleave.prevent="onDragLeave"
                            @drop.prevent="onDrop"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.png,.jpg,.jpeg"
                                class="absolute inset-0 z-10 cursor-pointer opacity-0"
                                :disabled="form.processing"
                                aria-label="Choose supporting files"
                                @change="onAttachment"
                            />
                            <div class="pointer-events-none flex flex-col items-center">
                                <span
                                    class="flex items-center justify-center border border-[#003366]/20 bg-white text-[#003366]"
                                    :class="form.attachments.length ? 'mb-2 h-9 w-9' : 'mb-3 h-12 w-12'"
                                >
                                    <FileUp :class="form.attachments.length ? 'h-4 w-4' : 'h-6 w-6'" />
                                </span>
                                <p class="text-sm font-semibold text-[#003366]">
                                    {{ dragging ? "Drop the files to attach them" : form.attachments.length ? "Add more files" : "Drag files here, or click to browse" }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    PDF, Word, PowerPoint, or images · up to {{ maxFiles }} files · 10 MB each
                                </p>
                            </div>
                        </div>

                        <p v-else class="border border-[#003366]/20 bg-[#003366]/5 px-4 py-3 text-xs text-[#003366]">
                            {{ maxFiles }} files selected. Remove one to add a different file.
                        </p>

                        <ul v-if="form.attachments.length" class="mt-3 space-y-2">
                            <li
                                v-for="(file, index) in form.attachments"
                                :key="`${file.name}-${file.size}-${index}`"
                                class="flex items-center gap-3 border border-gray-300 border-l-4 border-l-[#FFD700] bg-white px-4 py-3"
                            >
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center bg-[#003366] text-[10px] font-bold uppercase tracking-wide text-[#FFD700]">
                                    {{ fileExtension(file.name) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900" :title="file.name">
                                        {{ file.name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ form.processing ? progressLabel : `${formatSize(file.size)} · Ready to send` }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="form.processing"
                                    @click="removeAttachment(index)"
                                >
                                    Remove
                                </button>
                            </li>
                        </ul>

                        <p v-if="attachmentError" class="mt-2 text-xs text-red-600">
                            {{ attachmentError }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Request Details
                        </label>
                        <textarea
                            v-model="form.details"
                            class="soft-input min-h-[140px] resize-y"
                            placeholder="Describe your request in detail"
                        ></textarea>
                        <p v-if="form.errors.details" class="mt-1 text-xs text-red-600">
                            {{ form.errors.details }}
                        </p>
                    </div>

                    <div
                        v-if="form.processing && form.attachments.length"
                        class="md:col-span-2 border border-[#003366]/20 bg-[#003366]/5 px-4 py-3"
                    >
                        <div class="mb-2 flex items-center justify-between gap-3 text-xs font-semibold text-[#003366]">
                            <span>{{ progressLabel }}</span>
                            <span>{{ uploadProgress }}%</span>
                        </div>
                        <div
                            class="h-2.5 overflow-hidden bg-white"
                            role="progressbar"
                            :aria-valuenow="uploadProgress"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Upload progress"
                        >
                            <div
                                class="h-full bg-[#FFD700] transition-[width] duration-150 ease-out"
                                :style="{ width: `${uploadProgress}%` }"
                            ></div>
                        </div>
                        <p class="mt-2 truncate text-xs text-gray-600">
                            {{ form.attachments.length === 1 ? form.attachments[0].name : `${form.attachments.length} files` }}
                        </p>
                    </div>

                    <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 pt-2 border-t border-gray-200">
                        <button
                            type="submit"
                            class="soft-button w-full sm:w-auto px-6 py-2.5"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                {{ form.attachments.length ? progressLabel : "Submitting…" }}
                            </span>
                            <span v-else>Submit Request</span>
                        </button>
                        <button
                            type="button"
                            class="soft-button-light w-full sm:w-auto px-6 py-2.5"
                            :disabled="form.processing"
                            @click="clearForm"
                        >
                            Clear Form
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </GuestLayout>
</template>

<script setup>
import { computed, ref } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { FileUp } from "@lucide/vue";
import GuestLayout from "../../Layouts/GuestLayout.vue";
import SearchableSelect from "../../Components/SearchableSelect.vue";

const allowedExtensions = ["pdf", "doc", "docx", "ppt", "pptx", "png", "jpg", "jpeg"];
const maxBytes = 10 * 1024 * 1024;
const maxFiles = 10;

const props = defineProps({
    requestTypes: { type: Array, default: () => [] },
});

const page = usePage();
const barangays = computed(() => page.props.oasBarangays ?? []);

const fileInput = ref(null);
const dragging = ref(false);
const fileError = ref("");
const uploadProgress = ref(0);

const form = useForm({
    requester_name:    '',
    requester_email:   '',
    requester_phone:   '',
    requester_address: '',
    request_type_id:   '',
    details:           '',
    attachments:       [],
});

const availableRequestTypes = computed(() =>
    (props.requestTypes ?? []).filter((type) => type.is_active !== false)
);

const selectedType = computed(() =>
    availableRequestTypes.value.find((type) => String(type.id) === String(form.request_type_id)) ?? null
);

const attachmentError = computed(() => {
    if (fileError.value) {
        return fileError.value;
    }

    if (form.errors.attachments) {
        return form.errors.attachments;
    }

    const field = Object.keys(form.errors).find((key) => key.startsWith("attachments."));

    return field ? form.errors[field] : "";
});

const dropZoneClass = computed(() => {
    if (dragging.value) {
        return "border-[#003366] bg-[#003366]/5";
    }
    if (attachmentError.value) {
        return "border-red-400 bg-red-50";
    }
    return "border-gray-300 bg-gray-50 hover:border-[#003366] hover:bg-white";
});

const progressLabel = computed(() => {
    if (uploadProgress.value >= 100) {
        return "Finishing up…";
    }
    if (uploadProgress.value <= 0) {
        return "Preparing upload…";
    }
    return form.attachments.length > 1 ? "Uploading files" : "Uploading file";
});

function fileExtension(name) {
    const ext = name.includes(".") ? name.split(".").pop() : "file";
    return (ext || "file").slice(0, 4);
}

function formatSize(bytes) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function resetFileInput() {
    if (fileInput.value) {
        fileInput.value.value = "";
    }
}

function addFiles(fileList) {
    const incoming = [...(fileList ?? [])];
    if (!incoming.length) {
        return;
    }

    fileError.value = "";
    form.clearErrors("attachments");

    const next = [...form.attachments];
    const errors = [];

    for (const file of incoming) {
        if (next.length >= maxFiles) {
            errors.push(`You can attach up to ${maxFiles} files.`);
            break;
        }

        const extension = file.name.includes(".") ? file.name.split(".").pop().toLowerCase() : "";

        if (!allowedExtensions.includes(extension)) {
            errors.push(`${file.name} must be a PDF, Word, PowerPoint, or image file.`);
            continue;
        }

        if (file.size > maxBytes) {
            errors.push(`${file.name} is larger than 10 MB.`);
            continue;
        }

        const alreadyAdded = next.some((existing) => existing.name === file.name && existing.size === file.size);
        if (alreadyAdded) {
            continue;
        }

        next.push(file);
    }

    form.attachments = next;
    fileError.value = errors[0] ?? "";
    resetFileInput();
}

function onAttachment(event) {
    addFiles(event.target.files);
}

function onDragLeave(event) {
    if (!event.currentTarget.contains(event.relatedTarget)) {
        dragging.value = false;
    }
}

function onDrop(event) {
    dragging.value = false;
    addFiles(event.dataTransfer?.files);
}

function removeAttachment(index) {
    form.attachments = form.attachments.filter((_, fileIndex) => fileIndex !== index);
    fileError.value = "";
    form.clearErrors("attachments");
    uploadProgress.value = 0;
    resetFileInput();
}

function clearForm() {
    form.reset();
    form.clearErrors();
    fileError.value = "";
    dragging.value = false;
    uploadProgress.value = 0;
    resetFileInput();
}

function submit() {
    if (fileError.value) {
        return;
    }

    uploadProgress.value = 0;

    form.post("/requests", {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (progress) => {
            uploadProgress.value = progress?.percentage ?? 0;
        },
        onError: () => {
            uploadProgress.value = 0;
        },
    });
}
</script>
