<template>
    <span style="display: none" aria-hidden="true" />
</template>

<script setup>
import { watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";

const page = usePage();

function fire(flash) {
    if (!flash) return;
    if (flash.success) {
        toast.success(flash.success);
    }
    if (flash.error) {
        toast.error(flash.error);
    }
}

watch(
    () => page.props.flash,
    (flash) => fire(flash),
    { immediate: true, deep: true }
);
</script>
