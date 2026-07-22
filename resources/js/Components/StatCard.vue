<template>
    <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] p-5 shadow-sm">
        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
            {{ label }}
        </p>
        <p class="mt-2 text-3xl font-bold text-[#003366]">
            {{ value }}
        </p>
        <p v-if="note" class="mt-1 text-xs text-gray-500">
            {{ note }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    note: {
        type: String,
        default: "",
    },
});
</script>
