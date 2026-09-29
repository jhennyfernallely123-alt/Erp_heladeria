<template>
    <div class="bg-white p-6 rounded-2xl border border-aguamarina-100 shadow-sm space-y-4">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <h3 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                <AppIcon :name="BarChart3" :size="17" class="text-aguamarina-600" />
                {{ title }}
            </h3>
            <span class="text-xs text-niebla-300 font-semibold">{{ hint }}</span>
        </div>

        <div v-if="empty" class="py-6">
            <AppEmptyState
                :icon="BarChart3"
                title="Sin datos"
                :description="emptyDescription"
            />
        </div>

        <div v-else class="relative" :style="{ height: height }">
            <Bar :data="chartData" :options="options" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { Bar } from 'vue-chartjs';
import { BarChart3 } from 'lucide-vue-next';
import AppEmptyState from '../ui/AppEmptyState.vue';
import AppIcon from '../ui/AppIcon.vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

const props = defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    labels: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    stacked: { type: Boolean, default: false },
    height: { type: String, default: '260px' },
    emptyDescription: { type: String, default: 'No hay datos en este rango de fechas.' },
});

/**
 * series: [{ label, data, color, stack? }]
 *
 * Los colores salen de la paleta de la marca (aguamarina, petróleo) para que
 * los graficos no se vean ajenos al resto de la app.
 */
const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.series.map((s) => ({
        label: s.label,
        data: s.data,
        backgroundColor: s.color,
        borderRadius: 4,
        borderSkipped: false,
        maxBarThickness: 42,
    })),
}));

const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            display: props.series.length > 1,
            position: 'bottom',
            labels: {
                boxWidth: 10,
                boxHeight: 10,
                usePointStyle: true,
                pointStyle: 'circle',
                font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 },
                color: '#6d8894',
            },
        },
        tooltip: {
            backgroundColor: '#1a4a52',
            padding: 10,
            cornerRadius: 8,
            titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
            bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
            callbacks: {
                // Los valores llegan como numero crudo; acá se pasan a pesos
                // colombianos para que el tooltip se lea igual que el resto.
                label: (ctx) => ` ${ctx.dataset.label}: $ ${Number(ctx.raw || 0).toLocaleString('es-CO')}`,
            },
        },
    },
    scales: {
        x: {
            stacked: props.stacked,
            grid: { display: false },
            ticks: {
                font: { family: "'Plus Jakarta Sans', sans-serif", size: 10 },
                color: '#8fa5ab',
                // Con muchos dias los labels se enciman: se muestran salteados.
                maxRotation: 0,
                autoSkip: true,
                maxTicksLimit: 16,
            },
        },
        y: {
            stacked: props.stacked,
            beginAtZero: true,
            grid: { color: '#eef4f5' },
            ticks: {
                font: { family: "'Plus Jakarta Sans', sans-serif", size: 10 },
                color: '#8fa5ab',
                callback: (value) => `$${Number(value).toLocaleString('es-CO')}`,
            },
        },
    },
}));

const empty = computed(() => props.series.every((s) => s.data.every((v) => !v)));
</script>
