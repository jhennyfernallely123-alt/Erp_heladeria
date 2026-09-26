<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">Finanzas & Analítica de Ventas</h1>
        <p class="text-xs text-slate-500 mt-1">Métricas de rentabilidad, producto más vendido y flujo de ingresos vs egresos</p>
      </div>

      <div class="flex items-center space-x-2">
        <input
          v-model="startDate"
          type="date"
          class="text-xs px-3 py-2 border border-slate-300 rounded-xl bg-white"
        />
        <span class="text-slate-400 text-xs">a</span>
        <input
          v-model="endDate"
          type="date"
          class="text-xs px-3 py-2 border border-slate-300 rounded-xl bg-white"
        />
        <button
          @click="loadReports"
          class="px-3.5 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-xl text-xs font-bold transition"
        >
          Filtrar
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-20 text-slate-400">
      <span class="text-3xl animate-spin inline-block">🔄</span>
      <p class="text-xs mt-2 font-medium">Calculando reportes financieros...</p>
    </div>

    <div v-else-if="summary" class="space-y-6">
      <!-- KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
          <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Ventas Totales</span>
          <span class="text-2xl font-black text-slate-800 mt-1 block">${{ formatMoney(summary.total_sales) }}</span>
          <span class="text-[11px] text-slate-500 mt-1 block">{{ summary.invoice_count }} facturas / tickets</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
          <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Ticket Promedio</span>
          <span class="text-2xl font-black text-pink-600 mt-1 block">${{ formatMoney(summary.average_ticket) }}</span>
          <span class="text-[11px] text-slate-500 mt-1 block">Por cliente atendido</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
          <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Margen Bruto Estimado</span>
          <span class="text-2xl font-black text-emerald-600 mt-1 block">${{ formatMoney(summary.gross_margin) }}</span>
          <span class="text-[11px] text-slate-500 mt-1 block">Costo productos: ${{ formatMoney(summary.cost_of_goods) }}</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
          <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Egresos / Gastos Caja</span>
          <span class="text-2xl font-black text-rose-600 mt-1 block">${{ formatMoney(summary.total_expenses) }}</span>
          <span class="text-[11px] text-slate-500 mt-1 block">Flujo neto: ${{ formatMoney(summary.net_cash_flow) }}</span>
        </div>
      </div>

      <!-- Tables and Rankings -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Top Selling Ice Creams -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-800">🏆 Top Productos Más Vendidos</h3>
            <span class="text-xs text-pink-600 font-semibold">Ranking de Salida</span>
          </div>

          <div v-if="!summary.top_products?.length" class="text-center py-8 text-slate-400 text-xs">
            Sin ventas en este rango de fechas.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(tp, idx) in summary.top_products"
              :key="idx"
              class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100"
            >
              <div class="flex items-center space-x-3">
                <span class="w-6 h-6 rounded-full bg-pink-600 text-white flex items-center justify-center font-bold text-xs">
                  {{ idx + 1 }}
                </span>
                <div>
                  <h4 class="font-bold text-xs text-slate-800">{{ tp.name }}</h4>
                  <span class="text-[11px] text-slate-400">{{ Number(tp.total_qty) }} porciones / unidades</span>
                </div>
              </div>
              <span class="font-black text-xs text-slate-800">${{ formatMoney(tp.total_sales) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment Breakdown -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-800">💳 Desglose por Método de Pago</h3>
            <span class="text-xs text-slate-400 font-semibold">Ventas recaudadas</span>
          </div>

          <div v-if="!summary.payment_methods?.length" class="text-center py-8 text-slate-400 text-xs">
            No se han registrado pagos en el período.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(pm, idx) in summary.payment_methods"
              :key="idx"
              class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100"
            >
              <span class="font-bold text-xs text-slate-700 capitalize">
                {{ pm.payment_method === 'cash' ? '💵 Efectivo' : (pm.payment_method === 'card' ? '💳 Tarjeta' : '📱 Transferencia') }}
              </span>
              <span class="font-black text-xs text-slate-800">${{ formatMoney(pm.total) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';

const summary = ref(null);
const loading = ref(true);
const startDate = ref(new Date().toISOString().substring(0, 10));
const endDate = ref(new Date().toISOString().substring(0, 10));

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const loadReports = async () => {
  loading.value = true;
  try {
    const res = await api.get('/finance/reports', {
      params: { start_date: startDate.value, end_date: endDate.value }
    });
    summary.value = res.data.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadReports();
});
</script>
