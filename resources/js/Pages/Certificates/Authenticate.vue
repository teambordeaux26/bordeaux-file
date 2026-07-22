<template>
    <GuestLayout>
        <Head title="Certificate Authentication" />
        <div class="space-y-6 fade-up">

            <div class="border-l-4 border-emerald-500 pl-4">
                <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Verification</p>
                <h1 class="text-2xl font-bold text-[#003366]">Certificate Authentication</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Official verification record for Certificate of Appearance.
                </p>
            </div>

            <div class="flex items-start gap-3 bg-emerald-50 border border-emerald-300 px-4 py-4">
                <svg class="mt-0.5 h-6 w-6 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-base font-bold text-emerald-800">This certificate is authenticated</p>
                    <p class="text-sm text-emerald-700 mt-1">
                        The scanned Certificate of Appearance is valid and was officially issued by the Office of the Vice Mayor, Oas, Albay.
                    </p>
                </div>
            </div>

            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Certificate Details</p>
                    <h2 class="text-base font-bold text-[#003366]">Certificate of Appearance</h2>
                </div>

                <div class="px-5 py-4 grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Certificate No.</p>
                        <p class="font-mono font-bold text-[#003366] text-base">{{ certificate.number }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Date Issued</p>
                        <p class="text-gray-900">{{ certificate.issued }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Issued To</p>
                        <p class="text-gray-900 font-medium">{{ certificate.name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Address</p>
                        <p class="text-gray-900">{{ certificate.address }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Purpose</p>
                        <p class="text-gray-900">{{ certificate.purpose }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-300 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Issued By</p>
                    <h2 class="text-base font-bold text-[#003366]">Certificate Generator</h2>
                </div>

                <div class="px-5 py-4 grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Name</p>
                        <p class="text-gray-900 font-medium">{{ issuer.name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Position</p>
                        <p class="text-gray-900">{{ issuer.position }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Department</p>
                        <p class="text-gray-900">{{ issuer.department }}</p>
                    </div>
                    <div v-if="issuer.email">
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold mb-0.5">Email</p>
                        <p class="text-gray-900">{{ issuer.email }}</p>
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-500 text-center">
                This verification page confirms the authenticity of the printed certificate. For inquiries, contact the Office of the Vice Mayor, Oas, Albay.
            </p>
        </div>
    </GuestLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import GuestLayout from "../../Layouts/GuestLayout.vue";

defineProps({
    certificate: {
        type: Object,
        required: true,
    },
    issuer: {
        type: Object,
        required: true,
    },
});
</script>
