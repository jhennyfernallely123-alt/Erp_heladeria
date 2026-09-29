<template>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
        <PageHeader
            title="Insumos"
            subtitle="Conteo de lo que queda en la heladería. El administrador lo revisa al cerrar."
        >
            <template #actions>
                <AppButton label="Agregar insumo" :icon="Plus" @click="openForm()" />
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <StatCard
                label="Insumos activos"
                :value="stats.total_supplies"
                :icon="Boxes"
                tone="aguamarina"
            />
            <StatCard
                label="Sin existencias"
                :value="stats.empty_count"
                :icon="PackageX"
                tone="rose"
            />
            <StatCard
                label="Para comprar"
                :value="stats.pending_purchases"
                :icon="ShoppingCart"
                tone="amber"
            />
        </div>

        <div
            class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-4 flex flex-wrap items-center gap-3"
        >
            <div class="flex-1 min-w-[220px]">
                <AppInput v-model="search" placeholder="Buscar insumo" :icon="Search" />
            </div>
        </div>

        <div class="space-y-4">
            <AppTable
                :loading="store.loading"
                :total="filtered.length"
                empty-title="Sin insumos"
                empty-description="Agregá el primer insumo de la heladería."
            >
                <template #head>
                    <th class="p-3.5">Insumo</th>
                    <th class="p-3.5 text-right">Cantidad</th>
                    <th class="p-3.5 text-right">Acciones</th>
                </template>

                <tr
                    v-for="row in paged"
                    :key="row.id"
                    class="transition-colors hover:bg-aguamarina-50"
                    :class="!row.is_active ? 'opacity-50' : ''"
                >
                    <td class="p-3.5">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-lg bg-aguamarina-50 border border-aguamarina-100 flex items-center justify-center shrink-0"
                            >
                                <AppIcon :name="Package" :size="20" class="text-niebla-300" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-petrol-700 truncate">
                                    {{ row.name }}
                                </p>
                                <p v-if="row.notes" class="text-xs text-niebla-400 truncate">
                                    {{ row.notes }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="p-3.5 text-right">
                        <span
                            class="text-sm font-bold tabular-nums"
                            :class="row.quantity <= 0 ? 'text-rose-600' : 'text-petrol-700'"
                        >
                            {{ row.quantity_label }}
                        </span>
                    </td>
                    <td class="p-3.5">
                        <div class="flex justify-end gap-1">
                            <button
                                type="button"
                                class="p-2 rounded-lg text-niebla-400 hover:bg-aguamarina-50 hover:text-aguamarina-600 transition"
                                title="Cambiar cantidad"
                                :aria-label="`Cambiar cantidad de ${row.name}`"
                                @click="openQuantity(row)"
                            >
                                <AppIcon :name="Pencil" :size="16" />
                            </button>
                    <button
                        type="button"
                        class="p-2 rounded-lg text-amber-500 hover:bg-amber-50 hover:text-amber-600 transition"
                        title="Cambiar cantidad y comprar lo que falte"
                        :aria-label="`Cambiar cantidad de ${row.name}`"
                        @click="openQuantity(row)"
                    >
                        <AppIcon :name="ShoppingCart" :size="16" />
                    </button>
                            <button
                                type="button"
                                class="p-2 rounded-lg text-niebla-400 hover:bg-rose-50 hover:text-rose-600 transition"
                                title="Eliminar"
                                :aria-label="`Eliminar ${row.name}`"
                                @click="confirmDelete(row)"
                            >
                                <AppIcon :name="Trash2" :size="16" />
                            </button>
                        </div>
                    </td>
                </tr>
            </AppTable>

            <div
                v-if="filtered.length > 0"
                class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm"
            >
                <AppPagination v-model:page="page" :total="filtered.length" :per-page="perPage" />
            </div>
        </div>

        <ConfirmDialog
            :open="Boolean(store.deleteTarget)"
            title="Eliminar insumo"
            :message="deleteMessage"
            confirm-text="Eliminar"
            :danger="true"
            :loading="store.saving"
            @cancel="store.cancelDelete()"
            @confirm="store.confirmDelete()"
        />

        <!-- Cambiar la cantidad -->
        <AppModal
            :open="quantityOpen"
            :title="selected?.name ?? ''"
            subtitle="Compara lo que había con lo que queda y pasa a compras lo que falte"
            size="sm"
            @close="quantityOpen = false"
        >
            <div class="space-y-4">
                <!-- Lo que había antes, solo informativo -->
                <div class="rounded-xl bg-niebla-50 border border-aguamarina-100 px-4 py-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] uppercase font-bold text-niebla-400">
                            Tenía antes
                        </p>
                        <p class="text-xl font-bold text-petrol-800 mt-0.5">
                            {{ formatQty(previousQuantity) }}
                        </p>
                    </div>
                    <AppIcon :name="Minus" :size="20" class="text-niebla-300 shrink-0" />
                </div>

                <AppInput
                    v-model.number="countQuantity"
                    :label="`¿Cuánto queda? (${selected?.unit_label ?? ''})`"
                    type="number"
                    step="any"
                    required
                />

                <!-- Cuánto falta -->
                <div
                    class="rounded-xl border px-4 py-3 flex items-center justify-between gap-3"
                    :class="missing > 0 ? 'bg-amber-50 border-amber-200' : 'bg-aguamarina-50 border-aguamarina-200'"
                >
                    <div>
                        <p
                            class="text-[11px] uppercase font-bold"
                            :class="missing > 0 ? 'text-amber-700' : 'text-niebla-400'"
                        >
                            {{ missing > 0 ? 'Falta para completar' : 'No falta nada' }}
                        </p>
                        <p
                            class="text-xl font-bold mt-0.5"
                            :class="missing > 0 ? 'text-amber-700' : 'text-emerald-600'"
                        >
                            {{ formatQty(missing) }}
                        </p>
                    </div>
                    <AppIcon
                        :name="missing > 0 ? TrendingDown : CheckCircle2"
                        :size="20"
                        :class="missing > 0 ? 'text-amber-500 shrink-0' : 'text-emerald-500 shrink-0'"
                    />
                </div>

                <AppButton
                    v-if="missing > 0"
                    variant="secondary"
                    class="w-full"
                    label="Pasar lo que falta a compras"
                    :icon="ShoppingCart"
                    :loading="purchaseStore.saving"
                    @click="submitPurchaseMissing"
                />

                <p v-if="error" class="text-xs text-rose-600 flex items-start gap-1">
                    <AppIcon :name="AlertCircle" :size="13" class="mt-0.5 shrink-0" />
                    {{ error }}
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <AppButton variant="secondary" label="Cancelar" @click="quantityOpen = false" />
                    <AppButton
                        label="Guardar cantidad"
                        :icon="Check"
                        :loading="store.saving"
                        @click="submitQuantity"
                    />
                </div>
            </template>
        </AppModal>

        <!-- Alta / edición -->
        <AppModal
            :open="formOpen"
            :title="editing ? 'Editar insumo' : 'Nuevo insumo'"
            size="sm"
            @close="formOpen = false"
        >
            <div class="space-y-4">
                <AppInput v-model="form.name" label="Nombre" placeholder="Leche entera" required />
                <div class="grid grid-cols-2 gap-3">
                    <AppSelect v-model="form.unit" label="Se mide en" required>
                        <option value="units">Unidades (und)</option>
                        <option value="kg">Kilogramos (kg)</option>
                        <option value="g">Gramos (g)</option>
                        <option value="L">Litros (L)</option>
                        <option value="ml">Mililitros (ml)</option>
                    </AppSelect>
                    <AppInput v-model.number="form.quantity" label="Cantidad" type="number" step="any" />
                </div>
                <AppInput v-model="form.notes" label="Nota (opcional)" placeholder="Proveedor, marca..." />
                <p v-if="error" class="text-xs text-rose-600 flex items-start gap-1">
                    <AppIcon :name="AlertCircle" :size="13" class="mt-0.5 shrink-0" />
                    {{ error }}
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <AppButton variant="secondary" label="Cancelar" @click="formOpen = false" />
                    <AppButton
                        :label="editing ? 'Guardar cambios' : 'Agregar'"
                        :loading="store.saving"
                        @click="submitForm"
                    />
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import {
    AlertCircle,
    Boxes,
    Check,
    CheckCircle2,
    Minus,
    Package,
    PackageX,
    Pencil,
    Plus,
    Search,
    ShoppingCart,
    Trash2,
    TrendingDown,
} from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppModal from '../components/ui/AppModal.vue';
import AppPagination from '../components/ui/AppPagination.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import AppTable from '../components/ui/AppTable.vue';
import StatCard from '../components/ui/StatCard.vue';
import ConfirmDialog from '../components/ui/ConfirmDialog.vue';
import { useSupplyStore } from '../stores/supplies';
import { usePurchaseStore } from '../stores/purchases';
import { useToastStore } from '../stores/toast';

const store = useSupplyStore();
const purchaseStore = usePurchaseStore();
const toast = useToastStore();

const perPage = 10;
const page = ref(1);
const search = ref('');

const quantityOpen = ref(false);
const formOpen = ref(false);
const editing = ref(null);
const selected = ref(null);
const error = ref('');

const countQuantity = ref(0);
const previousQuantity = ref(0);

const form = ref({ name: '', unit: 'units', quantity: 0, notes: '' });

const stats = computed(() => store.stats);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return store.supplies;
    return store.supplies.filter((s) => s.name.toLowerCase().includes(term));
});

