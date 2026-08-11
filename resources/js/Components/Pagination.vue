<template>
    <div
        v-if="paginator && paginator.last_page > 1"
        class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-xs text-gray-500">
            Showing
            <span class="font-semibold text-gray-700">{{ paginator.from ?? 0 }}</span>
            –
            <span class="font-semibold text-gray-700">{{ paginator.to ?? 0 }}</span>
            of
            <span class="font-semibold text-gray-700">{{ paginator.total }}</span>
        </p>

        <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
            <template v-for="(link, index) in paginator.links" :key="index">
                <component
                    :is="link.url ? Link : 'span'"
                    :href="link.url || undefined"
                    :preserve-scroll="true"
                    :preserve-state="true"
                    class="min-w-[2rem] px-2.5 py-1.5 text-xs font-semibold border transition text-center"
                    :class="linkClass(link)"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>

    <p
        v-else-if="paginator && paginator.total > 0"
        class="mt-2 text-xs text-gray-400"
    >
        Showing {{ paginator.total }} record{{ paginator.total === 1 ? '' : 's' }}.
    </p>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    paginator: {
        type: Object,
        default: null,
    },
});

function linkClass(link) {
    if (!link.url) {
        return "border-gray-200 text-gray-300 cursor-default bg-white";
    }
    if (link.active) {
        return "border-[#003366] bg-[#003366] text-white";
    }
    return "border-gray-300 bg-white text-gray-700 hover:bg-blue-50 hover:border-[#003366]";
}
</script>
