<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">Punto de Venta & Mesas</h1>
        <p class="text-xs text-slate-500 mt-1">Control visual del salón y comandas en tiempo real</p>
      </div>

      <div class="flex items-center space-x-3">
        <button
          @click="openTakeawayOrder"
          class="flex items-center space-x-2 px-4 py-2.5 bg-gradient-to-r from-pink-600 to-rose-500 hover:from-pink-500 hover:to-rose-400 text-white font-bold rounded-xl shadow-lg shadow-pink-500/20 text-xs transition transform active:scale-95"
        >
          <span>🛍️</span>
          <span>Venta para Llevar / Mostrador</span>
        </button>

        <button
          @click="refreshData"
          class="p-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl transition shadow-sm"
          title="Actualizar mesas"
        >
          🔄
        </button>
      </div>
    </div>

    <!-- Status Legend -->
    <div class="flex flex-wrap items-center gap-4 bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm text-xs font-semibold text-slate-600">
      <span class="text-slate-400 font-medium">Estado del Salón:</span>
      <div class="flex items-center space-x-1.5">
        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
        <span>Libre ({{ tableStore.availableTables.length }})</span>
      </div>
      <div class="flex items-center space-x-1.5">
        <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
        <span>Ocupada / Comanda Activa ({{ tableStore.occupiedTables.length }})</span>
      </div>
      <div class="flex items-center space-x-1.5">
        <span class="w-3 h-3 rounded-full bg-sky-500"></span>
        <span>Reservada ({{ tableStore.reservedTables.length }})</span>
      </div>
    </div>

    <!-- Tables Grid -->
    <div v-if="tableStore.loading" class="text-center py-20 text-slate-400">
      <span class="text-4xl animate-bounce inline-block">🍨</span>
      <p class="text-sm mt-3 font-medium">Cargando mesas del salón...</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="table in tableStore.tables"
        :key="table.id"
        @click="handleTableClick(table)"
        class="bg-white rounded-2xl p-5 border-2 transition-all duration-200 hover:shadow-xl cursor-pointer flex flex-col justify-between group"
        :class="{
          'border-emerald-500/60 hover:border-emerald-500 bg-emerald-50/20': table.status === 'available',
          'border-amber-500/70 hover:border-amber-500 bg-amber-50/20 shadow-amber-500/5': table.status === 'occupied',
          'border-sky-500/60 hover:border-sky-500 bg-sky-50/20': table.status === 'reserved',
        }"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <span class="text-xs font-black px-2.5 py-1 rounded-lg uppercase tracking-wider"
              :class="{
                'bg-emerald-100 text-emerald-800': table.status === 'available',
                'bg-amber-100 text-amber-800': table.status === 'occupied',
                'bg-sky-100 text-sky-800': table.status === 'reserved',
              }"
            >
              {{ table.name }}
            </span>
            <p class="text-[11px] text-slate-500 mt-2 font-medium">
              👥 Capacidad: {{ table.capacity }} personas
            </p>
          </div>
          <span class="text-2xl group-hover:scale-110 transition transform">
            {{ table.status === 'occupied' ? '🍨' : (table.status === 'reserved' ? '📌' : '🪑') }}
          </span>
        </div>

        <!-- If table is occupied, show active order brief -->
        <div v-if="table.status === 'occupied' && table.active_order" class="mt-3 pt-3 border-t border-amber-200/60 space-y-1.5">
          <div class="flex justify-between items-center text-xs">
            <span class="font-bold text-amber-900 truncate">
              #{{ table.active_order.order_number }}
            </span>
            <span class="text-[11px] bg-amber-200/80 text-amber-900 px-1.5 py-0.5 rounded font-bold uppercase">
              {{ table.active_order.status }}
            </span>
          </div>
          <p class="text-[11px] text-slate-600 truncate">
            {{ table.active_order.items?.length || 0 }} productos en mesa
          </p>
          <div class="flex justify-between items-center pt-1 font-black text-amber-950 text-sm">
            <span>Total:</span>
            <span>${{ formatMoney(table.active_order.total) }}</span>
          </div>
        </div>

        <div v-else class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
          <span>Toca para abrir pedido</span>
          <span class="font-bold text-emerald-600">Disponible →</span>
        </div>
      </div>
    </div>

    <!-- Order Drawer Component -->
    <OrderDrawer
      :isOpen="isDrawerOpen"
      :products="products"
      :categories="categories"
      @close="isDrawerOpen = false"
      @orderSaved="refreshData"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useTableStore } from '../stores/tables';
import { useOrderStore } from '../stores/orders';
import OrderDrawer from '../components/OrderDrawer.vue';
import api from '../api';

const tableStore = useTableStore();
const orderStore = useOrderStore();

const isDrawerOpen = ref(false);
const products = ref([]);
const categories = ref([]);

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const loadCatalog = async () => {
  try {
    const [pRes, cRes] = await Promise.all([
      api.get('/products'),
      api.get('/categories')
    ]);
    products.value = pRes.data.data;
    categories.value = cRes.data.data;
  } catch (err) {
    console.error('Error al cargar catálogo', err);
  }
};

const refreshData = async () => {
  await Promise.all([tableStore.fetchTables(), loadCatalog()]);
};

onMounted(() => {
  refreshData();
});

const handleTableClick = (table) => {
  if (table.active_order) {
    orderStore.loadExistingOrder(table.active_order);
  } else {
    orderStore.initNewOrder(table, 'dine_in');
  }
  isDrawerOpen.value = true;
};

const openTakeawayOrder = () => {
  orderStore.initNewOrder(null, 'takeaway');
  isDrawerOpen.value = true;
};
</script>
