<template>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
        <PageHeader
            title="Compras"
            subtitle="Lo que hay que comprar para el día siguiente"
        >
            <template #actions>
                <AppButton
                    variant="secondary"
                    label="Actualizar"
                    :icon="RefreshCw"
                    @click="load"
                />
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <StatCard
                label="Pendientes"
                :value="stats.pending_count"
                :icon="ShoppingCart"
                tone="amber"
            />
            <StatCard
                label="Insumos distintos"
                :value="stats.supplies"
                :icon="ListChecks"
                tone="aguamarina"
            />
            <StatCard
                label="Comprados hoy"
                :value="stats.bought_today"
                :icon="CheckCircle2"
                tone="emerald"
            />
        </div>

        <div
            class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-4 flex flex-wrap items-center gap-3"
        >
            <div class="flex-1 min-w-[220px]">
                <AppInput v-model="search" placeholder="Buscar en la lista" :icon="Search" />
            </div>
            <div class="min-w-[180px]">
                <AppSelect v-model="statusFilter">
                    <option value="pending">Pendientes</option>
                    <option value="all">Todas</option>
                    <option value="bought">Ya compradas</option>
                </AppSelect>
            </div>
        </div>

        <div v-if="store.loading" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-16 rounded-2xl bg-aguamarina-50 animate-pulse" />
        </div>

        <div
            v-else-if="!filtered.length"
            class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-10 text-center space-y-2"
        >
            <AppIcon :name="ShoppingCart" :size="30" class="text-aguamarina-400 mx-auto" />
            <p class="text-sm text-niebla-400">
                {{
                    statusFilter === 'pending'
                        ? 'No hay nada pendiente de comprar.'
                        : 'No hay compras para mostrar.'
                }}
            </p>
            <p v-if="statusFilter === 'pending'" class="text-xs text-niebla-300">
                Desde Inventario, pasá un insumo con el botón de la flecha.
            </p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="row in filtered"
                :key="row.id"
                class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-4 flex items-center gap-4 flex-wrap"
                :class="row.status === 'bought' ? 'opacity-60' : ''"
            >
                <div
                    class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0"
                    :class="row.status === 'bought' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'"
                >
                    <AppIcon :name="row.status === 'bought' ? CheckCircle2 : ShoppingCart" :size="20" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-petrol-800 truncate">
                        {{ row.supply_name }}
                    </p>
                    <p class="text-xs text-niebla-400 truncate">
                        <template v-if="row.status === 'bought'">
                            Comprado{{ row.bought_at ? ' el ' + formatDate(row.bought_at) : '' }}
                            <template v-if="row.bought_by_name"> por {{ row.bought_by_name }}</template>
                        </template>
                        <template v-else>
                            Pedido{{ row.created_at ? ' el ' + formatDate(row.created_at) : '' }}
                            <template v-if="row.requested_by_name">
                                por {{ row.requested_by_name }}
                            </template>
                        </template>
                    </p>
                    <p v-if="row.note" class="text-xs text-niebla-400 mt-0.5">{{ row.note }}</p>
                    <p v-if="!row.supply_exists && row.status === 'pending'" class="text-[11px] text-amber-600 mt-0.5">
                        Este insumo se borró del inventario: la compra queda registrada pero ya
                        no suma stock.
                    </p>
                </div>

                <p class="text-sm font-bold text-petrol-800 tabular-nums shrink-0">
                    {{ row.quantity_label }}
                </p>

                <div class="flex items-center gap-1.5 shrink-0">
                    <AppButton
                        v-if="row.status === 'pending'"
                        label="Marcar comprado"
                        :icon="Check"
                        size="sm"
                        :loading="store.saving"
                        @click="confirmBought(row)"
                    />
                    <AppButton
                        v-if="row.status === 'pending'"
                        variant="secondary"
                        :icon="Pencil"
                        aria-label="Editar cantidad"
                        @click="openEdit(row)"
                    />
                    <AppButton
                        v-if="row.status === 'pending'"
                        variant="secondary"
                        :icon="Trash2"
                        aria-label="Cancelar compra"
                        @click="confirmCancel(row)"
                    />
                </div>
            </div>
        </div>

        <!-- Editar cantidad -->
        <AppModal
            :open="Boolean(editing)"
            :title="`Comprar de ${editing?.supply_name ?? ''}`"
            size="sm"
            @close="editing = null"
        >
            <div class="space-y-4">
                <AppInput
                    v-model.number="editQuantity"
                    label="Cuánto comprar"
                    type="number"
                    step="any"
                    required
                />
                <AppInput v-model="editNote" label="Nota (opcional)" />
                <p v-if="error" class="text-xs text-rose-600 flex items-start gap-1">
                    <AppIcon :name="AlertCircle" :size="13" class="mt-0.5 shrink-0" />
                    {{ error }}
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <AppButton variant="secondary" label="Cancelar" @click="editing = null" />
                    <AppButton
                        label="Guardar"
                        :loading="store.saving"
                        @click="submitEdit"
                    />
                </div>
            </template>
        </AppModal>

        <ConfirmDialog
            :open="Boolean(boughtTarget)"
            :title="`Marcar ${boughtTarget?.supply_name ?? ''} como comprado`"
            :message="boughtMessage"
            confirm-text="Sí, ya lo compré"
            :loading="store.saving"
            @cancel="boughtTarget = null"
            @confirm="submitBought"
        />

        <ConfirmDialog
            :open="Boolean(cancelTarget)"
            title="Cancelar la compra"
            :message="cancelMessage"
            confirm-text="Cancelar compra"
            :danger="true"
            :loading="store.saving"
            @cancel="cancelTarget = null"
            @confirm="submitCancel"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    ListChecks,
    Pencil,
    RefreshCw,
    Search,
    ShoppingCart,
    Trash2,
} from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppModal from '../components/ui/AppModal.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import ConfirmDialog from '../components/ui/ConfirmDialog.vue';
import StatCard from '../components/ui/StatCard.vue';
import { usePurchaseStore } from '../stores/purchases';
import { useToastStore } from '../stores/toast';

