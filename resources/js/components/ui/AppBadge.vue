<template>
    <span
        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
        :class="tones[tone]"
    >
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dots[tone]" />
        <slot>{{ label }}</slot>
    </span>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    tone: { type: String, default: 'neutral' },
    dot: { type: Boolean, default: false },
});

const tones = {
    neutral: 'bg-slate-100 text-slate-700',
    success: 'bg-emerald-50 text-emerald-700',
    warning: 'bg-amber-50 text-amber-700',
    danger: 'bg-rose-50 text-rose-700',
    info: 'bg-indigo-50 text-indigo-700',
};

const dots = {
    neutral: 'bg-slate-400',
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-rose-500',
    info: 'bg-indigo-500',
};
</script>
