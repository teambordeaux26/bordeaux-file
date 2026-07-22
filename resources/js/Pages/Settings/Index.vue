<template>
    <AppLayout>
        <Head title="Settings" />
        <div class="space-y-8 fade-up">
            <PageHeader
                title="System Settings"
                kicker="Administration"
                subtitle="Configure office contact details, public search access, and employee page permissions."
            />

            <form @submit.prevent="submit" class="space-y-6">
                <SectionCard
                    title="Contact & Office Hours"
                    eyebrow="Public Info"
                    subtitle="Shown on the landing page, guest portal, and request status pages."
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                Hotline Number
                            </label>
                            <input v-model="form.hotline" class="soft-input" placeholder="(052) 555-0198" />
                            <p v-if="form.errors.hotline" class="mt-1 text-xs text-red-600">{{ form.errors.hotline }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                Office Hours
                            </label>
                            <input
                                v-model="form.office_hours"
                                class="soft-input"
                                placeholder="Monday to Friday, 8:00 AM – 5:00 PM"
                            />
                            <p v-if="form.errors.office_hours" class="mt-1 text-xs text-red-600">{{ form.errors.office_hours }}</p>
                        </div>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Public Document Search"
                    eyebrow="Get Documents"
                    subtitle="Control what the public can search on the Get Documents page."
                >
                    <div class="space-y-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-600 mb-3">
                                Searchable Fields
                            </p>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <label
                                    v-for="(label, key) in publicFieldOptions"
                                    :key="key"
                                    class="flex items-center gap-2 rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm"
                                >
                                    <input
                                        v-model="form.public_search_fields"
                                        type="checkbox"
                                        :value="key"
                                        class="rounded border-gray-300 text-[#003366]"
                                    />
                                    <span>{{ label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.public_search_fields" class="mt-1 text-xs text-red-600">
                                {{ form.errors.public_search_fields }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                Public Categories
                            </p>
                            <p class="text-xs text-gray-500 mb-3">
                                Leave all unchecked to allow every category. Select specific categories to limit public search.
                            </p>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <label
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="flex items-center gap-2 rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm"
                                >
                                    <input
                                        v-model="form.public_search_categories"
                                        type="checkbox"
                                        :value="cat.id"
                                        class="rounded border-gray-300 text-[#003366]"
                                    />
                                    <span>{{ cat.name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Employee Page Permissions"
                    eyebrow="Access Control"
                    subtitle="Choose which pages employees can access. Admins always have full access."
                >
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="(label, key) in pageOptions"
                            :key="key"
                            class="flex items-center gap-2 rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm"
                        >
                            <input
                                v-model="form.employee_pages"
                                type="checkbox"
                                :value="key"
                                class="rounded border-gray-300 text-[#003366]"
                            />
                            <span>{{ label }}</span>
                        </label>
                    </div>
                    <p v-if="form.errors.employee_pages" class="mt-2 text-xs text-red-600">
                        {{ form.errors.employee_pages }}
                    </p>
                </SectionCard>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="soft-button" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save Settings' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

const props = defineProps({
    settings:           { type: Object, required: true },
    pageOptions:        { type: Object, required: true },
    publicFieldOptions: { type: Object, required: true },
    categories:         { type: Array, default: () => [] },
});

const form = useForm({
    hotline:                  props.settings.hotline,
    office_hours:             props.settings.office_hours,
    public_search_fields:     [...props.settings.public_search_fields],
    public_search_categories: [...props.settings.public_search_categories],
    employee_pages:           [...props.settings.employee_pages],
});

function submit() {
    form.put('/settings');
}
</script>
