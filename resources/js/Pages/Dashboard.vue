<template>
    <AppLayout>
        <Head title="Dashboard" />
        <div class="space-y-6 fade-up">
            <PageHeader
                title="System Dashboard"
                kicker="Overview"
                subtitle="Monitor document status, visitor activity, and upcoming office events from a single command view."
            >
                <template #actions>
                    <Link href="/documents/upload" class="soft-button">Upload Document</Link>
                    <button type="button" class="soft-button-light" @click="showEventForm = true">
                        Add Event
                    </button>
                </template>
            </PageHeader>

            <div class="grid gap-4 md:grid-cols-3">
                <StatCard
                    v-for="stat in stats"
                    :key="stat.label"
                    :label="stat.label"
                    :value="stat.value"
                    :note="stat.note"
                />
            </div>

            <div class="grid gap-6 xl:grid-cols-5">
                <SectionCard
                    class="xl:col-span-3"
                    title="Office Calendar"
                    eyebrow="Schedule"
                    subtitle="Meetings, deadlines, and office events for the selected month."
                >
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            class="soft-button-light text-xs"
                            @click="goMonth(calendar.prev)"
                        >
                            Previous
                        </button>
                        <p class="text-sm font-bold text-[#003366]">{{ calendar.label }}</p>
                        <button
                            type="button"
                            class="soft-button-light text-xs"
                            @click="goMonth(calendar.next)"
                        >
                            Next
                        </button>
                    </div>

                    <div class="grid grid-cols-7 text-center text-[10px] font-bold uppercase tracking-widest text-gray-500">
                        <div v-for="day in calendar.weekdays" :key="day" class="py-2">{{ day }}</div>
                    </div>
                    <div class="grid grid-cols-7 border-l border-t border-gray-200">
                        <button
                            v-for="day in calendar.days"
                            :key="day.date"
                            type="button"
                            class="min-h-[5.5rem] border-b border-r border-gray-200 p-1.5 text-left align-top"
                            :class="[
                                day.inMonth ? 'bg-white' : 'bg-gray-50 text-gray-400',
                                day.isToday ? 'ring-2 ring-inset ring-[#003366]' : '',
                                showDayModal && selectedDate === day.date ? 'bg-blue-50' : '',
                                day.events.length ? '!cursor-pointer hover:bg-blue-50' : '!cursor-default',
                            ]"
                            @click="openDay(day.date)"
                        >
                            <span class="text-xs font-bold" :class="day.isToday ? 'text-[#003366]' : ''">
                                {{ day.day }}
                            </span>
                            <div class="mt-1 space-y-0.5">
                                <p
                                    v-for="event in day.events.slice(0, 2)"
                                    :key="event.id"
                                    class="truncate rounded px-1 py-0.5 text-[10px] font-semibold"
                                    :class="eventTypeClass(event.type)"
                                >
                                    {{ event.title }}
                                </p>
                                <p
                                    v-if="day.events.length > 2"
                                    class="text-[10px] text-gray-400"
                                >
                                    +{{ day.events.length - 2 }} more
                                </p>
                            </div>
                        </button>
                    </div>
                </SectionCard>

                <SectionCard
                    class="xl:col-span-2"
                    title="Upcoming Reminders"
                    eyebrow="Next 14 days"
                    subtitle="Meetings, events, and document retention deadlines."
                >
                    <p v-if="reminders.length === 0" class="text-sm text-slate-500 py-4 text-center">
                        No upcoming events or deadlines.
                    </p>
                    <div v-else class="space-y-2">
                        <component
                            :is="item.url ? Link : 'div'"
                            v-for="item in pagedReminders"
                            :key="item.id"
                            :href="item.url"
                            class="block border border-gray-200 bg-white px-4 py-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ item.title }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ item.typeLabel }} · {{ item.when }}
                                        <span v-if="item.time"> · {{ item.time }}</span>
                                    </p>
                                    <p v-if="item.detail" class="text-xs text-gray-400 mt-0.5">{{ item.detail }}</p>
                                </div>
                                <span
                                    class="shrink-0 rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                    :class="eventTypeClass(item.type)"
                                >
                                    {{ item.typeLabel }}
                                </span>
                            </div>
                        </component>

                        <div
                            v-if="reminders.length > reminderPageSize"
                            class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p class="text-xs text-gray-500">
                                Showing
                                <span class="font-semibold text-gray-700">{{ reminderFrom }}</span>
                                –
                                <span class="font-semibold text-gray-700">{{ reminderTo }}</span>
                                of
                                <span class="font-semibold text-gray-700">{{ reminders.length }}</span>
                            </p>
                            <div class="flex flex-wrap items-center gap-1">
                                <button
                                    type="button"
                                    class="min-w-[2rem] px-2.5 py-1.5 text-xs font-semibold border transition"
                                    :class="reminderPage === 1
                                        ? 'border-gray-200 text-gray-300 cursor-default bg-white'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-blue-50 hover:border-[#003366]'"
                                    :disabled="reminderPage === 1"
                                    @click="reminderPage--"
                                >
                                    Prev
                                </button>
                                <button
                                    v-for="page in reminderPageCount"
                                    :key="page"
                                    type="button"
                                    class="min-w-[2rem] px-2.5 py-1.5 text-xs font-semibold border transition"
                                    :class="page === reminderPage
                                        ? 'border-[#003366] bg-[#003366] text-white'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-blue-50 hover:border-[#003366]'"
                                    @click="reminderPage = page"
                                >
                                    {{ page }}
                                </button>
                                <button
                                    type="button"
                                    class="min-w-[2rem] px-2.5 py-1.5 text-xs font-semibold border transition"
                                    :class="reminderPage === reminderPageCount
                                        ? 'border-gray-200 text-gray-300 cursor-default bg-white'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-blue-50 hover:border-[#003366]'"
                                    :disabled="reminderPage === reminderPageCount"
                                    @click="reminderPage++"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </SectionCard>
            </div>

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

        <div
            v-if="showDayModal"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
            @click.self="closeDayModal"
        >
            <div class="w-full max-w-lg bg-white border-t-4 border-[#003366] shadow-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Schedule</p>
                        <h3 class="text-base font-bold text-[#003366]">{{ formatSelectedDate }}</h3>
                    </div>
                    <button type="button" class="h-8 w-8 border border-gray-300 text-gray-500" @click="closeDayModal">×</button>
                </div>
                <div class="p-5 space-y-3">
                    <p v-if="selectedDayEvents.length === 0" class="text-sm text-gray-500 text-center py-4">
                        No events or meetings on this day.
                    </p>
                    <div
                        v-for="event in selectedDayEvents"
                        :key="event.id"
                        class="flex items-start justify-between gap-3 border border-gray-200 bg-gray-50 px-3 py-2"
                    >
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ event.title }}</p>
                            <p class="text-xs text-gray-500">
                                {{ event.typeLabel }}
                                <span v-if="event.time"> · {{ event.time }}</span>
                            </p>
                            <p v-if="event.detail" class="text-xs text-gray-500 mt-0.5">{{ event.detail }}</p>
                        </div>
                        <button
                            v-if="event.can_delete"
                            type="button"
                            class="text-xs font-semibold text-red-700 hover:underline"
                            @click="removeEvent(event)"
                        >
                            Remove
                        </button>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="soft-button-light" @click="closeDayModal">Close</button>
                        <button type="button" class="soft-button" @click="addEventForSelectedDay">Add Event</button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showEventForm"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
            @click.self="showEventForm = false"
        >
            <div class="w-full max-w-lg bg-white border-t-4 border-[#003366] shadow-lg">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Calendar</p>
                        <h3 class="text-base font-bold text-[#003366]">Add Event or Deadline</h3>
                    </div>
                    <button type="button" class="h-8 w-8 border border-gray-300 text-gray-500" @click="showEventForm = false">×</button>
                </div>
                <form class="p-5 space-y-4" @submit.prevent="submitEvent">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">Title</label>
                        <input v-model="eventForm.title" class="soft-input" required />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">Type</label>
                            <select v-model="eventForm.type" class="soft-select" required>
                                <option value="meeting">Meeting</option>
                                <option value="deadline">Deadline</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">Starts</label>
                            <input v-model="eventForm.starts_at" type="datetime-local" class="soft-input" required />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">Ends (optional)</label>
                        <input v-model="eventForm.ends_at" type="datetime-local" class="soft-input" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">Notes</label>
                        <textarea v-model="eventForm.description" class="soft-input min-h-[80px]" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="soft-button-light" @click="showEventForm = false">Cancel</button>
                        <button type="submit" class="soft-button" :disabled="eventForm.processing">
                            {{ eventForm.processing ? "Saving…" : "Save Event" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "../Layouts/AppLayout.vue";
import PageHeader from "../Components/PageHeader.vue";
import StatCard from "../Components/StatCard.vue";
import SectionCard from "../Components/SectionCard.vue";

const props = defineProps({
    stats:     { type: Array, default: () => [] },
    approvals: { type: Array, default: () => [] },
    activity:  { type: Array, default: () => [] },
    calendar:  { type: Object, required: true },
    reminders: { type: Array, default: () => [] },
});

const quickActions = [
    { label: "Log Visitor",          note: "Front desk", href: "/visitors" },
    { label: "Generate Certificate", note: "COA module", href: "/certificates" },
    { label: "Workflow Board",       note: "Stages",     href: "/workflow" },
    { label: "Returned Documents",   note: "Revisions",  href: "/documents/returned" },
];

const reminderPageSize = 5;
const reminderPage = ref(1);

const reminderPageCount = computed(() =>
    Math.max(1, Math.ceil((props.reminders?.length || 0) / reminderPageSize))
);

const pagedReminders = computed(() => {
    const start = (reminderPage.value - 1) * reminderPageSize;
    return (props.reminders || []).slice(start, start + reminderPageSize);
});

const reminderFrom = computed(() =>
    props.reminders.length === 0 ? 0 : (reminderPage.value - 1) * reminderPageSize + 1
);

const reminderTo = computed(() =>
    Math.min(reminderPage.value * reminderPageSize, props.reminders.length)
);

watch(
    () => props.reminders.length,
    () => {
        if (reminderPage.value > reminderPageCount.value) {
            reminderPage.value = reminderPageCount.value;
        }
    }
);

const showEventForm = ref(false);
const showDayModal = ref(false);
const selectedDate = ref("");
const eventForm = useForm({
    title: "",
    type: "meeting",
    starts_at: "",
    ends_at: "",
    description: "",
});

const selectedDayEvents = computed(() => {
    const day = (props.calendar.days || []).find((item) => item.date === selectedDate.value);
    return day?.events ?? [];
});

const formatSelectedDate = computed(() => {
    if (!selectedDate.value) return "";
    return new Date(`${selectedDate.value}T00:00:00`).toLocaleDateString(undefined, {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric",
    });
});

function eventTypeClass(type) {
    if (type === "meeting") return "bg-blue-100 text-blue-800";
    if (type === "deadline") return "bg-amber-100 text-amber-800";
    return "bg-gray-100 text-gray-700";
}

function openDay(date) {
    selectedDate.value = date;
    showDayModal.value = true;
}

function closeDayModal() {
    showDayModal.value = false;
}

function addEventForSelectedDay() {
    showDayModal.value = false;
    showEventForm.value = true;
    if (selectedDate.value) {
        eventForm.starts_at = `${selectedDate.value}T09:00`;
    }
}

function goMonth(month) {
    closeDayModal();
    router.get("/dashboard", { calendar: month }, { preserveScroll: true, preserveState: true });
}

function submitEvent() {
    eventForm.post("/events", {
        preserveScroll: true,
        onSuccess: () => {
            showEventForm.value = false;
            eventForm.reset();
            eventForm.type = "meeting";
        },
    });
}

function removeEvent(event) {
    if (!confirm(`Remove “${event.title}”?`)) return;
    router.delete(`/events/${event.id}`, { preserveScroll: true });
}
</script>
