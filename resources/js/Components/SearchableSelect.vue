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
        <p v-if="!modelValue && query && filtered.length === 0" class="mt-1 text-xs text-amber-600">
            No matching barangay in Oas. Search or pick from the list.
        </p>
        <ul
            v-if="open && filtered.length"
            class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto border border-gray-300 bg-white shadow-lg"
        >
            <li
                v-for="(option, index) in filtered"
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

watch(
    () => props.modelValue,
    (value) => {
        if (!query.value || value === query.value) {
            query.value = value || "";
        }
    },
    { immediate: true }
);

watch(query, (value) => {
    open.value = true;
    activeIndex.value = 0;
    if (value !== props.modelValue) {
        emit("update:modelValue", "");
    }
});

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter((option) =>
        `${option.name} ${option.label}`.toLowerCase().includes(q)
    );
});

function select(option) {
    query.value = option.label;
    emit("update:modelValue", option.label);
    open.value = false;
}

function pickActive() {
    if (!filtered.value[activeIndex.value]) return;
    select(filtered.value[activeIndex.value]);
}

function move(delta) {
    if (!filtered.value.length) return;
    const len = filtered.value.length;
    activeIndex.value = (activeIndex.value + delta + len) % len;
}

function onDocClick(e) {
    if (!root.value?.contains(e.target)) {
        open.value = false;
        if (!props.modelValue) {
            query.value = "";
        } else {
            query.value = props.modelValue;
        }
    }
}

onMounted(() => document.addEventListener("mousedown", onDocClick));
onBeforeUnmount(() => document.removeEventListener("mousedown", onDocClick));
</script>
