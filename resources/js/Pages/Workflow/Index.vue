<template>
    <AppLayout>
        <Head title="Workflow" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Document Workflow"
                kicker="Operations"
                subtitle="Track every document through its stages and see who can move it forward."
            >
                <template #actions>
                    <button
                        type="button"
                        class="soft-button-light inline-flex items-center gap-2"
                        @click="helpOpen = true"
                    >
                        <HelpCircle class="h-4 w-4" :stroke-width="2" />
                        How it works
                    </button>
                </template>
            </PageHeader>

            <!-- Filters -->
            <SectionCard
                title="Filters"
                eyebrow="Track a File"
                subtitle="Search and narrow the board by category, priority, or owner."
            >
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                            Search
                        </label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                                :stroke-width="2"
                            />
                            <input
                                v-model="filters.q"
                                type="search"
                                placeholder="Title, tracking, reference, or owner…"
                                class="soft-input !pl-10"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                            Category
                        </label>
                        <select v-model="filters.category_id" class="soft-select">
                            <option :value="null">All categories</option>
                            <option
                                v-for="c in filterOptions.categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                            Priority
                        </label>
                        <select v-model="filters.priority" class="soft-select">
                            <option :value="null">All priorities</option>
                            <option
                                v-for="p in filterOptions.priorities"
                                :key="p"
                                :value="p"
                            >
                                {{ p }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                            Currently handling
                        </label>
                        <select v-model="filters.handler_id" class="soft-select">
                            <option :value="null">Anyone</option>
                            <option
                                v-for="h in filterOptions.handlers"
                                :key="h.id"
                                :value="h.id"
                            >
                                {{ h.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">
                            Owner
                        </label>
                        <select v-model="filters.owner_id" class="soft-select">
                            <option :value="null">All owners</option>
                            <option
                                v-for="o in filterOptions.owners"
                                :key="o.id"
                                :value="o.id"
                            >
                                {{ o.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                    <p>
                        Showing
                        <span class="font-bold text-[#003366]">{{ visibleCount }}</span>
                        of
                        <span class="font-bold text-[#003366]">{{ totalCount }}</span>
                        documents.
                    </p>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-gray-300 text-[11px] font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50 transition"
                        :disabled="!hasActiveFilters"
                        :class="!hasActiveFilters && 'opacity-50 cursor-not-allowed'"
                        @click="clearFilters"
                    >
                        <X class="h-3.5 w-3.5" :stroke-width="2.25" />
                        Clear Filters
                    </button>
                </div>
            </SectionCard>

            <!-- Kanban board -->
            <div class="-mx-2 overflow-x-auto pb-2">
                <div class="flex gap-4 px-2 min-w-max">
                    <div
                        v-for="stage in stages"
                        :key="stage.key"
                        class="w-72 shrink-0 bg-white border border-gray-300 border-t-4 shadow-sm flex flex-col"
                        :style="{ borderTopColor: colorHex(stage.color) }"
                    >
                        <!-- Column header -->
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
                                    {{ stage.kicker }}
                                </p>
                                <h3 class="text-sm font-bold text-[#003366]">
                                    {{ stage.label }}
                                </h3>
                            </div>
                            <span
                                class="inline-flex h-6 min-w-[24px] items-center justify-center rounded-full bg-[#003366] text-white text-[11px] font-bold px-1.5"
                            >
                                {{ filteredDocuments[stage.key]?.length ?? 0 }}
                            </span>
                        </div>

                        <!-- Cards -->
                        <div class="flex-1 p-3 space-y-3 min-h-[80px] max-h-[calc(100vh-360px)] overflow-y-auto">
                            <p
                                v-if="!filteredDocuments[stage.key] || filteredDocuments[stage.key].length === 0"
                                class="text-xs text-gray-400 text-center py-6"
                            >
                                {{ hasActiveFilters ? 'No matches here.' : 'No documents here.' }}
                            </p>

                            <div
                                v-for="doc in filteredDocuments[stage.key] || []"
                                :key="doc.id"
                                class="border border-gray-200 bg-gray-50 p-3"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900 leading-tight">
                                        {{ doc.title }}
                                    </p>
                                    <span
                                        v-if="doc.priority"
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="priorityClass(doc.priority)"
                                    >
                                        {{ doc.priority }}
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-500">
                                    {{ doc.tracking }} &bull; {{ doc.category }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-gray-500">
                                    Owner: <span class="font-medium text-gray-700">{{ doc.owner }}</span>
                                </p>
                                <p class="mt-0.5 text-[11px] text-[#003366]">
                                    Handling:
                                    <span class="font-semibold">{{ doc.handler || 'Unassigned' }}</span>
                                </p>
                                <p class="mt-1 text-[10px] text-gray-400">
                                    Updated {{ doc.updated }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <button
                                        v-if="doc.has_file && stage.key !== 'pending'"
                                        type="button"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1.5 border bg-white border-[#003366] text-[#003366] hover:bg-[#003366] hover:text-white transition"
                                        @click="openPreview(doc)"
                                    >
                                        <Eye class="h-3.5 w-3.5" :stroke-width="2.25" />
                                        View File
                                    </button>
                                    <Link
                                        v-if="stage.key !== 'pending'"
                                        :href="doc.view_url"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1.5 border bg-white border-gray-300 text-gray-700 hover:bg-gray-50 transition"
                                    >
                                        <FileText class="h-3.5 w-3.5" :stroke-width="2.25" />
                                        Details
                                    </Link>
                                    <Link
                                        v-if="doc.edit_url"
                                        :href="doc.edit_url"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1.5 border bg-white border-orange-400 text-orange-700 hover:bg-orange-50 transition"
                                    >
                                        <Pencil class="h-3.5 w-3.5" :stroke-width="2.25" />
                                        Revise
                                    </Link>
                                    <button
                                        v-for="a in doc.actions"
                                        :key="a.key"
                                        type="button"
                                        class="text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1.5 border transition"
                                        :class="variantClass(a.variant)"
                                        :disabled="processing === doc.id + '-' + a.key"
                                        @click="run(doc, a)"
                                    >
                                        {{ processing === doc.id + '-' + a.key ? '…' : a.label }}
                                    </button>
                                </div>
                                <p
                                    v-if="!doc.actions.length"
                                    class="mt-2 text-[10px] text-gray-400 italic"
                                >
                                    No action available for you here.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How it works modal -->
        <div
            v-if="helpOpen"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
            @click.self="helpOpen = false"
        >
            <div class="w-full max-w-2xl bg-white border-t-4 border-[#003366] shadow-lg">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
                            Roles &amp; Responsibilities
                        </p>
                        <h3 class="text-base font-bold text-[#003366]">
                            How this workflow works
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Only the responsible role for each stage sees action buttons on a card.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-8 w-8 border border-gray-300 text-gray-500 hover:bg-gray-50"
                        aria-label="Close help"
                        @click="helpOpen = false"
                    >
                        <X class="h-4 w-4" :stroke-width="2.25" />
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <ul class="grid gap-3 sm:grid-cols-1">
                        <li
                            v-for="(entry, i) in legend"
                            :key="i"
                            class="border-l-4 border-[#003366] bg-gray-50/60 px-3 py-2"
                        >
                            <p class="text-[11px] font-bold uppercase tracking-widest text-[#003366]">
                                {{ entry.role }}
                            </p>
                            <p class="mt-1 text-xs text-gray-700 leading-relaxed">
                                {{ entry.does }}
                            </p>
                        </li>
                    </ul>

                    <div class="border-t border-gray-200 pt-3 text-xs text-gray-500">
                        Signed in as
                        <span class="font-semibold text-[#003366]">{{ currentUser.name }}</span>
                        ({{ currentUser.role }})
                    </div>
                </div>

                <div class="px-5 pb-4 flex justify-end">
                    <button
                        type="button"
                        class="soft-button text-xs"
                        @click="helpOpen = false"
                    >
                        Got it
                    </button>
                </div>
            </div>
        </div>

        <!-- File preview modal -->
        <div
            v-if="previewDoc"
            class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4"
            @click.self="closePreview"
        >
            <div class="w-full max-w-5xl h-[90vh] bg-white border-t-4 border-[#003366] shadow-lg flex flex-col">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-3">
                    <div class="min-w-0">
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
                            {{ previewDoc.tracking }} &bull; {{ previewDoc.category }}
                        </p>
                        <h3 class="text-base font-bold text-[#003366] truncate">
                            {{ previewDoc.title }}
                        </h3>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            :href="previewDoc.download_url"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider px-2.5 py-1.5 border bg-white border-gray-300 text-gray-700 hover:bg-gray-50 transition"
                        >
                            <Download class="h-3.5 w-3.5" :stroke-width="2.25" />
                            Download
                        </a>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center h-8 w-8 border border-gray-300 text-gray-500 hover:bg-gray-50"
                            aria-label="Close preview"
                            @click="closePreview"
                        >
                            <X class="h-4 w-4" :stroke-width="2.25" />
                        </button>
                    </div>
                </div>
                <div class="flex-1 min-h-0 bg-gray-100">
                    <img
                        v-if="isImage(previewDoc.file_url)"
                        :src="previewDoc.file_url"
                        :alt="previewDoc.title"
                        class="w-full h-full object-contain"
                    />
                    <iframe
                        v-else
                        :src="previewDoc.file_url"
                        class="w-full h-full border-0"
                        :title="previewDoc.title"
                    ></iframe>
                </div>
            </div>
        </div>

        <!-- Remarks modal -->
        <div
            v-if="remarkFor"
            class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
            @click.self="cancelRemark"
        >
            <div class="w-full max-w-md bg-white border-t-4 border-[#003366] shadow-lg p-5">
                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
                    {{ remarkFor.stageLabel }}
                </p>
                <h3 class="text-base font-bold text-[#003366]">
                    {{ remarkFor.action.label }}
                </h3>
                <p class="mt-1 text-xs text-gray-500">
                    Add a short remark explaining this decision for the audit trail.
                </p>

                <textarea
                    v-model="remarkText"
                    rows="4"
                    class="mt-3 w-full border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                    placeholder="e.g. Please attach the signed cover letter and resubmit."
                ></textarea>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-3 py-1.5 border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        @click="cancelRemark"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="soft-button text-xs"
                        :disabled="processing !== null"
                        @click="confirmRemark"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Download, Eye, FileText, HelpCircle, Pencil, Search, X } from "@lucide/vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

const props = defineProps({
    stages: { type: Array, default: () => [] },
    documents: { type: Object, default: () => ({}) },
    legend: { type: Array, default: () => [] },
    currentUser: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const filterOptions = computed(() => ({
    categories: props.filters?.categories ?? [],
    owners: props.filters?.owners ?? [],
    handlers: props.filters?.handlers ?? [],
    priorities: props.filters?.priorities ?? [],
}));

const filters = reactive({
    q: "",
    category_id: null,
    priority: null,
    owner_id: null,
    handler_id: null,
});

const hasActiveFilters = computed(
    () =>
        !!filters.q.trim() ||
        filters.category_id !== null ||
        filters.priority !== null ||
        filters.owner_id !== null ||
        filters.handler_id !== null
);

function matches(doc) {
    if (filters.category_id !== null && doc.category_id !== filters.category_id && doc.category_parent_id !== filters.category_id) return false;
    if (filters.priority !== null && doc.priority !== filters.priority) return false;
    if (filters.owner_id !== null && doc.owner_id !== filters.owner_id) return false;
    if (filters.handler_id !== null && doc.handler_id !== filters.handler_id) return false;
    const q = filters.q.trim().toLowerCase();
    if (!q) return true;
    return [
        doc.title,
        doc.tracking,
        doc.reference,
        doc.owner,
        doc.handler,
        doc.reviewer,
        doc.category,
    ]
        .filter(Boolean)
        .some((v) => String(v).toLowerCase().includes(q));
}

const filteredDocuments = computed(() => {
    const out = {};
    for (const key of Object.keys(props.documents || {})) {
        out[key] = (props.documents[key] || []).filter(matches);
    }
    return out;
});

const totalCount = computed(() =>
    Object.values(props.documents || {}).reduce((sum, arr) => sum + arr.length, 0)
);

const visibleCount = computed(() =>
    Object.values(filteredDocuments.value).reduce((sum, arr) => sum + arr.length, 0)
);

function clearFilters() {
    filters.q = "";
    filters.category_id = null;
    filters.priority = null;
    filters.owner_id = null;
    filters.handler_id = null;
}

const processing = ref(null);
const remarkFor = ref(null);
const remarkText = ref("");
const previewDoc = ref(null);
const helpOpen = ref(false);

function openPreview(doc) {
    if (!doc.has_file) return;
    previewDoc.value = doc;
}

function closePreview() {
    previewDoc.value = null;
}

function isImage(url) {
    if (!url) return false;
    return /\.(png|jpe?g|gif|webp|bmp|svg)(\?|$)/i.test(url);
}

function onEsc(e) {
    if (e.key !== "Escape") return;
    if (previewDoc.value) closePreview();
    else if (remarkFor.value) cancelRemark();
    else if (helpOpen.value) helpOpen.value = false;
}

watch(
    () => previewDoc.value || remarkFor.value || helpOpen.value,
    (open) => {
        if (typeof document === "undefined") return;
        document.body.style.overflow = open ? "hidden" : "";
        if (open) {
            window.addEventListener("keydown", onEsc);
        } else {
            window.removeEventListener("keydown", onEsc);
        }
    }
);

onBeforeUnmount(() => {
    if (typeof document !== "undefined") {
        document.body.style.overflow = "";
    }
    window.removeEventListener("keydown", onEsc);
});

function priorityClass(priority) {
    if (priority === "Urgent") return "bg-red-100 text-red-700";
    if (priority === "High") return "bg-orange-100 text-orange-700";
    if (priority === "Low") return "bg-slate-100 text-slate-600";
    return "bg-blue-50 text-blue-700";
}

function variantClass(variant) {
    if (variant === "primary")
        return "bg-[#003366] text-white border-[#003366] hover:bg-[#002244]";
    if (variant === "warn")
        return "bg-white border-orange-400 text-orange-700 hover:bg-orange-50";
    if (variant === "danger")
        return "bg-white border-red-400 text-red-700 hover:bg-red-50";
    return "bg-white border-gray-300 text-gray-700 hover:bg-gray-50";
}

function colorHex(color) {
    const map = {
        slate: "#64748b",
        amber: "#f59e0b",
        blue: "#2563eb",
        indigo: "#4f46e5",
        emerald: "#10b981",
        green: "#16a34a",
        orange: "#ea580c",
        rose: "#e11d48",
    };
    return map[color] || "#003366";
}

function run(doc, action) {
    if (action.needs_remarks) {
        remarkFor.value = {
            doc,
            action,
            stageLabel: doc.title,
        };
        remarkText.value = "";
        return;
    }
    send(doc, action, null);
}

function send(doc, action, remarks) {
    processing.value = doc.id + "-" + action.key;
    router.put(
        `/workflow/${doc.id}/advance`,
        { action: action.key, remarks },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = null;
                remarkFor.value = null;
            },
        }
    );
}

function confirmRemark() {
    if (!remarkFor.value) return;
    send(remarkFor.value.doc, remarkFor.value.action, remarkText.value || null);
}

function cancelRemark() {
    remarkFor.value = null;
    remarkText.value = "";
}
</script>
