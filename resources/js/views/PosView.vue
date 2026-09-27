<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <PageHeader title="Punto de Venta & Mesas" subtitle="Control visual del salón y comandas en tiempo real">
            <template #actions>
                <AppButton
                    label="Venta para Llevar / Mostrador"
                    :icon="ShoppingBag"
                    @click="openTakeawayOrder"
                />
                <AppButton
                    variant="secondary"
                    :icon="RefreshCw"
                    :icon-size="18"
                    title="Actualizar mesas"
                    aria-label="Actualizar mesas"
                    @click="refreshData"
                />
            </template>
        </PageHeader>

        <!-- Leyenda de estados -->
        <div
            class="flex flex-wrap items-center gap-4 bg-white p-3.5 rounded-2xl border border-aguamarina-100 shadow-sm text-xs font-semibold text-petrol-600"
        >
            <span class="text-niebla-400 font-medium">Estado del salón:</span>
            <span v-for="legend in legends" :key="legend.status" class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full" :class="legend.dot" />
                <span>{{ legend.label }} ({{ legend.count }})</span>
            </span>
        </div>

        <!-- Mesas -->
        <div v-if="tableStore.loading" class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm">
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div v-for="n in 8" :key="n" class="rounded-2xl border border-aguamarina-100 p-5 space-y-3">
                    <div class="h-5 w-20 rounded bg-aguamarina-50 animate-pulse" />
                    <div class="h-3 w-28 rounded bg-aguamarina-50 animate-pulse" />
                    <div class="h-8 w-full rounded bg-aguamarina-50 animate-pulse" />
                </div>
            </div>
        </div>

        <div
            v-else-if="tableStore.tables.length === 0"
            class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm"
        >
            <AppEmptyState
                :icon="Armchair"
                title="Sin mesas registradas"
                description="Registra las mesas del salón para poder tomar pedidos."
            />
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <button
                v-for="table in tableStore.tables"
                :key="table.id"
                type="button"
                class="text-left bg-white rounded-2xl p-5 border-2 transition-all duration-200 hover:shadow-lg cursor-pointer flex flex-col justify-between group"
                :class="tableClasses(table.status)"
                @click="handleTableClick(table)"
            >
                <div class="flex items-start justify-between mb-3 gap-2">
                    <div class="min-w-0">
                        <AppBadge :tone="badgeTone(table.status)" :label="table.name" />
                        <p class="text-[11px] text-niebla-400 mt-2 font-medium flex items-center gap-1">
                            <AppIcon :name="Users" :size="13" />
                            Capacidad: {{ table.capacity }} personas
                        </p>
                    </div>
                    <AppIcon
                        :name="statusIcon(table.status)"
                        :size="26"
                        class="shrink-0 text-petrol-400 group-hover:scale-110 transition-transform"
                    />
                </div>

                <div
                    v-if="table.status === 'occupied' && table.active_order"
                    class="mt-3 pt-3 border-t border-aguamarina-100 space-y-1.5"
                >
                    <div class="flex justify-between items-center text-xs gap-2">
                        <span class="font-semibold text-petrol-700 truncate">
                            #{{ table.active_order.order_number }}
                        </span>
                        <AppBadge
                            :label="table.active_order.status"
                            :tone="badgeTone(table.status)"
                        />
                    </div>
                    <p class="text-[11px] text-niebla-400 truncate">
                        {{ table.active_order.items?.length || 0 }} productos en mesa
                    </p>
                    <div class="flex justify-between items-center pt-1 font-bold text-petrol-800 text-sm">
                        <span>Total:</span>
                        <span>${{ formatMoney(table.active_order.total) }}</span>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-4 pt-3 border-t border-aguamarina-100 flex items-center justify-between text-xs"
                >
                    <span class="text-niebla-300">Toca para abrir pedido</span>
                    <span class="font-semibold text-aguamarina-600 flex items-center gap-1">
                        Disponible <AppIcon :name="ChevronRight" :size="13" />
                    </span>
                </div>
            </button>
        </div>

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
import { computed, onMounted, ref } from 'vue';
import {
    Armchair,
    BookmarkCheck,
    ChevronRight,
    IceCreamBowl,
    RefreshCw,
    ShoppingBag,
    Users,
} from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppBadge from '../components/ui/AppBadge.vue';
import AppEmptyState from '../components/ui/AppEmptyState.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import OrderDrawer from '../components/OrderDrawer.vue';
import { useTableStore } from '../stores/tables';
import { useOrderStore } from '../stores/orders';
import api from '../api';

const tableStore = useTableStore();
const orderStore = useOrderStore();

const isDrawerOpen = ref(false);
const products = ref([]);
const categories = ref([]);

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const legends = computed(() => [
    { status: 'available', label: 'Libre', dot: 'bg-emerald-500', count: tableStore.availableTables.length },
    { status: 'occupied', label: 'Ocupada / Comanda activa', dot: 'bg-amber-500', count: tableStore.occupiedTables.length },
    { status: 'reserved', label: 'Reservada', dot: 'bg-aguamarina-500', count: tableStore.reservedTables.length },
]);

const tableClasses = (status) =>
    ({
        available: 'border-aguamarina-300 hover:border-aguamarina-500 hover:bg-aguamarina-50/40',
        occupied: 'border-amber-300 hover:border-amber-500 hover:bg-amber-50/40',
        reserved: 'border-petrol-200 hover:border-petrol-400 hover:bg-petrol-50',
    })[status] || 'border-aguamarina-100';

const badgeTone = (status) =>
    ({ available: 'success', occupied: 'warning', reserved: 'neutral' })[status] || 'neutral';

// AppIcon espera el componente, no el nombre del icono.
const statusIcons = { available: Armchair, occupied: IceCreamBowl, reserved: BookmarkCheck };
const statusIcon = (status) => statusIcons[status] || Armchair;

const loadCatalog = async () => {
    try {
        const [pRes, cRes] = await Promise.all([api.get('/products'), api.get('/categories')]);
        products.value = pRes.data.data;
        categories.value = cRes.data.data;
    } catch (err) {
        console.error('Error al cargar catálogo', err);
    }
};

const refreshData = async () => {
    await Promise.all([tableStore.fetchTables(), loadCatalog()]);
};

onMounted(refreshData);

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
