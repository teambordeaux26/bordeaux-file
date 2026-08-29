<template>
    <div>
        <div
            class="relative overflow-hidden border bg-white"
            :class="disabled ? 'border-gray-200' : 'border-gray-300'"
        >
            <canvas
                ref="canvas"
                class="block w-full touch-none"
                :class="disabled ? 'cursor-not-allowed bg-gray-50' : 'cursor-crosshair'"
                height="160"
                @pointerdown="start"
                @pointermove="move"
                @pointerup="end"
                @pointerleave="end"
                @pointercancel="end"
            />
            <p
                v-if="!dirty && !existingUrl"
                class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs text-gray-400"
            >
                Draw or upload your signature here
            </p>
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="soft-button-light text-xs"
                :disabled="disabled"
                @click="clear"
            >
                Clear
            </button>
            <button
                type="button"
                class="soft-button-light text-xs"
                :disabled="disabled"
                @click="pickFile"
            >
                Upload Signature
            </button>
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                accept="image/png,image/jpeg,image/jpg,image/webp"
                :disabled="disabled"
                @change="onUpload"
            />
            <p class="text-[11px] text-gray-500">
                Draw with a mouse, trackpad, or finger, or upload a PNG, JPG, or WEBP image of your signature.
            </p>
        </div>
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
    existingUrl: { type: String, default: null },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const canvas = ref(null);
const fileInput = ref(null);
const dirty = ref(false);
const error = ref("");
let drawing = false;
let ctx = null;

function setupCanvas() {
    if (dirty.value) return;

    const el = canvas.value;
    if (!el) return;

    const ratio = window.devicePixelRatio || 1;
    const width = el.clientWidth || 480;
    const height = 160;
    el.width = Math.floor(width * ratio);
    el.height = Math.floor(height * ratio);
    el.style.width = `${width}px`;
    el.style.height = `${height}px`;

    ctx = el.getContext("2d");
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    applyStrokeStyle();

    if (props.existingUrl && !dirty.value) {
        paintImage(props.existingUrl, false);
    }
}

function applyStrokeStyle() {
    if (!ctx) return;
    ctx.lineWidth = 2.2;
    ctx.lineCap = "round";
    ctx.lineJoin = "round";
    ctx.strokeStyle = "#111827";
}

function resetSurface() {
    if (!ctx || !canvas.value) return;

    const ratio = window.devicePixelRatio || 1;
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    applyStrokeStyle();
}

function drawFitted(image) {
    const el = canvas.value;
    const width = el.clientWidth || 480;
    const height = 160;
    const padding = 8;
    const maxW = Math.max(width - padding * 2, 40);
    const maxH = Math.max(height - padding * 2, 40);
    const scale = Math.min(maxW / image.width, maxH / image.height);
    const drawW = image.width * scale;
    const drawH = image.height * scale;
    const x = (width - drawW) / 2;
    const y = (height - drawH) / 2;

    resetSurface();
    ctx.drawImage(image, x, y, drawW, drawH);
    applyStrokeStyle();
}

function paintImage(url, markDirty) {
    const el = canvas.value;
    if (!el || !ctx) return;

    const image = new Image();
    image.onload = () => {
        drawFitted(image);
        if (markDirty) {
            dirty.value = true;
            emit("update:modelValue", el.toDataURL("image/png"));
        }
    };
    image.onerror = () => {
        error.value = "The signature image could not be read. Try a PNG or JPG file.";
    };
    image.src = url;
}

function point(event) {
    const rect = canvas.value.getBoundingClientRect();
    return {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
    };
}

function start(event) {
    if (props.disabled || !ctx) return;
    drawing = true;
    dirty.value = true;
    error.value = "";
    canvas.value.setPointerCapture?.(event.pointerId);
    const { x, y } = point(event);
    ctx.beginPath();
    ctx.moveTo(x, y);
}

function move(event) {
    if (!drawing || !ctx) return;
    const { x, y } = point(event);
    ctx.lineTo(x, y);
    ctx.stroke();
}

function end() {
    if (!drawing) return;
    drawing = false;
    emit("update:modelValue", canvas.value.toDataURL("image/png"));
}

function clear() {
    if (!ctx || !canvas.value) return;
    resetSurface();
    dirty.value = false;
    error.value = "";
    emit("update:modelValue", "");
}

function pickFile() {
    if (props.disabled) return;
    fileInput.value?.click();
}

function onUpload(event) {
    const file = event.target.files?.[0];
    event.target.value = "";
    error.value = "";

    if (!file || props.disabled) return;

    const allowed = ["image/png", "image/jpeg", "image/jpg", "image/webp"];
    if (!allowed.includes(file.type)) {
        error.value = "Upload a PNG, JPG, or WEBP image of your signature.";
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        error.value = "The signature image must be 2 MB or smaller.";
        return;
    }

    const reader = new FileReader();
    reader.onload = () => paintImage(String(reader.result), true);
    reader.onerror = () => {
        error.value = "The signature image could not be read. Try a PNG or JPG file.";
    };
    reader.readAsDataURL(file);
}

watch(
    () => props.existingUrl,
    (url) => {
        if (!dirty.value && url) {
            setupCanvas();
        }
    }
);

onMounted(() => {
    setupCanvas();
    window.addEventListener("resize", setupCanvas);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", setupCanvas);
});

function toDataUrl() {
    if (!canvas.value || !dirty.value) {
        return "";
    }

    return canvas.value.toDataURL("image/png");
}

defineExpose({ clear, isDirty: () => dirty.value, toDataUrl });
</script>
