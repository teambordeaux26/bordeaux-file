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
                    <button class="soft-button">Add User</button>
                    <button class="soft-button-light">Export List</button>
                </template>
            </PageHeader>

            <SectionCard
                title="System Accounts"
                eyebrow="Users"
                subtitle="Active staff and authorized personnel for the Vice Mayor's Office."
            >
                <div class="mb-4">
                    <input
                        v-model="search"
                        class="soft-input w-full sm:w-72"
                        placeholder="Search by name or email"
                    />
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
                            <tr v-if="filtered.length === 0">
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">No users found.</td>
                            </tr>
                            <tr v-for="user in filtered" :key="user.id">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ user.name }}</p>
                                    <p class="text-xs text-slate-400">{{ user.email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="soft-chip">{{ user.role }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ user.department }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="user.status === 'Active'
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-rose-100 text-rose-700'"
                                    >
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ user.lastLogin }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button class="soft-button-light">Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

const props = defineProps({
    users: { type: Array, default: () => [] },
});

const search = ref('');

const filtered = computed(() =>
    props.users.filter(u =>
        !search.value
        || u.name.toLowerCase().includes(search.value.toLowerCase())
        || u.email.toLowerCase().includes(search.value.toLowerCase())
    )
);
</script>
