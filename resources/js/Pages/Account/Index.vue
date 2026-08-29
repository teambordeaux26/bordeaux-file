<template>
    <AppLayout>
        <Head title="My Account" />
        <div class="space-y-6 fade-up">
            <PageHeader
                title="My Account"
                kicker="Profile"
                subtitle="Review your account details, change your password, and save the e-signature used on certificates."
            />

            <div class="grid gap-6 lg:grid-cols-2">
                <SectionCard
                    title="Account Details"
                    eyebrow="Staff Profile"
                    subtitle="These details are managed by the office administrator."
                >
                    <dl class="grid gap-3 text-sm">
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Name</dt>
                            <dd class="mt-0.5 font-semibold text-[#003366]">{{ account.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Email</dt>
                            <dd class="mt-0.5 text-gray-800">{{ account.email }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Role</dt>
                            <dd class="mt-0.5 text-gray-800">{{ account.role }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Department</dt>
                            <dd class="mt-0.5 text-gray-800">{{ account.department }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Position</dt>
                            <dd class="mt-0.5 text-gray-800">{{ account.position }}</dd>
                        </div>
                    </dl>
                </SectionCard>

                <SectionCard
                    title="Change Password"
                    eyebrow="Security"
                    subtitle="Use a strong password that only you know."
                >
                    <form class="space-y-4" @submit.prevent="submitPassword">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="current_password">
                                Current Password
                            </label>
                            <input
                                id="current_password"
                                v-model="passwordForm.current_password"
                                type="password"
                                class="soft-input"
                                required
                            />
                            <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-red-600">
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="password">
                                New Password
                            </label>
                            <input
                                id="password"
                                v-model="passwordForm.password"
                                type="password"
                                class="soft-input"
                                minlength="8"
                                required
                            />
                            <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-600">
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="password_confirmation">
                                Confirm New Password
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="soft-input"
                                minlength="8"
                                required
                            />
                        </div>
                        <button type="submit" class="soft-button" :disabled="passwordForm.processing">
                            {{ passwordForm.processing ? "Updating…" : "Update Password" }}
                        </button>
                    </form>
                </SectionCard>
            </div>

            <SectionCard
                title="Certificate E-Signature"
                eyebrow="Signing"
                subtitle="This name and drawing are copied onto each Certificate of Appearance when you issue it. If you later retire or change your signature, old certificates keep the original name and sign."
            >
                <form class="space-y-4" @submit.prevent="submitSignature">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="signing_name">
                                Printed Name
                            </label>
                            <input
                                id="signing_name"
                                v-model="signatureForm.signing_name"
                                type="text"
                                class="soft-input"
                                required
                            />
                            <p v-if="signatureForm.errors.signing_name" class="mt-1 text-xs text-red-600">
                                {{ signatureForm.errors.signing_name }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="signing_title">
                                Title
                            </label>
                            <input
                                id="signing_title"
                                v-model="signatureForm.signing_title"
                                type="text"
                                class="soft-input"
                                required
                            />
                            <p v-if="signatureForm.errors.signing_title" class="mt-1 text-xs text-red-600">
                                {{ signatureForm.errors.signing_title }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <p class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1">Signature</p>
                        <SignaturePad
                            ref="pad"
                            :existing-url="signer.signature_url"
                            @update:model-value="signatureForm.signature = $event"
                        />
                        <p v-if="signatureForm.errors.signature" class="mt-1 text-xs text-red-600">
                            {{ signatureForm.errors.signature }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="soft-button" :disabled="signatureForm.processing">
                            {{ signatureForm.processing ? "Saving…" : "Save Signature" }}
                        </button>
                        <button
                            v-if="signer.has_signature"
                            type="button"
                            class="soft-button-light"
                            :disabled="clearForm.processing"
                            @click="clearSignature"
                        >
                            Remove Saved Signature
                        </button>
                    </div>
                </form>
            </SectionCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";
import PageHeader from "../../Components/PageHeader.vue";
import SectionCard from "../../Components/SectionCard.vue";
import SignaturePad from "../../Components/SignaturePad.vue";

const props = defineProps({
    account: { type: Object, required: true },
    signer: {
        type: Object,
        default: () => ({
            signing_name: "",
            signing_title: "",
            has_signature: false,
            signature_url: null,
        }),
    },
});

const pad = ref(null);

const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const signatureForm = useForm({
    signing_name: props.signer.signing_name,
    signing_title: props.signer.signing_title,
    signature: "",
});

const clearForm = useForm({});

function submitPassword() {
    passwordForm.put("/account/password", {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function submitSignature() {
    if (pad.value?.isDirty?.()) {
        signatureForm.signature = pad.value.toDataUrl();
    }

    signatureForm.put("/account/signature", {
        preserveScroll: true,
        onSuccess: () => {
            signatureForm.signature = "";
        },
    });
}

function clearSignature() {
    if (!confirm("Remove your saved e-signature? Certificates already issued will keep their original signature.")) {
        return;
    }

    clearForm.delete("/account/signature", { preserveScroll: true });
}
</script>
