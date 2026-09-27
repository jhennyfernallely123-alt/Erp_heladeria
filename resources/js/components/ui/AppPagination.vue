<template>
    <div
        class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50/60"
    >
        <p class="text-xs text-slate-500">
            Mostrando <span class="font-semibold text-slate-700">{{ from }}</span>-
            <span class="font-semibold text-slate-700">{{ to }}</span> de
            <span class="font-semibold text-slate-700">{{ total }}</span> registros
        </p>

        <div v-if="pageCount > 1" class="flex items-center gap-1">
            <button
                type="button"
                class="p-1.5 rounded-lg text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="page === 1"
                aria-label="Página anterior"
                @click="go(page - 1)"
            >
                <AppIcon :name="ChevronLeft" :size="16" />
            </button>

            <button
                v-for="(n, index) in pages"
                :key="index"
                type="button"
                class="min-w-8 h-8 px-2 rounded-lg text-xs font-semibold transition"
                :class="n === page ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-white'"
                :disabled="n === '...'"
                @click="n !== '...' && go(n)"
            >
                {{ n }}
            </button>

            <button
                type="button"
                class="p-1.5 rounded-lg text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="page === pageCount"
                aria-label="Página siguiente"
                @click="go(page + 1)"
            >
                <AppIcon :name="ChevronRight" :size="16" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    total: { type: Number, required: true },
    perPage: { type: Number, default: 10 },
});

const page = defineModel('page', { type: Number, default: 1 });

const pageCount = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)));
const from = computed(() => (props.total === 0 ? 0 : (page.value - 1) * props.perPage + 1));
const to = computed(() => Math.min(page.value * props.perPage, props.total));

const pages = computed(() => {
    const count = pageCount.value;

    if (count <= 7) {
        return Array.from({ length: count }, (_, i) => i + 1);
    }

    if (page.value <= 3) {
        return [1, 2, 3, 4, '...', count];
    }

    if (page.value >= count - 2) {
        return [1, '...', count - 3, count - 2, count - 1, count];
    }

    return [1, '...', page.value - 1, page.value, page.value + 1, '...', count];
});

const go = (next) => {
    if (next < 1 || next > pageCount.value || next === page.value) {
        return;
    }

    page.value = next;
};
</script>