const paged = computed(() => {
    const start = (page.value - 1) * perPage;
    return filtered.value.slice(start, start + perPage);
});

const load = async () => {
    try {
        await Promise.all([store.load(), purchaseStore.load()]);
    } catch (err) {
        toast.error('No se pudo cargar el inventario');
    }
};

onMounted(load);

watch(search, () => {
    page.value = 1;
});

const openQuantity = (row) => {
    selected.value = row;
    previousQuantity.value = row.quantity;
    countQuantity.value = row.quantity;
    error.value = '';
    quantityOpen.value = true;
};

/**
 * Lo que falta para volver a la cantidad que había. Si el admin subió la
 * cantidad en vez de bajarla, missing queda en 0 y no hay nada que comprar.
 */
const missing = computed(() =>
    Math.max(0, Math.round((Number(previousQuantity.value) - Number(countQuantity.value || 0)) * 1000) / 1000)
);

const formatQty = (value) => {
    const n = Number(value || 0);
    const formatted = String(Number(n.toFixed(3)));
    return `${formatted} ${selected.value?.unit_label ?? ''}`;
};

/** Manda exactamente lo que falta a la lista de compras. */
const submitPurchaseMissing = async () => {
    if (missing.value <= 0) return;

    try {
        await purchaseStore.add(selected.value.id, missing.value);
        toast.success(`Faltan ${formatQty(missing.value)} y ya están en la lista de compras`);
    } catch (err) {
        error.value = purchaseStore.error;
    }
};

