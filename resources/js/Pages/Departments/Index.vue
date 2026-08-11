<template>
    <AppLayout>
        <Head title="Departments" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="Departments"
                kicker="Administration"
                subtitle="Create and manage offices/departments used across the DMS."
            >
                <template #actions>
                    <button
                        type="button"
                        class="soft-button"
                        @click="openCreate"
                    >
                        Add Department
                    </button>
                </template>
            </PageHeader>

            <SectionCard
                title="All Departments"
                eyebrow="Directory"
                :subtitle="(departments.total ?? 0) + ' department(s) registered.'"
            >
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <input
                        v-model="search"
                        class="soft-input w-full sm:w-72"
                        placeholder="Search by name or code"
                        @keyup.enter="applyFilters"
                    />
                    <button
                        type="button"
                        class="soft-button-light text-xs"
                        @click="applyFilters"
                    >
                        Search
                    </button>
                </div>

                <div class="overflow-hidden border border-slate-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-100/80 text-xs uppercase tracking-[0.3em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Members</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                    No departments yet. Click "Add Department" to create one.
                                </td>
                            </tr>
                            <tr v-for="dept in rows" :key="dept.id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ dept.name }}</p>
                                    <p v-if="dept.created" class="text-xs text-slate-400">
                                        Since {{ dept.created }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <span v-if="dept.code" class="soft-chip">{{ dept.code }}</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 max-w-md">
                                    <span v-if="dept.description">{{ dept.description }}</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-slate-700 font-semibold">
                                    {{ dept.user_count }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                        :class="dept.status === 'active'
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-rose-100 text-rose-700'"
                                    >
                                        {{ dept.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-1.5">
                                        <button
                                            type="button"
                                            class="soft-button-light text-xs"
                                            @click="openEdit(dept)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold uppercase tracking-wider px-3 py-1.5 border border-red-300 text-red-700 bg-white hover:bg-red-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                            :disabled="dept.user_count > 0"
                                            :title="dept.user_count > 0 ? 'Cannot delete — this department has members.' : 'Delete department'"
                                            @click="confirmDelete(dept)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :paginator="departments" />
            </SectionCard>
        </div>

        <!-- Delete confirmation modal -->
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
            @click.self="cancelDelete"
        >
            <div class="w-full max-w-md bg-white border-t-4 border-red-500 shadow-lg">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-red-600 font-semibold">
                            Confirm Delete
                        </p>
                        <h3 class="text-base font-bold text-red-700">
                            Delete Department
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-8 w-8 border border-gray-300 text-gray-500 hover:bg-gray-50"
                        aria-label="Close"
                        @click="cancelDelete"
                    >
                        <X class="h-4 w-4" :stroke-width="2.25" />
                    </button>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-700">
                        Are you sure you want to permanently delete
                        <span class="font-semibold text-[#003366]">"{{ deleteTarget.name }}"</span>?
                    </p>
                    <p class="mt-2 text-xs text-gray-500">
                        This action cannot be undone.
                    </p>
                </div>
                <div class="px-5 pb-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-3 py-1.5 border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        :disabled="deleting"
                        @click="cancelDelete"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 border border-red-600 bg-red-600 text-white text-xs font-bold uppercase tracking-wider hover:bg-red-700 transition disabled:opacity-60"
                        :disabled="deleting"
                        @click="doDelete"
                    >
                        {{ deleting ? 'Deleting…' : 'Delete Department' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Add / Edit modal -->
        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
            @click.self="closeModal"
        >
            <div class="w-full max-w-lg bg-white border-t-4 border-[#003366] shadow-lg">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
                            Administration
                        </p>
                        <h3 class="text-base font-bold text-[#003366]">
                            {{ isEdit ? 'Edit Department' : 'Add Department' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-8 w-8 border border-gray-300 text-gray-500 hover:bg-gray-50"
                        aria-label="Close"
                        @click="closeModal"
                    >
                        <X class="h-4 w-4" :stroke-width="2.25" />
                    </button>
                </div>

                <form class="p-5 space-y-4" @submit.prevent="submit">
                    <div
                        v-if="Object.keys(form.errors).length > 0"
                        class="border border-red-400 bg-red-50 px-3 py-2 text-xs text-red-700"
                    >
                        <p class="font-semibold">Please fix the following:</p>
                        <ul class="mt-1 list-disc list-inside space-y-0.5">
                            <li v-for="(msg, field) in form.errors" :key="field">{{ msg }}</li>
                        </ul>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="name">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            class="soft-input"
                            placeholder="e.g. Administration"
                            required
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="code">
                                Short Code
                            </label>
                            <input
                                id="code"
                                v-model="form.code"
                                class="soft-input uppercase"
                                placeholder="e.g. ADMIN"
                                maxlength="20"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="status">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select id="status" v-model="form.status" class="soft-select" required>
                                <option v-for="s in statusOptions" :key="s" :value="s">
                                    {{ capitalize(s) }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="description">
                            Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="soft-input min-h-[90px] resize-y"
                            placeholder="Brief description of this department's function"
                        ></textarea>
                    </div>

                    <div class="border-t border-gray-200 pt-3 flex justify-end gap-2">
                        <button
                            type="button"
                            class="px-3 py-1.5 border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                            :disabled="form.processing"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="soft-button text-xs"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving…' : isEdit ? 'Save Changes' : 'Create Department' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { X } from "@lucide/vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import Pagination from "../../Components/Pagination.vue";

const props = defineProps({
    departments: { type: Object, default: () => ({ data: [] }) },
    statusOptions: { type: Array, default: () => ["active", "inactive"] },
    filters: { type: Object, default: () => ({ q: "" }) },
});

const rows = computed(() => props.departments?.data ?? []);
const search = ref(props.filters.q ?? "");

watch(
    () => props.filters.q,
    (value) => {
        search.value = value ?? "";
    }
);

function applyFilters() {
    router.get(
        "/departments",
        { q: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

const modalOpen = ref(false);
const editing = ref(null);
const isEdit = computed(() => editing.value !== null);

const deleteTarget = ref(null);
const deleting = ref(false);

function confirmDelete(dept) {
    deleteTarget.value = dept;
}

function cancelDelete() {
    if (deleting.value) return;
    deleteTarget.value = null;
}

function doDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(`/departments/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}

const form = useForm({
    name: "",
    code: "",
    description: "",
    status: "active",
});

function capitalize(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : "";
}

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.status = "active";
    modalOpen.value = true;
}

function openEdit(dept) {
    editing.value = dept;
    form.clearErrors();
    form.name = dept.name;
    form.code = dept.code || "";
    form.description = dept.description || "";
    form.status = dept.status;
    modalOpen.value = true;
}

function closeModal() {
    if (form.processing) return;
    modalOpen.value = false;
    editing.value = null;
}

function afterSubmit() {
    modalOpen.value = false;
    editing.value = null;
    form.reset();
}

function submit() {
    if (isEdit.value) {
        form.put(`/departments/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: afterSubmit,
        });
    } else {
        form.post("/departments", {
            preserveScroll: true,
            onSuccess: afterSubmit,
        });
    }
}

function onEsc(e) {
    if (e.key !== "Escape") return;
    if (deleteTarget.value) cancelDelete();
    else if (modalOpen.value) closeModal();
}

watch(
    () => modalOpen.value || deleteTarget.value !== null,
    (open) => {
        if (typeof document === "undefined") return;
        document.body.style.overflow = open ? "hidden" : "";
        if (open) window.addEventListener("keydown", onEsc);
        else window.removeEventListener("keydown", onEsc);
    }
);

onBeforeUnmount(() => {
    if (typeof document !== "undefined") document.body.style.overflow = "";
    window.removeEventListener("keydown", onEsc);
});
</script>
