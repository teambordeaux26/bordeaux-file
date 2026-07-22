<template>
    <GuestLayout>
        <Head title="Get Certificate of Appearance" />
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="border-l-4 border-[#003366] pl-4">
                <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Guest Services</p>
                <h1 class="text-2xl font-bold text-[#003366]">Certificate of Appearance</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Confirm your details to register your visit and generate your certificate.
                </p>
            </div>

            <!-- Request summary badge -->
            <div class="flex flex-wrap items-center gap-3 bg-emerald-50 border border-emerald-200 px-4 py-3">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-xs font-bold text-emerald-800">
                        Request <span class="font-mono">{{ docRequest.tracking }}</span> — Completed
                    </p>
                    <p class="text-xs text-emerald-700 mt-0.5">
                        {{ docRequest.type }}
                    </p>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm p-4 sm:p-6">
                <div class="border-b border-gray-200 pb-3 mb-6">
                    <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Visitor Registration</p>
                    <h2 class="text-lg font-bold text-[#003366]">Confirm Your Details</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        These details will appear on your Certificate of Appearance.
                    </p>
                </div>

                <form @submit.prevent="submit" class="grid gap-5 md:grid-cols-2">

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.visitor_name"
                            class="soft-input"
                            :class="{ 'border-red-400': form.errors.visitor_name }"
                            placeholder="Your full name"
                        />
                        <p v-if="form.errors.visitor_name" class="mt-1 text-xs text-red-600">
                            {{ form.errors.visitor_name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Contact Number
                        </label>
                        <input
                            v-model="form.visitor_phone"
                            type="tel"
                            class="soft-input"
                            placeholder="Mobile or landline"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Home Address <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.address"
                            class="soft-input"
                            :class="{ 'border-red-400': form.errors.address }"
                            placeholder="Barangay, Municipality / City, Province"
                        />
                        <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">
                            {{ form.errors.address }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">This will appear on your Certificate of Appearance.</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Purpose of Visit <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.purpose"
                            class="soft-input"
                            :class="{ 'border-red-400': form.errors.purpose }"
                            placeholder="e.g. Follow-up on Certificate of Appearance request"
                        />
                        <p v-if="form.errors.purpose" class="mt-1 text-xs text-red-600">
                            {{ form.errors.purpose }}
                        </p>
                    </div>

                    <!-- Info notice -->
                    <div class="md:col-span-2 bg-[#003366]/5 border border-[#003366]/20 px-4 py-3 text-xs text-gray-700 leading-relaxed">
                        <span class="font-bold text-[#003366]">Note:</span>
                        Submitting this form will log your visit and immediately generate your
                        Certificate of Appearance. You will be able to download it as a PDF.
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
                                Generating Certificate…
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                </svg>
                                Generate Certificate
                            </span>
                        </button>
                        <Link
                            :href="`/requests/status?tracking=${docRequest.tracking}`"
                            class="soft-button-light w-full sm:w-auto px-6 py-2.5 text-center"
                        >
                            Back to Status
                        </Link>
                    </div>

                </form>
            </div>

        </div>
    </GuestLayout>
</template>

<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import GuestLayout from "../../Layouts/GuestLayout.vue";

const props = defineProps({
    docRequest: { type: Object, required: true },
});

const form = useForm({
    visitor_name:  props.docRequest.name,
    visitor_phone: props.docRequest.phone,
    address:       props.docRequest.address,
    purpose:       props.docRequest.type,
});

function submit() {
    form.post(`/requests/${props.docRequest.id}/certificate`);
}
</script>
