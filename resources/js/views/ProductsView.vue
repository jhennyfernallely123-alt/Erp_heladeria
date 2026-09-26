<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">Catálogo de Productos & Inventario</h1>
        <p class="text-xs text-slate-500 mt-1">Gestión de helados, sabores, variantes de tamaño y alertas de stock</p>
      </div>

      <button
        @click="openCreateModal"
        class="px-4 py-2.5 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl shadow-lg shadow-pink-600/20 text-xs flex items-center space-x-2"
      >
        <span>+ Nuevo Producto</span>
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center space-x-3 flex-1 min-w-[260px]">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar helado o producto..."
          class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-pink-500 focus:outline-none"
        />
        <select
          v-model="selectedCategory"
          class="text-xs px-3 py-2.5 rounded-xl border border-slate-300 bg-white"
        >
          <option :value="null">Todas las categorías</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>

      <div class="flex items-center space-x-2">
        <label class="flex items-center space-x-2 text-xs font-semibold text-slate-700 cursor-pointer">
          <input
            type="checkbox"
            v-model="onlyLowStock"
            class="w-4 h-4 text-pink-600 rounded border-slate-300 focus:ring-pink-500"
          />
          <span>🚨 Solo Stock Crítico / Bajo</span>
        </label>
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
            <th class="p-3.5">Producto</th>
            <th class="p-3.5">Categoría</th>
            <th class="p-3.5 text-right">Precio Costo</th>
            <th class="p-3.5 text-right">Precio Venta</th>
            <th class="p-3.5 text-right">Margen Estimado</th>
            <th class="p-3.5 text-center">Stock Actual</th>
            <th class="p-3.5 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
          <tr v-for="prod in filteredProducts" :key="prod.id" class="hover:bg-slate-50 transition">
            <td class="p-3.5">
              <span class="font-bold text-slate-800 text-sm block">{{ prod.name }}</span>
              <span class="text-[11px] text-slate-400">{{ prod.description }}</span>
              <span v-if="prod.has_variants" class="inline-block mt-0.5 text-[10px] text-pink-600 font-bold bg-pink-50 px-1.5 py-0.5 rounded">
                {{ prod.variants?.length || 0 }} tamaños/variantes
              </span>
            </td>
            <td class="p-3.5">
              <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg text-[11px] font-bold">
                {{ prod.category?.name }}
              </span>
            </td>
            <td class="p-3.5 text-right text-slate-500">${{ formatMoney(prod.cost_price) }}</td>
            <td class="p-3.5 text-right font-black text-slate-800">${{ formatMoney(prod.sale_price) }}</td>
            <td class="p-3.5 text-right">
              <span class="font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                ${{ formatMoney(prod.sale_price - prod.cost_price) }}
              </span>
            </td>
            <td class="p-3.5 text-center">
              <span
                class="px-2.5 py-1 rounded-full text-xs font-black inline-block"
                :class="Number(prod.stock_quantity) <= Number(prod.min_stock_alert)
                  ? 'bg-rose-100 text-rose-700 animate-pulse'
                  : 'bg-emerald-100 text-emerald-800'"
              >
                {{ Number(prod.stock_quantity) }}
              </span>
              <span v-if="Number(prod.stock_quantity) <= Number(prod.min_stock_alert)" class="block text-[10px] text-rose-500 font-bold mt-0.5">
                ¡Alerta Stock!
              </span>
            </td>
            <td class="p-3.5 text-right space-x-1">
              <button
                @click="openStockAdjust(prod)"
                class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold"
                title="Ajustar Stock"
              >
                ⚖️ Ajustar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Stock Adjust Modal -->
    <div v-if="adjustingProduct" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl space-y-3">
        <h3 class="font-bold text-sm text-slate-800">Ajuste Manual de Inventario</h3>
        <p class="text-xs text-slate-500">{{ adjustingProduct.name }}</p>

        <div>
          <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nueva Cantidad de Stock</label>
          <input
            v-model.number="newStockVal"
            type="number"
            class="w-full text-sm font-bold px-3 py-2 border border-slate-300 rounded-xl"
          />
        </div>

        <div class="flex justify-end space-x-2 pt-2">
          <button @click="adjustingProduct = null" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">
            Cancelar
          </button>
          <button @click="saveStockAdjust" class="px-4 py-1.5 bg-pink-600 hover:bg-pink-700 text-white rounded-xl text-xs font-bold">
            Guardar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../api';

const products = ref([]);
const categories = ref([]);
const searchQuery = ref('');
const selectedCategory = ref(null);
const onlyLowStock = ref(false);

const adjustingProduct = ref(null);
const newStockVal = ref(0);

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const loadData = async () => {
  const [pRes, cRes] = await Promise.all([
    api.get('/products'),
    api.get('/categories')
  ]);
  products.value = pRes.data.data;
  categories.value = cRes.data.data;
};

onMounted(() => {
  loadData();
});

const filteredProducts = computed(() => {
  return products.value.filter(p => {
    const matchesSearch = !searchQuery.value || p.name.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesCat = !selectedCategory.value || p.category_id === selectedCategory.value;
    const matchesLowStock = !onlyLowStock.value || Number(p.stock_quantity) <= Number(p.min_stock_alert);
    return matchesSearch && matchesCat && matchesLowStock;
  });
});

const openStockAdjust = (prod) => {
  adjustingProduct.value = prod;
  newStockVal.value = Number(prod.stock_quantity);
};

const saveStockAdjust = async () => {
  try {
    await api.post(`/products/${adjustingProduct.value.id}/adjust-stock`, {
      new_quantity: newStockVal.value,
      reason: 'Ajuste manual de control',
    });
    adjustingProduct.value = null;
    loadData();
  } catch (err) {
    alert('Error al ajustar stock: ' + err.message);
  }
};
</script>
