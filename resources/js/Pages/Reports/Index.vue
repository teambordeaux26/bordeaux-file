<template>
    <AppLayout>
        <Head title="Reports" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Reports"
                kicker="Administration"
                subtitle="Generate performance summaries for documents and visitor transactions."
            >
                <template #actions>
                    <button class="soft-button">Export All</button>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-3">
                <SectionCard
                    title="Document Reports"
                    eyebrow="Reports"
                    subtitle="Monthly document movement overview."
                >
                    <p class="text-sm text-slate-600">
                        Includes totals by status and category.
                    </p>
                    <div class="mt-4">
                        <button class="soft-button">Generate Report</button>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Visitor Reports"
                    eyebrow="Reports"
                    subtitle="Daily and weekly visitor summaries."
                >
                    <p class="text-sm text-slate-600">
                        Track volume, purpose, and time-on-site.
                    </p>
                    <div class="mt-4">
                        <button class="soft-button">Generate Report</button>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Transaction Reports"
                    eyebrow="Reports"
                    subtitle="Monthly office transaction analytics."
                >
                    <p class="text-sm text-slate-600">
                        Export CSV or PDF for audit use.
                    </p>
                    <div class="mt-4">
                        <button class="soft-button">Generate Report</button>
                    </div>
                </SectionCard>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
</script>
