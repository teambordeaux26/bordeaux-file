<template>
    <div ref="root" class="relative">
        <input
            :id="id"
            v-model="query"
            type="text"
            role="combobox"
            :aria-expanded="open"
            autocomplete="off"
            :placeholder="placeholder"
            class="soft-input"
            :class="inputClass"
            @focus="open = true"
            @keydown.escape.prevent="open = false"
            @keydown.enter.prevent="pickActive"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
        />
        <p v-if="!otherMode && !modelValue && query && filtered.length === 0" class="mt-1 text-xs text-amber-600">
            No matching barangay in Oas. Choose Other to enter a different address.
        </p>
        <div v-if="otherMode" class="mt-2">
            <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-gray-600">
                Other address or barangay
            </label>
            <input
                v-model="custom"
                type="text"
                class="soft-input"
                placeholder="Enter the address for visitors who are not from Oas"
            />
        </div>
        <ul
            v-if="open && choices.length"
            class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto border border-gray-300 bg-white shadow-lg"
        >
            <li
                v-for="(option, index) in choices"
                :key="option.label"
                class="cursor-pointer px-3 py-2 text-sm"
                :class="index === activeIndex ? 'bg-[#003366] text-white' : 'text-gray-800 hover:bg-blue-50'"
                @mousedown.prevent="select(option)"
                @mouseenter="activeIndex = index"
            >
                <span class="font-semibold">{{ option.name }}</span>
                <span class="block text-[11px] opacity-80">{{ option.label }}</span>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
    id: { type: String, default: undefined },
    modelValue: { type: String, default: "" },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: "Search barangay in Oas…" },
    inputClass: { type: [String, Object, Array], default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const root = ref(null);
const open = ref(false);
const query = ref("");
const activeIndex = ref(0);
const otherMode = ref(false);
const custom = ref("");

function isOfficial(value) {
    return props.options.some((option) => option.label === value);
}

watch(
    () => props.modelValue,
    (value) => {
        if (value && !isOfficial(value)) {
            otherMode.value = true;
            custom.value = value;
            query.value = "Other";
            return;
        }

        if (!otherMode.value && (!query.value || value === query.value)) {
            query.value = value || "";
        }
    },
    { immediate: true }
);

watch(query, (value) => {
    activeIndex.value = 0;
    if (otherMode.value && value === "Other") {
        open.value = false;
        return;
    }

    open.value = true;

    const match = props.options.find((option) => option.label === value);
    if (match) {
        otherMode.value = false;
        emit("update:modelValue", match.label);
    } else if (value !== "Other") {
        otherMode.value = false;
        emit("update:modelValue", "");
    }
});

watch(custom, (value) => {
    if (otherMode.value) {
        emit("update:modelValue", value.trim());
    }
});

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q || q === "other") return props.options;
    return props.options.filter((option) =>
        `${option.name} ${option.label}`.toLowerCase().includes(q)
    );
});

const choices = computed(() => {
    const other = { name: "Other", label: "Other" };
    if (filtered.value.some((option) => option.label === "Other")) {
        return filtered.value;
    }

    return [...filtered.value, other];
});

function select(option) {
    if (option.label === "Other") {
        otherMode.value = true;
        query.value = "Other";
        open.value = false;
        emit("update:modelValue", custom.value.trim());
        return;
    }

    otherMode.value = false;
    custom.value = "";
    query.value = option.label;
    emit("update:modelValue", option.label);
    open.value = false;
}

function pickActive() {
    if (!choices.value[activeIndex.value]) return;
    select(choices.value[activeIndex.value]);
}

function move(delta) {
    if (!choices.value.length) return;
    const len = choices.value.length;
    activeIndex.value = (activeIndex.value + delta + len) % len;
}

function onDocClick(e) {
    if (!root.value?.contains(e.target)) {
        open.value = false;
        if (otherMode.value) {
            query.value = "Other";
        } else if (!props.modelValue) {
            query.value = "";
        } else {
            query.value = props.modelValue;
        }
    }
}

onMounted(() => document.addEventListener("mousedown", onDocClick));
onBeforeUnmount(() => document.removeEventListener("mousedown", onDocClick));
</script>
