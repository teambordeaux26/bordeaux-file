<template>
    <div ref="container"></div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from "vue";

const props = defineProps({
    siteKey: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(["verify", "expire", "error"]);

const container = ref(null);
let widgetId = null;

function loadScript() {
    return new Promise((resolve) => {
        if (window.turnstile) {
            resolve();
            return;
        }

        const existing = document.querySelector('script[src*="turnstile/v0/api.js"]');
        if (existing) {
            existing.addEventListener("load", () => resolve(), { once: true });
            return;
        }

        const script = document.createElement("script");
        script.src = "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit";
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        document.head.appendChild(script);
    });
}

function renderWidget() {
    if (!container.value || !props.siteKey || !window.turnstile) {
        return;
    }

    if (widgetId !== null) {
        window.turnstile.remove(widgetId);
        widgetId = null;
    }

    widgetId = window.turnstile.render(container.value, {
        sitekey: props.siteKey,
        callback: (token) => emit("verify", token),
        "expired-callback": () => emit("expire"),
        "error-callback": () => emit("error"),
    });
}

onMounted(async () => {
    await loadScript();
    renderWidget();
});

onUnmounted(() => {
    if (window.turnstile && widgetId !== null) {
        window.turnstile.remove(widgetId);
    }
});

function reset() {
    if (window.turnstile && widgetId !== null) {
        window.turnstile.reset(widgetId);
    }
}

defineExpose({ reset });
</script>
