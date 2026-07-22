<template>
    <div class="flex w-full flex-wrap items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-600">
                    {{ result.type }}
                </span>
                <p class="truncate text-sm font-semibold text-slate-900">{{ result.title }}</p>
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ result.subtitle }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-3">
            <span
                class="rounded-full px-3 py-1 text-xs font-semibold"
                :class="statusClass(result.status)"
            >
                {{ result.status }}
            </span>
            <span class="soft-button-light pointer-events-none">Open</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    result: { type: Object, required: true },
});

function statusClass(status) {
    if (status === "Approved" || status === "Issued" || status === "Completed") {
        return "bg-emerald-100 text-emerald-800";
    }
    if (status === "Under Review" || status === "Checked In") {
        return "bg-amber-100 text-amber-800";
    }
    if (status === "Returned" || status === "Rejected") {
        return "bg-rose-100 text-rose-700";
    }
    if (status === "Archived Document" || status === "Archived") {
        return "bg-gray-100 text-gray-600";
    }
    return "bg-slate-100 text-slate-700";
}
</script>
