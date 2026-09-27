<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <PageHeader
            title="Finanzas & Analítica de Ventas"
            subtitle="Métricas de rentabilidad, producto más vendido y flujo de ingresos vs egresos"
        >
            <template #actions>
                <input
                    v-model="startDate"
                    type="date"
                    class="text-xs px-3 py-2.5 border border-aguamarina-200 rounded-xl bg-white text-petrol-700 focus:outline-none focus:ring-2 focus:ring-aguamarina-400"
                />
                <span class="text-niebla-300 text-xs">a</span>
                <input
                    v-model="endDate"
                    type="date"
                    class="text-xs px-3 py-2.5 border border-aguamarina-200 rounded-xl bg-white text-petrol-700 focus:outline-none focus:ring-2 focus:ring-aguamarina-400"
                />
                <AppButton label="Filtrar" :icon="Filter" @click="loadReports" />
            </template>
        </PageHeader>

        <div v-if="loading" class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm">
            <div class="p-5 space-y-3">
                <div class="h-4 w-56 rounded bg-aguamarina-50 animate-pulse" />
                <div class="h-3 w-full rounded bg-aguamarina-50 animate-pulse" />
                <div class="h-3 w-3/4 rounded bg-aguamarina-50 animate-pulse" />
            </div>
        </div>

        <div v-else-if="summary" class="space-y-6">
            <!-- KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    label="Ventas Totales"
                    :value="`$${formatMoney(summary.total_sales)}`"
                    :icon="TrendingUp"
                    tone="aguamarina"
                />
                <StatCard
                    label="Ticket Promedio"
                    :value="`$${formatMoney(summary.average_ticket)}`"
                    :icon="Receipt"
                    tone="petrol"
                />
                <StatCard
                    label="Margen Bruto Estimado"
                    :value="`$${formatMoney(summary.gross_margin)}`"
                    :icon="PiggyBank"
                    tone="emerald"
                />
                <StatCard
                    label="Egresos / Gastos Caja"
                    :value="`$${formatMoney(summary.total_expenses)}`"
                    :icon="TrendingDown"
                    tone="rose"
                />
            </div>

            <p class="text-xs text-niebla-400 -mt-2">
                {{ summary.invoice_count }} facturas / tickets ·
                Costo de productos: ${{ formatMoney(summary.cost_of_goods) }} · Flujo neto:
                ${{ formatMoney(summary.net_cash_flow) }}
            </p>

            <!-- Tablas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-aguamarina-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                            <AppIcon :name="Trophy" :size="17" class="text-aguamarina-600" />
                            Top productos más vendidos
                        </h3>
                        <span class="text-xs text-aguamarina-700 font-semibold">Ranking de salida</span>
                    </div>

                    <div v-if="!summary.top_products?.length">
                        <AppEmptyState
                            :icon="PackageSearch"
                            title="Sin ventas"
                            description="No hay ventas en este rango de fechas."
                        />
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(tp, idx) in summary.top_products"
                            :key="idx"
                            class="flex items-center justify-between p-3 rounded-xl bg-aguamarina-50 border border-aguamarina-100 gap-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span
                                    class="w-6 h-6 rounded-full bg-aguamarina-600 text-white flex items-center justify-center font-bold text-xs shrink-0"
                                >
                                    {{ idx + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <h4 class="font-semibold text-xs text-petrol-700 truncate">
                                        {{ tp.name }}
                                    </h4>
                                    <span class="text-[11px] text-niebla-300">
                                        {{ Number(tp.total_qty) }} porciones / unidades
                                    </span>
                                </div>
                            </div>
                            <span class="font-bold text-xs text-petrol-800 shrink-0">
                                ${{ formatMoney(tp.total_sales) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-aguamarina-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                            <AppIcon :name="CreditCard" :size="17" class="text-aguamarina-600" />
                            Desglose por método de pago
                        </h3>
                        <span class="text-xs text-niebla-300 font-semibold">Ventas recaudadas</span>
                    </div>

                    <div v-if="!summary.payment_methods?.length">
                        <AppEmptyState
                            :icon="Banknote"
                            title="Sin pagos"
                            description="No se han registrado pagos en el período."
                        />
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(pm, idx) in summary.payment_methods"
                            :key="idx"
                            class="flex items-center justify-between p-3 rounded-xl bg-aguamarina-50 border border-aguamarina-100 gap-3"
                        >
                            <span
                                class="font-semibold text-xs text-petrol-700 flex items-center gap-2"
                            >
                                <AppIcon :name="paymentIcon(pm.payment_method)" :size="15" class="text-aguamarina-600" />
                                {{ paymentLabel(pm.payment_method) }}
                            </span>
                            <span class="font-bold text-xs text-petrol-800 shrink-0">
                                ${{ formatMoney(pm.total) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import {
    Banknote,
    CreditCard,
    Filter,
    PackageSearch,
    PiggyBank,
    Receipt,
    Smartphone,
    TrendingDown,
    TrendingUp,
    Trophy,
} from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppEmptyState from '../components/ui/AppEmptyState.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import StatCard from '../components/ui/StatCard.vue';
import api from '../api';

const summary = ref(null);
const loading = ref(true);
const startDate = ref(new Date().toISOString().substring(0, 10));
const endDate = ref(new Date().toISOString().substring(0, 10));

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const paymentLabels = { cash: 'Efectivo', card: 'Tarjeta', transfer: 'Transferencia' };
const paymentIcons = { cash: Banknote, card: CreditCard, transfer: Smartphone };

const paymentLabel = (method) => paymentLabels[method] || method;
const paymentIcon = (method) => paymentIcons[method] || Banknote;

const loadReports = async () => {
    loading.value = true;
    try {
        const res = await api.get('/finance/reports', {
            params: { start_date: startDate.value, end_date: endDate.value },
        });
        summary.value = res.data.data;
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

onMounted(loadReports);
</script>