const submitQuantity = async () => {
    if (Number(countQuantity.value) < 0) {
        error.value = 'La cantidad no puede ser negativa.';
        return;
    }

    try {
        await store.countSupply(selected.value.id, Number(countQuantity.value));
        quantityOpen.value = false;
        toast.success('Cantidad actualizada');
    } catch (err) {
        error.value = store.error;
    }
};

const openForm = (row = null) => {
    editing.value = row;
    form.value = row
        ? { name: row.name, unit: row.unit, quantity: row.quantity, notes: row.notes || '' }
        : { name: '', unit: 'units', quantity: 0, notes: '' };
    error.value = '';
    formOpen.value = true;
};

const submitForm = async () => {
    if (!form.value.name.trim()) {
        error.value = 'El nombre es obligatorio.';
        return;
    }

    try {
        if (editing.value) {
            await store.updateSupply(editing.value.id, { ...form.value, name: form.value.name.trim() });
            toast.success('Insumo actualizado');
        } else {
            await store.createSupply({ ...form.value, name: form.value.name.trim() });
            toast.success('Insumo agregado');
        }
        formOpen.value = false;
    } catch (err) {
        error.value = store.error;
    }
};

/** Las comillas se arman acá y no en el template: escaparlas dentro del
 *  atributo rompía el parseo del SFC. */
const deleteMessage = computed(() => {
    const name = store.deleteTarget?.name ?? '';
    return `¿Eliminar "${name}" de la lista de insumos?`;
});

const confirmDelete = (row) => {
    store.askDelete(row);
};
</script>
