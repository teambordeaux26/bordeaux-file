<template>
    <Head title="Login" />
    <div class="min-h-screen bg-[#f5f5f5] text-gray-900 font-sans flex flex-col">

        <!-- Top Government Banner -->
        <div class="anim-1 bg-[#003366] text-white text-[10px] sm:text-xs py-1 text-center tracking-widest uppercase leading-relaxed px-4">
            Republic of the Philippines &nbsp;|&nbsp; Province of Albay &nbsp;|&nbsp; Municipality of Oas
        </div>

        <!-- Header -->
        <header class="anim-2 bg-white border-b-4 border-[#FFD700] shadow-md">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 py-3 sm:py-4 flex items-center gap-3">
                <img :src="'/images/logo.png'" alt="Oas Albay Seal" class="h-10 w-10 sm:h-14 sm:w-14 object-contain shrink-0" />
                <div>
                    <p class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-500 font-semibold hidden sm:block">Official Website</p>
                    <h1 class="text-sm sm:text-lg font-bold text-[#003366] leading-tight">Document Management System</h1>
                    <p class="text-xs text-gray-600 hidden sm:block">Office of the Vice Mayor &mdash; Oas, Albay</p>
                </div>
            </div>
        </header>

        <!-- Page Body -->
        <div class="flex-1 flex flex-col items-center justify-start lg:justify-center px-4 sm:px-6 py-8 sm:py-12">
            <div class="w-full max-w-5xl">

                <!-- Back link -->
                <Link href="/" class="anim-3 inline-flex items-center gap-1 text-xs text-gray-500 hover:text-[#003366] transition mb-5">
                    &larr; Back to Home
                </Link>

                <div class="grid gap-8 lg:gap-10 lg:grid-cols-2 items-start">

                    <!-- Login Form — first on mobile, right on desktop -->
                    <div class="anim-4 bg-white border border-gray-300 border-t-4 border-t-[#003366] shadow-md p-6 sm:p-8 order-first lg:order-last">
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-200">
                            <img :src="'/images/logo.png'" alt="Seal" class="h-9 w-9 sm:h-10 sm:w-10 object-contain shrink-0" />
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">System Access</p>
                                <h2 class="text-base sm:text-lg font-bold text-[#003366]">Staff Login</h2>
                            </div>
                        </div>

                        <form class="space-y-4 sm:space-y-5" @submit.prevent="submit">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="email">
                                    Email Address
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="w-full border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                    autocomplete="username"
                                    placeholder="Enter your email"
                                    required
                                />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="password">
                                    Password
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="w-full border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-[#003366] focus:outline-none focus:ring-1 focus:ring-[#003366]"
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                    required
                                />
                                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="h-4 w-4 border-gray-300 text-[#003366] focus:ring-[#003366]"
                                />
                                Remember this device
                            </label>

                            <div v-if="turnstileSiteKey">
                                <TurnstileWidget
                                    ref="turnstileRef"
                                    :site-key="turnstileSiteKey"
                                    @verify="onTurnstileVerify"
                                    @expire="onTurnstileExpire"
                                    @error="onTurnstileExpire"
                                />
                                <p v-if="form.errors['cf-turnstile-response']" class="mt-2 text-xs text-red-600">
                                    {{ form.errors['cf-turnstile-response'] }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="w-full bg-[#003366] text-white text-sm font-bold py-2.5 px-4 hover:bg-[#002244] transition disabled:opacity-60"
                                :disabled="form.processing || (turnstileSiteKey && !form['cf-turnstile-response'])"
                            >
                                {{ form.processing ? "Signing in..." : "Login to System" }}
                            </button>

                            <div class="relative flex items-center gap-3 text-xs text-gray-400">
                                <div class="flex-1 border-t border-gray-200"></div>
                                <span>or</span>
                                <div class="flex-1 border-t border-gray-200"></div>
                            </div>

                            <Link
                                href="/requests/new"
                                class="block w-full text-center border border-[#003366] text-[#003366] text-sm font-semibold py-2.5 px-4 hover:bg-[#003366] hover:text-white transition"
                            >
                                Continue as Guest
                            </Link>
                        </form>
                    </div>

                    <!-- Info Panel — second on mobile, left on desktop -->
                    <div class="anim-5 space-y-5 sm:space-y-6 order-last lg:order-first">
                        <div class="border-l-4 border-[#003366] pl-4">
                            <p class="text-xs uppercase tracking-widest text-gray-500 font-semibold">Staff Portal</p>
                            <h2 class="text-xl sm:text-2xl font-bold text-[#003366]">Document Management System</h2>
                        </div>
                        <p class="text-sm text-gray-600 max-w-md">
                            Secure, track, and archive office records with real-time visibility.
                            This portal is for authorized government personnel only.
                        </p>

                        <div class="bg-[#003366]/5 border border-[#003366]/20 p-4 text-xs text-gray-600 leading-relaxed">
                            <span class="font-bold text-[#003366]">Notice:</span> Unauthorized access to this system is prohibited
                            and may be subject to administrative and criminal penalties under applicable laws.
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="anim-6 bg-[#003366] text-white border-t-4 border-[#FFD700] mt-auto">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs font-bold uppercase tracking-widest text-[#FFD700]">Office of the Vice Mayor &mdash; Oas, Albay</p>
                <div class="text-xs text-blue-200 flex flex-wrap gap-3 sm:gap-4">
                    <span>Hotline: {{ site.hotline || '(052) 555-0198' }}</span>
                    <span>Email: info@oas-albay.gov.ph</span>
                </div>
            </div>
        </footer>

    </div>
</template>

<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import TurnstileWidget from "../Components/TurnstileWidget.vue";

const page = usePage();
const site = computed(() => page.props.site ?? {});
const turnstileSiteKey = computed(() => page.props.turnstileSiteKey ?? "");
const turnstileRef = ref(null);

const form = useForm({
    email: "",
    password: "",
    remember: false,
    "cf-turnstile-response": "",
});

function onTurnstileVerify(token) {
    form["cf-turnstile-response"] = token;
}

function onTurnstileExpire() {
    form["cf-turnstile-response"] = "";
}

function resetTurnstile() {
    form["cf-turnstile-response"] = "";
    turnstileRef.value?.reset();
}

const submit = () => {
    form.post("/login", {
        preserveScroll: true,
        onError: () => resetTurnstile(),
    });
};
</script>

<style scoped>
@keyframes fadeSlideUp {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.anim-1,
.anim-2,
.anim-3,
.anim-4,
.anim-5,
.anim-6 {
    opacity: 0;
    animation: fadeSlideUp 0.5s ease forwards;
}

.anim-1 { animation-delay: 0ms; }
.anim-2 { animation-delay: 100ms; }
.anim-3 { animation-delay: 220ms; }
.anim-4 { animation-delay: 340ms; }
.anim-5 { animation-delay: 460ms; }
.anim-6 { animation-delay: 580ms; }
</style>
