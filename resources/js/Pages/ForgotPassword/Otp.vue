<template>
    <Head title="Enter Code" />
    <AuthLayout>
        <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-md p-4 sm:p-8">
            <div class="mb-4 sm:mb-5 pb-3 sm:pb-4 border-b border-gray-200">
                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Account Recovery</p>
                <h2 class="text-base sm:text-lg font-bold text-[#003366]">Enter the code</h2>
                <p class="mt-1 text-xs text-gray-500">
                    A 6-digit code was sent to <span class="font-semibold text-[#003366]">{{ maskedEmail }}</span>.
                    It expires in 10 minutes.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1.5" for="code">
                        Verification code
                    </label>
                    <input
                        id="code"
                        v-model="form.code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        class="w-full border border-gray-300 bg-gray-50 px-3 py-3 text-center text-2xl tracking-[0.4em] font-bold text-[#003366] focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                        placeholder="000000"
                        required
                        @input="onCode"
                    />
                    <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">
                        {{ form.errors.code }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#003366] text-white text-sm font-bold py-3 sm:py-2.5 px-4 hover:bg-[#002244] transition disabled:opacity-60 min-h-[48px] sm:min-h-0"
                    :disabled="form.processing || form.code.length !== 6"
                >
                    {{ form.processing ? "Checking…" : "Verify code" }}
                </button>
            </form>

            <p class="mt-4 text-center text-xs text-gray-500">
                Didn’t receive it?
                <Link href="/forgot-password" class="font-semibold text-[#003366] hover:underline">
                    Request a new code
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>

<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";

defineProps({
    maskedEmail: { type: String, default: "" },
});

const form = useForm({
    code: "",
});

function onCode(event) {
    form.code = event.target.value.replace(/\D/g, "").slice(0, 6);
}

function submit() {
    form.post("/forgot-password/verify");
}
</script>
