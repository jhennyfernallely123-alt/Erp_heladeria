<template>
  <div class="p-6 max-w-4xl mx-auto space-y-6">
    <!-- Top back navigation -->
    <div class="flex items-center justify-between">
      <router-link to="/" class="text-xs font-bold text-slate-500 hover:text-slate-800 flex items-center space-x-1">
        <span>← Volver al Salón</span>
      </router-link>
      <span class="text-xs font-extrabold uppercase px-2.5 py-1 rounded bg-pink-100 text-pink-700">
        Punto de Cobro
      </span>
    </div>

    <div v-if="loading" class="text-center py-20 text-slate-400">
      <span class="text-3xl animate-spin inline-block">🔄</span>
      <p class="text-xs mt-2 font-medium">Cargando datos del pedido...</p>
    </div>

    <div v-else-if="order" class="grid grid-cols-1 md:grid-cols-12 gap-6">
      <!-- Order Summary Card (Left 5 cols) -->
      <div class="md:col-span-5 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
              <h2 class="text-lg font-black text-slate-800">
                {{ order.table ? order.table.name : 'Venta para Llevar' }}
              </h2>
              <p class="text-xs text-pink-600 font-semibold">Orden #{{ order.order_number }}</p>
            </div>
            <span class="text-3xl">🧾</span>
          </div>

          <!-- Items list -->
          <div class="py-4 space-y-2.5 max-h-72 overflow-y-auto">
            <div v-for="item in order.items" :key="item.id" class="flex justify-between items-start text-xs">
              <div class="pr-2">
                <span class="font-bold text-slate-800">{{ Number(item.quantity) }}x</span>
                <span class="text-slate-700 ml-1">{{ item.product.name }}</span>
                <span v-if="item.variant" class="text-slate-500 block text-[11px]">({{ item.variant.name }})</span>
                <span v-if="item.notes" class="text-slate-400 block text-[10px] italic">* {{ item.notes }}</span>
              </div>
              <span class="font-extrabold text-slate-800 whitespace-nowrap">${{ formatMoney(item.subtotal) }}</span>
            </div>
          </div>
        </div>

        <!-- Totals summary -->
        <div class="pt-4 border-t border-slate-100 space-y-1.5 text-xs">
          <div class="flex justify-between text-slate-500">
            <span>Subtotal:</span>
            <span>${{ formatMoney(order.subtotal) }}</span>
          </div>
          <div v-if="order.discount_total > 0" class="flex justify-between text-emerald-600 font-semibold">
            <span>Descuento:</span>
            <span>-${{ formatMoney(order.discount_total) }}</span>
          </div>
          <div v-if="order.tip_amount > 0" class="flex justify-between text-slate-600">
            <span>Propina:</span>
            <span>+${{ formatMoney(order.tip_amount) }}</span>
          </div>
          <div class="flex justify-between text-base font-black text-slate-900 pt-2 border-t border-slate-200">
            <span>TOTAL A PAGAR:</span>
            <span class="text-pink-600">${{ formatMoney(order.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Payment & Customer Settlement Form (Right 7 cols) -->
      <div class="md:col-span-7 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-6">
        <div>
          <h3 class="font-bold text-sm text-slate-800 mb-1">Datos del Cliente (Opcional)</h3>
          <p class="text-xs text-slate-400 mb-3">Si requiere factura con datos específicos</p>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nombre / Razón Social</label>
              <input
                v-model="customer.name"
                type="text"
                class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-pink-500 focus:outline-none"
                placeholder="Consumidor Final"
              />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">NIT / Cédula</label>
              <div class="flex space-x-1">
                <select v-model="customer.doc_type" class="text-xs px-2 py-2 rounded-xl border border-slate-300 bg-slate-50">
                  <option value="CC">CC</option>
                  <option value="NIT">NIT</option>
                  <option value="CE">CE</option>
                </select>
                <input
                  v-model="customer.doc_number"
                  type="text"
                  class="flex-1 text-xs px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-pink-500 focus:outline-none"
                  placeholder="222222222222"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Payments Method Allocation -->
        <div class="pt-4 border-t border-slate-100">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-sm text-slate-800">Métodos de Pago</h3>
            <button
              @click="splitEqually(2)"
              class="text-xs text-pink-600 hover:text-pink-700 font-bold"
            >
              ➗ Dividir en 2 cuentas iguales
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(pay, idx) in payments"
              :key="idx"
              class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <select
                    v-model="pay.payment_method"
                    class="text-xs font-bold px-3 py-1.5 rounded-lg border border-slate-300 bg-white"
                  >
                    <option value="cash">💵 Efectivo</option>
                    <option value="card">💳 Tarjeta Débito/Crédito</option>
                    <option value="transfer">📱 Transferencia (Nequi/Daviplata)</option>
                  </select>
                </div>
                <button
                  v-if="payments.length > 1"
                  @click="removePayment(idx)"
                  class="text-rose-500 text-xs font-bold hover:underline"
                >
                  Eliminar
                </button>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-[10px] font-semibold text-slate-500">Monto asignado ($)</label>
                  <input
                    v-model.number="pay.amount"
                    type="number"
                    min="0"
                    class="w-full text-xs font-bold px-3 py-1.5 rounded-lg border border-slate-300 bg-white"
                  />
                </div>
                <div v-if="pay.payment_method !== 'cash'">
                  <label class="block text-[10px] font-semibold text-slate-500">Ref / Código Aprobación</label>
                  <input
                    v-model="pay.reference_code"
                    type="text"
                    placeholder="ej. OP-4589"
                    class="w-full text-xs px-3 py-1.5 rounded-lg border border-slate-300 bg-white"
                  />
                </div>
                <div v-else>
                  <label class="block text-[10px] font-semibold text-slate-500">Efectivo recibido (Cambio)</label>
                  <input
                    v-model.number="cashReceived"
                    type="number"
                    min="0"
                    placeholder="Monto entregado"
                    class="w-full text-xs px-3 py-1.5 rounded-lg border border-slate-300 bg-white"
                  />
                </div>
              </div>

              <!-- Change calculation if cash -->
              <div v-if="pay.payment_method === 'cash' && cashReceived > pay.amount" class="text-xs font-black text-emerald-700 bg-emerald-50 p-2 rounded-lg">
                💰 Cambio / Vuelto a entregar: ${{ formatMoney(cashReceived - pay.amount) }}
              </div>
            </div>
          </div>

          <button
            @click="addPaymentSplit"
            class="mt-3 text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center space-x-1"
          >
            <span>+ Agregar otro método (Pago Mixto)</span>
          </button>
        </div>

        <!-- Mode Toggle & Emit Invoice Button -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <input
              type="checkbox"
              id="dianMode"
              v-model="isDianMode"
              class="w-4 h-4 text-pink-600 rounded border-slate-300 focus:ring-pink-500"
            />
            <label for="dianMode" class="text-xs font-semibold text-slate-700 cursor-pointer">
              Generar Factura Electrónica (DIAN UBL 2.1)
            </label>
          </div>

          <div class="text-right">
            <span class="block text-[11px] text-slate-400">Total cubierto: ${{ formatMoney(totalPaid) }}</span>
            <span
              v-if="remainingBalance !== 0"
              class="block text-xs font-bold"
              :class="remainingBalance > 0 ? 'text-rose-600' : 'text-amber-600'"
            >
              {{ remainingBalance > 0 ? `Falta: $${formatMoney(remainingBalance)}` : `Sobra: $${formatMoney(Math.abs(remainingBalance))}` }}
            </span>
          </div>
        </div>

        <button
          @click="submitCheckout"
          :disabled="isSubmitting || Math.abs(remainingBalance) > 0.05"
          class="w-full py-4 px-4 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-extrabold rounded-2xl shadow-xl shadow-emerald-600/20 transition transform active:scale-98 disabled:opacity-40 text-sm tracking-wide"
        >
          {{ isSubmitting ? 'Generando Factura y Ticket...' : 'Emitir Factura & Cerrar Mesa' }}
        </button>
      </div>
    </div>

    <!-- Success Modal with PDF print action -->
    <div v-if="issuedInvoice" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl text-center space-y-4">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-500/20">
          ✅
        </div>
        <h3 class="text-lg font-black text-slate-800">¡Venta Completada con Éxito!</h3>
        <p class="text-xs text-slate-500">
          Factura No. <strong class="text-slate-800">{{ issuedInvoice.invoice_number }}</strong>
          <span class="block text-[11px] mt-1 text-emerald-600 font-semibold">
            Modo: {{ issuedInvoice.billing_mode === 'dian' ? 'Electrónica DIAN' : 'Interno' }}
          </span>
        </p>

        <div class="grid grid-cols-2 gap-3 pt-2">
          <a
            :href="`/api/v1/invoices/${issuedInvoice.id}/pdf`"
            target="_blank"
            class="py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs flex items-center justify-center space-x-1 shadow"
          >
            <span>🖨️ Imprimir Ticket</span>
          </a>
          <button
            @click="finishCheckout"
            class="py-3 px-4 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl text-xs shadow"
          >
            Siguiente Pedido
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';

const route = useRoute();
const router = useRouter();

const orderId = route.params.orderId;
const order = ref(null);
const loading = ref(true);
const isSubmitting = ref(false);
const issuedInvoice = ref(null);

const isDianMode = ref(false);
const cashReceived = ref(0);

const customer = ref({
  name: 'Consumidor Final',
  doc_type: 'CC',
  doc_number: '222222222222',
});

const payments = ref([
  { payment_method: 'cash', amount: 0, reference_code: '' }
]);

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const loadOrder = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/orders/${orderId}`);
    order.value = res.data.data;
    payments.value[0].amount = Number(order.value.total);
  } catch (err) {
    alert('Error al cargar pedido: ' + err.message);
    router.push('/');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadOrder();
});

const totalPaid = computed(() => {
  return payments.value.reduce((acc, p) => acc + Number(p.amount || 0), 0);
});

const remainingBalance = computed(() => {
  if (!order.value) return 0;
  return Number(order.value.total) - totalPaid.value;
});

const addPaymentSplit = () => {
  const currentTotal = totalPaid.value;
  const rem = Math.max(0, Number(order.value.total) - currentTotal);
  payments.value.push({ payment_method: 'card', amount: rem, reference_code: '' });
};

const removePayment = (idx) => {
  payments.value.splice(idx, 1);
};

const splitEqually = (parts) => {
  const tot = Number(order.value.total);
  const partVal = Math.floor(tot / parts);
  const remainder = tot - (partVal * parts);

  payments.value = [];
  for (let i = 0; i < parts; i++) {
    payments.value.push({
      payment_method: i === 0 ? 'cash' : 'card',
      amount: i === 0 ? partVal + remainder : partVal,
      reference_code: '',
    });
  }
};

const submitCheckout = async () => {
  isSubmitting.value = true;
  try {
    const res = await api.post('/invoices', {
      order_id: order.value.id,
      customer_name: customer.value.name,
      customer_doc_type: customer.value.doc_type,
      customer_doc_number: customer.value.doc_number,
      billing_mode: isDianMode.value ? 'dian' : 'internal',
      payments: payments.value.map(p => ({
        payment_method: p.payment_method,
        amount: Number(p.amount),
        reference_code: p.reference_code || null,
      })),
    });

    issuedInvoice.value = res.data.data.invoice;
  } catch (err) {
    alert('Error al facturar: ' + (err.response?.data?.message || err.message));
  } finally {
    isSubmitting.value = false;
  }
};

const finishCheckout = () => {
  issuedInvoice.value = null;
  router.push('/');
};
</script>
