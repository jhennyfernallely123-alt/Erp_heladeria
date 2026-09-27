<template>
    <div class="p-6 max-w-4xl mx-auto space-y-6">
        <!-- Navegacion superior -->
        <div class="flex items-center justify-between">
            <router-link
                to="/"
                class="text-xs font-bold text-niebla-400 hover:text-petrol-700 flex items-center gap-1"
            >
                <AppIcon :name="ArrowLeft" :size="14" />
                Volver al salón
            </router-link>
            <AppBadge tone="info" label="Punto de Cobro" />
        </div>

        <div v-if="loading" class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm">
            <div class="p-6 space-y-3">
                <div class="h-4 w-48 rounded bg-aguamarina-50 animate-pulse" />
                <div class="h-3 w-full rounded bg-aguamarina-50 animate-pulse" />
                <div class="h-3 w-4/5 rounded bg-aguamarina-50 animate-pulse" />
            </div>
        </div>

        <div v-else-if="order" class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Resumen del pedido -->
            <div
                class="md:col-span-5 bg-white rounded-2xl p-6 border border-aguamarina-100 shadow-sm flex flex-col justify-between"
            >
                <div>
                    <div
                        class="flex items-center justify-between pb-4 border-b border-aguamarina-100"
                    >
                        <div>
                            <h2 class="text-lg font-bold text-petrol-800">
                                {{ order.table ? order.table.name : 'Venta para Llevar' }}
                            </h2>
                            <p class="text-xs text-aguamarina-700 font-semibold">
                                Orden #{{ order.order_number }}
                            </p>
                        </div>
                        <span
                            class="h-11 w-11 rounded-xl bg-aguamarina-50 flex items-center justify-center shrink-0"
                        >
                            <AppIcon :name="Receipt" :size="22" class="text-aguamarina-600" />
                        </span>
                    </div>

                    <div class="py-4 space-y-2.5 max-h-72 overflow-y-auto">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex justify-between items-start text-xs gap-2"
                        >
                            <div class="pr-2 min-w-0">
                                <span class="font-bold text-petrol-700">
                                    {{ Number(item.quantity) }}x
                                </span>
                                <span class="text-petrol-600 ml-1">{{ item.product.name }}</span>
                                <span
                                    v-if="item.variant"
                                    class="text-niebla-400 block text-[11px]"
                                >
                                    ({{ item.variant.name }})
                                </span>
                                <span v-if="item.notes" class="text-niebla-300 block text-[10px] italic">
                                    * {{ item.notes }}
                                </span>
                            </div>
                            <span class="font-bold text-petrol-800 whitespace-nowrap">
                                ${{ formatMoney(item.subtotal) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-aguamarina-100 space-y-1.5 text-xs">
                    <div class="flex justify-between text-niebla-400">
                        <span>Subtotal:</span>
                        <span>${{ formatMoney(order.subtotal) }}</span>
                    </div>
                    <div
                        v-if="order.discount_total > 0"
                        class="flex justify-between text-emerald-600 font-semibold"
                    >
                        <span>Descuento:</span>
                        <span>-${{ formatMoney(order.discount_total) }}</span>
                    </div>
                    <div v-if="order.tip_amount > 0" class="flex justify-between text-petrol-600">
                        <span>Propina:</span>
                        <span>+${{ formatMoney(order.tip_amount) }}</span>
                    </div>
                    <div
                        class="flex justify-between text-base font-bold text-petrol-800 pt-2 border-t border-aguamarina-100"
                    >
                        <span>TOTAL A PAGAR:</span>
                        <span class="text-aguamarina-700">${{ formatMoney(order.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Formulario de cobro -->
            <div
                class="md:col-span-7 bg-white rounded-2xl p-6 border border-aguamarina-100 shadow-sm space-y-6"
            >
                <div>
                    <h3 class="font-bold text-sm text-petrol-800 mb-1">Datos del cliente (opcional)</h3>
                    <p class="text-xs text-niebla-300 mb-3">
                        Si requiere factura con datos específicos
                    </p>

                    <div class="grid grid-cols-2 gap-3">
                        <AppInput
                            v-model="customer.name"
                            label="Nombre / Razón Social"
                            placeholder="Consumidor Final"
                        />
                        <div class="space-y-1.5">
                            <p class="text-xs font-semibold text-petrol-700">NIT / Cédula</p>
                            <div class="flex gap-1">
                                <AppSelect v-model="customer.doc_type" class="w-24 shrink-0">
                                    <option value="CC">CC</option>
                                    <option value="NIT">NIT</option>
                                    <option value="CE">CE</option>
                                </AppSelect>
                                <AppInput v-model="customer.doc_number" placeholder="222222222222" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metodos de pago -->
                <div class="pt-4 border-t border-aguamarina-100">
                    <div class="flex items-center justify-between mb-3 gap-2">
                        <h3 class="font-bold text-sm text-petrol-800">Métodos de pago</h3>
                        <button
                            type="button"
                            class="text-xs text-aguamarina-700 hover:text-aguamarina-600 font-bold flex items-center gap-1"
                            @click="splitEqually(2)"
                        >
                            <AppIcon :name="Split" :size="14" />
                            Dividir en 2 cuentas iguales
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(pay, idx) in payments"
                            :key="idx"
                            class="p-3 bg-aguamarina-50 border border-aguamarina-100 rounded-xl space-y-2"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <AppSelect v-model="pay.payment_method" class="max-w-[15rem]">
                                    <option value="cash">Efectivo</option>
                                    <option value="card">Tarjeta Débito/Crédito</option>
                                    <option value="transfer">Transferencia (Nequi/Daviplata)</option>
                                </AppSelect>
                                <button
                                    v-if="payments.length > 1"
                                    type="button"
                                    class="text-rose-500 text-xs font-bold hover:underline"
                                    @click="removePayment(idx)"
                                >
                                    Eliminar
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <AppInput v-model.number="pay.amount" label="Monto asignado ($)" type="number" />
                                <AppInput
                                    v-if="pay.payment_method !== 'cash'"
                                    v-model="pay.reference_code"
                                    label="Ref / Código Aprobación"
                                    placeholder="ej. OP-4589"
                                />
                                <AppInput
                                    v-else
                                    v-model.number="cashReceived"
                                    label="Efectivo recibido (cambio)"
                                    type="number"
                                    placeholder="Monto entregado"
                                />
                            </div>

                            <p
                                v-if="pay.payment_method === 'cash' && cashReceived > pay.amount"
                                class="text-xs font-bold text-emerald-700 bg-emerald-50 p-2 rounded-lg flex items-center gap-1.5"
                            >
                                <AppIcon :name="Banknote" :size="14" />
                                Cambio / vuelto a entregar: ${{ formatMoney(cashReceived - pay.amount) }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="mt-3 text-xs font-bold text-petrol-600 hover:text-petrol-800 flex items-center gap-1"
                        @click="addPaymentSplit"
                    >
                        <AppIcon :name="Plus" :size="14" />
                        Agregar otro método (pago mixto)
                    </button>
                </div>

                <!-- DIAN y balance -->
                <div class="pt-4 border-t border-aguamarina-100 flex items-center justify-between gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="isDianMode"
                            type="checkbox"
                            class="w-4 h-4 rounded border-aguamarina-300 text-aguamarina-600 focus:ring-aguamarina-500"
                        />
                        <span class="text-xs font-semibold text-petrol-700">
                            Generar factura electrónica (DIAN UBL 2.1)
                        </span>
                    </label>

                    <div class="text-right shrink-0">
                        <span class="block text-[11px] text-niebla-400">
                            Total cubierto: ${{ formatMoney(totalPaid) }}
                        </span>
                        <span
                            v-if="remainingBalance !== 0"
                            class="block text-xs font-bold"
                            :class="remainingBalance > 0 ? 'text-rose-600' : 'text-amber-600'"
                        >
                            {{
                                remainingBalance > 0
                                    ? `Falta: $${formatMoney(remainingBalance)}`
                                    : `Sobra: $${formatMoney(Math.abs(remainingBalance))}`
                            }}
                        </span>
                    </div>
                </div>

                <AppButton
                    class="w-full"
                    size="md"
                    :label="isSubmitting ? 'Generando factura y ticket...' : 'Emitir factura & cerrar mesa'"
                    :loading="isSubmitting"
                    :disabled="isSubmitting || Math.abs(remainingBalance) > 0.05"
                    @click="submitCheckout"
                />
            </div>
        </div>

        <!-- Modal de exito -->
        <AppModal :open="Boolean(issuedInvoice)" size="sm" @close="finishCheckout">
            <div class="text-center space-y-4">
                <div
                    class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto"
                >
                    <AppIcon :name="CheckCircle2" :size="32" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-petrol-800">¡Venta completada con éxito!</h3>
                    <p class="text-xs text-niebla-400 mt-1">
                        Factura No. <strong class="text-petrol-700">{{ issuedInvoice?.invoice_number }}</strong>
                        <span class="block text-[11px] mt-1 text-emerald-600 font-semibold">
                            Modo: {{ issuedInvoice?.billing_mode === 'dian' ? 'Electrónica DIAN' : 'Interno' }}
                        </span>
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="grid grid-cols-2 gap-3">
                    <AppButton
                        variant="secondary"
                        label="Ver factura"
                        :icon="FileText"
                        @click="previewOpen = true"
                    />
                    <AppButton label="Siguiente pedido" @click="finishCheckout" />
                </div>
            </template>
        </AppModal>

        <!-- Previsualizacion de la factura -->
        <InvoicePreviewModal
            :open="previewOpen"
            :invoice-id="issuedInvoice?.id"
            :title="`Factura ${issuedInvoice?.invoice_number || ''}`"
            :subtitle="issuedInvoice?.billing_mode === 'dian' ? 'Facturación electrónica DIAN UBL 2.1' : 'Facturación interna'"
            @close="previewOpen = false"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    ArrowLeft,
    Banknote,
    CheckCircle2,
    FileText,
    Plus,
    Receipt,
    Split,
} from 'lucide-vue-next';
import AppBadge from '../components/ui/AppBadge.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppModal from '../components/ui/AppModal.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import InvoicePreviewModal from '../components/invoice/InvoicePreviewModal.vue';
import api from '../api';

const route = useRoute();
const router = useRouter();

const orderId = route.params.orderId;
const order = ref(null);
const loading = ref(true);
const isSubmitting = ref(false);
const issuedInvoice = ref(null);
const previewOpen = ref(false);

const isDianMode = ref(false);
const cashReceived = ref(0);

const customer = ref({
    name: 'Consumidor Final',
    doc_type: 'CC',
    doc_number: '222222222222',
});

const payments = ref([{ payment_method: 'cash', amount: 0, reference_code: '' }]);

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

onMounted(loadOrder);

const totalPaid = computed(() =>
    payments.value.reduce((acc, p) => acc + Number(p.amount || 0), 0)
);

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
    const remainder = tot - partVal * parts;

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
            payments: payments.value.map((p) => ({
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
    previewOpen.value = false;
    issuedInvoice.value = null;
    router.push('/');
};
</script>
