<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-hidden flex justify-end bg-petrol-900/50 backdrop-blur-sm"
    >
        <div class="w-full max-w-2xl bg-white h-full shadow-2xl flex flex-col">
            <!-- Encabezado -->
            <div
                class="px-6 py-4 bg-petrol-800 text-white flex items-center justify-between border-b border-petrol-700"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="h-10 w-10 rounded-xl bg-aguamarina-500 flex items-center justify-center shrink-0"
                    >
                        <AppIcon :name="IceCreamBowl" :size="20" />
                    </span>
                    <div>
                        <h2 class="font-bold text-lg leading-tight">
                            {{ drawerTitle }}
                        </h2>
                        <p class="text-xs text-aguamarina-300 font-medium">
                            {{
                                orderStore.activeOrder
                                    ? `Pedido #${orderStore.activeOrder.order_number}`
                                    : 'Nuevo Pedido'
                            }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="text-aguamarina-200 hover:text-white p-2 rounded-lg hover:bg-petrol-700 transition"
                    aria-label="Cerrar"
                    @click="emit('close')"
                >
                    <AppIcon :name="X" :size="20" />
                </button>
            </div>

            <!-- Cuerpo: catálogo a la izquierda, comanda a la derecha -->
            <div class="flex-1 flex overflow-hidden">
                <!-- Catálogo -->
                <div class="w-7/12 border-r border-aguamarina-100 flex flex-col bg-aguamarina-50">
                    <div
                        class="p-3 border-b border-aguamarina-100 overflow-x-auto flex gap-2 bg-white"
                    >
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition"
                            :class="
                                selectedCategory === null
                                    ? 'bg-aguamarina-600 text-white shadow-sm'
                                    : 'bg-aguamarina-50 text-petrol-600 hover:bg-aguamarina-100'
                            "
                            @click="selectedCategory = null"
                        >
                            Todos
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            type="button"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition"
                            :class="
                                selectedCategory === cat.id
                                    ? 'bg-aguamarina-600 text-white shadow-sm'
                                    : 'bg-aguamarina-50 text-petrol-600 hover:bg-aguamarina-100'
                            "
                            @click="selectedCategory = cat.id"
                        >
                            {{ cat.name }}
                        </button>
                    </div>

                    <div class="flex-1 p-3 overflow-y-auto grid grid-cols-2 gap-2.5 content-start">
                        <!-- Datos de entrega: solo para pedidos a domicilio -->
                        <div
                            v-if="orderStore.isDelivery"
                            class="col-span-2 bg-aguamarina-50 border border-aguamarina-200 rounded-xl p-3 space-y-2.5"
                        >
                            <div class="flex items-center gap-1.5">
                                <AppIcon :name="MapPin" :size="15" class="text-aguamarina-600" />
                                <h4 class="text-[11px] font-bold uppercase tracking-wide text-petrol-700">
                                    Datos de entrega
                                </h4>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <AppInput
                                    v-model="orderStore.delivery.name"
                                    label="Nombre de quien recibe"
                                    placeholder="María López"
                                />
                                <AppInput
                                    v-model="orderStore.delivery.phone"
                                    label="Teléfono"
                                    placeholder="300 123 4567"
                                />
                            </div>

                            <AppInput
                                v-model="orderStore.delivery.address"
                                label="Dirección"
                                placeholder="Cra 45 # 12-34, Apto 501"
                            />

                            <AppInput
                                v-model="orderStore.delivery.notes"
                                label="Referencias (opcional)"
                                placeholder="Timbre en la portería, portón azul"
                            />
                        </div>

                        <button
                            v-for="prod in filteredProducts"
                            :key="prod.id"
                            type="button"
                            class="text-left bg-white p-3 rounded-xl border border-aguamarina-100 hover:border-aguamarina-400 hover:shadow-md transition cursor-pointer flex flex-col justify-between"
                            @click="selectProductToAdd(prod)"
                        >
                            <div>
                                <h4 class="font-bold text-xs text-petrol-700 line-clamp-1">
                                    {{ prod.name }}
                                </h4>
                                <p class="text-[11px] text-niebla-400 line-clamp-1 mt-0.5">
                                    {{ prod.description }}
                                </p>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-aguamarina-700">
                                    ${{ formatMoney(prod.sale_price) }}
                                </span>
                                <AppBadge
                                    v-if="prod.has_variants"
                                    tone="info"
                                    :label="`${prod.variants?.length || 0} variantes`"
                                />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Comanda -->
                <div class="w-5/12 flex flex-col bg-white">
                    <div
                        class="p-3 border-b border-aguamarina-100 flex items-center justify-between bg-aguamarina-50"
                    >
                        <span class="text-xs font-bold text-petrol-700 uppercase tracking-wider">
                            Comanda ({{ orderStore.itemsCount }})
                        </span>
                        <button
                            v-if="orderStore.cartItems.length"
                            type="button"
                            class="text-[11px] text-rose-500 hover:underline font-medium"
                            @click="orderStore.cartItems = []"
                        >
                            Vaciar
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-3 space-y-2.5">
                        <div v-if="!orderStore.cartItems.length" class="text-center py-12">
                            <div
                                class="h-14 w-14 rounded-2xl bg-aguamarina-50 flex items-center justify-center mx-auto mb-3"
                            >
                                <AppIcon :name="ShoppingBag" :size="24" class="text-aguamarina-400" />
                            </div>
                            <p class="text-xs text-petrol-600">No hay productos en la comanda.</p>
                            <p class="text-[11px] text-niebla-300 mt-1">
                                Selecciona del catálogo para agregar.
                            </p>
                        </div>

                        <div
                            v-for="(item, idx) in orderStore.cartItems"
                            :key="idx"
                            class="p-2.5 rounded-xl border border-aguamarina-100 bg-aguamarina-50 hover:bg-aguamarina-100 transition"
                        >
                            <div class="flex justify-between items-start gap-2">
                                <div class="pr-2 min-w-0">
                                    <h5 class="text-xs font-bold text-petrol-700 leading-tight">
                                        {{ item.name }}
                                    </h5>
                                    <p class="text-[11px] text-aguamarina-700 font-semibold mt-0.5">
                                        ${{ formatMoney(item.unit_price) }} c/u
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="text-niebla-300 hover:text-rose-500 shrink-0"
                                    :aria-label="`Quitar ${item.name}`"
                                    @click="orderStore.removeItem(idx)"
                                >
                                    <AppIcon :name="X" :size="15" />
                                </button>
                            </div>

                            <div class="mt-2 flex items-center justify-between">
                                <div
                                    class="flex items-center gap-1 bg-white border border-aguamarina-200 rounded-lg p-0.5"
                                >
                                    <button
                                        type="button"
                                        class="w-5 h-5 flex items-center justify-center rounded text-petrol-600 hover:bg-aguamarina-50 font-bold text-xs"
                                        aria-label="Quitar una unidad"
                                        @click="orderStore.updateQuantity(idx, item.quantity - 1)"
                                    >
                                        <AppIcon :name="Minus" :size="12" />
                                    </button>
                                    <span class="w-6 text-center text-xs font-bold text-petrol-700">
                                        {{ item.quantity }}
                                    </span>
                                    <button
                                        type="button"
                                        class="w-5 h-5 flex items-center justify-center rounded text-petrol-600 hover:bg-aguamarina-50 font-bold text-xs"
                                        aria-label="Agregar una unidad"
                                        @click="orderStore.updateQuantity(idx, item.quantity + 1)"
                                    >
                                        <AppIcon :name="Plus" :size="12" />
                                    </button>
                                </div>
                                <span class="text-xs font-bold text-petrol-800">
                                    ${{ formatMoney(item.unit_price * item.quantity) }}
                                </span>
                            </div>

                            <input
                                v-model="item.notes"
                                type="text"
                                placeholder="Sabores o notas..."
                                class="mt-1.5 w-full text-[11px] px-2 py-1 bg-white border border-aguamarina-200 rounded text-petrol-600 placeholder-niebla-300 focus:outline-none focus:ring-2 focus:ring-aguamarina-400"
                            />
                        </div>
                    </div>

                    <!-- Totales y acciones -->
                    <div class="p-4 border-t border-aguamarina-100 bg-aguamarina-50 space-y-3">
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between text-niebla-400">
                                <span>Subtotal:</span>
                                <span>${{ formatMoney(orderStore.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-niebla-400 items-center">
                                <span>Propina sugerida:</span>
                                <input
                                    v-model.number="orderStore.tipAmount"
                                    type="number"
                                    min="0"
                                    class="w-20 px-1.5 py-0.5 bg-white border border-aguamarina-200 rounded text-right text-xs text-petrol-700"
                                />
                            </div>
                            <div
                                v-if="orderStore.isDelivery && orderStore.deliveryFee > 0"
                                class="flex justify-between text-niebla-400"
                            >
                                <span>Domicilio:</span>
                                <span>${{ formatMoney(orderStore.deliveryFee) }}</span>
                            </div>
                            <div
                                class="flex justify-between text-sm font-bold text-petrol-800 pt-1 border-t border-aguamarina-100"
                            >
                                <span>TOTAL:</span>
                                <span class="text-aguamarina-700">
                                    ${{ formatMoney(orderStore.total) }}
                                </span>
                            </div>
                        </div>

                        <p
                            v-if="orderStore.deliveryMissing.length"
                            class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5"
                        >
                            Para enviar a domicilio falta: {{ orderStore.deliveryMissing.join(', ') }}.
                        </p>

                        <div class="grid gap-2" :class="canCharge ? 'grid-cols-2' : 'grid-cols-1'">
                            <AppButton
                                variant="secondary"
                                size="sm"
                                class="w-full"
                                :label="saving ? 'Guardando...' : 'Guardar Comanda'"
                                :disabled="!orderStore.cartItems.length || saving || orderStore.deliveryMissing.length > 0"
                                :loading="saving"
                                @click="handleSaveOrder"
                            />
                            <AppButton
                                v-if="canCharge"
                                size="sm"
                                class="w-full"
                                label="Cobrar / Facturar"
                                :icon="CreditCard"
                                :disabled="!orderStore.cartItems.length"
                                @click="goToCheckout"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selector de variante -->
        <AppModal
            :open="Boolean(selectedProductForVariant)"
            title="Elige el tamaño o variante"
            :subtitle="selectedProductForVariant?.name"
            size="sm"
            @close="selectedProductForVariant = null"
        >
            <div class="space-y-2">
                <button
                    v-for="v in selectedProductForVariant?.variants || []"
                    :key="v.id"
                    type="button"
                    class="w-full p-2.5 border border-aguamarina-100 rounded-xl hover:border-aguamarina-400 hover:bg-aguamarina-50 transition cursor-pointer flex justify-between items-center"
                    @click="confirmVariantAdd(v)"
                >
                    <span class="text-xs font-bold text-petrol-700">{{ v.name }}</span>
                    <span class="text-xs font-bold text-aguamarina-700">
                        ${{ formatMoney(v.sale_price) }}
                    </span>
                </button>
            </div>

            <template #footer>
                <AppButton
                    variant="secondary"
                    class="w-full"
                    label="Cancelar"
                    @click="selectedProductForVariant = null"
                />
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { CreditCard, IceCreamBowl, MapPin, Minus, Plus, ShoppingBag, X } from 'lucide-vue-next';
import AppButton from './ui/AppButton.vue';
import AppBadge from './ui/AppBadge.vue';
import AppInput from './ui/AppInput.vue';
import AppIcon from './ui/AppIcon.vue';
import AppModal from './ui/AppModal.vue';
import { useAuthStore } from '../stores/auth';
import { useOrderStore } from '../stores/orders';

const props = defineProps({
    isOpen: Boolean,
    products: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'orderSaved']);

const orderStore = useOrderStore();
const authStore = useAuthStore();
const router = useRouter();

/**
 * El mesero arma y modifica comandas, pero no cobra. Sin este chequeo el
 * boton "Cobrar / Facturar" se le mostraba igual y el guard de rutas lo
 * rebotaba a Inicio sin explicar nada.
 */
const canCharge = computed(() => authStore.can('checkout_invoice'));

/** Titulo del encabezado: la mesa, o el tipo de venta sin mesa. */
const drawerTitle = computed(() => {
    if (orderStore.selectedTable) return orderStore.selectedTable.name;
    return orderStore.isDelivery ? 'Domicilio' : 'Venta para Llevar';
});

const selectedCategory = ref(null);
const selectedProductForVariant = ref(null);
const saving = ref(false);

const filteredProducts = computed(() => {
    if (!selectedCategory.value) return props.products;
    return props.products.filter((p) => p.category_id === selectedCategory.value);
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
