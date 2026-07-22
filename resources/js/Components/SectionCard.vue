<template>
    <section class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-sm p-5">
        <div class="flex flex-wrap items-start justify-between gap-3 pb-3 mb-4 border-b border-gray-200">
            <div>
                <p
                    v-if="eyebrow"
                    class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold"
                >
                    {{ eyebrow }}
                </p>
                <h2 class="text-base font-bold text-[#003366]">
                    {{ title }}
                </h2>
                <p v-if="subtitle" class="mt-0.5 text-xs text-gray-500">
                    {{ subtitle }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <div>
            <slot />
        </div>
    </section>
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
    eyebrow: {
        type: String,
        default: "",
    },
});
</script>
