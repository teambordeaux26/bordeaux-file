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
            </form>

            <div class="space-y-6">
                <SectionCard
                    title="Request Types"
                    eyebrow="Guest Form"
                    subtitle="These options are stored in the database. Add, edit, or remove a type to update the public request form immediately."
                >
                    <div class="space-y-5">
                        <ol class="grid gap-3 text-sm text-gray-700 sm:grid-cols-3">
                            <li class="rounded border border-gray-200 bg-white px-3 py-2">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">1. Name</p>
                                <p class="mt-1">What appears in the dropdown.</p>
                            </li>
                            <li class="rounded border border-gray-200 bg-white px-3 py-2">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">2. Purpose</p>
                                <p class="mt-1">Why this request exists. Saved with the request and printed on a certificate if one is issued.</p>
                            </li>
                            <li class="rounded border border-gray-200 bg-white px-3 py-2">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">3. Office action</p>
                                <p class="mt-1">Issue a Certificate of Appearance, or send back a document file.</p>
                            </li>
                        </ol>

                        <div class="rounded border border-[#003366]/20 bg-[#003366]/5 px-4 py-3">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-[#003366]">Guest dropdown preview</p>
                            <select class="soft-select mt-2 bg-white" disabled>
                                <option>— Select a request type —</option>
                                <option v-for="type in visibleRequestTypes" :key="type.id ?? type.name">
                                    {{ type.name || 'Untitled option' }}
                                </option>
                            </select>
                            <p v-if="visibleRequestTypes.length === 0" class="mt-2 text-xs text-amber-700">
                                No options are set to “Show on the request form.” Guests will not see a dropdown until one is visible.
                            </p>
                            <p class="mt-2 text-xs text-gray-600">
                                Add and Remove write to the database right away. Guests only see types set to “Show on the request form.”
                            </p>
                        </div>

                        <div
                            v-for="(type, index) in types"
                            :key="type.id"
                            class="rounded border border-gray-300 bg-white p-4 space-y-4"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-[#003366]">
                                    Option {{ index + 1 }}
                                    <span
                                        class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                        :class="type.visibility === 'shown' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600'"
                                    >
                                        {{ type.visibility === 'shown' ? 'Shown on form' : 'Hidden' }}
                                    </span>
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="soft-button-light text-xs"
                                        :disabled="index === 0 || typesBusy"
                                        @click="moveType(index, -1)"
                                    >
                                        Move up
                                    </button>
                                    <button
                                        type="button"
                                        class="soft-button-light text-xs"
                                        :disabled="index === types.length - 1 || typesBusy"
                                        @click="moveType(index, 1)"
                                    >
                                        Move down
                                    </button>
                                    <button
                                        type="button"
                                        class="soft-button-light text-xs"
                                        :disabled="types.length === 1 || typesBusy"
                                        @click="removeType(type)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                        Name in the dropdown
                                    </label>
                                    <input
                                        v-model="type.name"
                                        class="soft-input"
                                        placeholder="e.g. Certificate of Appearance"
                                        :disabled="typesBusy"
                                        @blur="saveType(type)"
                                        @keydown.enter.prevent="saveType(type)"
                                    />
                                    <p class="mt-1 text-[11px] text-gray-500">Guests choose this on the request form.</p>
                                    <p v-if="fieldError(type, 'name')" class="mt-1 text-xs text-red-600">
                                        {{ fieldError(type, 'name') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                        Purpose of this request
                                    </label>
                                    <input
                                        v-model="type.purpose"
                                        class="soft-input"
                                        placeholder="e.g. Official appearance at the Office of the Vice Mayor"
                                        :disabled="typesBusy"
                                        @blur="saveType(type)"
                                        @keydown.enter.prevent="saveType(type)"
                                    />
                                    <p class="mt-1 text-[11px] text-gray-500">
                                        Shown after they pick the name. Stored with the request.
                                    </p>
                                    <p v-if="fieldError(type, 'purpose')" class="mt-1 text-xs text-red-600">
                                        {{ fieldError(type, 'purpose') }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                        When the office completes this
                                    </label>
                                    <select
                                        v-model="type.fulfillment"
                                        class="soft-select"
                                        :disabled="typesBusy"
                                        @change="saveType(type)"
                                    >
                                        <option value="certificate">Issue a Certificate of Appearance</option>
                                        <option value="file">Send a document or file</option>
                                    </select>
                                    <p class="mt-1 text-[11px] text-gray-500">
                                        {{
                                            type.fulfillment === 'certificate'
                                                ? 'Staff will e-sign and generate a certificate.'
                                                : 'Staff will upload a file and email it to the requester.'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                        Show on the request form
                                    </label>
                                    <select
                                        v-model="type.visibility"
                                        class="soft-select"
                                        :disabled="typesBusy"
                                        @change="saveType(type)"
                                    >
                                        <option value="shown">Yes — guests can select this</option>
                                        <option value="hidden">No — hide it for now</option>
                                    </select>
                                    <p class="mt-1 text-[11px] text-gray-500">
                                        Hidden options stay in Settings and can be shown again later.
                                    </p>
                                    <p v-if="fieldError(type, 'is_active')" class="mt-1 text-xs text-red-600">
                                        {{ fieldError(type, 'is_active') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="showDraft" class="rounded border border-dashed border-[#003366] bg-white p-4 space-y-4">
                            <p class="text-sm font-semibold text-[#003366]">New request type</p>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                        Name in the dropdown
                                    </label>
                                    <input
                                        v-model="draft.name"
                                        class="soft-input"
                                        placeholder="e.g. Barangay Endorsement"
                                        @keydown.enter.prevent="saveDraft"
                                    />
                                    <p v-if="draft.errors.name" class="mt-1 text-xs text-red-600">{{ draft.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                        Purpose of this request
                                    </label>
                                    <input
                                        v-model="draft.purpose"
                                        class="soft-input"
                                        placeholder="e.g. Endorsement from the barangay captain"
                                        @keydown.enter.prevent="saveDraft"
                                    />
                                    <p v-if="draft.errors.purpose" class="mt-1 text-xs text-red-600">{{ draft.errors.purpose }}</p>
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                        When the office completes this
                                    </label>
                                    <select v-model="draft.fulfillment" class="soft-select">
                                        <option value="certificate">Issue a Certificate of Appearance</option>
                                        <option value="file">Send a document or file</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                                        Show on the request form
                                    </label>
                                    <select v-model="draft.visibility" class="soft-select">
                                        <option value="shown">Yes — guests can select this</option>
                                        <option value="hidden">No — hide it for now</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="soft-button text-xs" :disabled="draft.processing" @click="saveDraft">
                                    {{ draft.processing ? 'Saving…' : 'Save to database' }}
                                </button>
                                <button type="button" class="soft-button-light text-xs" :disabled="draft.processing" @click="cancelDraft">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <button
                            v-if="!showDraft"
                            type="button"
                            class="soft-button-light text-xs"
                            :disabled="typesBusy"
                            @click="addType"
                        >
                            Add another request type
                        </button>
                    </div>
                </SectionCard>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <SectionCard
                    title="Official Certificate Signer"
                    eyebrow="E-Signature"
                    subtitle="Used on guest-issued Certificates of Appearance when no staff member is signing in person. Staff-issued certificates still snapshot whoever actually signs."
                >
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">
                            Default Signatory
                        </label>
                        <select v-model="form.official_signer_user_id" class="soft-select">
                            <option value="">— First staff member with a saved signature —</option>
                            <option v-for="person in signers" :key="person.id" :value="person.id">
                                {{ person.name }} ({{ person.position }}){{ person.has_signature ? "" : " — no saved signature yet" }}
                            </option>
                        </select>
                        <p class="mt-2 text-xs text-gray-500">
                            Each issued certificate stores a copy of that signer's name and drawing.
                            If they later leave or retire, previously issued certificates keep the old signature.
                        </p>
                        <p v-if="form.errors.official_signer_user_id" class="mt-1 text-xs text-red-600">
                            {{ form.errors.official_signer_user_id }}
                        </p>
                    </div>
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
import { computed, ref, watch } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";

const props = defineProps({
    settings:           { type: Object, required: true },
    pageOptions:        { type: Object, required: true },
    publicFieldOptions: { type: Object, required: true },
    categories:         { type: Array, default: () => [] },
    signers:            { type: Array, default: () => [] },
    requestTypes:       { type: Array, default: () => [] },
});

function hydrateRequestTypes(list) {
    return (list || []).map((type) => ({
        id: type.id,
        name: type.name,
        purpose: type.purpose,
        issues_certificate: type.issues_certificate,
        is_active: type.is_active,
        fulfillment: type.issues_certificate ? "certificate" : "file",
        visibility: type.is_active ? "shown" : "hidden",
    }));
}

function typePayload(type) {
    return {
        name: String(type.name || "").trim(),
        purpose: String(type.purpose || "").trim(),
        issues_certificate: type.fulfillment === "certificate" ? 1 : 0,
        is_active: type.visibility === "shown" ? 1 : 0,
    };
}

const page = usePage();
const types = ref(hydrateRequestTypes(props.requestTypes));
const typesBusy = ref(false);
const savingTypeId = ref(null);
const showDraft = ref(false);

const draft = useForm({
    name: "",
    purpose: "",
    fulfillment: "file",
    visibility: "shown",
});

const validCategoryIds = new Set(props.categories.map((cat) => Number(cat.id)));
const form = useForm({
    hotline:                  props.settings.hotline,
    office_hours:             props.settings.office_hours,
    public_search_fields:     [...props.settings.public_search_fields],
    public_search_categories: (props.settings.public_search_categories || []).filter((id) => validCategoryIds.has(Number(id))),
    employee_pages:           [...props.settings.employee_pages],
    official_signer_user_id:  props.settings.official_signer_user_id ?? "",
});

const visibleRequestTypes = computed(() => {
    const saved = types.value.filter((type) => type.visibility === "shown" && String(type.name || "").trim() !== "");

    if (showDraft.value && draft.visibility === "shown" && String(draft.name || "").trim() !== "") {
        return [...saved, { id: "draft", name: draft.name }];
    }

    return saved;
});

watch(
    () => props.requestTypes,
    (list) => {
        types.value = hydrateRequestTypes(list);
    },
);

function typeVisit(extra = {}) {
    return {
        preserveScroll: true,
        onStart: () => {
            typesBusy.value = true;
        },
        onFinish: () => {
            typesBusy.value = false;
        },
        ...extra,
    };
}

function fieldError(type, field) {
    if (savingTypeId.value !== type.id) {
        return null;
    }

    return page.props.errors?.[field] ?? null;
}

function saveType(type) {
    if (! type?.id || typesBusy.value) {
        return;
    }

    const payload = typePayload(type);
    if (! payload.name || ! payload.purpose) {
        return;
    }

    savingTypeId.value = type.id;
    router.put(`/settings/request-types/${type.id}`, payload, typeVisit());
}

function removeType(type) {
    if (! type?.id || types.value.length === 1 || typesBusy.value) {
        return;
    }

    savingTypeId.value = type.id;
    router.delete(`/settings/request-types/${type.id}`, typeVisit());
}

function moveType(index, direction) {
    const next = index + direction;
    if (next < 0 || next >= types.value.length || typesBusy.value) {
        return;
    }

    const rows = [...types.value];
    const [row] = rows.splice(index, 1);
    rows.splice(next, 0, row);
    types.value = rows;

    router.put("/settings/request-types/reorder", {
        ids: rows.map((type) => type.id),
    }, typeVisit());
}

function addType() {
    showDraft.value = true;
    draft.clearErrors();
}

function saveDraft() {
    draft
        .transform((data) => ({
            name: String(data.name || "").trim(),
            purpose: String(data.purpose || "").trim(),
            issues_certificate: data.fulfillment === "certificate" ? 1 : 0,
            is_active: data.visibility === "shown" ? 1 : 0,
        }))
        .post("/settings/request-types", typeVisit({
            onSuccess: () => {
                draft.reset();
                showDraft.value = false;
            },
        }));
}

function cancelDraft() {
    draft.reset();
    showDraft.value = false;
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            official_signer_user_id: data.official_signer_user_id || null,
        }))
        .put("/settings");
}
</script>
