<template>
    <AppLayout>
        <Head title="Documents" />
        <div class="space-y-6 fade-up">
            <PageHeader
                title="Document Management"
                kicker="Documents"
                subtitle="Centralize, categorize, and monitor all incoming and outgoing records."
            >
                <template #actions>
                    <Link href="/documents/upload" class="soft-button"
                        >Upload Document</Link
                    >
                </template>
            </PageHeader>

            <SectionCard
                title="Document Queue"
                eyebrow="Current"
                subtitle="Most recent submissions and active files."
            >
                <!-- Filters -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        class="soft-input !w-auto min-w-[12rem] flex-1 max-w-xs"
                        placeholder="Search title or tracking no."
                    />
                    <select
                        v-model="filterCategory"
                        class="soft-select !w-48 shrink-0"
                    >
                        <option value="">All categories</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.name"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                    <select
                        v-model="filterStatus"
                        class="soft-select !w-40 shrink-0"
                    >
                        <option value="">All statuses</option>
                        <option>Pending</option>
                        <option>Under Review</option>
                        <option>Approved</option>
                        <option>Returned</option>
                    </select>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto border border-gray-300">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="bg-[#003366] text-[10px] uppercase tracking-widest text-white"
                        >
                            <tr>
                                <th class="px-4 py-3">Tracking No.</th>
                                <th class="px-4 py-3">Reference No.</th>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Priority</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Submitted By</th>
                                <th class="px-4 py-3">Expires</th>
                                <th class="px-4 py-3">Updated</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-if="filtered.length === 0">
                                <td
                                    colspan="10"
                                    class="px-4 py-8 text-center text-sm text-gray-400"
                                >
                                    No documents found.
                                </td>
                            </tr>
                            <tr
                                v-for="doc in filtered"
                                :key="doc.id"
                                class="hover:bg-blue-50/40 transition"
                            >
                                <td
                                    class="px-4 py-3 font-bold text-[#003366] whitespace-nowrap"
                                >
                                    {{ doc.tracking }}
                                </td>
                                <td
                                    class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap"
                                >
                                    {{ doc.reference }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">
                                        {{ doc.title }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ doc.summary }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 whitespace-nowrap"
                                >
                                    {{ doc.category }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wider px-2 py-0.5"
                                        :class="priorityClass(doc.priority)"
                                    >
                                        {{ doc.priority }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="px-2 py-0.5 text-xs font-semibold uppercase tracking-wide"
                                        :class="statusClass(doc.status)"
                                    >
                                        {{ doc.status }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-600 whitespace-nowrap"
                                >
                                    {{ doc.owner }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="text-xs text-gray-700">{{ doc.expires_at }}</p>
                                    <p class="text-[10px] text-gray-400">{{ doc.retention }}</p>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap"
                                >
                                    {{ doc.updated }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <Link
                                            :href="doc.view_url"
                                            class="soft-button-light inline-flex items-center gap-1.5"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            View
                                        </Link>
                                        <a
                                            v-if="doc.download_url"
                                            :href="doc.download_url"
                                            class="soft-button-light inline-flex items-center gap-1.5"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                                            </svg>
                                            Download
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    Showing {{ filtered.length }} of
                    {{ documents.length }} record(s).
                </p>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

const props = defineProps({
    documents: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const search = ref("");
const filterCategory = ref("");
const filterStatus = ref("");

const filtered = computed(() => {
    return props.documents.filter((doc) => {
        const matchSearch =
            !search.value ||
            doc.title.toLowerCase().includes(search.value.toLowerCase()) ||
            doc.tracking.toLowerCase().includes(search.value.toLowerCase());
        const matchCat =
            !filterCategory.value || doc.category === filterCategory.value;
        const matchStat =
            !filterStatus.value || doc.status === filterStatus.value;
        return matchSearch && matchCat && matchStat;
    });
});

const statusClass = (status) => {
    const map = {
        Approved: "bg-green-100 text-green-800",
        "Under Review": "bg-yellow-100 text-yellow-800",
        Returned: "bg-red-100 text-red-700",
        Archived: "bg-gray-100 text-gray-600",
        Pending: "bg-blue-50 text-[#003366]",
    };
    return map[status] ?? "bg-gray-100 text-gray-600";
};

const priorityClass = (priority) => {
    const map = {
        Urgent: "bg-red-100 text-red-700",
        Priority: "bg-amber-100 text-amber-800",
        Standard: "bg-gray-100 text-gray-600",
    };
    return map[priority] ?? "bg-gray-100 text-gray-600";
};
</script>
