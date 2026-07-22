<template>
    <div v-if="successVisible || errorVisible" class="space-y-2 mb-6">

        <!-- Success -->
        <Transition name="flash">
            <div
                v-if="successVisible"
                class="flex items-start gap-3 border border-green-400 bg-green-50 px-4 py-3 text-sm font-medium text-green-800"
            >
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="flex-1">{{ successMsg }}</span>
                <button
                    @click="successVisible = false"
                    class="ml-2 text-green-600 hover:text-green-900 font-bold leading-none"
                    aria-label="Dismiss"
                >
                    &times;
                </button>
            </div>
        </Transition>

        <!-- Error -->
        <Transition name="flash">
            <div
                v-if="errorVisible"
                class="flex items-start gap-3 border border-red-400 bg-red-50 px-4 py-3 text-sm font-medium text-red-800"
            >
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span class="flex-1">{{ errorMsg }}</span>
                <button
                    @click="errorVisible = false"
                    class="ml-2 text-red-600 hover:text-red-900 font-bold leading-none"
                    aria-label="Dismiss"
                >
                    &times;
                </button>
            </div>
        </Transition>

    </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const successVisible = ref(false);
const errorVisible   = ref(false);
const successMsg     = ref('');
const errorMsg       = ref('');

const DISMISS_DELAY = 5000;

let successTimer = null;
let errorTimer   = null;

watch(
    () => page.props.flash?.success,
    (val) => {
        if (val) {
            successMsg.value   = val;
            successVisible.value = true;
            clearTimeout(successTimer);
            successTimer = setTimeout(() => { successVisible.value = false; }, DISMISS_DELAY);
        }
    },
    { immediate: true }
);

watch(
    () => page.props.flash?.error,
    (val) => {
        if (val) {
            errorMsg.value   = val;
            errorVisible.value = true;
            clearTimeout(errorTimer);
            errorTimer = setTimeout(() => { errorVisible.value = false; }, DISMISS_DELAY);
        }
    },
    { immediate: true }
);

onUnmounted(() => {
    clearTimeout(successTimer);
    clearTimeout(errorTimer);
});
</script>

<style scoped>
.flash-enter-active,
.flash-leave-active {
    transition: opacity 0.35s ease, transform 0.35s ease;
}
.flash-enter-from,
.flash-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
