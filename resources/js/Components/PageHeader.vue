<template>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="border-l-4 border-[#003366] pl-4">
            <p
                v-if="kicker"
                class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold"
            >
                {{ kicker }}
            </p>
            <h1 class="text-2xl font-bold text-[#003366]">
                {{ title }}
            </h1>
            <p v-if="subtitle" class="mt-1 max-w-3xl text-sm text-gray-600">
                {{ subtitle }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: "",
    },
    kicker: {
        type: String,
        default: "",
    },
});
</script>
