<template>
    <AppLayout>
        <Head title="Visitor's Log" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Visitor's Log"
                kicker="Front Desk"
                subtitle="Digitally record visitor information and monitor daily activity."
            >
                <template #actions>
                    <button class="soft-button-light">Daily Summary</button>
                </template>
            </PageHeader>

            <SectionCard
                title="Record Visitor"
                eyebrow="Registration"
                subtitle="Capture visitor details accurately for security and reporting."
            >
                <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs uppercase tracking-[0.3em] text-slate-500">Visitor Name</label>
                        <input
                            v-model="form.visitor_name"
                            class="soft-input mt-2"
                            placeholder="Full name"
                            :class="{ 'border-red-400': form.errors.visitor_name }"
                        />
                        <p v-if="form.errors.visitor_name" class="mt-1 text-xs text-red-600">{{ form.errors.visitor_name }}</p>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-[0.3em] text-slate-500">Contact Number</label>
                        <input
                            v-model="form.visitor_phone"
                            class="soft-input mt-2"
                            placeholder="Phone number"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs uppercase tracking-[0.3em] text-slate-500">Home Address</label>
                        <input
                            v-model="form.address"
                            class="soft-input mt-2"
                            :class="{ 'border-red-400': form.errors.address }"
                            placeholder="Barangay, Municipality / City, Province"
                        />
                        <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">{{ form.errors.address }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs uppercase tracking-[0.3em] text-slate-500">Purpose of Visit</label>
                        <input
                            v-model="form.purpose"
                            class="soft-input mt-2"
                            placeholder="Purpose"
                            :class="{ 'border-red-400': form.errors.purpose }"
                        />
                        <p v-if="form.errors.purpose" class="mt-1 text-xs text-red-600">{{ form.errors.purpose }}</p>
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-3">
                        <button type="submit" class="soft-button w-full sm:w-auto" :disabled="form.processing">
                            {{ form.processing ? 'Saving…' : 'Save Entry' }}
                        </button>
                        <button type="button" class="soft-button-light w-full sm:w-auto" @click="form.reset()">
                            Clear
                        </button>
                    </div>
                </form>
            </SectionCard>

            <SectionCard
                title="Today's Visitors"
                eyebrow="Monitoring"
                subtitle="Overview of visitors recorded today."
            >
                <p v-if="visitors.length === 0" class="text-sm text-slate-500 py-4 text-center">No visitors recorded today.</p>
                <div v-else class="space-y-3">
                    <div
                        v-for="visitor in visitors"
                        :key="visitor.id"
                        class="card-shell flex flex-wrap items-center justify-between gap-4 px-4 py-3"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ visitor.name }}</p>
                            <p class="text-xs text-slate-500">{{ visitor.purpose }}</p>
                            <p v-if="visitor.address !== '—'" class="text-xs text-slate-400">{{ visitor.address }}</p>
                            <p v-if="visitor.contact !== '—'" class="text-xs text-slate-400">{{ visitor.contact }}</p>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            <p>Time In: {{ visitor.timeIn }}</p>
                            <p v-if="visitor.timeOut">Time Out: {{ visitor.timeOut }}</p>
                            <span v-else class="inline-block mt-1 rounded-full px-2 py-0.5 bg-emerald-100 text-emerald-700 font-semibold">
                                Still inside
                            </span>
                        </div>
                    </div>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

defineProps({
    visitors: { type: Array, default: () => [] },
});

const form = useForm({
    visitor_name:  '',
    visitor_phone: '',
    address:       '',
    purpose:       '',
});

function submit() {
    form.post('/visitors', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>
