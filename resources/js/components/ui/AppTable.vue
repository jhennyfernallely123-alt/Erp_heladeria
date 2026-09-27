<template>
    <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-4 space-y-3">
            <div v-for="row in 6" :key="row" class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-lg bg-aguamarina-50 animate-pulse" />
                <div class="h-3 rounded bg-aguamarina-50 animate-pulse flex-1" />
                <div class="h-3 rounded bg-aguamarina-50 animate-pulse w-24" />
                <div class="h-3 rounded bg-aguamarina-50 animate-pulse w-16" />
            </div>
        </div>

        <div v-else-if="total === 0" class="px-6 py-4">
            <slot name="empty">
                <AppEmptyState :icon="emptyIcon" :title="emptyTitle" :description="emptyDescription" />
            </slot>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr
                        class="bg-aguamarina-50 text-niebla-400 font-semibold border-b border-aguamarina-100 text-xs uppercase tracking-wider"
                    >
                        <slot name="head" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-petrol-700">
                    <slot />
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import AppEmptyState from './AppEmptyState.vue';
import { PackageOpen } from 'lucide-vue-next';

defineProps({
    loading: { type: Boolean, default: false },
    total: { type: Number, default: 0 },
    emptyTitle: { type: String, default: 'Sin resultados' },
    emptyDescription: { type: String, default: 'Prueba ajustando los filtros de búsqueda.' },
    emptyIcon: { type: [Object, Function], default: () => PackageOpen },
});
</script>
