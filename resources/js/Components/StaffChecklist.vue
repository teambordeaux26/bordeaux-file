<template>
    <div class="space-y-2">
        <input
            v-model="query"
            type="search"
            class="soft-input"
            :placeholder="placeholder"
        />
        <div class="grid max-h-56 gap-2 overflow-y-auto border border-gray-200 bg-gray-50 p-2 sm:grid-cols-2">
            <label
                v-for="person in filtered"
                :key="person.id"
                class="flex items-start gap-2 rounded border border-gray-200 bg-white px-3 py-2 text-sm"
            >
                <input
                    v-model="selected"
                    type="checkbox"
                    :value="person.id"
                    class="mt-0.5 rounded border-gray-300 text-[#003366]"
                />
                <span>
                    <span class="font-medium text-gray-900">{{ person.name }}</span>
                    <span class="block text-[11px] text-gray-500">
                        {{ person.position }} · {{ person.role }}
                    </span>
                </span>
            </label>
            <p v-if="filtered.length === 0" class="col-span-full px-1 py-3 text-xs text-gray-500">
                No matching staff.
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: "Search staff by name…" },
});

const emit = defineEmits(["update:modelValue"]);
const query = ref("");

const selected = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", (value || []).map((id) => Number(id))),
});

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) {
        return props.options;
    }

    return props.options.filter((person) =>
        [person.name, person.position, person.role, person.department, person.label]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(q))
    );
});
</script>
