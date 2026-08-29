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
                                Submitting…
                            </span>
                            <span v-else>Submit Request</span>
                        </button>
                        <button
                            type="button"
                            class="soft-button-light w-full sm:w-auto px-6 py-2.5"
                            :disabled="form.processing"
                            @click="form.reset()"
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
import { computed } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import GuestLayout from "../../Layouts/GuestLayout.vue";
import SearchableSelect from "../../Components/SearchableSelect.vue";

const props = defineProps({
    requestTypes: { type: Array, default: () => [] },
});

const page = usePage();
const barangays = computed(() => page.props.oasBarangays ?? []);

const form = useForm({
    requester_name:    '',
    requester_email:   '',
    requester_phone:   '',
    requester_address: '',
    request_type_id:   '',
    details:           '',
});

const availableRequestTypes = computed(() =>
    (props.requestTypes ?? []).filter((type) => type.is_active !== false)
);

const selectedType = computed(() =>
    availableRequestTypes.value.find((type) => String(type.id) === String(form.request_type_id)) ?? null
);

function submit() {
    form.post('/requests');
}
</script>
