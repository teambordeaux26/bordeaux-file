<template>
    <AppLayout>
        <Head title="User Management" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="User Management"
                kicker="Administration"
                subtitle="Manage access rights, roles, and staff assignments across the office."
            >
                <template #actions>
                    <button
                        type="button"
                        class="soft-button"
                        @click="openCreate"
                    >
                        Add User
                    </button>
                </template>
            </PageHeader>

            <SectionCard
                title="System Accounts"
                eyebrow="Users"
                subtitle="Active staff and authorized personnel for the Vice Mayor's Office."
            >
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <input
                        v-model="search"
                        class="soft-input w-full sm:w-72"
                        placeholder="Search by name or email"
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
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Department</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Last Login</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-if="rows.length === 0">
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">No users found.</td>
                            </tr>
                            <tr v-for="user in rows" :key="user.id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ user.name }}</p>
                                    <p class="text-xs text-slate-400">{{ user.email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="soft-chip">{{ user.role_label }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ user.department || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="user.status === 'active'
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-rose-100 text-rose-700'"
                                    >
                                        {{ user.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ user.lastLogin }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        class="soft-button-light text-xs"
                                        @click="openEdit(user)"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :paginator="users" />
            </SectionCard>
        </div>

        <!-- Add / Edit User Modal -->
        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
            @click.self="closeModal"
        >
            <div class="w-full max-w-xl bg-white border-t-4 border-[#003366] shadow-lg max-h-[90vh] flex flex-col">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">
                            System Accounts
                        </p>
                        <h3 class="text-base font-bold text-[#003366]">
                            {{ isEdit ? 'Edit User' : 'Add New User' }}
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Emails are automatically issued under
                            <span class="font-semibold text-[#003366]">{{ emailDomain }}</span>.
                        </p>
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

                <form
                    class="p-5 space-y-4 overflow-y-auto"
                    @submit.prevent="submit"
                >
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
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            class="soft-input"
                            placeholder="e.g. Juan Dela Cruz"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="email_local">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-stretch border border-gray-300 bg-gray-50 focus-within:border-[#003366] focus-within:ring-1 focus-within:ring-[#003366]">
                            <input
                                id="email_local"
                                v-model="form.email_local"
                                type="text"
                                class="flex-1 border-0 bg-transparent px-3 py-2 text-sm text-gray-900 focus:outline-none"
                                placeholder="juan.delacruz"
                                autocomplete="off"
                                required
                                @input="normalizeLocal"
                            />
                            <span class="flex items-center px-3 bg-white border-l border-gray-300 text-xs font-semibold text-[#003366]">
                                {{ emailDomain }}
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-500">
                            Full address: <span class="font-semibold text-[#003366]">{{ fullEmail || '—' }}</span>
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="role">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select id="role" v-model="form.role" class="soft-select" required>
                                <option v-for="r in roleOptions" :key="r" :value="r">
                                    {{ capitalize(r) }}
                                </option>
                            </select>
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

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="department">
                                Department
                            </label>
                            <select
                                id="department"
                                v-model="form.department"
                                class="soft-select"
                                :disabled="departments.length === 0"
                            >
                                <option value="">— Select department —</option>
                                <option
                                    v-for="d in departments"
                                    :key="d.id"
                                    :value="d.name"
                                >
                                    {{ d.name }}
                                </option>
                            </select>
                            <p
                                v-if="departments.length === 0"
                                class="mt-1 text-[11px] text-orange-600"
                            >
                                No departments defined yet.
                                <Link href="/departments" class="font-semibold underline">
                                    Add one first
                                </Link>.
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="position">
                                Position
                            </label>
                            <input
                                id="position"
                                v-model="form.position"
                                class="soft-input"
                                placeholder="e.g. Records Officer"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="phone">
                            Contact Number
                        </label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            class="soft-input"
                            placeholder="e.g. 09XX XXX XXXX"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="password">
                                {{ isEdit ? 'New Password' : 'Temporary Password' }}
                                <span v-if="!isEdit" class="text-red-500">*</span>
                                <span v-else class="normal-case tracking-normal font-normal text-gray-400">(optional)</span>
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="text"
                                class="soft-input font-mono"
                                minlength="8"
                                :required="!isEdit"
                            />
                            <button
                                type="button"
                                class="mt-1 text-[11px] font-semibold text-[#003366] hover:underline"
                                @click="regeneratePassword"
                            >
                                Generate a strong password
                            </button>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="password_confirmation">
                                Confirm Password
                                <span v-if="!isEdit || form.password" class="text-red-500">*</span>
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="text"
                                class="soft-input font-mono"
                                minlength="8"
                                :required="!isEdit || !!form.password"
                            />
                        </div>
                    </div>
                </form>

                <div class="border-t border-gray-200 px-5 py-3 flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-3 py-1.5 border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        :disabled="form.processing"
                        @click="closeModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="soft-button text-xs"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <template v-if="form.processing">
                            {{ isEdit ? 'Saving…' : 'Creating…' }}
                        </template>
                        <template v-else>
                            {{ isEdit ? 'Save Changes' : 'Create User' }}
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { X } from "@lucide/vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import Pagination from "../../Components/Pagination.vue";

const props = defineProps({
    users: { type: Object, default: () => ({ data: [] }) },
    departments: { type: Array, default: () => [] },
    emailDomain: { type: String, default: "@oas-dms.com" },
    roleOptions: { type: Array, default: () => ["admin", "employee"] },
    statusOptions: { type: Array, default: () => ["active", "inactive"] },
    filters: { type: Object, default: () => ({ q: "" }) },
});

const rows = computed(() => props.users?.data ?? []);
const search = ref(props.filters.q ?? "");

watch(
    () => props.filters.q,
    (value) => {
        search.value = value ?? "";
    }
);

function applyFilters() {
    router.get(
        "/users",
        { q: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

const modalOpen = ref(false);
const editing = ref(null);
const isEdit = computed(() => editing.value !== null);

const form = useForm({
    name: "",
    email_local: "",
    role: "employee",
    department: "",
    position: "",
    phone: "",
    status: "active",
    password: "",
    password_confirmation: "",
});

const fullEmail = computed(() =>
    form.email_local ? form.email_local.toLowerCase() + props.emailDomain : ""
);

function capitalize(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : "";
}

function normalizeLocal(e) {
    form.email_local = e.target.value
        .toLowerCase()
        .replace(/[^a-z0-9._-]/g, "");
}

function regeneratePassword() {
    const chars = "abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%";
    let out = "";
    for (let i = 0; i < 12; i++) {
        out += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    form.password = out;
    form.password_confirmation = out;
}

function resetFormDefaults() {
    form.reset();
    form.clearErrors();
    form.role = "employee";
    form.status = "active";
    form.department = "";
    form.position = "";
    form.phone = "";
    form.password = "";
    form.password_confirmation = "";
}

function openCreate() {
    editing.value = null;
    resetFormDefaults();
    regeneratePassword();
    modalOpen.value = true;
}

function openEdit(user) {
    editing.value = user;
    form.clearErrors();
    form.name = user.name ?? "";
    form.email_local = user.email_local ?? "";
    form.role = user.role ?? "employee";
    form.department = user.department ?? "";
    form.position = user.position ?? "";
    form.phone = user.phone ?? "";
    form.status = user.status ?? "active";
    form.password = "";
    form.password_confirmation = "";
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
    form.clearErrors();
}

function submit() {
    if (isEdit.value) {
        form.put(`/users/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: afterSubmit,
        });
        return;
    }

    form.post("/users", {
        preserveScroll: true,
        onSuccess: afterSubmit,
    });
}

function onEsc(e) {
    if (e.key === "Escape" && modalOpen.value) closeModal();
}

watch(modalOpen, (open) => {
    if (typeof document === "undefined") return;
    document.body.style.overflow = open ? "hidden" : "";
    if (open) window.addEventListener("keydown", onEsc);
    else window.removeEventListener("keydown", onEsc);
});

onBeforeUnmount(() => {
    if (typeof document !== "undefined") {
        document.body.style.overflow = "";
    }
    window.removeEventListener("keydown", onEsc);
});
</script>
