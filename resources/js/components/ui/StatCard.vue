<template>
    <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-4 flex items-center gap-3">
        <div class="h-11 w-11 rounded-xl flex items-center justify-center shrink-0" :class="tones[tone]">
            <AppIcon :name="icon" :size="20" />
        </div>
        <div class="min-w-0">
            <p class="text-xs text-niebla-400 font-medium truncate">{{ label }}</p>
            <p class="text-xl font-bold text-petrol-800 leading-tight">{{ value }}</p>
        </div>
    </div>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: [Object, Function], required: true },
    tone: { type: String, default: 'indigo' },
});

const tones = {
    aguamarina: 'bg-aguamarina-50 text-aguamarina-600',
    petrol: 'bg-petrol-50 text-petrol-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
};
</script>
