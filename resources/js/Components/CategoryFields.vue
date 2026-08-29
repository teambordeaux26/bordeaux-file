<template>
    <div class="contents">
        <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="category">
                Category <span class="text-red-500">*</span>
            </label>
            <select
                id="category"
                v-model.number="parentId"
                class="soft-select"
                required
            >
                <option :value="null">— Select category —</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
            <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
        </div>

        <div v-if="hasChildren">
            <label class="block text-xs font-bold uppercase tracking-widest text-gray-600 mb-1" for="subcategory">
                Sub-category <span class="text-red-500">*</span>
            </label>
            <select
                id="subcategory"
                v-model.number="childId"
                class="soft-select"
                required
            >
                <option :value="null">— Select sub-category —</option>
                <option v-for="child in selectedParent.children" :key="child.id" :value="child.id">
                    {{ child.name }}
                </option>
            </select>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    categories: { type: Array, default: () => [] },
    modelValue: { type: [Number, String], default: null },
    error: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const parentId = ref(null);
const childId = ref(null);

const selectedParent = computed(() =>
    props.categories.find((cat) => Number(cat.id) === Number(parentId.value)) ?? null
);
const hasChildren = computed(() => (selectedParent.value?.children?.length ?? 0) > 0);

function locate(id) {
    if (!id) return { parentId: null, childId: null };

    for (const parent of props.categories) {
        if (Number(parent.id) === Number(id)) {
            return { parentId: parent.id, childId: null };
        }
        const child = (parent.children || []).find((item) => Number(item.id) === Number(id));
        if (child) {
            return { parentId: parent.id, childId: child.id };
        }
    }

    return { parentId: null, childId: null };
}

watch(
    () => [props.modelValue, props.categories],
    () => {
        const found = locate(props.modelValue);
        parentId.value = found.parentId;
        childId.value = found.childId;
    },
    { immediate: true, deep: true }
);

watch(parentId, () => {
    const parent = selectedParent.value;
    const belongs = (parent?.children || []).some(
        (child) => Number(child.id) === Number(childId.value)
    );
    if (!belongs) {
        childId.value = null;
    }
});

watch([parentId, childId, hasChildren], () => {
    if (!parentId.value) {
        emit("update:modelValue", null);
        return;
    }

    if (hasChildren.value) {
        emit("update:modelValue", childId.value ?? null);
        return;
    }

    emit("update:modelValue", parentId.value);
});
</script>
