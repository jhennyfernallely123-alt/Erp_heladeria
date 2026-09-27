<template>
    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="h-14 w-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <AppIcon :name="icon" :size="26" class="text-slate-400" />
        </div>
        <p class="text-sm font-semibold text-slate-800">{{ title }}</p>
        <p class="text-xs text-slate-500 mt-1 max-w-sm">{{ description }}</p>
    </div>
</template>

<script setup>
import AppIcon from './AppIcon.vue';
import { Inbox } from 'lucide-vue-next';

defineProps({
    title: { type: String, default: 'Sin resultados' },
    description: { type: String, default: 'Prueba ajustando los filtros de búsqueda.' },
    icon: { type: [Object, Function], default: () => Inbox },
});
</script>
