<template>
    <Head title="Forgot Password" />
    <AuthLayout>
        <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-md p-4 sm:p-8">
            <div class="mb-4 sm:mb-5 pb-3 sm:pb-4 border-b border-gray-200">
                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Account Recovery</p>
                <h2 class="text-base sm:text-lg font-bold text-[#003366]">Forgot Password</h2>
                <p class="mt-1 text-xs text-gray-500">
                    Enter the contact email saved on the account. A 6-digit code will be sent there.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1.5" for="contact_email">
                        Contact Email
                    </label>
                    <input
                        id="contact_email"
                        v-model="form.contact_email"
                        type="email"
                        class="w-full border border-gray-300 bg-gray-50 px-3 py-2.5 sm:py-2 text-base sm:text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                        placeholder="name@example.com"
                        autocomplete="email"
                        required
                    />
                    <p v-if="form.errors.contact_email" class="mt-1 text-xs text-red-600">
                        {{ form.errors.contact_email }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#003366] text-white text-sm font-bold py-3 sm:py-2.5 px-4 hover:bg-[#002244] transition disabled:opacity-60 min-h-[48px] sm:min-h-0"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Sending code…" : "Send verification code" }}
                </button>
            </form>
        </div>
    </AuthLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";

const form = useForm({
    contact_email: "",
});

function submit() {
    form.post("/forgot-password");
}
</script>
