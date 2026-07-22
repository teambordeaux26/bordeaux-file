<template>
    <GuestLayout>
        <Head title="Track Document" />
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="border-l-4 border-[#003366] pl-4">
                <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Guest Services</p>
                <h1 class="text-2xl font-bold text-[#003366]">Track Your Document</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Enter your tracking number to view the latest status and timeline.
                </p>
            </div>

            <!-- Tracking Lookup -->
            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm p-4 sm:p-6">
                <div class="border-b border-gray-200 pb-3 mb-6">
                    <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Status</p>
                    <h2 class="text-lg font-bold text-[#003366]">Tracking Lookup</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Keep your tracking number ready for faster service.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input
                        class="flex-1 border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                        placeholder="Enter tracking number (e.g. DMS-2024-00123)"
                    />
                    <button
                        class="w-full sm:w-auto px-6 py-2.5 bg-[#003366] text-white text-sm font-bold hover:bg-[#002244] transition whitespace-nowrap"
                    >
                        Check Status
                    </button>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm p-4 sm:p-6">
                <div class="border-b border-gray-200 pb-3 mb-6">
                    <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Progress</p>
                    <h2 class="text-lg font-bold text-[#003366]">Status Timeline</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Sample timeline for an active document request.</p>
                </div>

                <div class="space-y-0">
                    <div
                        v-for="(step, index) in steps"
                        :key="step.label"
                        class="flex items-start gap-4"
                    >
                        <!-- Step indicator + connector line -->
                        <div class="flex flex-col items-center">
                            <div
                                class="flex h-9 w-9 items-center justify-center text-xs font-bold border-2 shrink-0"
                                :class="step.complete
                                    ? 'bg-[#003366] border-[#003366] text-white'
                                    : 'bg-white border-gray-300 text-gray-400'"
                            >
                                {{ step.complete ? '✓' : index + 1 }}
                            </div>
                            <div
                                v-if="index < steps.length - 1"
                                class="w-0.5 flex-1 min-h-[24px]"
                                :class="step.complete ? 'bg-[#003366]' : 'bg-gray-200'"
                            ></div>
                        </div>

                        <!-- Step content -->
                        <div class="pb-6">
                            <p
                                class="text-sm font-bold"
                                :class="step.complete ? 'text-[#003366]' : 'text-gray-400'"
                            >
                                {{ step.label }}
                            </p>
                            <p class="text-xs mt-0.5" :class="step.complete ? 'text-gray-600' : 'text-gray-400'">
                                {{ step.detail }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </GuestLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import GuestLayout from "../../Layouts/GuestLayout.vue";

const steps = [
    {
        label: "Request Submitted",
        detail: "Tracking number generated and queued.",
        complete: true,
    },
    {
        label: "Under Review",
        detail: "Office staff evaluating your request.",
        complete: true,
    },
    {
        label: "Approved for Release",
        detail: "Certificate or document ready for pickup.",
        complete: false,
    },
    {
        label: "Archived",
        detail: "Stored in the office digital archive.",
        complete: false,
    },
];
</script>
