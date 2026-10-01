<template>
    <Head title="New Password" />
    <AuthLayout>
        <div class="bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-md p-4 sm:p-8">
            <div class="mb-4 sm:mb-5 pb-3 sm:pb-4 border-b border-gray-200">
                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Account Recovery</p>
                <h2 class="text-base sm:text-lg font-bold text-[#003366]">Choose a new password</h2>
                <p class="mt-1 text-xs text-gray-500">
                    Use at least 8 characters. You will sign in with this password.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1.5" for="password">
                        New password
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="w-full border border-gray-300 bg-gray-50 px-3 py-2.5 sm:py-2 pr-11 text-base sm:text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center justify-center px-3 text-gray-500 hover:text-[#003366]"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            @click="showPassword = !showPassword"
                        >
                            <EyeOff v-if="showPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1.5" for="password_confirmation">
                        Confirm password
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        class="w-full border border-gray-300 bg-gray-50 px-3 py-2.5 sm:py-2 text-base sm:text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    />
                </div>

                <button
                    type="submit"
                    class="w-full bg-[#003366] text-white text-sm font-bold py-3 sm:py-2.5 px-4 hover:bg-[#002244] transition disabled:opacity-60 min-h-[48px] sm:min-h-0"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Saving…" : "Update password" }}
                </button>
            </form>
        </div>
    </AuthLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Eye, EyeOff } from "@lucide/vue";
import AuthLayout from "../../Layouts/AuthLayout.vue";

const showPassword = ref(false);

const form = useForm({
    password: "",
    password_confirmation: "",
});

function submit() {
    form.post("/forgot-password/reset");
}
</script>
