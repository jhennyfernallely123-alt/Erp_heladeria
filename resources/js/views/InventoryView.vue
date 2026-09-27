<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <PageHeader title="Inventario" subtitle="Existencias por producto y variante">
            <template #actions>
                <AppButton label="Registrar entrada" :icon="Plus" @click="openFirstCritical" />
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <StatCard
                label="Productos en inventario"
                :value="inventoryStore.stats.total_products"
                :icon="Boxes"
                tone="indigo"
            />
            <StatCard
                label="Unidades en stock"
                :value="formatNumber(inventoryStore.stats.total_units)"
                :icon="Package"
                tone="emerald"
            />
            <StatCard
                label="Stock bajo"
                :value="inventoryStore.stats.low_count"
                :icon="AlertTriangle"
                tone="amber"
            />
            <StatCard
                label="Stock crítico"
                :value="inventoryStore.stats.critical_count"
                :icon="AlertOctagon"
                tone="rose"
            />
        </div>

        <div
            class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center gap-3"
        >
            <div class="flex-1 min-w-[220px]">
                <AppInput v-model="search" placeholder="Buscar producto" :icon="Search" />
            </div>
            <div class="min-w-[180px]">
                <AppSelect v-model="categoryId">
                    <option :value="null">Todas las categorías</option>
                    <option v-for="cat in productStore.categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </AppSelect>
            </div>
            <div class="min-w-[160px]">
                <AppSelect v-model="statusFilter">
                    <option value="all">Todos los estados</option>
                    <option value="normal">Normal</option>
                    <option value="low">Bajo</option>
                    <option value="critical">Crítico</option>
                </AppSelect>
            </div>
        </div>

        <div class="space-y-4">
            <AppTable
                :loading="inventoryStore.loading"
                :total="inventoryStore.items.length"
                empty-title="Sin existencias"
                empty-description="Todavía no hay registros de stock para los productos del catálogo."
            >
                <template #head>
                    <th class="p-3.5">Producto</th>
                    <th class="p-3.5">Categoría</th>
                    <th class="p-3.5">Variante</th>
                    <th class="p-3.5 text-right">Cantidad</th>
                    <th class="p-3.5 text-right">Stock mínimo</th>
                    <th class="p-3.5 text-center">Estado</th>
                    <th class="p-3.5 text-right">Acciones</th>
                </template>

                <tr
                    v-for="row in paged"
                    :key="row.id"
                    class="transition-colors hover:bg-slate-50"
                    :class="row.status === 'critical' ? 'bg-rose-50/40' : ''"
                >
                    <td class="p-3.5">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0"
                            >
                                <AppIcon
                                    :name="categoryIcon(row)"
                                    :size="20"
                                    class="text-slate-400"
                                />
                            </div>
                            <p class="text-sm font-semibold text-slate-800 truncate">
                                {{ row.product?.name }}
                            </p>
                        </div>
                    </td>
                    <td class="p-3.5">
                        <AppBadge :label="row.product?.category?.name || 'Sin categoría'" />
                    </td>
                    <td class="p-3.5">
                        <span v-if="row.variant" class="text-sm text-slate-700">
                            {{ row.variant.name }}
                        </span>
                        <AppBadge v-else label="Producto base" />
                    </td>
                    <td class="p-3.5 text-right font-semibold text-slate-800">
                        {{ formatQuantity(row) }}
                    </td>
                    <td class="p-3.5 text-right text-slate-500">{{ formatNumber(row.min_alert) }}</td>
                    <td class="p-3.5 text-center">
                        <AppBadge :tone="statusTone(row.status)" dot :label="statusLabel(row.status)" />
                    </td>
                    <td class="p-3.5">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                class="p-2 rounded-lg text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition"
                                title="Ajustar stock"
                                :aria-label="`Ajustar stock de ${row.product?.name}`"
                                @click="openAdjust(row)"
                            >
                                <AppIcon :name="SlidersHorizontal" :size="16" />
                            </button>
                        </div>
                    </td>
                </tr>
            </AppTable>

            <div
                v-if="inventoryStore.items.length > 0"
                class="bg-white rounded-2xl border border-slate-200 shadow-sm"
            >
                <AppPagination v-model:page="page" :total="inventoryStore.items.length" :per-page="perPage" />
            </div>
        </div>

        <StockAdjustModal :open="adjustOpen" :stock="selected" @close="adjustOpen = false" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import {
    AlertOctagon,
    AlertTriangle,
    Boxes,
    Package,
    Plus,
    Search,
    SlidersHorizontal,
} from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import AppBadge from '../components/ui/AppBadge.vue';
import AppTable from '../components/ui/AppTable.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppPagination from '../components/ui/AppPagination.vue';
import StatCard from '../components/ui/StatCard.vue';
import StockAdjustModal from '../components/StockAdjustModal.vue';
import { useInventoryStore } from '../stores/inventory';
import { useProductStore } from '../stores/products';
import { useToastStore } from '../stores/toast';
import { resolveCategoryIcon } from '../config/categoryIcons';

const inventoryStore = useInventoryStore();
const productStore = useProductStore();
const toastStore = useToastStore();

const perPage = 10;
const search = ref('');
const categoryId = ref(null);
const statusFilter = ref('all');
const page = ref(1);
const adjustOpen = ref(false);
const selected = ref(null);

const formatNumber = (value) =>
    Number(value || 0).toLocaleString('es-CO', { maximumFractionDigits: 3 });

const formatQuantity = (row) =>
    row.stock_type === 'bulk_grams' ? `${formatNumber(row.quantity)} kg` : formatNumber(row.quantity);

const categoryIcon = (row) => resolveCategoryIcon(row.product?.category?.icon);

const statusTone = (status) =>
    ({ normal: 'success', low: 'warning', critical: 'danger' })[status] || 'neutral';

const statusLabel = (status) =>
    ({ normal: 'Normal', low: 'Bajo', critical: 'Crítico' })[status] || '—';

const paged = computed(() => {
    const start = (page.value - 1) * perPage;
    return inventoryStore.items.slice(start, start + perPage);
});

const load = async () => {
    try {
        await Promise.all([
            inventoryStore.fetchInventory({
                search: search.value || undefined,
                categoryId: categoryId.value || undefined,
                status: statusFilter.value,
            }),
            productStore.categories.length === 0
                ? productStore.fetchCategories()
                : Promise.resolve(),
        ]);
    } catch (err) {
        toastStore.error(inventoryStore.error || 'No se pudo cargar el inventario');
    }
};

onMounted(load);

watch([search, categoryId, statusFilter], () => {
    page.value = 1;
    load();
});

const openAdjust = (row) => {
    selected.value = row;
    adjustOpen.value = true;
};

// "Registrar entrada" apunta directamente a la primera fila que lo necesita,
// que es el caso de uso real: reponer lo que está por agotarse.
const openFirstCritical = () => {
    const target =
        inventoryStore.items.find((row) => row.status === 'critical') ||
        inventoryStore.items.find((row) => row.status === 'low') ||
        inventoryStore.items[0];

    if (!target) {
        toastStore.info('No hay existencias registradas todavía');
        return;
    }

    openAdjust(target);
};
</script>
