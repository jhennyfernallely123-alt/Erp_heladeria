<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden flex justify-end bg-slate-900/60 backdrop-blur-sm transition-opacity">
    <div class="w-full max-w-2xl bg-white h-full shadow-2xl flex flex-col transform transition-transform">
      <!-- Header -->
      <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
        <div class="flex items-center space-x-3">
          <span class="text-2xl">🍧</span>
          <div>
            <h2 class="font-bold text-lg leading-tight">
              {{ orderStore.selectedTable ? orderStore.selectedTable.name : 'Venta para Llevar' }}
            </h2>
            <p class="text-xs text-pink-400 font-medium">
              {{ orderStore.activeOrder ? `Pedido #${orderStore.activeOrder.order_number}` : 'Nuevo Pedido' }}
            </p>
          </div>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800 transition">
          ✕
        </button>
      </div>

      <!-- Main Body: Two Columns (Catalog Left, Cart Right) -->
      <div class="flex-1 flex overflow-hidden">
        <!-- Catalog Picker (Left 55%) -->
        <div class="w-7/12 border-r border-slate-200 flex flex-col bg-slate-50">
          <!-- Category Tabs -->
          <div class="p-3 border-b border-slate-200 overflow-x-auto flex space-x-2 scrollbar-none bg-white">
            <button
              @click="selectedCategory = null"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition"
              :class="selectedCategory === null ? 'bg-pink-600 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
            >
              Todos
            </button>
            <button
              v-for="cat in categories"
              :key="cat.id"
              @click="selectedCategory = cat.id"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition"
              :class="selectedCategory === cat.id ? 'bg-pink-600 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
            >
              {{ cat.name }}
            </button>
          </div>

          <!-- Product Grid -->
          <div class="flex-1 p-3 overflow-y-auto grid grid-cols-2 gap-2.5 content-start">
            <div
              v-for="prod in filteredProducts"
              :key="prod.id"
              @click="selectProductToAdd(prod)"
              class="bg-white p-3 rounded-xl border border-slate-200 hover:border-pink-500 hover:shadow-md transition cursor-pointer flex flex-col justify-between"
            >
              <div>
                <h4 class="font-bold text-xs text-slate-800 line-clamp-1">{{ prod.name }}</h4>
                <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ prod.description }}</p>
              </div>
              <div class="mt-2 flex items-center justify-between">
                <span class="text-xs font-extrabold text-pink-600">
                  ${{ formatMoney(prod.sale_price) }}
                </span>
                <span v-if="prod.has_variants" class="text-[10px] bg-pink-100 text-pink-700 px-1.5 py-0.5 rounded font-bold">
                  Variantes
                </span>
                <span v-else class="text-[10px] text-slate-500">
                  Stock: {{ Number(prod.stock_quantity) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Active Cart (Right 45%) -->
        <div class="w-5/12 flex flex-col bg-white">
          <div class="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
              Comanda ({{ orderStore.itemsCount }})
            </span>
            <button
              v-if="orderStore.cartItems.length"
              @click="orderStore.cartItems = []"
              class="text-[11px] text-rose-500 hover:underline font-medium"
            >
              Vaciar
            </button>
          </div>

          <!-- Cart items list -->
          <div class="flex-1 overflow-y-auto p-3 space-y-2.5">
            <div v-if="!orderStore.cartItems.length" class="text-center py-12 text-slate-400">
              <span class="text-3xl block mb-2">🍨</span>
              <p class="text-xs">No hay productos en la comanda.</p>
              <p class="text-[11px] text-slate-400 mt-1">Selecciona del catálogo para agregar.</p>
            </div>

            <div
              v-for="(item, idx) in orderStore.cartItems"
              :key="idx"
              class="p-2.5 rounded-xl border border-slate-100 bg-slate-50 hover:bg-slate-100 transition relative group"
            >
              <div class="flex justify-between items-start">
                <div class="pr-4">
                  <h5 class="text-xs font-bold text-slate-800 leading-tight">{{ item.name }}</h5>
                  <p class="text-[11px] text-pink-600 font-semibold mt-0.5">
                    ${{ formatMoney(item.unit_price) }} c/u
                  </p>
                </div>
                <button @click="orderStore.removeItem(idx)" class="text-slate-400 hover:text-rose-500 text-xs">
                  ✕
                </button>
              </div>

              <!-- Quantity changer & subtotal -->
              <div class="mt-2 flex items-center justify-between">
                <div class="flex items-center space-x-1.5 bg-white border border-slate-200 rounded-lg p-0.5">
                  <button
                    @click="orderStore.updateQuantity(idx, item.quantity - 1)"
                    class="w-5 h-5 flex items-center justify-center rounded text-slate-600 hover:bg-slate-100 font-bold text-xs"
                  >
                    -
                  </button>
                  <span class="w-6 text-center text-xs font-bold text-slate-800">{{ item.quantity }}</span>
                  <button
                    @click="orderStore.updateQuantity(idx, item.quantity + 1)"
                    class="w-5 h-5 flex items-center justify-center rounded text-slate-600 hover:bg-slate-100 font-bold text-xs"
                  >
                    +
                  </button>
                </div>
                <span class="text-xs font-black text-slate-800">
                  ${{ formatMoney(item.unit_price * item.quantity) }}
                </span>
              </div>

              <!-- Item notes -->
              <input
                v-model="item.notes"
                type="text"
                placeholder="Sabores o notas..."
                class="mt-1.5 w-full text-[11px] px-2 py-1 bg-white border border-slate-200 rounded text-slate-600 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-pink-500"
              />
            </div>
          </div>

          <!-- Totals & Actions Footer -->
          <div class="p-4 border-t border-slate-200 bg-slate-50 space-y-3">
            <div class="space-y-1.5 text-xs">
              <div class="flex justify-between text-slate-500">
                <span>Subtotal:</span>
                <span>${{ formatMoney(orderStore.subtotal) }}</span>
              </div>
              <div class="flex justify-between text-slate-500 items-center">
                <span>Propina sugerida:</span>
                <input
                  v-model.number="orderStore.tipAmount"
                  type="number"
                  min="0"
                  class="w-20 px-1.5 py-0.5 bg-white border border-slate-300 rounded text-right text-xs"
                />
              </div>
              <div class="flex justify-between text-sm font-black text-slate-900 pt-1 border-t border-slate-200">
                <span>TOTAL:</span>
                <span class="text-pink-600">${{ formatMoney(orderStore.total) }}</span>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <button
                @click="handleSaveOrder"
                :disabled="!orderStore.cartItems.length || saving"
                class="w-full py-2.5 px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs transition disabled:opacity-40"
              >
                {{ saving ? 'Guardando...' : 'Guardar Comanda' }}
              </button>

              <button
                @click="goToCheckout"
                :disabled="!orderStore.cartItems.length"
                class="w-full py-2.5 px-3 bg-gradient-to-r from-pink-600 to-rose-500 hover:from-pink-500 hover:to-rose-400 text-white font-bold rounded-xl text-xs shadow-md shadow-pink-600/20 transition disabled:opacity-40"
              >
                💳 Cobrar / Facturar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Variant Selector Modal -->
    <div v-if="selectedProductForVariant" class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl">
        <h3 class="font-bold text-sm text-slate-800 mb-1">Elige el tamaño o variante</h3>
        <p class="text-xs text-slate-500 mb-3">{{ selectedProductForVariant.name }}</p>

        <div class="space-y-2 mb-4">
          <div
            v-for="v in selectedProductForVariant.variants"
            :key="v.id"
            @click="confirmVariantAdd(v)"
            class="p-2.5 border border-slate-200 rounded-xl hover:border-pink-500 hover:bg-pink-50 transition cursor-pointer flex justify-between items-center"
          >
            <span class="text-xs font-bold text-slate-700">{{ v.name }}</span>
            <span class="text-xs font-extrabold text-pink-600">${{ formatMoney(v.sale_price) }}</span>
          </div>
        </div>

        <button @click="selectedProductForVariant = null" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">
          Cancelar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useOrderStore } from '../stores/orders';
import { useRouter } from 'vue-router';

const props = defineProps({
  isOpen: Boolean,
  products: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'orderSaved']);

const orderStore = useOrderStore();
const router = useRouter();

const selectedCategory = ref(null);
const selectedProductForVariant = ref(null);
const saving = ref(false);

const filteredProducts = computed(() => {
  if (!selectedCategory.value) return props.products;
  return props.products.filter(p => p.category_id === selectedCategory.value);
});

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const selectProductToAdd = (product) => {
  if (product.has_variants && product.variants?.length) {
    selectedProductForVariant.value = product;
  } else {
    orderStore.addItem(product);
  }
};

const confirmVariantAdd = (variant) => {
  orderStore.addItem(selectedProductForVariant.value, variant);
  selectedProductForVariant.value = null;
};

const handleSaveOrder = async () => {
  saving.value = true;
  try {
    const saved = await orderStore.saveOrder();
    emit('orderSaved', saved);
    emit('close');
  } catch (err) {
    alert('Error al guardar el pedido: ' + (err.response?.data?.message || err.message));
  } finally {
    saving.value = false;
  }
};

const goToCheckout = async () => {
  saving.value = true;
  try {
    const saved = await orderStore.saveOrder();
    emit('close');
    router.push(`/cobro/${saved.id}`);
  } catch (err) {
    alert('Error al procesar el pedido para cobro: ' + (err.response?.data?.message || err.message));
  } finally {
    saving.value = false;
  }
};
</script>