const store = usePurchaseStore();
const toast = useToastStore();

const search = ref('');
const statusFilter = ref('pending');

const editing = ref(null);
const editQuantity = ref(0);
const editNote = ref('');
const error = ref('');

const boughtTarget = ref(null);
const cancelTarget = ref(null);

const stats = computed(() => store.stats);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return store.purchases;
    return store.purchases.filter((p) => p.supply_name.toLowerCase().includes(term));
});

const formatDate = (iso) =>
    new Date(iso).toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit' });

const boughtMessage = computed(() => {
    const row = boughtTarget.value;
    if (!row) return '';
    return `Se sumará ${row.quantity_label} al stock de ${row.supply_name}.`;
});

const cancelMessage = computed(() => {
    const row = cancelTarget.value;
    if (!row) return '';
    return `Se saca "${row.supply_name}" de la lista y se le descuenta ${row.quantity_label} al stock.`;
});

const load = () => store.load(statusFilter.value);

onMounted(load);

watch(statusFilter, load);

const openEdit = (row) => {
    editing.value = row;
    editQuantity.value = row.quantity;
    editNote.value = row.note || '';
    error.value = '';
};

const submitEdit = async () => {
    if (Number(editQuantity.value) <= 0) {
        error.value = 'La cantidad debe ser mayor a cero.';
        return;
    }

    try {
        await store.update(editing.value.id, {
            quantity: Number(editQuantity.value),
            note: editNote.value || null,
        });
        editing.value = null;
        toast.success('Cantidad actualizada');
    } catch (err) {
        error.value = store.error;
    }
};

const confirmBought = (row) => {
    boughtTarget.value = row;
};

const submitBought = async () => {
    try {
        await store.markBought(boughtTarget.value.id);
        boughtTarget.value = null;
        toast.success('Compra marcada. El stock ya fue actualizado.');
    } catch (err) {
        toast.error(store.error);
    }
};

const confirmCancel = (row) => {
    cancelTarget.value = row;
};

const submitCancel = async () => {
    try {
        await store.cancel(cancelTarget.value.id);
        cancelTarget.value = null;
        toast.success('Compra cancelada');
    } catch (err) {
        toast.error(store.error);
    }
};
</script>
