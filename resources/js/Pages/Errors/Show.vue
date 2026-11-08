<template>
    <AppLayout>
        <Head :title="title" />
        <div class="mx-auto flex min-h-[60vh] max-w-lg items-center">
            <section class="w-full border border-gray-300 border-t-4 border-t-[#003366] bg-white p-8 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Office portal</p>
                <p class="mt-2 text-3xl font-bold text-[#003366]">{{ status }}</p>
                <p class="mt-3 text-sm font-semibold text-gray-700">{{ message }}</p>
                <Link href="/dashboard" class="soft-button mt-6 inline-flex">Back to Dashboard</Link>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    status: { type: Number, default: 403 },
    message: { type: String, default: "You do not have permission to access this page." },
});

const title = computed(() => (props.status === 404 ? "Page not found" : "Access denied"));
</script>
