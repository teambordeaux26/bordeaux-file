<template>
    <AppLayout>
        <Head title="Dashboard" />
        <div class="space-y-6 fade-up">
            <PageHeader
                title="System Dashboard"
                kicker="Overview"
                subtitle="Monitor document status, visitor activity, and operational performance from a single command view."
            >
                <template #actions>
                    <Link href="/documents/upload" class="soft-button">Upload Document</Link>
                    <Link href="/reports" class="soft-button-light">View Reports</Link>
                </template>
            </PageHeader>

            <!-- Stat Cards -->
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    v-for="stat in stats"
                    :key="stat.label"
                    :label="stat.label"
                    :value="stat.value"
                    :note="stat.note"
                />
            </div>

            <!-- Quick Actions + Pending Reviews -->
            <div class="grid gap-6 xl:grid-cols-2">
                <SectionCard
                    title="Quick Actions"
                    eyebrow="Operations"
                    subtitle="Jump into the most common office transactions."
                >
                    <div class="grid gap-2 sm:grid-cols-2">
                        <Link
                            v-for="action in quickActions"
                            :key="action.label"
                            :href="action.href"
                            class="flex items-center justify-between border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-[#003366] hover:bg-[#003366] hover:text-white hover:border-[#003366] transition"
                        >
                            <span>{{ action.label }}</span>
                            <span class="text-xs font-normal opacity-70">{{ action.note }}</span>
                        </Link>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Pending Reviews"
                    eyebrow="Approvals"
                    subtitle="Documents currently waiting for administrative action."
                >
                    <p v-if="approvals.length === 0" class="text-sm text-slate-500 py-4 text-center">
                        No documents pending review.
                    </p>
                    <div v-else class="space-y-2">
                        <div
                            v-for="item in approvals"
                            :key="item.id"
                            class="flex items-center justify-between border border-gray-200 bg-white px-4 py-3"
                        >
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ item.title }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ item.tracking }} &bull; {{ item.submittedBy }}
                                </p>
                            </div>
                            <span class="soft-chip">{{ item.age }}</span>
                        </div>
                    </div>
                </SectionCard>
            </div>

            <!-- Recent Activity -->
            <SectionCard
                title="Recent Activity"
                eyebrow="Audit Trail"
                subtitle="Latest document movements and visitor actions."
            >
                <p v-if="activity.length === 0" class="text-sm text-slate-500 py-4 text-center">
                    No recent activity recorded.
                </p>
                <div v-else class="space-y-2">
                    <div
                        v-for="entry in activity"
                        :key="entry.summary + entry.time"
                        class="flex flex-wrap items-center justify-between gap-3 border border-gray-200 bg-white px-4 py-3"
                    >
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ entry.summary }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ entry.detail }}</p>
                        </div>
                        <span class="text-xs uppercase tracking-widest text-gray-400 font-semibold">
                            {{ entry.time }}
                        </span>
                    </div>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../Layouts/AppLayout.vue";
import PageHeader from "../Components/PageHeader.vue";
import StatCard from "../Components/StatCard.vue";
import SectionCard from "../Components/SectionCard.vue";

defineProps({
    stats:     { type: Array, default: () => [] },
    approvals: { type: Array, default: () => [] },
    activity:  { type: Array, default: () => [] },
});

const quickActions = [
    { label: "Log Visitor",          note: "Front desk", href: "/visitors" },
    { label: "Generate Certificate", note: "COA module", href: "/certificates" },
    { label: "Track Document",       note: "Live status", href: "/tracking" },
    { label: "Search Records",       note: "Archives",    href: "/search" },
];
</script>
