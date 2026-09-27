<template>
    <component :is="name" :size="size" :stroke-width="strokeWidth" aria-hidden="true" />
</template>

<script setup>
defineProps({
    name: { type: [Object, Function], required: true },
    size: { type: [Number, String], default: 16 },
    strokeWidth: { type: [Number, String], default: 2 },
});
</script>
